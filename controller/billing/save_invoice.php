<?php
include __DIR__ . '/../../config/auth_guard.php';

// Helper Function for Amount in Words
function getIndianCurrency(float $number) {
    $no = floor($number);
    $point = round($number - $no, 2) * 100;
    $point = (int)$point; 
    $hundred = null;
    $digits_1 = strlen($no);
    $i = 0;
    $str = array();
    $words = array('0' => '', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine', '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve', '13' => 'Thirteen', '14' => 'Fourteen', '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen', '18' => 'Eighteen', '19' => 'Nineteen', '20' => 'Twenty', '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty', '60' => 'Sixty', '70' => 'Seventy', '80' => 'Eighty', '90' => 'Ninety');
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    while ($i < $digits_1) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? '' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
        } else $str[] = null;
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    $points = '';
    if($point > 0){
        $points .= " and ";
        $points .= ($point < 21) ? ($words[$point] ?? $point) : ($words[floor($point / 10) * 10] . " " . $words[$point % 10]);
        $points .= " Paise";
    }
    return $result . " Rupees" . $points . " Only";
}

if (isset($_POST['save_invoice'])) {
 
    $organization_id = $_SESSION['organization_id'];
    $created_by = $_SESSION['user_id'] ?? 0;

    // Validate inputs
    if(empty($_POST['customer_id']) || empty($_POST['invoice_number'])){
        header("Location: ../../tax_invoice?error=Missing required fields");
        exit;
    }

    $customer_id = intval($_POST['customer_id']);
    $invoice_number = $_POST['invoice_number'];
    $reference_no = $_POST['reference_no'];
    $invoice_date = $_POST['invoice_date'];
    $payment_terms = $_POST['payment_terms'];
    $discount_type = $_POST['discount_type'] ?? '';
    $discount_value = floatval($_POST['discount_value'] ?? 0);
    $adjustment = floatval($_POST['adjustment']);
    $advance_amount = floatval($_POST['advance_amount'] ?? 0);
    $balance_due = floatval($_POST['balance_due'] ?? 0);
    
    // GST Fields (Global for invoice)
    $gst_type = $_POST['gst_type'] ?? '';
    $gst_rate = floatval($_POST['gst_rate'] ?? 0);
    $cgst_amount = floatval($_POST['cgst_amount'] ?? 0);
    $sgst_amount = floatval($_POST['sgst_amount'] ?? 0);
    $igst_amount = floatval($_POST['igst_amount'] ?? 0);

    $sub_total = floatval($_POST['sub_total']);
    $total_amount = floatval($_POST['total_amount']);
    $notes = $_POST['notes'];
    $terms_conditions = $_POST['terms_conditions'];
    $status = 'sent'; // Always final, no draft mode

    // Loyalty Points Inputs
    $loyalty_slab_id = !empty($_POST['loyalty_slab_id']) ? intval($_POST['loyalty_slab_id']) : 0;
    
    // Fix: Sanitize 'redeemed_points' to remove currency symbols (e.g. ₹)
    $raw_redeemed = $_POST['redeemed_points'] ?? '';
    // Remove everything except numbers and dots
    $clean_redeemed = preg_replace('/[^0-9.]/', '', $raw_redeemed);
    $input_redeemed_points = !empty($clean_redeemed) ? floatval($clean_redeemed) : 0;

    // We trust the redemption amount from UI for Invoice logic, but deduction logic relies on points
    $raw_redemption_amt = $_POST['redemption_amount'] ?? '';
    $clean_redemption_amt = preg_replace('/[^0-9.]/', '', $raw_redemption_amt);
    $redemption_amount = !empty($clean_redemption_amt) ? floatval($clean_redemption_amt) : 0;

    // New Fields
    $proforma_invoice_id = !empty($_POST['proforma_invoice_id']) ? intval($_POST['proforma_invoice_id']) : 0;
    $sales_employee_id = !empty($_POST['sales_employee_id']) ? intval($_POST['sales_employee_id']) : 0;
    $delivery_mode = $_POST['delivery_mode'] ?? '';

    $items = $_POST['items'] ?? [];

    $conn->begin_transaction();
    try {
        
        // 0. Calculate Earned Points (Server Side)
        $points_earned = 0;
        $points_valid_till = NULL;

        // Calculate Base for Loyalty (Taxable Amount = Sub Total - Discount) to match Frontend
        $loyalty_calc_base = $sub_total - $discount_value; 
        if($loyalty_calc_base < 0) $loyalty_calc_base = 0;

        if ($loyalty_slab_id > 0) {
            $slabSql = "SELECT points_per_100_rupees, valid_for_days FROM loyalty_point_slabs WHERE slab_id = $loyalty_slab_id AND organization_id = $organization_id";
            $slabRes = $conn->query($slabSql);
            if ($slabRes && $slabRes->num_rows > 0) {
                $slab = $slabRes->fetch_assoc();
            }
        } else {
            // Auto-detect slab based on sales amount
            $currentDate = date('Y-m-d');
            $slabSql = "SELECT slab_id, points_per_100_rupees, valid_for_days FROM loyalty_point_slabs 
                        WHERE organization_id = $organization_id 
                        AND from_sale_amount <= $loyalty_calc_base
                        AND '$currentDate' BETWEEN applicable_from_date AND applicable_to_date 
                        ORDER BY from_sale_amount DESC
                        LIMIT 1";
            $slabRes = $conn->query($slabSql);
             if ($slabRes && $slabRes->num_rows > 0) {
                $slab = $slabRes->fetch_assoc();
                $loyalty_slab_id = $slab['slab_id']; // Update ID for logging
            }
        }

        if (isset($slab)) {
                // Formula: Floor((Taxable - RedeemedVal) / 100) * PointsPer100
                // User Request: Loyalty Points only apply with out Loyalty Points Redeemed
                // Interpretation: Earn points only on the amount NOT covered by redemption.
                
                // Assuming 1 Point = 1 Rupee for value deduction logic
                $redemption_value = $input_redeemed_points * 1; 
                $eligible_amount = $loyalty_calc_base - $redemption_value;
                if ($eligible_amount < 0) $eligible_amount = 0;

                $points_earned = floor($eligible_amount / 100) * floatval($slab['points_per_100_rupees']);
                
                if (!empty($slab['valid_for_days'])) {
                    $points_valid_till = date('Y-m-d', strtotime("+" . intval($slab['valid_for_days']) . " days"));
                }
        }


        // 1. Insert Invoice Header
        // Added reward_points_earned, reward_points_redeemed, proforma_invoice_id, sales_employee_id, delivery_mode
        // Added reference_customer_id (missing in previous version)
        // Added balance_due (Critical for payment received module)
        $reference_customer_id = !empty($_POST['reference_customer_id']) ? intval($_POST['reference_customer_id']) : NULL;
        $balance_due = $total_amount; // Initially, balance due is the full amount
        $make_employee_id = $_SESSION['user_id'] ?? 0;

        $sql = "INSERT INTO sales_invoices (organization_id, customer_id, invoice_number, reference_no, invoice_date, payment_terms, discount_type, discount_value, adjustment, gst_type, cgst_amount, sgst_amount, igst_amount, sub_total, total_amount, balance_due, reward_points_earned, reward_points_redeemed, status, notes, terms_conditions, proforma_invoice_id, sales_employee_id, delivery_mode, reference_customer_id, make_employee_id, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
             throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        
        // bind parameters
        // Corrected type definition string: 24 variables
        // i(org), i(cust), s(inv), s(ref), s(date), s(pay), s(dtype), d(dval), d(adj), s(gst), d(c), d(s), d(i), d(sub), d(tot), d(bal), d(earn), d(red), s(stat), s(notes), s(term), i(prof), i(sales), s(deliv), i(ref_cust), i(make_emp)
        $stmt->bind_param("iisssssddsddddddddsssiisii", $organization_id, $customer_id, $invoice_number, $reference_no, $invoice_date, $payment_terms, $discount_type, $discount_value, $adjustment, $gst_type, $cgst_amount, $sgst_amount, $igst_amount, $sub_total, $total_amount, $balance_due, $points_earned, $input_redeemed_points, $status, $notes, $terms_conditions, $proforma_invoice_id, $sales_employee_id, $delivery_mode, $reference_customer_id, $make_employee_id);
        
        if(!$stmt->execute()){
            throw new Exception("Invoice Insert Failed: " . $stmt->error);
        }
        $invoice_id = $conn->insert_id;
        $stmt->close();

        // 2. Insert Items & Build Notification Data
        $itemsHtml = ''; 
        $pdfItemsHtml = '';
        $idx = 1;

        if (!empty($items)) {
            // Updated INSERT to include hsn_code, gst_rate, total_amount, discount_type
            $itemSql = "INSERT INTO sales_invoice_items (organization_id, invoice_id, item_id, item_name, unit_id, quantity, rate, discount, discount_type, amount, hsn_code, gst_rate, total_amount, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $itemStmt = $conn->prepare($itemSql);
            if (!$itemStmt) {
                 throw new Exception("Item Prepare Failed: " . $conn->error);
            }

            foreach ($items as $item) {
                $item_id = intval($item['item_id']);
                $quantity = floatval($item['quantity']);
                $rate = floatval($item['rate']);
                $discount = floatval($item['discount'] ?? 0);
                $discount_type = $item['discount_type'] ?? 'amount';
                $amount = floatval($item['amount']); // Taxable Value

                // Fetch Full Item Details (Name, Full HSN, GST Rate, Unit Name)
                $detSql = "SELECT i.item_name, i.unit_id, h.hsn_code, h.gst_rate, u.unit_name 
                           FROM items_listing i 
                           LEFT JOIN hsn_listing h ON i.hsn_id = h.hsn_id 
                           LEFT JOIN units_listing u ON i.unit_id = u.unit_id 
                           WHERE i.item_id = $item_id";
                $detRes = $conn->query($detSql);
                $prod = $detRes->fetch_assoc();
                
                $item_name = $prod['item_name'] ?? 'Unknown Item';
                $unit_id_db = $prod['unit_id'] ?? 0;
                $hsn_code = $prod['hsn_code'] ?? '-';
                $item_gst_rate = floatval($prod['gst_rate'] ?? 0);
                $unit_name = $prod['unit_name'] ?? 'Unit';

                // Calculate Taxes Per Item
                $cgst_rate = 0; $cgst_amt = 0;
                $sgst_rate = 0; $sgst_amt = 0;
                $igst_rate = 0; $igst_amt = 0;

                if ($gst_type === 'CGST_SGST') {
                    $cgst_rate = $item_gst_rate / 2;
                    $sgst_rate = $item_gst_rate / 2;
                    $cgst_amt = $amount * ($cgst_rate / 100);
                    $sgst_amt = $amount * ($sgst_rate / 100);
                } else if ($gst_type === 'IGST') {
                    $igst_rate = $item_gst_rate;
                    $igst_amt = $amount * ($igst_rate / 100);
                }
                
                $item_total = $amount + $cgst_amt + $sgst_amt + $igst_amt; // Total Amount (Taxable + Tax)

                // Insert into DB
                // Types: i (org), i (inv), i (item), s (name), i (unit), d (qty), d (rate), d (disc), s (type), d (amt), s (hsn), d (gst_rate), d (total)
                // total 13 params
                $itemStmt->bind_param("iiisidddsdsdd", $organization_id, $invoice_id, $item_id, $item_name, $unit_id_db, $quantity, $rate, $discount, $discount_type, $amount, $hsn_code, $item_gst_rate, $item_total);
                
                if(!$itemStmt->execute()) {
                     throw new Exception("Item Insert Failed: " . $itemStmt->error);
                }
                
                $total_tax_percent = $cgst_rate + $sgst_rate + $igst_rate;

                // Build HTML for Email (Simplified Professional Table)
                $itemsHtml .= "
                <tr>
                    <td style='padding:8px; border-bottom:1px solid #ddd; color:#333;'>$idx</td>
                    <td style='padding:8px; border-bottom:1px solid #ddd; color:#333;'>
                        <strong>$item_name</strong>
                    </td>
                    <td style='padding:8px; border-bottom:1px solid #ddd; color:#555; text-align:center;'>$hsn_code</td>
                    <td style='padding:8px; border-bottom:1px solid #ddd; color:#555; text-align:center;'>$quantity</td>
                    <td style='padding:8px; border-bottom:1px solid #ddd; color:#555; text-align:center;'>$unit_name</td>
                    <td style='padding:8px; border-bottom:1px solid #ddd; color:#555; text-align:right;'>₹" . number_format($rate, 2) . "</td>
                    <td style='padding:8px; border-bottom:1px solid #ddd; color:#555; text-align:right;'>₹" . number_format($amount, 2) . "</td>
                    <td style='padding:8px; border-bottom:1px solid #ddd; color:#555; text-align:right;'>" . number_format($total_tax_percent, 1) . "%</td>
                    <td style='padding:8px; border-bottom:1px solid #ddd; color:#333; text-align:right; font-weight:bold;'>₹" . number_format($item_total, 2) . "</td>
                </tr>";

                 // Build HTML for PDF (Simplified Professional Table)
                 $pdfItemsHtml .= "
                 <tr>
                     <td class='text-center'>$idx</td>
                     <td>$item_name</td>
                     <td class='text-center'>$hsn_code</td>
                     <td class='text-center'>$quantity</td>
                     <td class='text-center'>$unit_name</td>
                     <td class='text-right'>₹" . number_format($rate, 2) . "</td>
                     <td class='text-right'>₹" . number_format($amount, 2) . "</td>
                     <td class='text-right'>" . number_format($total_tax_percent, 1) . "%</td>
                     <td class='text-right'><b>₹" . number_format($item_total, 2) . "</b></td>
                 </tr>";
                 
                 $idx++;
            }
            $itemStmt->close();
        }

        // 3. Post-Creation Logic (Stock, Balance, Loyalty)
        if ($status !== 'draft') {
            
            // A. Deduct Stock
            $updateStock = $conn->prepare("UPDATE items_listing SET current_stock = current_stock - ? WHERE item_id = ?");
            foreach ($items as $item) {
                $qty = floatval($item['quantity']);
                $iid = intval($item['item_id']);
                $updateStock->bind_param("di", $qty, $iid);
                $updateStock->execute();
            }
            $updateStock->close();

            // B. Loyalty Points Logic
            
            // ... (Loyalty Logic continues below)

            // D. Reference Customer Commission Logic
            if ($reference_customer_id > 0) {
                // 1. Fetch Reference Customer Type
                $refTypeSql = "SELECT customers_type_id FROM customers_listing WHERE customer_id = $reference_customer_id";
                $refTypeRes = $conn->query($refTypeSql);
                
                if ($refTypeRes && $refTypeRes->num_rows > 0) {
                    $refTypeId = intval($refTypeRes->fetch_assoc()['customers_type_id']);
                    
                    // 2. Fetch Commission Percentages for this Type (Map: ItemID -> %)
                    $commRates = [];
                    $commSql = "SELECT item_id, commission_percentage FROM item_commissions WHERE customers_type_id = $refTypeId AND organization_id = $organization_id";
                    $commRes = $conn->query($commSql);
                    if ($commRes) {
                        while ($row = $commRes->fetch_assoc()) {
                            $commRates[$row['item_id']] = floatval($row['commission_percentage']);
                        }
                    }

                    // 3. Calculate Total Commission
                    $totalCommission = 0;
                    foreach ($items as $item) {
                        $itemId = intval($item['item_id']);
                        $qty = floatval($item['quantity']);
                        $rate = floatval($item['rate']); // Selling Price
                        
                        if (isset($commRates[$itemId]) && $commRates[$itemId] > 0) {
                            $commPercent = $commRates[$itemId];
                            // Commission = (Qty * Rate * %) / 100
                            $itemComm = ($qty * $rate * $commPercent) / 100;
                            $totalCommission += $itemComm;
                        }
                    }

                    if ($totalCommission > 0) {
                        // 4. Update Reference Customer's Commission Balance
                        // Check if column exists or just update (assuming schema matches user request)
                        $updComm = $conn->prepare("UPDATE customers_listing SET commissions_amount = commissions_amount + ? WHERE customer_id = ?");
                        $updComm->bind_param("di", $totalCommission, $reference_customer_id);
                        $updComm->execute();
                        $updComm->close();

                        // 5. Insert into Ledger
                        // Check table existence first (Safety)
                        $conn->query("CREATE TABLE IF NOT EXISTS customers_commissions_ledger (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            organization_id INT NOT NULL,
                            customer_id INT NOT NULL,
                            invoice_id INT NOT NULL,
                            commission_amount DECIMAL(15,2) DEFAULT 0.00,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            INDEX (organization_id),
                            INDEX (customer_id),
                            INDEX (invoice_id)
                        )");

                        $insLedger = $conn->prepare("INSERT INTO customers_commissions_ledger (organization_id, customer_id, invoice_id, commission_amount, created_at) VALUES (?, ?, ?, ?, NOW())");
                        $insLedger->bind_param("iiid", $organization_id, $reference_customer_id, $invoice_id, $totalCommission);
                        $insLedger->execute();
                        $insLedger->close();
                    }
                }
            }

            // B. Loyalty Points Logic (Original)
            $custSql = "SELECT loyalty_point_balance FROM customers_listing WHERE customer_id = $customer_id FOR UPDATE";
            $custRes = $conn->query($custSql);
            $currentPoints = 0;
            if($custRes->num_rows > 0) {
                 $currentPoints = floatval($custRes->fetch_assoc()['loyalty_point_balance']);
            }

            // REDEMPTION (Deduct First)
            if ($input_redeemed_points > 0) {
                if ($currentPoints < $input_redeemed_points) {
                    throw new Exception("Insufficient loyalty points balance.");
                }

                $newBalance = $currentPoints - $input_redeemed_points;
                $pointsSql = "UPDATE customers_listing SET loyalty_point_balance = ? WHERE customer_id = ?";
                $ps = $conn->prepare($pointsSql);
                $ps->bind_param("di", $newBalance, $customer_id);
                $ps->execute();
                $ps->close();

                // Log Transaction
                $transSql = "INSERT INTO loyalty_point_transactions (organization_id, customer_id, invoice_id, transaction_type, points, balance_after_transaction, note, created_at) VALUES (?, ?, ?, 'REDEEM', ?, ?, 'Points Redeemed for Invoice $invoice_number', NOW())";
                $ts = $conn->prepare($transSql);
                $ts->bind_param("iiidd", $organization_id, $customer_id, $invoice_id, $input_redeemed_points, $newBalance);
                $ts->execute();
                $ts->close();
                
                $currentPoints = $newBalance; // Update local tracker
            }

            // EARNING (Add Second)
            if ($points_earned > 0) {
                $newBalance = $currentPoints + $points_earned;
                 $pointsSql = "UPDATE customers_listing SET loyalty_point_balance = ? WHERE customer_id = ?";
                $ps = $conn->prepare($pointsSql);
                $ps->bind_param("di", $newBalance, $customer_id);
                $ps->execute();
                $ps->close();

                // Log Earned
                // Added points_remaining column handling
                $earnSql = "INSERT INTO loyalty_points_earned (organization_id, customer_id, slab_id, invoice_id, bill_amount, points_earned, points_remaining, valid_till, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $es = $conn->prepare($earnSql);
                // Types: i, i, i, i, d, d, d, s
                $es->bind_param("iiiiddds", $organization_id, $customer_id, $loyalty_slab_id, $invoice_id, $total_amount, $points_earned, $points_earned, $points_valid_till);
                $es->execute();
                $es->close();

                // Log Transaction
                $transSql = "INSERT INTO loyalty_point_transactions (organization_id, customer_id, invoice_id, transaction_type, points, balance_after_transaction, expiry_date, note, created_at) VALUES (?, ?, ?, 'EARN', ?, ?, ?, 'Points Earned from Invoice $invoice_number', NOW())";
                $ts = $conn->prepare($transSql);
                $ts->bind_param("iiidds", $organization_id, $customer_id, $invoice_id, $points_earned, $newBalance, $points_valid_till);
                $ts->execute();
                $ts->close();

                // --- SEND EMAIL NOTIFICATION ---
                try {
                    // 1. Fetch Customer Email
                    $custMailSql = "SELECT email, customer_name FROM customers_listing WHERE customer_id = $customer_id";
                    $custMailRes = $conn->query($custMailSql);
                    if($custMailRes && $custMailRes->num_rows > 0){
                        $custData = $custMailRes->fetch_assoc();
                        $customerEmail = $custData['email'];
                        $customerName = $custData['customer_name'];

                        if(!empty($customerEmail) && !empty($authentication_email)) {
                            // 2. Fetch Org Details for Branding
                            $orgQ = $conn->query("SELECT * FROM organizations WHERE organization_id = $organization_id");
                            $orgData = $orgQ->fetch_assoc();
                            $orgName = $orgData['organization_name'];
                            $orgCode = $orgData['organizations_code'];
                            $logo = $orgData['organization_logo'];
                            
                            $logoHtml = '';
                            $logoPath = '';
                            $logoDataUri = ''; // For PDF base64 embedding

                            if(!empty($logo)){
                                $baseDir = __DIR__ . "/../../uploads/$orgCode/organization_logo/$logo";
                                if(file_exists($baseDir)){
                                     $logoPath = $baseDir;
                                     $logoHtml = "<div style='text-align:center; margin-bottom:20px;'><img src='cid:org_logo' style='max-height:80px; width:auto;'></div>";
                                     
                                     // Prepare Base64 for PDF
                                     $type = pathinfo($logoPath, PATHINFO_EXTENSION);
                                     $data = file_get_contents($logoPath);
                                     $base64 = base64_encode($data);
                                     $logoDataUri = 'data:image/' . $type . ';base64,' . $base64;
                                }
                            }

                            // QR Code Processing
                            $org_qr = $orgData['qr_code'] ?? '';
                            $qrDataUri = '';
                            if(!empty($org_qr)){
                                $qrPath = __DIR__ . "/../../uploads/$orgCode/bank_details/$org_qr";
                                if(file_exists($qrPath)){
                                     $type = pathinfo($qrPath, PATHINFO_EXTENSION);
                                     $data = file_get_contents($qrPath);
                                     $base64 = base64_encode($data);
                                     $qrDataUri = 'data:image/' . $type . ';base64,' . $base64;
                                }
                            }

                            $org_bank = $orgData['bank_name'] ?? '';
                            $org_acc = $orgData['account_number'] ?? '';
                            $org_ifsc = $orgData['ifsc_code'] ?? '';
                            $org_acc_holder = $orgData['account_holder_name'] ?? '';
                            $org_branch = $orgData['branch_name'] ?? '';
                            $org_upi = $orgData['upi_id'] ?? '';

                            // 3. Prepare Email Body
                            
                             // Recalculate Totals for Consistency
                            $taxable_calc = $sub_total - $discount_value;
                            $tax_calc = $igst_amount + $cgst_amount + $sgst_amount;
                            $redeem_calc = $input_redeemed_points;
                            $display_total = $taxable_calc + $tax_calc + $adjustment - $redeem_calc;
                            if($display_total < 0) $display_total = 0;

                            $amtInWords = getIndianCurrency($display_total);
                            $termsContent = !empty($terms_conditions) ? nl2br($terms_conditions) : '';

                            $msg = "
                                 <div style='font-family: Arial, sans-serif; max-width: 800px; color: #333; border:1px solid #eee; padding:30px; border-radius:4px; margin:0 auto;'>
                                    
                                    <!-- Header -->
                                    <div style='margin-bottom:30px;'>
                                        $logoHtml
                                        <h2 style='margin:0; text-align:center; color:#333; text-transform:uppercase;'>Tax Invoice</h2>
                                        <p style='text-align:center; color:#777; margin:5px 0;'>Invoice #$invoice_number</p>
                                    </div>

                                    <!-- Details -->
                                    <table style='width:100%; margin-bottom:30px; border-collapse:collapse;'>
                                        <tr>
                                            <td style='width:50%; vertical-align:top; padding-right:15px;'>
                                                 <h4 style='margin:0 0 5px 0; color:#555;'>Billed To:</h4>
                                                 <p style='margin:0; line-height:1.5;'>
                                                    <strong>$customerName</strong><br>
                                                    " . ($custData['address'] ?? '') . "<br>
                                                    GSTIN: " . ($custData['gst_number'] ?? '-') . "
                                                 </p>
                                            </td>
                                            <td style='width:50%; vertical-align:top; text-align:right; padding-left:15px;'>
                                                 <h4 style='margin:0 0 5px 0; color:#555;'>Invoice Details:</h4>
                                                 <p style='margin:0; line-height:1.5;'>
                                                    Date: " . date('d-m-Y', strtotime($invoice_date)) . "<br>
                                                    Status: $status
                                                 </p>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Items Table -->
                                    <table style='width:100%; border-collapse: collapse; margin-bottom:30px; font-size:13px;'>
                                        <thead>
                                            <tr style='background-color:#f8f9fa; border-bottom:2px solid #ddd;'>
                                                <th style='padding:8px; text-align:left;'>#</th>
                                                <th style='padding:8px; text-align:left;'>Item</th>
                                                <th style='padding:8px; text-align:center;'>HSN</th>
                                                <th style='padding:8px; text-align:center;'>Qty</th>
                                                <th style='padding:8px; text-align:center;'>Unit</th>
                                                <th style='padding:8px; text-align:right;'>Rate</th>
                                                <th style='padding:8px; text-align:right;'>Taxable</th>
                                                <th style='padding:8px; text-align:right;'>Tax %</th>
                                                <th style='padding:8px; text-align:right;'>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            $itemsHtml
                                        </tbody>
                                    </table>
                                     
                                    <!-- Footer Section -->
                                    <table style='width:100%; border-collapse:collapse;'>
                                        <tr>
                                            <td style='width:60%; vertical-align:top; padding-right:20px;'>
                                                 <div style='margin-bottom:15px;'>
                                                    <strong style='font-size:11px; text-transform:uppercase; color:#777;'>Amount in Words:</strong>
                                                    <div style='background:#f9f9f9; padding:8px; border:1px solid #eee; margin-top:5px; font-weight:bold; font-size:12px;'>
                                                        $amtInWords
                                                    </div>
                                                 </div>
                                                 
                                                 " . (!empty($termsContent) ? "
                                                 <div>
                                                    <strong style='font-size:11px; text-transform:uppercase; color:#777;'>Terms & Conditions:</strong>
                                                    <div style='font-size:11px; color:#555; margin-top:5px; line-height:1.4;'>
                                                        $termsContent
                                                    </div>
                                                 </div>" : "") . "
                                            </td>
                                            <td style='width:40%; vertical-align:top;'>
                                                <table style='width:100%; border-collapse:collapse;'>
                                                    <tr>
                                                        <td style='padding:5px 0; text-align:right; color:#555;'>Taxable Amount:</td>
                                                        <td style='padding:5px 0; text-align:right; font-weight:bold;'>₹" . number_format($sub_total - $discount_value, 2) . "</td>
                                                    </tr>
                                                    <tr>
                                                        <td style='padding:5px 0; text-align:right; color:#555;'>Total Tax:</td>
                                                        <td style='padding:5px 0; text-align:right; font-weight:bold;'>₹" . number_format($igst_amount + $cgst_amount + $sgst_amount, 2) . "</td>
                                                    </tr>
                                                    <tr>
                                                        <td style='padding:5px 0; text-align:right; color:#555;'>Adjustment:</td>
                                                        <td style='padding:5px 0; text-align:right; font-weight:bold;'>₹" . number_format($adjustment, 2) . "</td>
                                                    </tr>
                                                    " . ($input_redeemed_points > 0 ? "
                                                    <tr>
                                                        <td style='padding:5px 0; text-align:right; color:green;'>Loyalty Redeemed:</td>
                                                        <td style='padding:5px 0; text-align:right; color:green; font-weight:bold;'>- ₹" . number_format($input_redeemed_points, 2) . "</td>
                                                    </tr>" : "") . "
                                                    <tr style='border-top:2px solid #333;'>
                                                        <td style='padding:10px 0; text-align:right; font-weight:bold; font-size:14px;'>Grand Total:</td>
                                                        <td style='padding:10px 0; text-align:right; font-weight:bold; font-size:14px; color:#000;'>₹" . number_format($display_total, 2) . "</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <div style='margin-top:40px; text-align:center; color:#999; font-size:11px; border-top:1px solid #eee; padding-top:20px;'>
                                        Thank you for your business!<br>
                                        &copy; " . date('Y') . " $orgName.
                                    </div>
                                </div>";
                            
                            // 4. Send Email via PHPMailer
                            require_once __DIR__ . '/../../public/phpmailer/src/Exception.php';
                            require_once __DIR__ . '/../../public/phpmailer/src/PHPMailer.php';
                            require_once __DIR__ . '/../../public/phpmailer/src/SMTP.php';

                            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                        
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.gmail.com'; 
                            $mail->SMTPAuth   = true;
                            $mail->Username   = $authentication_email; 
                            $mail->Password   = $authentication_password; 
                            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port       = 587;

                            $mail->setFrom($authentication_email, $orgName);
                            $mail->addAddress($customerEmail, $customerName);
                            
                            if(!empty($logoPath) && file_exists($logoPath)){
                                 $mail->addEmbeddedImage($logoPath, 'org_logo', 'Logo.png');
                            }

                            $mail->isHTML(true);
                            $mail->Subject = "Invoice #$invoice_number from $orgName";
                            $mail->Body    = $msg;
                            
                            // --- PDF GENERATION START ---
                            require_once __DIR__ . '/../../vendor/autoload.php';
                            
                            // Fetch Customer Extended Details
                            $custPdfQ = $conn->query("SELECT * FROM customers_listing WHERE customer_id = $customer_id");
                            $custPdf = $custPdfQ->fetch_assoc();

                            // Build PDF HTML
                            $pdfHtml = "
                            <html>
                            <head>
                                <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
                                <style>
                                    body { font-family: 'DejaVu Sans', sans-serif; font-size:11px; color:#333; line-height:1.4; }
                                    .header { margin-bottom: 20px; }
                                    .col-left { float:left; width:48%; }
                                    .col-right { float:right; width:48%; text-align:right; }
                                    .clear { clear:both; }
                                    table { width:100%; border-collapse: collapse; margin-top:15px; table-layout: fixed; }
                                    th { background-color: #f1f1f1; padding:5px; border-bottom:1px solid #ccc; text-align:center; font-weight:bold; font-size:10px; overflow:hidden; }
                                    td { padding:5px; border-bottom:1px solid #eee; font-size:10px; vertical-align:top; overflow:hidden; word-wrap:break-word; }
                                    .text-left { text-align:left; }
                                    .text-right { text-align:right; }
                                    .text-center { text-align:center; }
                                    
                                    .footer-section { width:100%; margin-top:20px; }
                                    .footer-left { float:left; width:58%; padding-right:2%; }
                                    .footer-right { float:right; width:40%; }
                                    .box-light { background-color:#f9f9f9; padding:8px; border:1px solid #eee; margin-bottom:10px; border-radius:4px; }
                                    .terms-box { margin-top:10px; }
                                    .totals-table td { padding: 4px; border-bottom: none; }

                                    .header-logo { float: left; margin-bottom: 15px; }

                                    .footer-table { width: 100%; border-collapse: collapse; margin-top: 20px;}
                                    .bank-details-td { width: 45%; vertical-align: bottom; font-size: 10px; color: #555; }
                                    .qr-td { width: 20%; vertical-align: bottom; text-align: center; }
                                    .signature-td { width: 35%; vertical-align: bottom; text-align: right; }
                                </style>
                            </head>
                            <body>
                                <div class='header'>
                                    " . (!empty($logoDataUri) ? "<div class='header-logo'><img src='$logoDataUri' style='max-height:60px;'></div><div class='clear'></div>" : "") . "
                                    
                                    <div class='col-left'>
                                        <h5 style='margin:0; color:#777; font-size:9px; text-transform:uppercase;'>Details of Supplier (From)</h5>
                                        <h3 style='margin:5px 0;'>{$orgData['organization_name']}</h3>
                                        <p style='margin:0;'>{$orgData['address']}<br>GSTIN: {$orgData['gst_number']}</p>
                                    </div>
                                    <div class='col-right'>
                                        <h5 style='margin:0; color:#777; font-size:9px; text-transform:uppercase;'>Details of Recipient (To)</h5>
                                        <h3 style='margin:5px 0;'>{$custPdf['company_name']}</h3>
                                        <p style='margin:0;'>{$custPdf['address']}<br>GSTIN: {$custPdf['gst_number']}<br>State Code: " . substr($custPdf['gst_number'], 0, 2) . "</p>
                                    </div>
                                    <div class='clear'></div>
                                </div>
                                
                                <div style='border-top:1px solid #ddd; border-bottom:1px solid #ddd; padding:5px 0; margin-bottom:15px; text-align:center;'>
                                    <h2 style='margin:5px 0;'>TAX INVOICE</h2>
                                </div>
                                <div style='margin-bottom:15px;'>
                                    <strong>Invoice No:</strong> $invoice_number 
                                    <span style='float:right;'><strong>Date:</strong> " . date('d-m-Y', strtotime($invoice_date)) . "</span>
                                </div>

                                <table>
                                    <thead>
                                        <tr>
                                            <th width='5%'>#</th>
                                            <th width='35%' class='text-left'>Description</th>
                                            <th width='10%'>HSN/SAC</th>
                                            <th width='8%'>Qty</th>
                                            <th width='8%'>Unit</th>
                                            <th width='12%' class='text-right'>Rate</th>
                                            <th width='12%' class='text-right'>Taxable</th>
                                            <th width='5%' class='text-right'>Tax %</th>
                                            <th width='15%' class='text-right'>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        $pdfItemsHtml
                                    </tbody>
                                </table>

                                <div class='footer-section'>
                                    <div class='footer-left'>
                                        <div style='margin-bottom:5px;'>
                                            <strong style='font-size:10px; text-transform:uppercase; color:#777;'>Amount in Words:</strong>
                                            <div class='box-light' style='font-weight:bold; font-size:10px; line-height:1.4;'>
                                                $amtInWords
                                            </div>
                                        </div>
                                        " . (!empty($termsContent) ? "
                                        <div class='terms-box'>
                                            <strong style='font-size:10px; text-transform:uppercase; color:#777;'>Terms & Conditions:</strong>
                                            <div style='margin-top:2px; font-size:9px; color:#555;'>
                                                $termsContent
                                            </div>
                                        </div>
                                        " : "") . "
                                    </div>
                                    
                                    <div class='footer-right'>
                                        <table class='totals-table' style='margin-top:0;'>
                                            <tr>
                                                <td class='text-right'>Taxable Amount:</td>
                                                <td class='text-right'>₹" . number_format($sub_total - $discount_value, 2) . "</td>
                                            </tr>
                                            <tr>
                                                <td class='text-right'>Total Tax:</td>
                                                <td class='text-right'>₹" . number_format($igst_amount + $cgst_amount + $sgst_amount, 2) . "</td>
                                            </tr>
                                            <tr>
                                                <td class='text-right'>Adjustment:</td>
                                                <td class='text-right'>₹" . number_format($adjustment, 2) . "</td>
                                            </tr>
                                            " . ((!empty($input_redeemed_points) && $input_redeemed_points > 0) ? "
                                            <tr>
                                                <td class='text-right' style='color:green;'>Loyalty Redeemed:</td>
                                                <td class='text-right' style='color:green; font-weight:bold;'>-₹" . number_format($input_redeemed_points, 2) . "</td>
                                            </tr>" : "") . "
                                            <tr>
                                                <td class='text-right' style='border-top:1px solid #333; padding-top:5px;'><strong>Grand Total:</strong></td>
                                                <td class='text-right' style='border-top:1px solid #333; padding-top:5px;'><strong>₹" . number_format($display_total, 2) . "</strong></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class='clear'></div>
                                </div>
                                
                                <div class=\"footer-section\" style=\"margin-top: 30px;\">
                                    <table class=\"footer-table\" style=\"border: none;\">
                                        <tr>
                                            <td class=\"bank-details-td\" style=\"border: none;\">
                                                <strong>Bank Details:</strong><br>
                                                Account Name: " . htmlspecialchars($org_acc_holder) . "<br>
                                                Bank: " . htmlspecialchars($org_bank) . "<br>
                                                Branch: " . htmlspecialchars($org_branch) . "<br>
                                                A/c No: " . htmlspecialchars($org_acc) . "<br>
                                                IFSC: " . htmlspecialchars($org_ifsc) . "<br>
                                                UPI: " . htmlspecialchars($org_upi) . "
                                            </td>
                                            <td class=\"qr-td\" style=\"border: none;\">
                                                " . (!empty($qrDataUri) ? "<img src=\"$qrDataUri\" alt=\"QR Code\" style=\"height: 80px; width: 80px;\">" : "") . "
                                            </td>
                                            <td class=\"signature-td\" style=\"border: none;\">
                                                <p style=\"margin-bottom: 40px; font-weight: 600;\">For " . htmlspecialchars($orgData['organization_name'] ?? '') . "</p>
                                                <div style=\"font-size: 11px; margin-top: 5px;\">Authorized Signatory</div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </body>
                            </html>";

                            // Dompdf Configuration
                            $options = new \Dompdf\Options();
                            $options->set('isRemoteEnabled', true);
                            
                            $dompdf = new \Dompdf\Dompdf($options);
                            $dompdf->loadHtml($pdfHtml);
                            $dompdf->setPaper('A4', 'portrait');
                            $dompdf->render();
                            $pdfOutput = $dompdf->output();
                            
                            $mail->addStringAttachment($pdfOutput, "Invoice_$invoice_number.pdf");
                            
                            // Send
                            $mail->send();
                        }
                    }
                } catch (Exception $mailEx) {
                     // Log error but generally continue process
                     error_log("Loyalty Mail/PDF Error: " . $mailEx->getMessage());
                }
            }

            // C. Update Customer Outstanding Balance & Ledger Entry
            
            // Assuming tax invoice is fully credit unless advance is handled (not implemented/visible yet)
            // Use 'total_amount' which implies the final payable amount on this invoice
            $ledger_debit = $total_amount; 
            
            // 1. Update Customer Master Balance
            $updateCust = $conn->prepare("UPDATE customers_listing SET current_balance_due = current_balance_due + ? WHERE customer_id = ?");
            $updateCust->bind_param("di", $ledger_debit, $customer_id);
            $updateCust->execute();
            $updateCust->close();

            // 2. Fetch New Balance for Ledger correctness
            $balSql = "SELECT current_balance_due FROM customers_listing WHERE customer_id = $customer_id";
            $balRes = $conn->query($balSql);
            $new_ledger_balance = ($balRes && $balRes->num_rows > 0) ? floatval($balRes->fetch_assoc()['current_balance_due']) : 0;

            // 3. Add to Ledger
            $particulars = "Invoice #" . $invoice_number . ($reference_no ? " (Ref: $reference_no)" : "");
            $ledgerRefType = 'invoice';

            $ledgerSql = "INSERT INTO customers_ledger (organization_id, customer_id, transaction_date, particulars, debit, credit, balance, reference_id, reference_type, created_at) 
                          VALUES (?, ?, ?, ?, ?, 0.00, ?, ?, ?, NOW())";
            
            $ls = $conn->prepare($ledgerSql);
            // Types: i (org), i (cust), s (date), s (part), d (dr), d (bal), i (ref_id), s (ref_type)
            $ls->bind_param("iissddis", $organization_id, $customer_id, $invoice_date, $particulars, $ledger_debit, $new_ledger_balance, $invoice_id, $ledgerRefType);
            $ls->execute();
            $ls->close();
        }
        
        // 3. Attachments (Optionally add table sales_invoice_files if needed, skipping for now)

        $conn->commit();
        header("Location: ../../tax_invoice?success=Invoice created successfully");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../../tax_invoice?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: ../../tax_invoice");
    exit;
}
?>
