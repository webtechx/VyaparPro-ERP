<?php
require_once '../../config/conn.php';

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $response = [];

    // Header
    $sql = "SELECT po.*, v.display_name as vendor_name 
            FROM purchase_orders po
            LEFT JOIN vendors_listing v ON po.vendor_id = v.vendor_id
            WHERE po.purchase_orders_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response['po'] = $result->fetch_assoc();

        // Items
        $itemSql = "SELECT * FROM purchase_order_items WHERE purchase_order_id = ?";
        $itemStmt = $conn->prepare($itemSql);
        $itemStmt->bind_param("i", $id);
        $itemStmt->execute();
        $itemRes = $itemStmt->get_result();
        $items = [];
        while($row = $itemRes->fetch_assoc()){
            $items[] = $row;
        }
        $response['items'] = $items;

        // Files
        $fileSql = "SELECT * FROM purchase_order_files WHERE purchase_order_id = ?";
        $fileStmt = $conn->prepare($fileSql);
        $fileStmt->bind_param("i", $id);
        $fileStmt->execute();
        $fileRes = $fileStmt->get_result();
        $files = [];
        while($row = $fileRes->fetch_assoc()){
            $files[] = $row;
        }
        $response['files'] = $files;

    } else {
        $response['error'] = "Purchase Order not found";
    }

    echo json_encode($response);
}
?>
