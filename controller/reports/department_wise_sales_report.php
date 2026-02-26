<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// --- Filters ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;

// --- Query ---
// Aggregate sales per department
$sql = "SELECT 
            d.department_id,
            d.department_name,
            COUNT(si.invoice_id) as invoice_count,
            SUM(si.total_amount) as total_sales
        FROM sales_invoices si
        LEFT JOIN employees e ON si.sales_employee_id = e.employee_id
        LEFT JOIN department_listing d ON e.department_id = d.department_id
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

if ($department_id > 0) {
    $sql .= " AND d.department_id = ?";
    $params[] = $department_id;
    $types .= "i";
}

$sql .= " GROUP BY d.department_id ORDER BY total_sales DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$reportData = [];
$total_sales_all = 0;
$total_invoices_all = 0;

while ($row = $result->fetch_assoc()) {
    $reportData[] = $row;
    $total_sales_all += $row['total_sales'];
    $total_invoices_all += $row['invoice_count'];
}
$stmt->close();
?>
