<?php
require_once '../../config/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $organization_id = $_SESSION['organization_id'];
    $dn_id = intval($_POST['dn_id']);
    
    // Inputs (similar to create)
    $po_id = intval($_POST['po_id']);
    $vendor_id = intval($_POST['vendor_id']);
    $dn_date = $_POST['dn_date'];
    $remarks = $_POST['dn_remarks'];
    $items = $_POST['items'] ?? [];

    if(!$dn_id) {
        header("Location: ../../debit_note?error=Invalid Update Request");
        exit;
    }

    $conn->begin_transaction();
    try {
        // 1. REVERT OLD IMPACT
        // Fetch old items to revert stock and calculate old total amount
        $oldItemsSql = "SELECT dni.*, poi.rate 
                        FROM debit_note_items dni 
                        LEFT JOIN purchase_order_items poi ON dni.po_item_id = poi.id
                        WHERE dni.debit_note_id = ?";
        $oldStmt = $conn->prepare($oldItemsSql);
        $oldStmt->bind_param("i", $dn_id);
        $oldStmt->execute();
        $oldRes = $oldStmt->get_result();
        
        $old_total_amount = 0;
        
        while($old = $oldRes->fetch_assoc()){
             $qty = floatval($old['return_qty']);
             $item_id = $old['item_id'];
             $rate = floatval($old['rate']);
             
             // Revert Stock (Add back the returned quantity to stock)
             // Because when we Created DN, we did stock = stock - qty.
             // So Revert: stock = stock + qty.
             if($qty > 0){
                 $stkSql = "UPDATE items_listing SET current_stock = COALESCE(current_stock, 0) + ? WHERE item_id = ?";
                 $stkStmt = $conn->prepare($stkSql);
                 $stkStmt->bind_param("di", $qty, $item_id);
                 if(!$stkStmt->execute()) throw new Exception("Stock Revert Failed");
                 $stkStmt->close();
             }
             
             $old_total_amount += ($qty * $rate);
        }
        $oldStmt->close();

        // Revert Vendor Balance (Add back the amount we deducted)
        if($old_total_amount > 0){
             $vSql = "UPDATE vendors_listing SET current_balance_due = current_balance_due + ? WHERE vendor_id = ?";
             $vStmt = $conn->prepare($vSql);
             $vStmt->bind_param("di", $old_total_amount, $vendor_id);
             if(!$vStmt->execute()) throw new Exception("Vendor Balance Revert Failed");
             $vStmt->close();
        }

        // 2. UPDATE HEADER
        $updSql = "UPDATE debit_notes SET debit_note_date = ?, remarks = ? WHERE debit_note_id = ? AND organization_id = ?";
        $updStmt = $conn->prepare($updSql);
        $updStmt->bind_param("ssii", $dn_date, $remarks, $dn_id, $organization_id);
        if(!$updStmt->execute()) throw new Exception("Header Update Failed");
        $updStmt->close();


        // 3. DELETE OLD ITEMS
        $delSql = "DELETE FROM debit_note_items WHERE debit_note_id = ?";
        $delStmt = $conn->prepare($delSql);
        $delStmt->bind_param("i", $dn_id);
        if(!$delStmt->execute()) throw new Exception("Delete Old Items Failed");
        $delStmt->close();


        // 4. INSERT NEW ITEMS & APPLY NEW IMPACT
        $new_total_amount = 0;
        
        if (!empty($items)) {
            $itemSql = "INSERT INTO debit_note_items (debit_note_id, po_item_id, item_id, return_qty, return_reason, remarks) 
                        VALUES (?, ?, ?, ?, ?, ?)";
            $itemStmt = $conn->prepare($itemSql);

            foreach ($items as $item) {
                $po_item_id = intval($item['po_item_id']);
                $item_id = intval($item['item_id']);
                $return_qty = floatval($item['return_qty']);
                $reason = $item['reason'];
                $line_remarks = $item['remarks'];

                if ($return_qty > 0) {
                     // Get Rate
                     $rateSql = "SELECT rate FROM purchase_order_items WHERE id = ?";
                     $rateStmt = $conn->prepare($rateSql);
                     $rateStmt->bind_param("i", $po_item_id);
                     $rateStmt->execute();
                     $rRes = $rateStmt->get_result();
                     $rate = ($rRes->num_rows > 0) ? floatval($rRes->fetch_assoc()['rate']) : 0;
                     $rateStmt->close();
                     
                     $new_total_amount += ($return_qty * $rate);

                     // Insert Item
                     $itemStmt->bind_param("iiidss", $dn_id, $po_item_id, $item_id, $return_qty, $reason, $line_remarks);
                     if(!$itemStmt->execute()) throw new Exception("Item Insert Failed");
                     
                     // Decrease Stock (New)
                     $stkSql = "UPDATE items_listing SET current_stock = COALESCE(current_stock, 0) - ? WHERE item_id = ?";
                     $stkStmt = $conn->prepare($stkSql);
                     $stkStmt->bind_param("di", $return_qty, $item_id);
                     if(!$stkStmt->execute()) throw new Exception("Stock Update Failed");
                     $stkStmt->close();
                }
            }
            $itemStmt->close();
        }

        // Apply New Vendor Balance (Deduct new amount)
        if($new_total_amount > 0){
             $vSql = "UPDATE vendors_listing SET current_balance_due = current_balance_due - ? WHERE vendor_id = ?";
             $vStmt = $conn->prepare($vSql);
             $vStmt->bind_param("di", $new_total_amount, $vendor_id);
             if(!$vStmt->execute()) throw new Exception("Vendor Balance Update Failed");
             $vStmt->close();
        }

        $conn->commit();
        header("Location: ../../debit_note?success=Debit Note Updated Successfully&id=" . $dn_id);
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
