<?php
require_once '../../config/auth_guard.php';

if (isset($_POST['save_grn'])) { 
    $organization_id = $_SESSION['organization_id'];

    // Inputs
    $po_id = intval($_POST['po_id']);
    $vendor_id = intval($_POST['vendor_id']);
    $grn_number = $_POST['grn_number']; // Auto generated or manual
    $grn_date = $_POST['grn_date'];
    $challan_no = $_POST['challan_no'];
    $grn_remarks = $_POST['grn_remarks'];

    // Server-side Date Validation
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Check Permission for Backdating (Module Slug: grn_backdate)
    $allow_backdate = false;
    // Check if user has 'can_view' permission for 'grn_backdate'
    $permQ = $conn->query("SELECT can_view FROM employee_permissions WHERE employee_id = $user_id AND module_slug = 'grn_backdate'");
    if($permQ && $permQ->num_rows > 0){
         $pRow = $permQ->fetch_assoc();
         if($pRow['can_view'] == 1) {
             $allow_backdate = true;
         }
    }
    
    // Allow SUPER ADMIN (Check by Role Name)
    $saQ = $conn->query("SELECT r.role_name FROM employees e JOIN roles_listing r ON e.role_id = r.role_id WHERE e.employee_id = $user_id");
    if($saQ && $saQ->num_rows > 0){
        $rName = $saQ->fetch_assoc()['role_name'];
        if(strcasecmp($rName, 'SUPER ADMIN') === 0) { // Case-insensitive check
            $allow_backdate = true;
        }
    }
    
    // If backdating is NOT allowed, ensure date is NOT in the past (Future dates are OK, Today is OK)
    if (!$allow_backdate && $grn_date < date('Y-m-d')) {
        header("Location: ../../goods_received_notes?error=" . urlencode("Permission Denied: You do not have permission to backdate GRNs (Select Today or Future)."));
        exit;
    }
    
    // Items
    $items = $_POST['items'] ?? [];

    $conn->begin_transaction();
    try {
        // 1. Insert GRN Header
        // Assuming table goods_received_notes exists
        $sql = "INSERT INTO goods_received_notes (organization_id, po_id, vendor_id, grn_number, grn_date, challan_no, remarks, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        if(!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        
        $stmt->bind_param("iiissss", $organization_id, $po_id, $vendor_id, $grn_number, $grn_date, $challan_no, $grn_remarks);
        
        if(!$stmt->execute()){
            throw new Exception("GRN Header Insert Failed: " . $stmt->error);
        }
        $grn_id = $conn->insert_id;
        $stmt->close();

        // 2. Insert GRN Items
        if (!empty($items)) {
            $itemSql = "INSERT INTO goods_received_note_items (grn_id, po_item_id, item_id, ordered_qty, received_qty, condition_status, remarks) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
            $itemStmt = $conn->prepare($itemSql);
            if(!$itemStmt) throw new Exception("Item Prepare failed: " . $conn->error);

            foreach ($items as $item) {
                // $item key might be the index loop
                $po_item_id = intval($item['po_item_id']);
                $item_id = intval($item['item_id']);
                $ordered_qty = floatval($item['ordered_qty']);
                $received_qty = floatval($item['received_qty']);
                $condition = $item['condition'];
                $line_remarks = $item['remarks'];

                $itemStmt->bind_param("iiiddss", $grn_id, $po_item_id, $item_id, $ordered_qty, $received_qty, $condition, $line_remarks);
                if(!$itemStmt->execute()){
                     throw new Exception("Item Insert Failed: " . $itemStmt->error);
                }

                // Update Stock Logic
                // We assume 'items_listing' has a 'current_stock' column.
                if ($received_qty > 0) {
                     $stkSql = "UPDATE items_listing SET current_stock = COALESCE(current_stock, 0) + ? WHERE item_id = ?";
                     $stkStmt = $conn->prepare($stkSql);
                     if(!$stkStmt) {
                         throw new Exception("Stock Update Prepare Failed: " . $conn->error);
                     }
                     $stkStmt->bind_param("di", $received_qty, $item_id);
                     if(!$stkStmt->execute()){
                         throw new Exception("Stock Update Execute Failed: " . $stkStmt->error);
                     }
                     $stkStmt->close();
                }
            }
            $itemStmt->close();
        }

        // 3. Update Vendor Balance Due (based on actual GRN received value)
        // Calculate total value of THIS GRN: SUM(received_qty * rate)
        $grnValueSql = "SELECT COALESCE(SUM(gi.received_qty * poi.rate), 0) as grn_total
                        FROM goods_received_note_items gi
                        LEFT JOIN purchase_order_items poi ON gi.po_item_id = poi.id
                        WHERE gi.grn_id = ?";
        $grnValueStmt = $conn->prepare($grnValueSql);
        if (!$grnValueStmt) throw new Exception("GRN Value Prepare Failed: " . $conn->error);
        $grnValueStmt->bind_param("i", $grn_id);
        $grnValueStmt->execute();
        $grnValueRow = $grnValueStmt->get_result()->fetch_assoc();
        $grn_total_value = floatval($grnValueRow['grn_total'] ?? 0);
        $grnValueStmt->close();

        // Add GRN value to vendor's current_balance_due
        if ($grn_total_value > 0) {
            $updVendorSql = "UPDATE vendors_listing SET current_balance_due = current_balance_due + ? WHERE vendor_id = ?";
            $updVendorStmt = $conn->prepare($updVendorSql);
            if (!$updVendorStmt) throw new Exception("Vendor Balance Update Prepare Failed: " . $conn->error);
            $updVendorStmt->bind_param("di", $grn_total_value, $vendor_id);
            if (!$updVendorStmt->execute()) {
                throw new Exception("Vendor Balance Update Failed: " . $updVendorStmt->error);
            }
            $updVendorStmt->close();
        }

        // 4. Update PO Status
        // Check if all items are fully received
        $checkSql = "SELECT * FROM purchase_order_items WHERE purchase_order_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $po_id);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        
        $all_items_fully_received = true;
        $at_least_one_received = false;
        $total_items = $checkRes->num_rows;

        while($row = $checkRes->fetch_assoc()){
            // Determine PK (handle common variations)
            $pk = $row['purchase_order_items_id'] ?? $row['id'] ?? null;
            if(!$pk) continue; 

            $ordered = floatval($row['quantity']);
            
            // Get total received for this item from DB (including current GRN)
            $sumSql = "SELECT SUM(received_qty) as total FROM goods_received_note_items WHERE po_item_id = ?";
            $sumStmt = $conn->prepare($sumSql);
            $sumStmt->bind_param("i", $pk);
            $sumStmt->execute();
            $sumRow = $sumStmt->get_result()->fetch_assoc();
            $received = floatval($sumRow['total'] ?? 0);
            $sumStmt->close();
            
            // Comparison
            if($received < $ordered - 0.0001){ // Float tolerance
                $all_items_fully_received = false;
            }
            if($received > 0.0001){
                $at_least_one_received = true;
            }
        }
        $checkStmt->close();

        // Determine new status
        $new_status = 'confirmed'; // default
        if($total_items > 0 && $all_items_fully_received){
            $new_status = 'received';
        } elseif ($at_least_one_received) {
            $new_status = 'partially_received';
        }

        // Apply Update
        // Note: Only update if status implies progress (don't revert a 'closed' status if logic is complex, 
        // strictly speaking we move forward from confirmed -> partial -> received)
        $updPo = "UPDATE purchase_orders SET status = ? WHERE purchase_orders_id = ?";
        $updStmt = $conn->prepare($updPo);
        $updStmt->bind_param("si", $new_status, $po_id);
        $updStmt->execute();
        $updStmt->close();

        $conn->commit();
        header("Location: ../../goods_received_notes?success=GRN Created Successfully. PO Status updated to ".ucfirst(str_replace('_',' ',$new_status))."&grn_id=" . $grn_id);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../../goods_received_notes?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: ../../goods_received_notes");
    exit;
}
?>
