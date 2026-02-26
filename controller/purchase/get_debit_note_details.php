<?php
require_once '../../config/conn.php';

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Header
    $sql = "SELECT dn.*, po.po_number, v.display_name as vendor_name 
            FROM debit_notes dn
            LEFT JOIN purchase_orders po ON dn.po_id = po.purchase_orders_id
            LEFT JOIN vendors_listing v ON dn.vendor_id = v.vendor_id
            WHERE dn.debit_note_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $header = $res->fetch_assoc();
        $header['debit_note_date'] = date('d M Y', strtotime($header['debit_note_date']));
        
        // Items
        $iSql = "SELECT dni.*, il.item_name, u.unit_name, po_item.rate as po_rate, po_item.quantity as ordered_qty
                 FROM debit_note_items dni
                 LEFT JOIN items_listing il ON dni.item_id = il.item_id
                 LEFT JOIN units_listing u ON il.unit_id = u.unit_id
                 LEFT JOIN purchase_order_items po_item ON dni.po_item_id = po_item.id
                 WHERE dni.debit_note_id = ?";
                 
        $iStmt = $conn->prepare($iSql);
        if(!$iStmt){
             echo json_encode(['status' => 'error', 'message' => 'Items SQL Prepare Failed: ' . $conn->error]);
             exit;
        }
        $iStmt->bind_param("i", $id);
        if(!$iStmt->execute()){
             echo json_encode(['status' => 'error', 'message' => 'Items Query Failed: ' . $iStmt->error]);
             exit;
        }
        $iRes = $iStmt->get_result();
        
        $items = [];
        while($row = $iRes->fetch_assoc()){
            $items[] = $row;
        }

        echo json_encode(['status' => 'success', 'header' => $header, 'items' => $items]);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Debit Note not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'ID required']);
}
?>
