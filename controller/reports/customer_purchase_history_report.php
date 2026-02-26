<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// --- Filters ---
$start_date         = isset($_GET['start_date'])   ? $_GET['start_date']          : date('Y-m-01');
$end_date           = isset($_GET['end_date'])     ? $_GET['end_date']            : date('Y-m-d');
$customer_id        = isset($_GET['customer_id'])  ? intval($_GET['customer_id']) : 0;
// We might want to filter by item or category too in future, but core request is Customer Purchase History
$status             = isset($_GET['status'])       ? trim($_GET['status'])        : '';
$search_query       = isset($_GET['q'])            ? trim($_GET['q'])             : '';

// --- Base Query ---
// We want to show invoices for customers (Sales History from our perspective, Purchase History from theirs)
// Or is it "What items has a customer purchased"? Usually implies comprehensive list of invoices/items.
// Let's stick to Sales Invoices listing for a customer, similar to Sales Report but maybe focused?
// Actually, "Purchase History Report of Customers" sounds exactly like "Sales Report" filtered by customer.
// However, often "Purchase History" might imply line-item details - i.e. what products they bought.
// But given the previous pattern (Purchase History of Vendors was PO listing), 
// "Purchase History of Customers" is likely "Sales History" i.e. Invoice Listing.
// BUT, we already have Sales Report.
// Let's assume it mimics the Sales Report but maybe strictly focused on customer-centric view or just a clone for navigation purposes as requested.
// Wait, "Purchase History Report of Vendors" showed POs.
// "Purchase History Report of Customers" should show what they purchased from us -> Sales Invoices.
// So this is effectively a Sales Report. I will implement it as such, but titled "Purchase History (Customer)".

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
            c.phone,
            c.email
        FROM sales_invoices si
        LEFT JOIN customers_listing c  ON si.customer_id = c.customer_id
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

if (!empty($status)) {
    $sql .= " AND si.status = ?";
    $params[] = $status;
    $types   .= "s";
}

if (!empty($search_query)) {
    // Search invoice or customer name
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
$count_invoices    = 0;

while ($row = $result->fetch_assoc()) {
    $invoices[]         = $row;
    $total_sales       += floatval($row['total_amount']);
    $count_invoices++;
}
$stmt->close();

// --- Prefill Customer Name for Filter ---
$customer_name_prefill = '';
$customer_avatar_prefill = '';
if ($customer_id > 0) {
    // Assuming customers_listing has avatar
    $custStmt = $conn->prepare("SELECT customer_name, company_name, customer_code, avatar FROM customers_listing WHERE customer_id = ?");
    $custStmt->bind_param("i", $customer_id);
    $custStmt->execute();
    $custStmt->bind_result($cName, $compName, $code, $avatar);
    if ($custStmt->fetch()) {
        $customer_name_prefill = $compName ? "$compName ($cName)" : "$cName ($code)";
        $customer_avatar_prefill = $avatar;
    }
    $custStmt->close();
}
?>
