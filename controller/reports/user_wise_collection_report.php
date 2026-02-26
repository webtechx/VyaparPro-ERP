<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// --- Filters ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');
$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

// --- Query ---
// Aggregate collections per user (created_by)
$sql = "SELECT 
            p.created_by as user_id,
            e.first_name,
            e.last_name,
            e.employee_code,
            o.organizations_code,
            e.employee_image,
            COUNT(p.payment_id) as collection_count,
            SUM(p.amount) as total_collection
        FROM payment_received p
        LEFT JOIN employees e ON p.created_by = e.employee_id
        LEFT JOIN organizations o ON e.organization_id = o.organization_id
        WHERE p.organization_id = ?";

$params = [$organization_id];
$types = "i";

if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND DATE(p.created_at) BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

if ($employee_id > 0) {
    $sql .= " AND p.created_by = ?";
    $params[] = $employee_id;
    $types .= "i";
}

$sql .= " GROUP BY p.created_by ORDER BY total_collection DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$reportData = [];
$total_collection_all = 0;
$total_transactions_all = 0;

while ($row = $result->fetch_assoc()) {
    $reportData[] = $row;
    $total_collection_all += $row['total_collection'];
    $total_transactions_all += $row['collection_count'];
}
$stmt->close();
?>
