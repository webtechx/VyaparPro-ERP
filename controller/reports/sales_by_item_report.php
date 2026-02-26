<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// --- Filters ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');
$item_id     = isset($_GET['item_id'])     ? intval($_GET['item_id'])     : 0;

// --- Query ---
// Aggregate sales per item
$sql = "SELECT 
            sii.item_id,
            sii.item_name,
            sii.hsn_code,
            SUM(sii.quantity) as total_qty,
            SUM(sii.amount) as taxable_revenue,
            SUM(sii.total_amount) as total_revenue,
            AVG(sii.rate) as avg_rate
        FROM sales_invoice_items sii
        JOIN sales_invoices si ON sii.invoice_id = si.invoice_id
        WHERE si.organization_id = ? 
        AND si.status != 'cancelled'";

$params = [$organization_id];
$types = "i";

if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND si.invoice_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

if ($item_id > 0) {
    $sql .= " AND sii.item_id = ?";
    $params[] = $item_id;
    $types .= "i";
}

$sql .= " GROUP BY sii.item_id ORDER BY total_revenue DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$reportData = [];
$total_qty_sold = 0;
$total_revenue_all = 0;

while ($row = $result->fetch_assoc()) {
    $reportData[] = $row;
    $total_qty_sold += $row['total_qty'];
    $total_revenue_all += $row['total_revenue'];
}
$stmt->close();
?>
