<?php
require_once '../../config/conn.php';

if(isset($_GET['id'])){
    // Fetch Single GRN Details
    $grn_id = intval($_GET['id']);
    
    $response = [];
    
    // Header
    $sql = "SELECT grn.*, po.po_number, v.display_name as vendor_name 
            FROM goods_received_notes grn
            LEFT JOIN purchase_orders po ON grn.po_id = po.purchase_orders_id
            LEFT JOIN vendors_listing v ON grn.vendor_id = v.vendor_id
            WHERE grn.grn_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $grn_id);
    $stmt->execute();
    $headRes = $stmt->get_result();
    
    if($headRes->num_rows > 0){
        $response['header'] = $headRes->fetch_assoc();
        
        // Items
        $iSql = "SELECT gi.*, il.item_name, u.unit_name, poi.rate as po_rate, h.hsn_code
                 FROM goods_received_note_items gi
                 LEFT JOIN items_listing il ON gi.item_id = il.item_id
                 LEFT JOIN units_listing u ON il.unit_id = u.unit_id
                 LEFT JOIN hsn_listing h ON il.hsn_id = h.hsn_id
                 LEFT JOIN purchase_order_items poi ON gi.po_item_id = poi.id
                 WHERE gi.grn_id = ?";
        $iStmt = $conn->prepare($iSql);
        $iStmt->bind_param("i", $grn_id);
        $iStmt->execute();
        $iRes = $iStmt->get_result();
        
        $items = [];
        while($row = $iRes->fetch_assoc()){
            $items[] = $row;
        }
        $response['items'] = $items;
        $response['status'] = 'success';
    } else {
        $response['status'] = 'error';
        $response['message'] = 'GRN not found';
    }
    
    echo json_encode($response);
    exit;
}
?>
