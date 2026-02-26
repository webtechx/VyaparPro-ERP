<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// --- Filters ---
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : ''; // Legacy/Text search
$item_id_filter = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
$sku_filter = isset($_GET['sku']) ? trim($_GET['sku']) : '';

// 1. Fetch current stock for all items
$sqlItems = "SELECT 
                i.item_id,
                i.item_name,
                i.brand,
                i.stock_keeping_unit as sku,
                i.current_stock,
                i.unit_id,
                u.unit_name,
                i.mrp,
                IFNULL((
                    SELECT poi.rate
                    FROM purchase_order_items poi
                    JOIN purchase_orders po ON poi.purchase_order_id = po.purchase_orders_id
                    WHERE poi.item_id = i.item_id
                      AND po.organization_id = i.organization_id
                    ORDER BY po.order_date DESC, poi.id DESC
                    LIMIT 1
                ), 0) AS purchase_price
             FROM items_listing i
             LEFT JOIN units_listing u ON i.unit_id = u.unit_id
             WHERE i.organization_id = ? 
             AND i.current_stock > 0"; // Only interested in items with stock

$params = [$organization_id];
$types = "i";

if ($item_id_filter > 0) {
    $sqlItems .= " AND i.item_id = ?";
    $params[] = $item_id_filter;
    $types .= "i";
}

if (!empty($sku_filter)) {
    $sqlItems .= " AND i.stock_keeping_unit LIKE ?";
    $skuLike = "%$sku_filter%";
    $params[] = $skuLike;
    $types .= "s";
}

if (!empty($search_query)) {
    $sqlItems .= " AND (i.item_name LIKE ? OR i.stock_keeping_unit LIKE ?)";
    $like = "%$search_query%";
    // ... logic for generic search if used alongside others
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

$sqlItems .= " ORDER BY i.item_name ASC";

$stmt = $conn->prepare($sqlItems);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultItems = $stmt->get_result();

$inventoryData = [];
$total_inventory_value = 0;
// Buckets (Value)
$total_0_30 = 0;
$total_31_60 = 0;
$total_61_90 = 0;
$total_90_plus = 0;

$today = new DateTime();

// Prepare statement for fetching GRNs per item (FIFO Allocation)
// We want latest GRNs first to allocate stock.
$sqlGrn = "SELECT 
                gni.received_qty, 
                gn.grn_date 
           FROM goods_received_note_items gni
           JOIN goods_received_notes gn ON gni.grn_id = gn.grn_id
           WHERE gni.item_id = ? 
           AND gn.organization_id = ?
           ORDER BY gn.grn_date DESC"; // LIFO for 'Age' calculation means standard FIFO inventory flow (Last In, First Out?? No. FIFO means First In First Sold. So Last In is what remains.)
           // So we match current stock against the LATEST GRNs.

$stmtGrn = $conn->prepare($sqlGrn);

while ($item = $resultItems->fetch_assoc()) {
    $itemId = $item['item_id'];
    $currentStock = floatval($item['current_stock']);
    $price = floatval($item['purchase_price']); // Using latest purchase price from purchase_order_items
    $stockValue = $currentStock * $price;
    
    // Buckets for this item
    $b0_30 = 0;
    $b31_60 = 0;
    $b61_90 = 0;
    $b90_plus = 0;
    
    // Fetch GRNs
    $stmtGrn->bind_param("ii", $itemId, $organization_id);
    $stmtGrn->execute();
    $resGrn = $stmtGrn->get_result();
    
    $remainingStockToAllocate = $currentStock;
    
    while ($remainingStockToAllocate > 0 && $grn = $resGrn->fetch_assoc()) {
        $grnQty = floatval($grn['received_qty']);
        $grnDate = new DateTime($grn['grn_date']);
        $interval = $today->diff($grnDate);
        $daysOld = $interval->days;
        
        // How much of this GRN is still in stock?
        // Allocation: Take min(remaining, grnQty)
        $allocated = min($remainingStockToAllocate, $grnQty);
        
        $value = $allocated * $price;
        
        // Bucket
        if ($daysOld <= 30) {
            $b0_30 += $value;
        } elseif ($daysOld <= 60) {
            $b31_60 += $value;
        } elseif ($daysOld <= 90) {
            $b61_90 += $value;
        } else {
            $b90_plus += $value;
        }
        
        $remainingStockToAllocate -= $allocated;
    }
    
    // If stock remains (e.g. Opening Stock or GRNs missing), put in > 90 days
    if ($remainingStockToAllocate > 0) {
        $b90_plus += ($remainingStockToAllocate * $price);
    }
    
    // Add to item row
    $item['total_value'] = $stockValue;
    $item['age_0_30'] = $b0_30;
    $item['age_31_60'] = $b31_60;
    $item['age_61_90'] = $b61_90;
    $item['age_90_plus'] = $b90_plus;
    
    $inventoryData[] = $item;
    
    // Totals
    $total_inventory_value += $stockValue;
    $total_0_30 += $b0_30;
    $total_31_60 += $b31_60;
    $total_61_90 += $b61_90;
    $total_90_plus += $b90_plus;
}

$stmtGrn->close();
$stmt->close();
?>
