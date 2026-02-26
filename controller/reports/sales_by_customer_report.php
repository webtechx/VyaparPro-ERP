<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// --- Filters ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

// --- Query ---
// Aggregate sales per customer
$sql = "SELECT 
            c.customer_id,
            c.customer_name,
            c.company_name,
            c.customer_code,
            c.email as primary_email,
            c.phone,
            COUNT(si.invoice_id) as invoice_count,
            SUM(si.total_amount) as total_sales,
            SUM(si.balance_due) as total_due
        FROM sales_invoices si
        JOIN customers_listing c ON si.customer_id = c.customer_id
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

if ($customer_id > 0) {
    $sql .= " AND si.customer_id = ?";
    $params[] = $customer_id;
    $types .= "i";
}

$sql .= " GROUP BY si.customer_id ORDER BY total_sales DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$reportData = [];
$grand_total_sales = 0;
$grand_total_due = 0;

while ($row = $result->fetch_assoc()) {
    $reportData[] = $row;
    $grand_total_sales += $row['total_sales'];
    $grand_total_due += $row['total_due'];
}
$stmt->close();

// Fetch customer prefill data if customer_id acts as filter
$customer_name_prefill = '';
$customer_avatar_prefill = '';
if ($customer_id > 0) {
    // Re-use connection to fetch basic details for filter display
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
?>
