<?php
require_once '../../config/auth_guard.php';

if (isset($_POST['save_dn'])) { 
    $organization_id = $_SESSION['organization_id'];

    // Inputs
    $po_id = intval($_POST['po_id']);
    $vendor_id = intval($_POST['vendor_id']);
    $dn_number = $_POST['dn_number']; // Auto generated or manual
    $dn_date = $_POST['dn_date'];
    $remarks = $_POST['dn_remarks'];

    $items = $_POST['items'] ?? [];

    $conn->begin_transaction();
    try {
        // 1. Insert Debit Note Header
        $sql = "INSERT INTO debit_notes (organization_id, po_id, vendor_id, debit_note_number, debit_note_date, remarks, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        if(!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        
        $stmt->bind_param("iiisss", $organization_id, $po_id, $vendor_id, $dn_number, $dn_date, $remarks);
        
        if(!$stmt->execute()){
            throw new Exception("Debit Note Header Insert Failed: " . $stmt->error);
        }
        $dn_id = $conn->insert_id;
        $stmt->close();

        // 2. Insert Debit Note Items
        $total_dn_amount = 0;

        if (!empty($items)) {
            $itemSql = "INSERT INTO debit_note_items (debit_note_id, po_item_id, item_id, return_qty, return_reason, remarks) 
                        VALUES (?, ?, ?, ?, ?, ?)";
            $itemStmt = $conn->prepare($itemSql);
            if(!$itemStmt) throw new Exception("Item Prepare failed: " . $conn->error);

            foreach ($items as $item) {
                $po_item_id = intval($item['po_item_id']);
                $item_id = intval($item['item_id']);
                $return_qty = floatval($item['return_qty']);
                $reason = $item['reason'];
                $line_remarks = $item['remarks'];
                
                // Get PO Rate to calculate amount
                 // We need to fetch rate again or trust client? 
                 // Safer to fetch from DB to avoid manipulation, but for speed using hidden might be done if validated.
                 // Let's fetch rate from purchase_order_items for this item
                 $rateSql = "SELECT rate FROM purchase_order_items WHERE id = ?"; 
                 $rateStmt = $conn->prepare($rateSql);
                 $rateStmt->bind_param("i", $po_item_id);
                 $rateStmt->execute();
                 $rRes = $rateStmt->get_result();
                 $rate = 0;
                 if($rRes->num_rows > 0){
                     $rate = floatval($rRes->fetch_assoc()['rate']);
                 }
                 $rateStmt->close();
                 
                 $line_total = $return_qty * $rate;
                 $total_dn_amount += $line_total;

                if ($return_qty > 0) { // Only Insert if returning something
                    $itemStmt->bind_param("iiidss", $dn_id, $po_item_id, $item_id, $return_qty, $reason, $line_remarks);
                    if(!$itemStmt->execute()){
                        throw new Exception("Item Insert Failed: " . $itemStmt->error);
                    }

                    // 3. Update Stock Logic (DECREASE Stock)
                    // We assume 'items_listing' has a 'current_stock' column.
                    $stkSql = "UPDATE items_listing SET current_stock = COALESCE(current_stock, 0) - ? WHERE item_id = ?";
                    $stkStmt = $conn->prepare($stkSql);
                    if(!$stkStmt) {
                         throw new Exception("Stock Update Prepare Failed: " . $conn->error);
                    }
                    $stkStmt->bind_param("di", $return_qty, $item_id);
                    if(!$stkStmt->execute()){
                         throw new Exception("Stock Update Execute Failed: " . $stkStmt->error);
                    }
                    $stkStmt->close();
                }
            }
            $itemStmt->close();
        }

        // 4. Update Vendor Current Balance Due (Subtract DN Amount)
        if($total_dn_amount > 0){
            $updVendorSql = "UPDATE vendors_listing SET current_balance_due = current_balance_due - ? WHERE vendor_id = ?";
            $updVendorStmt = $conn->prepare($updVendorSql);
            if($updVendorStmt){
                $updVendorStmt->bind_param("di", $total_dn_amount, $vendor_id);
                $updVendorStmt->execute();
                $updVendorStmt->close();
            }
        }

        // We do NOT update PO status automatically for now, as Returns are ad-hoc. 
        // Or we could have a 'Returned' status, but 'Received' is technically correct for the lifecycle phases covered.

        $conn->commit();
        header("Location: ../../debit_note?success=Debit Note Created Successfully&id=" . $dn_id);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../../debit_note?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: ../../debit_note");
    exit;
}
?>
