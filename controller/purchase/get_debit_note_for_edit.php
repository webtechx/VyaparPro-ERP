<?php
require_once '../../config/conn.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
    exit;
}

$dn_id = intval($_GET['id']);

// 1. Fetch Header
$sql = "SELECT dn.*, po.po_number, po.order_date, po.reference_no, v.display_name as vendor_name 
        FROM debit_notes dn
        LEFT JOIN purchase_orders po ON dn.po_id = po.purchase_orders_id
        LEFT JOIN vendors_listing v ON dn.vendor_id = v.vendor_id
        WHERE dn.debit_note_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $dn_id);
$stmt->execute();
$dn = $stmt->get_result()->fetch_assoc();

if (!$dn) {
    echo json_encode(['status' => 'error', 'message' => 'Debit Note Not Found']);
    exit;
}

$po_id = $dn['po_id'];

// 2. Fetch Items (Need to reconstruct the full PO item list to allow adding/removing items from return?)
// Or just fetch the items that were in the DN?
// Users typically want to see all PO items to potentially return items they didn't return before.
// So we should fetch ALL PO items, and merge with DN items.

// Fetch All PO Items
$itemSql = "SELECT poi.*, il.item_name, u.unit_name, h.hsn_code
            FROM purchase_order_items poi
            LEFT JOIN items_listing il ON poi.item_id = il.item_id
            LEFT JOIN units_listing u ON il.unit_id = u.unit_id
            LEFT JOIN hsn_listing h ON il.hsn_id = h.hsn_id
            WHERE poi.purchase_order_id = ?";
$itemStmt = $conn->prepare($itemSql);
$itemStmt->bind_param("i", $po_id);
$itemStmt->execute();
$poItems = $itemStmt->get_result();

$items = [];

while($row = $poItems->fetch_assoc()){
    $po_item_id = $row['id']; // assuming 'id' is PK of purchase_order_items based on previous context
    
    // Get stats
    
    // Total Received
    $recvSql = "SELECT SUM(received_qty) as total FROM goods_received_note_items WHERE po_item_id = ?";
    $recvStmt = $conn->prepare($recvSql);
    $recvStmt->bind_param("i", $po_item_id);
    $recvStmt->execute();
    $received = floatval($recvStmt->get_result()->fetch_assoc()['total'] ?? 0);
    $recvStmt->close();

    // Total Returned (All DNs)
    $retSql = "SELECT SUM(return_qty) as total FROM debit_note_items WHERE po_item_id = ?";
    $retStmt = $conn->prepare($retSql);
    $retStmt->bind_param("i", $po_item_id);
    $retStmt->execute();
    $returned_total = floatval($retStmt->get_result()->fetch_assoc()['total'] ?? 0);
    $retStmt->close();

    // Qty Returned in THIS DN
    $thisDnSql = "SELECT * FROM debit_note_items WHERE debit_note_id = ? AND po_item_id = ?";
    $thisDnStmt = $conn->prepare($thisDnSql);
    $thisDnStmt->bind_param("ii", $dn_id, $po_item_id);
    $thisDnStmt->execute();
    $thisDnRes = $thisDnStmt->get_result();
    $thisDnItem = $thisDnRes->fetch_assoc();
    $thisDnStmt->close();

    $current_return_qty = $thisDnItem ? floatval($thisDnItem['return_qty']) : 0;
    $current_reason = $thisDnItem ? $thisDnItem['return_reason'] : '';
    $current_remarks = $thisDnItem ? $thisDnItem['remarks'] : '';
    
    // Returned in OTHER DNs = Total Returned - Current Return
    $returned_others = $returned_total - $current_return_qty;

    // Available to Return = Received - Returned Others
    $available_max = max(0, $received - $returned_others);

    $row['received_qty'] = $received;
    $row['returned_qty_others'] = $returned_others;
    $row['available_qty'] = $available_max; // This is the MAX for the input
    $row['current_return_qty'] = $current_return_qty; // This is the VALUE for the input
    $row['current_reason'] = $current_reason;
    $row['current_remarks'] = $current_remarks;
    $row['hsn_code'] = $row['hsn_code'] ?: '-';

    $items[] = $row;
}

echo json_encode([
    'status' => 'success',
    'header' => $dn,
    'items' => $items
]);
?>
