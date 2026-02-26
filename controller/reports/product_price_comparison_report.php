<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// Filters
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$brand_filter = isset($_GET['brand']) ? trim($_GET['brand']) : '';

// Base Query
// We fetch:
// 1. Item Details
// 2. Current Selling Price (from Master)
// 3. Last Purchase Price (most recent Approved PO)
// 4. Average Purchase Price (all Approved POs)
// 5. Total Qty Purchased (to give weight)

$sql = "SELECT 
            i.item_id,
            i.item_name,
            i.brand,
            i.stock_keeping_unit,
            i.selling_price as current_selling_price,
            i.mrp,
            AVG(poi.rate) as avg_purchase_price,
            MAX(poi.rate) as max_purchase_price,
            MIN(poi.rate) as min_purchase_price,
            COUNT(poi.id) as purchase_count,
            (SELECT rate FROM purchase_order_items poi2 
             JOIN purchase_orders po2 ON poi2.purchase_order_id = po2.purchase_orders_id 
             WHERE poi2.item_id = i.item_id AND po2.status = 'approved' AND po2.organization_id = i.organization_id
             ORDER BY po2.order_date DESC, po2.purchase_orders_id DESC LIMIT 1) as last_purchase_price
        FROM items_listing i
        LEFT JOIN purchase_order_items poi ON i.item_id = poi.item_id
        LEFT JOIN purchase_orders po ON poi.purchase_order_id = po.purchase_orders_id AND po.status = 'approved'
        WHERE i.organization_id = ?";

$params = [$organization_id];
$types = "i";

$sku_filter = isset($_GET['sku']) ? trim($_GET['sku']) : '';

// Apply Filters

if (isset($_GET['search_item_id']) && intval($_GET['search_item_id']) > 0) {
    $search_item_id = intval($_GET['search_item_id']);
    $sql .= " AND i.item_id = ?";
    $params[] = $search_item_id;
    $types .= "i";
} elseif (!empty($search_query)) {
    $sql .= " AND (i.item_name LIKE ? OR i.stock_keeping_unit LIKE ?)";
    $like = "%$search_query%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

if (!empty($sku_filter)) {
    $sql .= " AND i.stock_keeping_unit LIKE ?";
    $skuLike = "%$sku_filter%";
    $params[] = $skuLike;
    $types .= "s";
}

if (!empty($brand_filter)) {
    $sql .= " AND i.brand = ?";
    $params[] = $brand_filter;
    $types .= "s";
}

$sql .= " GROUP BY i.item_id ORDER BY i.item_name ASC";

// Execute
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
$stmt->close();

// Fetch Brands for Filter
$brands = [];
$bSql = "SELECT DISTINCT brand FROM items_listing WHERE organization_id = ? AND brand IS NOT NULL AND brand != '' ORDER BY brand ASC";
$bStmt = $conn->prepare($bSql);
$bStmt->bind_param("i", $organization_id);
$bStmt->execute();
$bRes = $bStmt->get_result();
while ($b = $bRes->fetch_assoc()) $brands[] = $b['brand'];
$bStmt->close();
?>
