<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// --- Filters ---
$start_date    = isset($_GET['start_date'])   ? $_GET['start_date']          : date('Y-m-01');
$end_date      = isset($_GET['end_date'])     ? $_GET['end_date']            : date('Y-m-d');
$customer_id   = isset($_GET['customer_id'])  ? intval($_GET['customer_id']) : 0;
$salesperson_id= isset($_GET['sales_employee_id']) ? intval($_GET['sales_employee_id']) : 0;
$department_id = isset($_GET['department_id'])  ? intval($_GET['department_id']) : 0;
$status        = isset($_GET['status'])       ? trim($_GET['status'])        : '';
$search_query  = isset($_GET['q'])            ? trim($_GET['q'])             : '';

// --- Base Query ---
$sql = "SELECT
            si.invoice_id,
            si.invoice_number,
            si.invoice_date,
            si.total_amount,
            si.status,
            si.balance_due,
            c.customer_name,
            c.company_name,
            c.customer_code,
            CONCAT(e.first_name, ' ', e.last_name) AS salesperson_name
        FROM sales_invoices si
        LEFT JOIN customers_listing c  ON si.customer_id       = c.customer_id
        LEFT JOIN employees e          ON si.sales_employee_id = e.employee_id
        WHERE si.organization_id = ?";

$params = [$organization_id];
$types  = "i";

// Apply Filters
if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND si.invoice_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types   .= "ss";
}

if ($customer_id > 0) {
    $sql .= " AND si.customer_id = ?";
    $params[] = $customer_id;
    $types   .= "i";
}

if ($salesperson_id > 0) {
    $sql .= " AND si.sales_employee_id = ?";
    $params[] = $salesperson_id;
    $types   .= "i";
}

if ($department_id > 0) {
    $sql .= " AND e.department_id = ?";
    $params[] = $department_id;
    $types   .= "i";
}

if (!empty($status)) {
    $sql .= " AND si.status = ?";
    $params[] = $status;
    $types   .= "s";
}

if (!empty($search_query)) {
    $sql .= " AND (si.invoice_number LIKE ? OR c.customer_name LIKE ? OR c.company_name LIKE ?)";
    $like     = "%$search_query%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= "sss";
}

$sql .= " ORDER BY si.invoice_date DESC, si.invoice_id DESC";

// Execute
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$invoices          = [];
$total_sales       = 0;
$total_balance_due = 0;
$count_invoices    = 0;

while ($row = $result->fetch_assoc()) {
    $invoices[]         = $row;
    $total_sales       += floatval($row['total_amount']);
    $total_balance_due += floatval($row['balance_due']);
    $count_invoices++;
}
$stmt->close();

// --- Prefill Customer Name for Filter ---
$customer_name_prefill = '';
$customer_avatar_prefill = '';
if ($customer_id > 0) {
    $custStmt = $conn->prepare("SELECT customer_name, company_name, customer_code, avatar FROM customers_listing WHERE customer_id = ?");
    $custStmt->bind_param("i", $customer_id);
    $custStmt->execute();
    $custStmt->bind_result($cName, $compName, $cCode, $cAvatar);
    if ($custStmt->fetch()) {
        $customer_name_prefill = $compName ? "$compName ($cName)" : "$cName ($cCode)";
        $customer_avatar_prefill = $cAvatar;
    }
    $custStmt->close();
}

// --- Prefill Sales Person Name for Filter ---
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

// --- Fetch Departments for Filter ---
$departments = [];
$deptStmt = $conn->prepare("SELECT department_id, department_name FROM department_listing WHERE organization_id = ? ORDER BY department_name ASC");
$deptStmt->bind_param("i", $organization_id);
$deptStmt->execute();
$deptResult = $deptStmt->get_result();
while ($row = $deptResult->fetch_assoc()) {
    $departments[] = $row;
}
$deptStmt->close();
?>
