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

if (isset($_POST['save_invoice'])) { // Reusing same submit button name for convenience
 
    $organization_id = $_SESSION['organization_id'];
    
    // Validate inputs
    if(empty($_POST['customer_id']) || empty($_POST['invoice_number'])){
        header("Location: ../../proforma_invoice?error=Missing required fields");
        exit;
    }

    $customer_id = intval($_POST['customer_id']);
    $proforma_invoice_id = !empty($_POST['proforma_invoice_id']) ? intval($_POST['proforma_invoice_id']) : 0;
    
    $invoice_number = $_POST['invoice_number']; // PRO-XXXX
    $reference_no = $_POST['reference_no'];
    $invoice_date = $_POST['invoice_date']; // Issue Date
    $payment_terms = $_POST['payment_terms'];
    
    // New Fields
    $sales_employee_id = !empty($_POST['sales_employee_id']) ? intval($_POST['sales_employee_id']) : NULL;
    $delivery_mode = $_POST['delivery_mode'] ?? '';
    
    
    // GST Fields & Auto-Calculation
    $adjustment = floatval($_POST['adjustment'] ?? 0);
    // Note: gst_rate is stored per item in proforma_invoice_items table

    // Calculate Total Tax from Input to redistribute
    $in_cgst = floatval($_POST['cgst_amount'] ?? 0);
    $in_sgst = floatval($_POST['sgst_amount'] ?? 0);
    $in_igst = floatval($_POST['igst_amount'] ?? 0);
    $total_tax_calc = $in_cgst + $in_sgst + $in_igst;
    
    // Debug: Log received tax values
    error_log("Received from form - CGST: $in_cgst, SGST: $in_sgst, IGST: $in_igst, Total: $total_tax_calc");

    // Fetch State Codes for Logic
    $org_state_code_logic = $_SESSION['organization_state_code'] ?? '';
    if(empty($org_state_code_logic)){
         // Fallback fetch
         $lQ1 = $conn->query("SELECT state_code FROM organizations WHERE organization_id = $organization_id");
         if($lQ1 && $lR1=$lQ1->fetch_assoc()) $org_state_code_logic = trim($lR1['state_code']);
    }

    $cust_state_code_logic = '';
    $lQ2 = $conn->query("SELECT state_code FROM customers_listing WHERE customer_id = $customer_id");
    if($lQ2 && $lR2=$lQ2->fetch_assoc()) $cust_state_code_logic = trim($lR2['state_code']);

    // GST Logic: If Customer State differs from Org State -> IGST
    $is_inter_state = false;
    // Ensure both are valid before comparing. If Customer state missing, assume Local (Intra-state)
    if(!empty($cust_state_code_logic) && !empty($org_state_code_logic) && strcasecmp($cust_state_code_logic, $org_state_code_logic) !== 0){
        $is_inter_state = true;
    }
    // Strict adherence to standard: 
    // User requested "OR customers_listing.state_code != null". 
    // If we followed that, any customer with a state would get IGST, even local. We stick to Diff Check.

    if($is_inter_state){
        $gst_type = 'IGST';
        $igst_amount = $total_tax_calc;
        $cgst_amount = 0;
        $sgst_amount = 0;
    } else {
        $gst_type = 'CGST_SGST';
        $igst_amount = 0;
        $cgst_amount = $total_tax_calc / 2;
        $sgst_amount = $total_tax_calc / 2;
    }
    
    // Debug: Log final tax values after redistribution
    error_log("After redistribution - GST Type: $gst_type, CGST: $cgst_amount, SGST: $sgst_amount, IGST: $igst_amount");

    $sub_total = floatval($_POST['sub_total']);
    $total_amount = floatval($_POST['total_amount']);
    $notes = $_POST['notes'];
    $terms_conditions = $_POST['terms_conditions'];
    $status = 'sent'; // Proforma is usually sent immediately

    $items = $_POST['items'] ?? [];

    $conn->begin_transaction();
    try {
        

        if ($proforma_invoice_id > 0) {
            // --- UPDATE MODE ---
            
            // 1. Update Header
            $sql = "UPDATE proforma_invoices SET 
                    customer_id=?, reference_no=?, invoice_date=?, payment_terms=?, 
                    adjustment=?, gst_type=?, 
                    cgst_amount=?, sgst_amount=?, igst_amount=?, sub_total=?, total_amount=?, status=?, 
                    notes=?, terms_conditions=?, sales_employee_id=?, delivery_mode=?, updated_at=NOW()
                    WHERE proforma_invoice_id=? AND organization_id=?";
            
            $stmt = $conn->prepare($sql);
            if(!$stmt) throw new Exception("Prepare Update Failed: " . $conn->error);
            
            // Removed: due_date, discount_type, discount_value, gst_rate
            // Note: gst_rate is stored per item in proforma_invoice_items
            $stmt->bind_param("isssdsdddddsssisii", $customer_id, $reference_no, $invoice_date, $payment_terms, $adjustment, $gst_type, $cgst_amount, $sgst_amount, $igst_amount, $sub_total, $total_amount, $status, $notes, $terms_conditions, $sales_employee_id, $delivery_mode, $proforma_invoice_id, $organization_id);
            
            if(!$stmt->execute()){
                throw new Exception("Proforma Update Failed: " . $stmt->error);
            }
            $stmt->close();
            
            $invoice_id = $proforma_invoice_id; // Set ID for item insertion
            $is_update = true;
            
            // 2. Delete Existing Items
            $delSql = "DELETE FROM proforma_invoice_items WHERE proforma_invoice_id = ?";
            $delStmt = $conn->prepare($delSql);
            $delStmt->bind_param("i", $invoice_id);
            $delStmt->execute();
            $delStmt->close();
            
        } else {
            // --- INSERT MODE ---
            
            // 1. Insert Proforma Header
            $sql = "INSERT INTO proforma_invoices (organization_id, customer_id, proforma_invoice_number, reference_no, invoice_date, payment_terms, adjustment, gst_type, cgst_amount, sgst_amount, igst_amount, sub_total, total_amount, status, notes, terms_conditions, make_employee_id, sales_employee_id, delivery_mode, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                 throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error . ". Please ensure 'proforma_invoices' table exists. Run migration.");
            }
            
            // Removed: due_date, discount_type, discount_value, gst_rate
            // Note: gst_rate is stored per item in proforma_invoice_items
            $make_employee_id = $_SESSION['user_id'];
            $stmt->bind_param("iissssdsdddddsssiis", $organization_id, $customer_id, $invoice_number, $reference_no, $invoice_date, $payment_terms, $adjustment, $gst_type, $cgst_amount, $sgst_amount, $igst_amount, $sub_total, $total_amount, $status, $notes, $terms_conditions, $make_employee_id, $sales_employee_id, $delivery_mode);
            
            if(!$stmt->execute()){
                throw new Exception("Proforma Insert Failed: " . $stmt->error);
            }
            $invoice_id = $conn->insert_id;
            $stmt->close();
            $is_update = false;
        }

        // 2. Insert Items (Common for both)
        if (!empty($items)) {
            // Assuming table 'proforma_invoice_items' exists and has new columns
            $itemSql = "INSERT INTO proforma_invoice_items (organization_id, proforma_invoice_id, item_id, item_name, unit_id, hsn_code, quantity, rate, discount, discount_type, amount, gst_rate, total_amount, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $itemStmt = $conn->prepare($itemSql);
            if(!$itemStmt){
                throw new Exception("Prepare Item failed: " . $conn->error);
            }

            foreach ($items as $item) {
                $item_id = intval($item['item_id']);
                $quantity = floatval($item['quantity']);
                $rate = floatval($item['rate']);
                $discount = floatval($item['discount'] ?? 0);
                $discount_type = $item['discount_type'] ?? 'amount';
                $amount = floatval($item['amount']); // Taxable Value

                // Fetch Full Item Details including HSN & GST
                $detSql = "SELECT i.item_name, i.unit_id, h.hsn_code, h.gst_rate 
                           FROM items_listing i 
                           LEFT JOIN hsn_listing h ON i.hsn_id = h.hsn_id 
                           WHERE i.item_id = $item_id";
                $detRes = $conn->query($detSql);
                $prod = $detRes->fetch_assoc();
                
                $item_name = $prod['item_name'] ?? 'Unknown Item';
                $unit_id_db = $prod['unit_id'] ?? 0;
                $hsn_code = $prod['hsn_code'] ?? '';
                $item_gst_rate = floatval($prod['gst_rate'] ?? 0);

                // Calculate Total Amount (Taxable + GST)
                $tax_amt = $amount * ($item_gst_rate / 100);
                $item_total_amount = $amount + $tax_amt;

                // Insert
                // Params: 13 params now + NOW()
                // Types: iiisisdddsddd -> iiisssidddsd dd -> iiis s dddssd dd
                // organization_id(i), invoice_id(i), item_id(i), item_name(s), unit_id(i), hsn_code(s), qty(d), rate(d), disc(d), distype(s), amount(d), gst_rate(d), total(d)
                // string: iiisisdddsddd
                $itemStmt->bind_param("iiisisdddsddd", $organization_id, $invoice_id, $item_id, $item_name, $unit_id_db, $hsn_code, $quantity, $rate, $discount, $discount_type, $amount, $item_gst_rate, $item_total_amount);
                $itemStmt->execute();
            }
            $itemStmt->close();
        }



        // --- EMAIL & PDF GENERATION ---

        // 2b. Re-fetch Items for Email/PDF construction (since we just inserted them or we might need details like HSN)
        // Ideally we should have built this loop earlier, but to match save_invoice structure, let's build it here or loop $items again.
        // We will loop $items again as it has the latest post data.

        $itemsHtml = '';
        $pdfItemsHtml = '';
        $idx = 1;

        if (!empty($items)) {
            foreach ($items as $item) {
                $item_id = intval($item['item_id']);
                $quantity = floatval($item['quantity']);
                $rate = floatval($item['rate']);
                $discount = floatval($item['discount'] ?? 0);
                $discount_type = $item['discount_type'] ?? 'amount';
                $amount = floatval($item['amount']); // Taxable Value

                // Fetch Full Item Details
                $detSql = "SELECT i.item_name, i.unit_id, h.hsn_code, h.gst_rate, u.unit_name 
                           FROM items_listing i 
                           LEFT JOIN hsn_listing h ON i.hsn_id = h.hsn_id 
                           LEFT JOIN units_listing u ON i.unit_id = u.unit_id 
                           WHERE i.item_id = $item_id";
                $detRes = $conn->query($detSql);
                $prod = $detRes->fetch_assoc();
                
                $item_name = $prod['item_name'] ?? 'Unknown Item';
                $unit_name = $prod['unit_name'] ?? 'Unit';
                $hsn_code = $prod['hsn_code'] ?? '-';
                
                // Calculate Taxes Per Item (Using Global Invoice Settings)
                $cgst_rate = 0; $cgst_amt = 0;
                $sgst_rate = 0; $sgst_amt = 0;
                $igst_rate = 0; $igst_amt = 0;

                $item_gst_rate = floatval($prod['gst_rate'] ?? 0);

                if ($gst_type === 'CGST_SGST') {
                    $cgst_rate = $item_gst_rate / 2;
                    $sgst_rate = $item_gst_rate / 2;
                    $cgst_amt = $amount * ($cgst_rate / 100);
                    $sgst_amt = $amount * ($sgst_rate / 100);
                } else if ($gst_type === 'IGST') {
                    $igst_rate = $item_gst_rate;
                    $igst_amt = $amount * ($igst_rate / 100);
                }
                
                $item_total = $amount + $cgst_amt + $sgst_amt + $igst_amt;
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

                 // Build HTML for PDF (Detailed Table)
                 $pdfItemsHtml .= "
                 <tr>
                     <td class='text-center'>$idx</td>
                     <td>$item_name</td>
                     <td class='text-center'>$hsn_code</td>
                     <td class='text-center'>$quantity</td>
                     <td class='text-center'>$unit_name</td>
                     <td class='text-right'>₹" . number_format($rate, 2) . "</td>
                     <td class='text-right'>₹" . number_format($amount, 2) . "</td>
                     <td class='text-right'>" . number_format($cgst_rate + $sgst_rate + $igst_rate, 1) . "%</td>
                     <td class='text-right'><b>₹" . number_format($item_total, 2) . "</b></td>
                 </tr>";
                 
                 $idx++;
            }
        }

        $conn->commit();

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
                    
                    $amtInWords = getIndianCurrency($total_amount);
                    $termsContent = !empty($terms_conditions) ? nl2br($terms_conditions) : '';

                    $msg = "
                         <div style='font-family: Arial, sans-serif; max-width: 800px; color: #333; border:1px solid #eee; padding:30px; border-radius:4px; margin:0 auto;'>
                            
                            <!-- Header -->
                            <div style='margin-bottom:30px;'>
                                $logoHtml
                                <h2 style='margin:0; text-align:center; color:#333; text-transform:uppercase;'>Proforma Invoice</h2>
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
                                            Payment Terms: $payment_terms
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
                                                <td style='padding:5px 0; text-align:right; font-weight:bold;'>₹" . number_format($sub_total, 2) . "</td>
                                            </tr>
                                            <tr>
                                                <td style='padding:5px 0; text-align:right; color:#555;'>Total Tax:</td>
                                                <td style='padding:5px 0; text-align:right; font-weight:bold;'>₹" . number_format($igst_amount + $cgst_amount + $sgst_amount, 2) . "</td>
                                            </tr>
                                            <tr>
                                                <td style='padding:5px 0; text-align:right; color:#555;'>Adjustment:</td>
                                                <td style='padding:5px 0; text-align:right; font-weight:bold;'>₹" . number_format($adjustment, 2) . "</td>
                                            </tr>
                                            <tr style='border-top:2px solid #333;'>
                                                <td style='padding:10px 0; text-align:right; font-weight:bold; font-size:14px;'>Grand Total:</td>
                                                <td style='padding:10px 0; text-align:right; font-weight:bold; font-size:14px; color:#000;'>₹" . number_format($total_amount, 2) . "</td>
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
                    $mail->Subject = "Proforma Invoice #$invoice_number from $orgName";
                    $mail->Body    = $msg;
                    
                    // --- PDF GENERATION START ---

                    // Fetch Sales & Maker Name for PDF/Email
                    $sales_name = '';
                    $maker_name = '';

                    if(!empty($sales_employee_id)){
                        $sQ = $conn->query("SELECT first_name, last_name, employee_code FROM employees WHERE employee_id = $sales_employee_id");
                        if($sQ && $row = $sQ->fetch_assoc()){
                             $sales_name = trim($row['first_name'] . ' ' . $row['last_name']);
                             if(!empty($row['employee_code'])) $sales_name .= " ({$row['employee_code']})";
                        }
                    }

                    $creator_id = 0;
                    if(isset($is_update) && $is_update && $invoice_id > 0){
                         // Update Mode - fetch original
                         $cQ = $conn->query("SELECT make_employee_id FROM proforma_invoices WHERE proforma_invoice_id = $invoice_id");
                         if($cQ && $row = $cQ->fetch_assoc()){
                             $creator_id = $row['make_employee_id'];
                         }
                    } else {
                        // Insert Mode - current user
                        $creator_id = $_SESSION['user_id'] ?? 0;
                    }

                    if(!empty($creator_id)){
                        $mQ = $conn->query("SELECT first_name, last_name, employee_code FROM employees WHERE employee_id = $creator_id");
                        if($mQ && $row = $mQ->fetch_assoc()){
                            $maker_name = trim($row['first_name'] . ' ' . $row['last_name']);
                            if(!empty($row['employee_code'])) $maker_name .= " ({$row['employee_code']})";
                        }
                    }

                    require_once __DIR__ . '/../../vendor/autoload.php';
                    
                    // Fetch Customer Extended Details
                    $custPdfQ = $conn->query("SELECT * FROM customers_listing WHERE customer_id = $customer_id");
                    $custPdf = $custPdfQ->fetch_assoc();
                    $cust_gst = $custPdf['gst_number'] ?? 'N/A';
                    $cust_state_code = (strlen($cust_gst) >= 2) ? substr($cust_gst, 0, 2) : '-';

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
                            .totals { margin-top:20px; float:right; width:50%; }
                            .totals td { border-bottom:none; padding:4px; }
                            .row { width:100%; }
                            .col { width:50%; float:left; }
                            .header-logo { float: left; margin-bottom: 15px; }

                            .footer-section { margin-top: 30px; }
                            .footer-table { width: 100%; }
                            .bank-details-td { width: 50%; vertical-align: bottom; font-size: 10px; color: #555; border: none; }
                            .qr-td { width: 15%; vertical-align: bottom; text-align: center; border: none; }
                            .signature-td { width: 35%; vertical-align: bottom; text-align: right; border: none; }
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
                                <p style='margin:0;'>{$custPdf['address']}<br>GSTIN: $cust_gst<br>State Code: $cust_state_code</p>
                            </div>
                            <div class='clear'></div>
                        </div>
                        
                        <div style='border-top:1px solid #ddd; border-bottom:1px solid #ddd; padding:5px 0; margin-bottom:15px; text-align:center;'>
                            <h2 style='margin:5px 0;'>PROFORMA INVOICE</h2>
                        </div>

                        <div style='margin-bottom:15px;'>
                            <strong>Invoice No:</strong> $invoice_number 
                            <span style='float:right;'><strong>Date:</strong> " . date('d-m-Y', strtotime($invoice_date)) . "</span>
                        </div>

                        <div style='margin-bottom:15px; font-size:10px; border-bottom:1px solid #ddd; padding-bottom:5px; color:#555;'>
                            <table style='width:100%; border-collapse:collapse; margin:0;'>
                                <tr>
                                    <td style='border:none; padding:0; width:50%; text-align:left;'><strong>Sales Person:</strong> " . ($sales_name ?: '-') . "</td>
                                    <td style='border:none; padding:0; width:50%; text-align:right;'><strong>Delivery Mode:</strong> " . ($delivery_mode ?: '-') . "</td>
                                </tr>
                                <tr>
                                    <td style='border:none; padding-top:5px; width:50%; text-align:left;'><strong>Created By:</strong> " . ($maker_name ?: '-') . "</td>
                                    <td style='border:none; padding-top:5px; width:50%;'></td>
                                </tr>
                            </table>
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

                        <div class='totals'>
                            <table>
                                <tr>
                                    <td class='text-right'>Gross Amount:</td>
                                    <td class='text-right'>₹" . number_format($sub_total, 2) . "</td>
                                </tr>
                                <tr>
                                    <td class='text-right'>Taxable Amount:</td>
                                    <td class='text-right'>₹" . number_format($sub_total, 2) . "</td>
                                </tr>
                                <tr>
                                    <td class='text-right'>Total Tax (IGST+CGST+SGST):</td>
                                    <td class='text-right'>₹" . number_format($igst_amount + $cgst_amount + $sgst_amount, 2) . "</td>
                                </tr>
                                <tr>
                                    <td class='text-right'>Adjustment:</td>
                                    <td class='text-right'>₹" . number_format($adjustment, 2) . "</td>
                                </tr>
                                <tr>
                                    <td class='text-right'><strong>Grand Total:</strong></td>
                                    <td class='text-right'><strong>₹" . number_format($total_amount, 2) . "</strong></td>
                                </tr>
                            </table>
                        </div>
                        <div class='clear'></div>
                        
                        <!-- Footer (Using Table for Layout) -->
                        <div class=\"footer-section\">
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
                    
                    $mail->addStringAttachment($pdfOutput, "Proforma_Invoice_$invoice_number.pdf");
                    
                    // Send
                    $mail->send();
                }
            }
        } catch (Exception $mailEx) {
             // Log error but generally continue process
             error_log("Proforma Mail/PDF Error: " . $mailEx->getMessage());
        }


        
        $msg = $is_update ? "Proforma Invoice updated successfully" : "Proforma Invoice created successfully";
        header("Location: ../../proforma_invoice?success=" . urlencode($msg));
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../../proforma_invoice?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: ../../proforma_invoice");
    exit;
}
?>
