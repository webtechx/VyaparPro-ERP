<?php
include __DIR__ . '/../../config/auth_guard.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $organization_id = $_SESSION['organization_id'];
    $user_id = $_SESSION['user_id'] ?? 0;

    // input validation
    $credit_note_number = $_POST['credit_note_number'];
    $credit_note_date = $_POST['credit_note_date'];
    $invoice_id = intval($_POST['invoice_id']);
    $customer_id = intval($_POST['customer_id']);
    $reason = $_POST['reason'];
    $notes = $_POST['notes'];
    
    // Totals
    $sub_total = floatval($_POST['sub_total']);
    $total_amount = floatval($_POST['total_amount']);
    $adjustment = floatval($_POST['adjustment']);
    
    // Taxes
    $cgst_amount = floatval($_POST['cgst_amount']);
    $sgst_amount = floatval($_POST['sgst_amount']);
    $igst_amount = floatval($_POST['igst_amount']);
    
    // GST Type (Infer from amounts or pass hidden?)
    // If IGST > 0 => IGST, else CGST_SGST.
    $gst_type = ($igst_amount > 0) ? 'IGST' : 'CGST_SGST';

    $items = $_POST['items'] ?? [];

    if (empty($credit_note_number) || empty($invoice_id) || empty($items)) {
        header("Location: ../../billing/credit_note?id=$invoice_id&error=Missing required fields");
        exit;
    }

    $conn->begin_transaction();
    try {
        // 1. Insert Header
        // Calculate Total Discount first
        $total_discount = 0;
        if(isset($_POST['items'])) {
            foreach($_POST['items'] as $itm) {
                 // Calculate item discount
                 $q = floatval($itm['quantity']);
                 $r = floatval($itm['rate']);
                 $d = floatval($itm['discount'] ?? 0);
                 $dt = $itm['discount_type'] ?? 'amount';
                 
                 $base = $q * $r;
                 $discAmt = 0;
                 if($dt == 'percentage') $discAmt = $base * ($d/100);
                 else $discAmt = $d;
                 
                 $total_discount += $discAmt;
            }
        }

        // Determine status based on credit note amount vs original invoice amount
        $original_invoice_total = floatval($invoice['total_amount']);
        if ($total_amount == 0) {
            $status = 'paid';  // No credit amount
        } elseif ($total_amount >= $original_invoice_total) {
            $status = 'refunded';  // Full refund
        } else {
            $status = 'approved';  // Partial credit
        }
        
        $sql = "INSERT INTO credit_notes (organization_id, customer_id, invoice_id, credit_note_number, credit_note_date, reason, sub_total, total_discount, total_amount, adjustment, gst_type, cgst_amount, sgst_amount, igst_amount, notes, status, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        // Types: i(org), i(cust), i(inv), s(num), s(date), s(reason), d(sub), d(disc), d(tot), d(adj), s(gst), d(c), d(s), d(i), s(note), s(status), i(creator)
        // 17 params
        $stmt->bind_param("iiisssddddssddssi", $organization_id, $customer_id, $invoice_id, $credit_note_number, $credit_note_date, $reason, $sub_total, $total_discount, $total_amount, $adjustment, $gst_type, $cgst_amount, $sgst_amount, $igst_amount, $notes, $status, $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Header Insertion Failed: " . $stmt->error);
        }
        $credit_note_id = $conn->insert_id;
        $stmt->close();

        // Update original invoice status to 'refunded' when credit note is created
        $updateInvoiceSql = "UPDATE sales_invoices SET status = 'refunded', updated_at = NOW() WHERE invoice_id = ?";
        $updateStmt = $conn->prepare($updateInvoiceSql);
        $updateStmt->bind_param("i", $invoice_id);
        $updateStmt->execute();
        $updateStmt->close();

        // 2. Insert Items
        $itemSql = "INSERT INTO credit_note_items (organization_id, credit_note_id, item_id, item_name, hsn_code, unit_id, quantity, rate, discount, discount_type, amount, gst_rate, total_amount, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $itemStmt = $conn->prepare($itemSql);

        // Prepare Stock Update Statement
        $stockSql = "UPDATE items_listing SET current_stock = current_stock + ? WHERE item_id = ?";
        $stockStmt = $conn->prepare($stockSql);

        foreach ($items as $item) {
            $itemId = intval($item['item_id']);
            $itemName = $item['item_name'];
            $hsn = $item['hsn_code'];
            $unitId = intval($item['unit_id']);
            $qty = floatval($item['quantity']);
            
            // Skip if qty is 0 (User removed item logic)
            if ($qty <= 0) continue;

            $rate = floatval($item['rate']);
            $discount = floatval($item['discount'] ?? 0);
            $discountType = $item['discount_type'] ?? 'amount';
            $amount = floatval($item['amount']); // Taxable
            $gstRate = floatval($item['gst_rate']);
            $itemTotal = floatval($item['total_amount']);

            // Insert Item
            // Types: i(org), i(cn), i(item), s(name), s(hsn), i(unit), d(qty), d(rate), d(disc), s(type), d(amt), d(gst), d(tot)
            $itemStmt->bind_param("iiissidddsddd", $organization_id, $credit_note_id, $itemId, $itemName, $hsn, $unitId, $qty, $rate, $discount, $discountType, $amount, $gstRate, $itemTotal);
            $itemStmt->execute();

            // Return Stock (If Goods Returned)
            if ($reason === 'Return' || $reason === 'Damage') { // Or assume always? Let's safeguard with Reason check for now or just do it.
                // Assuming Credit Note involves physical return unless specified otherwise.
                 $stockStmt->bind_param("di", $qty, $itemId);
                 $stockStmt->execute();
            }
        }
        $itemStmt->close();
        $stockStmt->close();

        // 3. Update Customer Balance (Decrease Due Amount)
        $balSql = "UPDATE customers_listing SET current_balance_due = current_balance_due - ? WHERE customer_id = ?";
        $balStmt = $conn->prepare($balSql);
        $balStmt->bind_param("di", $total_amount, $customer_id);
        $balStmt->execute();
        $balStmt->close();

        // 3.1 Update Invoice Balance Due
        $invBalSql = "UPDATE sales_invoices SET balance_due = balance_due - ? WHERE invoice_id = ?";
        $invBalStmt = $conn->prepare($invBalSql);
        $invBalStmt->bind_param("di", $total_amount, $invoice_id);
        $invBalStmt->execute();
        $invBalStmt->close();

        // 4. Ledger Entry (Credit to Customer)
        // Fetch new balance
        $checkBal = $conn->query("SELECT current_balance_due FROM customers_listing WHERE customer_id = $customer_id");
        $newBalance = ($checkBal && $checkBal->num_rows > 0) ? floatval($checkBal->fetch_assoc()['current_balance_due']) : 0;

        $particulars = "Credit Note #$credit_note_number (Against Invoice #$invoice_id)";
        $ledgerSql = "INSERT INTO customers_ledger (organization_id, customer_id, transaction_date, particulars, debit, credit, balance, reference_id, reference_type, created_at) 
                      VALUES (?, ?, ?, ?, 0.00, ?, ?, ?, 'credit_note', NOW())";
        $ledgerStmt = $conn->prepare($ledgerSql);
        $ledgerStmt->bind_param("iissddi", $organization_id, $customer_id, $credit_note_date, $particulars, $total_amount, $newBalance, $credit_note_id);
        $ledgerStmt->execute();
        $ledgerStmt->close();

        // 5. History Log
        $histSql = "INSERT INTO credit_note_history (organization_id, credit_note_id, action, description, performed_by, created_at) 
                    VALUES (?, ?, 'created', 'Credit Note Generated', ?, NOW())";
        $histStmt = $conn->prepare($histSql);
        $histStmt->bind_param("iii", $organization_id, $credit_note_id, $user_id);
        $histStmt->execute();
        $histStmt->close();

        $conn->commit();
        
        // Redirect to View
        header("Location: ../../credit_note_view?id=$credit_note_id"); 
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        // echo "Error: " . $e->getMessage();
        header("Location: ../../billing/credit_note?id=$invoice_id&error=" . urlencode($e->getMessage()));
        exit;
    }
}
?>
