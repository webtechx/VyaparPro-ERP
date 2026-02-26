<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// --- Filters ---
$start_date   = isset($_GET['start_date'])   ? $_GET['start_date']          : date('Y-m-01');
$end_date     = isset($_GET['end_date'])     ? $_GET['end_date']            : date('Y-m-d');
$customer_id  = isset($_GET['customer_id'])  ? intval($_GET['customer_id']) : 0;
// We allow filtering by discount type: 'percentage' or 'flat' or 'all'
$discount_type_filter = isset($_GET['discount_type']) ? trim($_GET['discount_type']) : '';

// --- Query ---
// We need to fetch invoices where discount_amount > 0 OR discount_percent > 0
// We join with customers to get names
// We calculate 'total_discount' if it's not stored directly.
// In sales_invoices:
// We have `discount_type` (Percentage/Flat), `discount_percent`, `discount_amount`?
// Let's assume standard fields `total_amount`, `sub_total`, `tax_amount`.
// Usually discount is applied either on items or on subtotal.
// Let's check typical invoice structure. If not sure, we assume header level discount exists.
// Assuming columns: `discount_type` (enum), `discount_percent` (decimal), `discount_amount` (decimal).
// If `discount_amount` stores the calculated value, we use that.

$sql = "SELECT 
            si.invoice_id,
            si.invoice_number,
            si.invoice_date,
            si.total_amount,
            si.sub_total,
            si.discount_type,
            si.discount_type,
            si.discount_value as discount_amount,
            c.customer_name,
            c.company_name,
            c.customer_code
        FROM sales_invoices si
        LEFT JOIN customers_listing c ON si.customer_id = c.customer_id
        WHERE si.organization_id = ? 
        AND si.discount_value > 0";

$params = [$organization_id];
$types  = "i";

// Apply Date Filter
if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND si.invoice_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

// Apply Customer Filter
if ($customer_id > 0) {
    $sql .= " AND si.customer_id = ?";
    $params[] = $customer_id;
    $types .= "i";
}

// Apply Discount Type Filter
if (!empty($discount_type_filter) && $discount_type_filter !== 'all') {
    $sql .= " AND si.discount_type = ?";
    $params[] = $discount_type_filter; // e.g., 'Percentage' or 'Flat'
    $types .= "s";
}

$sql .= " ORDER BY si.invoice_date DESC, si.invoice_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$invoices = [];
$total_discount_given = 0;
$total_sales_with_discount = 0;
$count_invoices = 0;

while ($row = $result->fetch_assoc()) {
    $invoices[] = $row;
    
    // If discount_amount is 0 but percent > 0, we might need to calc, but ideally it's stored.
    // Let's trust field `discount_amount` is the monetary value.
    $total_discount_given += floatval($row['discount_amount']);
    $total_sales_with_discount += floatval($row['total_amount']);
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
    $custStmt->bind_result($cName, $compName, $code, $avatar);
    if ($custStmt->fetch()) {
        $customer_name_prefill = $compName ? "$compName ($cName)" : "$cName ($code)";
        $customer_avatar_prefill = $avatar;
    }
    $custStmt->close();
}
?>
