<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// --- Filters ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');
$item_id     = isset($_GET['item_id'])     ? intval($_GET['item_id'])     : 0;

// --- Query ---
// Aggregate purchases per item
$sql = "SELECT 
            poi.item_id,
            poi.item_name,
            hl.hsn_code,
            SUM(poi.quantity) as total_qty,
            SUM(
                (SELECT COALESCE(SUM(gi.received_qty), 0) 
                 FROM goods_received_note_items gi 
                 WHERE gi.po_item_id = poi.id)
            ) as total_received,
            SUM(poi.amount) as total_purchase,
            AVG(poi.rate) as avg_rate
        FROM purchase_order_items poi
        JOIN purchase_orders po ON poi.purchase_order_id = po.purchase_orders_id
        LEFT JOIN items_listing il ON poi.item_id = il.item_id
        LEFT JOIN hsn_listing hl ON il.hsn_id = hl.hsn_id
        WHERE po.organization_id = ? 
        AND po.status != 'cancelled'";

$params = [$organization_id];
$types = "i";

if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND po.order_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

if ($item_id > 0) {
    $sql .= " AND poi.item_id = ?";
    $params[] = $item_id;
    $types .= "i";
}

$sql .= " GROUP BY poi.item_id ORDER BY total_purchase DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if(!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $reportData = [];
    $total_qty_purchased = 0;
    $total_qty_received = 0;
    $total_purchase_all = 0;

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $reportData[] = $row;
            $total_qty_purchased += $row['total_qty'];
            $total_qty_received += $row['total_received'];
            $total_purchase_all += $row['total_purchase'];
        }
    }
    $stmt->close();
} else {
    $reportData = [];
    $total_qty_purchased = 0;
    $total_qty_received = 0;
    $total_purchase_all = 0;
}
?>
