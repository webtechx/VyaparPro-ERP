<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// --- Filters ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// --- Base Query ---
$sql = "SELECT 
            po.purchase_orders_id as purchase_order_id,
            po.po_number,
            po.order_date,
            po.total_amount,
            po.status,
            v.display_name as vendor_name,
            v.company_name,
            v.vendor_code,
            v.mobile
        FROM purchase_orders po
        LEFT JOIN vendors_listing v ON po.vendor_id = v.vendor_id
        WHERE po.organization_id = ?";

$params = [$organization_id];
$types = "i";

// Apply Filters
if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND po.order_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

if ($vendor_id > 0) {
    $sql .= " AND po.vendor_id = ?";
    $params[] = $vendor_id;
    $types .= "i";
}

if (!empty($status)) {
    $sql .= " AND po.status = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($search_query)) {
    $sql .= " AND (po.po_number LIKE ? OR v.display_name LIKE ? OR v.company_name LIKE ?)";
    $like = "%$search_query%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

$sql .= " ORDER BY po.order_date DESC, po.purchase_orders_id DESC";

// Execute
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$purchase_orders = [];
$total_purchases = 0;
$count_orders = 0;

while ($row = $result->fetch_assoc()) {
    $purchase_orders[] = $row;
    $total_purchases += floatval($row['total_amount']);
    $count_orders++;
}
$stmt->close();

// --- Prefill Filter Data (Vendor) ---
$vendor_name_prefill = '';
$vendor_avatar_prefill = '';
if ($vendor_id > 0) {
    // Attempt to fetch avatar if it exists in schema, otherwise just standard fields
    // Using simple query first to be safe, or just matching debit_note_report logic
    $venSql = "SELECT display_name, company_name, vendor_code, mobile, avatar FROM vendors_listing WHERE vendor_id = ?";
    $venStmt = $conn->prepare($venSql);
    $venStmt->bind_param("i", $vendor_id);
    $venStmt->execute();
    $venStmt->bind_result($vName, $cName, $vCode, $vMobile, $vAvatar);
    if($venStmt->fetch()){
        $display = $cName ? "$cName ($vName)" : $vName;
        $vendor_name_prefill = "$display - $vCode";
        $vendor_avatar_prefill = $vAvatar;
    }
    $venStmt->close();
}
?>
