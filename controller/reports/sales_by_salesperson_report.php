<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// --- Filters ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');
$salesperson_id = isset($_GET['sales_employee_id']) ? intval($_GET['sales_employee_id']) : 0;

// --- Query ---
// Aggregate sales per sales person
$sql = "SELECT 
            si.sales_employee_id,
            e.first_name,
            e.last_name,
            e.employee_code,
            e.employee_image,
            o.organizations_code, 
            COUNT(si.invoice_id) as invoice_count,
            SUM(si.total_amount) as total_sales
        FROM sales_invoices si
        LEFT JOIN employees e ON si.sales_employee_id = e.employee_id
        LEFT JOIN organizations o ON e.organization_id = o.organization_id
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

if ($salesperson_id > 0) {
    $sql .= " AND si.sales_employee_id = ?";
    $params[] = $salesperson_id;
    $types .= "i";
}

$sql .= " GROUP BY si.sales_employee_id ORDER BY total_sales DESC";

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

// Prefill Sales Person Name
$salesperson_name_prefill = '';
$salesperson_avatar_prefill = '';
if ($salesperson_id > 0) {
    $spStmt = $conn->prepare("SELECT CONCAT(e.first_name, ' ', e.last_name) as name, e.employee_code, e.employee_image, o.organizations_code 
                              FROM employees e 
                              JOIN organizations o ON e.organization_id = o.organization_id 
                              WHERE e.employee_id = ?");
    $spStmt->bind_param("i", $salesperson_id);
    $spStmt->execute();
    $spStmt->bind_result($spName, $spCode, $spImage, $orgCode);
    if ($spStmt->fetch()) {
        $salesperson_name_prefill = "$spName ($spCode)";
        if (!empty($spImage)) {
            $salesperson_avatar_prefill = "uploads/" . $orgCode . "/employees/avatars/" . $spImage;
        }
    }
    $spStmt->close();
}
?>
