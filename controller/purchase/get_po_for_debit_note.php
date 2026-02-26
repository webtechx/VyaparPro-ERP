<?php
require_once '../../config/conn.php';

header('Content-Type: application/json');

if (isset($_GET['po_number'])) {
    $po_number = $conn->real_escape_string($_GET['po_number']);
    $response = [];

    // Header
    $sql = "SELECT po.*, v.display_name as vendor_name, v.vendor_id 
            FROM purchase_orders po
            LEFT JOIN vendors_listing v ON po.vendor_id = v.vendor_id
            WHERE po.po_number = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $po_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $po = $result->fetch_assoc();
        
        // Status checks - Typically we can only return if we have received something?
        // So status should be 'partially_received' or 'received' or 'confirmed' (if GRN exists but PO status didn't update properly?)
        // Let's allow fetching any PO, but item logic will dictate availability.
        
        $response['po'] = $po;
        $po_id = $po['purchase_orders_id'];

        // Items
        $itemSql = "SELECT poi.*, il.item_name, u.unit_name, h.hsn_code
                    FROM purchase_order_items poi
                    LEFT JOIN items_listing il ON poi.item_id = il.item_id
                    LEFT JOIN units_listing u ON il.unit_id = u.unit_id
                    LEFT JOIN hsn_listing h ON il.hsn_id = h.hsn_id
                    WHERE poi.purchase_order_id = ?";
                    
        $itemStmt = $conn->prepare($itemSql);
        $itemStmt->bind_param("i", $po_id);
        $itemStmt->execute();
        $itemRes = $itemStmt->get_result();
        $items = [];
        
        while($row = $itemRes->fetch_assoc()){
            $pk = $row['id'];
            $ordered = floatval($row['quantity']);
            
            // 1. Get Total Received
            $recvSql = "SELECT SUM(received_qty) as total FROM goods_received_note_items WHERE po_item_id = ?";
            $recvStmt = $conn->prepare($recvSql);
            $recvStmt->bind_param("i", $pk);
            $recvStmt->execute();
            $recvRow = $recvStmt->get_result()->fetch_assoc();
            $total_received = floatval($recvRow['total'] ?? 0);
            $recvStmt->close();

            // 2. Get Total Returned (Already Debited)
            $retSql = "SELECT SUM(return_qty) as total FROM debit_note_items WHERE po_item_id = ?";
            $retStmt = $conn->prepare($retSql);
            $retStmt->bind_param("i", $pk);
            $retStmt->execute();
            $retRow = $retStmt->get_result()->fetch_assoc();
            $total_returned = floatval($retRow['total'] ?? 0);
            $retStmt->close();

            // 3. Calculate Available to Return
            // You can only return what you actually received, minus what you already returned.
            $available_to_return = max(0, $total_received - $total_returned);

            $row['received_qty'] = $total_received;
            $row['returned_qty'] = $total_returned;
            $row['available_qty'] = $available_to_return;
            $row['hsn_code'] = $row['hsn_code'] ?? '-';
            
            // Only add if there is something received? 
            // Or include all items to show status? Including all is safer.
            $items[] = $row;
        }
        $response['items'] = $items;
        $response['status'] = 'success';

    } else {
        $response['status'] = 'error';
        $response['message'] = "Purchase Order not found.";
    }

    echo json_encode($response);
} else {
    echo json_encode(['status' => 'error', 'message' => 'PO Number is required']);
}
?>
