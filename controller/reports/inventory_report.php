<?php
require_once __DIR__ . '/../../config/auth_guard.php';

// Organization ID from session
$organization_id = $_SESSION['organization_id'];

// Default filters
$brand_filter = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Base Query
$sql = "SELECT 
            i.item_id,
            i.item_name,
            i.brand,
            i.stock_keeping_unit,
            i.current_stock,
            i.unit_id,
            u.unit_name,
            i.mrp,
            i.create_at,
            i.update_at,
            IFNULL((
                SELECT poi.rate
                FROM purchase_order_items poi
                JOIN purchase_orders po ON poi.purchase_order_id = po.purchase_orders_id
                WHERE poi.item_id = i.item_id
                  AND po.organization_id = i.organization_id
                ORDER BY po.order_date DESC, poi.id DESC
                LIMIT 1
            ), 0) AS purchase_price,
            (i.current_stock * IFNULL((
                SELECT poi.rate
                FROM purchase_order_items poi
                JOIN purchase_orders po ON poi.purchase_order_id = po.purchase_orders_id
                WHERE poi.item_id = i.item_id
                  AND po.organization_id = i.organization_id
                ORDER BY po.order_date DESC, poi.id DESC
                LIMIT 1
            ), 0)) as total_value
        FROM items_listing i
        LEFT JOIN units_listing u ON i.unit_id = u.unit_id
        WHERE i.organization_id = ?";

$params = [$organization_id];
$types = "i";

// Apply Filters
if (!empty($brand_filter)) {
    $sql .= " AND i.brand = ?";
    $params[] = $brand_filter;
    $types .= "s";
}

if (!empty($search_query)) {
    $sql .= " AND (i.item_name LIKE ? OR i.stock_keeping_unit LIKE ?)";
    $likeQuery = "%" . $search_query . "%";
    $params[] = $likeQuery;
    $params[] = $likeQuery;
    $types .= "ss";
}

// Status Filter Logic
if (!empty($status_filter)) {
    if ($status_filter === 'in_stock') {
        $sql .= " AND i.current_stock > 0";
    } elseif ($status_filter === 'out_of_stock') {
        $sql .= " AND i.current_stock <= 0";
    } elseif ($status_filter === 'low_stock') {
        // Assuming low stock is < 10, typically this is configurable but we'll use a static threshold or just leave it for now if not defined in DB
        $sql .= " AND i.current_stock > 0 AND i.current_stock < 10"; 
    }
}

$sql .= " ORDER BY i.item_name ASC";

// Execute Query
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$inventory_items = [];
$total_items = 0;
$total_stock_qty = 0;
$total_inventory_value = 0;
$start_items = 0;

while ($row = $result->fetch_assoc()) {
    $inventory_items[] = $row;
    $total_items++;
    $total_stock_qty += (float)$row['current_stock'];
    $total_inventory_value += (float)$row['total_value'];
    
    // Starred/featured logic can be added here if needed
}

// Fetch Brands for Filter
$brands_sql = "SELECT DISTINCT brand FROM items_listing WHERE organization_id = ? AND brand IS NOT NULL AND brand != '' ORDER BY brand ASC";
$brands_stmt = $conn->prepare($brands_sql);
$brands_stmt->bind_param("i", $organization_id);
$brands_stmt->execute();
$brands_result = $brands_stmt->get_result();
$brands = [];
while($b = $brands_result->fetch_assoc()){
    $brands[] = $b['brand'];
}

// Calculate Low Stock Count (separate query for summary card if needed, or just iterate)
// Let's do a quick separate count for summary cards to be independent of filters?
// Actually, usually summary cards reflect the *filtered* view or the *total* view. 
// In sales_report, headers showed "Total Sales (Selected Period)".
// So we will stick to the calculated sums from the loop which respect filters.

?>
