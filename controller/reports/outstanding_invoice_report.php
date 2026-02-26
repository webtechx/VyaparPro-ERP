<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// Default filters
// For outstanding, we often want to see EVERYTHING outstanding, not just this month's. 
// But to keep consistent performance, let's default to a wide range or handle empty dates as "All".
$start_date    = isset($_GET['start_date'])   ? $_GET['start_date']          : ''; 
$end_date      = isset($_GET['end_date'])     ? $_GET['end_date']            : date('Y-m-d');
$customer_id   = isset($_GET['customer_id'])  ? intval($_GET['customer_id']) : 0;
$salesperson_id= isset($_GET['sales_employee_id']) ? intval($_GET['sales_employee_id']) : 0;
$search_query  = isset($_GET['q'])            ? trim($_GET['q'])             : '';

// Base Query
$sql = "SELECT 
            si.invoice_id,
            si.invoice_number,
            si.invoice_date,
            si.total_amount,
            si.balance_due,
            si.status,
            c.customer_name,
            c.company_name,
            c.customer_code,
            CONCAT(e.first_name, ' ', e.last_name) as salesperson_name,
            e.employee_code,
            DATEDIFF(CURRENT_DATE, si.invoice_date) as age_days
        FROM sales_invoices si
        LEFT JOIN customers_listing c ON si.customer_id = c.customer_id
        LEFT JOIN employees e ON si.sales_employee_id = e.employee_id
        WHERE si.organization_id = ? 
          AND si.balance_due > 0 
          AND si.status != 'cancelled' 
          AND si.status != 'refunded'";

$params = [$organization_id];
$types = "i";

// Apply Filters

// Date filter is optional for "Outstanding". If provided, use it.
if (!empty($start_date)) {
    $sql .= " AND si.invoice_date >= ?";
    $params[] = $start_date;
    $types .= "s";
}
if (!empty($end_date)) {
    $sql .= " AND si.invoice_date <= ?";
    $params[] = $end_date;
    $types .= "s";
}

if ($customer_id > 0) {
    $sql .= " AND si.customer_id = ?";
    $params[] = $customer_id;
    $types .= "i";
}

if ($salesperson_id > 0) {
    $sql .= " AND si.sales_employee_id = ?";
    $params[] = $salesperson_id;
    $types .= "i";
}

if (!empty($search_query)) {
    $sql .= " AND (si.invoice_number LIKE ? OR c.customer_name LIKE ? OR c.company_name LIKE ?)";
    $like = "%$search_query%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

$sql .= " ORDER BY si.invoice_date ASC"; // Oldest first typically for outstanding

// Execute
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$outstanding_invoices = [];
$total_outstanding = 0;
$total_invoice_amt = 0;
$count_invoices = 0;

// Age buckets for summary
$bucket_0_30 = 0;
$bucket_31_60 = 0;
$bucket_61_90 = 0;
$bucket_90_plus = 0;

while ($row = $result->fetch_assoc()) {
    $outstanding_invoices[] = $row;
    $bal = floatval($row['balance_due']);
    $total_outstanding += $bal;
    $total_invoice_amt += floatval($row['total_amount']);
    $count_invoices++;

    // Ageing buckets
    $age = intval($row['age_days']);
    if ($age <= 30) $bucket_0_30 += $bal;
    elseif ($age <= 60) $bucket_31_60 += $bal;
    elseif ($age <= 90) $bucket_61_90 += $bal;
    else $bucket_90_plus += $bal;
}
$stmt->close();

// Prefill Helper for Customer/Salesperson filter (reused code)
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

$salesperson_name_prefill = '';
$salesperson_avatar_prefill = '';
if ($salesperson_id > 0) {
    $spStmt = $conn->prepare("SELECT CONCAT(e.first_name, ' ', e.last_name) as name, e.employee_code, e.employee_image, o.organizations_code FROM employees e JOIN organizations o ON e.organization_id = o.organization_id WHERE e.employee_id = ?");
    $spStmt->bind_param("i", $salesperson_id);
    $spStmt->execute();
    $spStmt->bind_result($spName, $spCode, $spImage, $orgCode);
    if ($spStmt->fetch()) {
        $salesperson_name_prefill = "$spName ($spCode)";
        if (!empty($spImage)) $salesperson_avatar_prefill = "uploads/" . $orgCode . "/employees/avatars/" . $spImage;
    }
    $spStmt->close();
}
?>
