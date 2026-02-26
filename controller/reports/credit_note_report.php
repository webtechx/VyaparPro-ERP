<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// --- Filters ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$salesperson_id = isset($_GET['sales_employee_id']) ? intval($_GET['sales_employee_id']) : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// --- Base Query for Credit Notes ---
$sql = "SELECT 
            cn.credit_note_id,
            cn.credit_note_number,
            cn.credit_note_date,
            cn.total_amount,
            cn.status,
            cn.reason,
            c.customer_name,
            c.company_name,
            i.invoice_number,
            CONCAT(e.first_name, ' ', e.last_name) AS salesperson_name
        FROM credit_notes cn
        LEFT JOIN customers_listing c ON cn.customer_id = c.customer_id
        LEFT JOIN sales_invoices i ON cn.invoice_id = i.invoice_id
        LEFT JOIN employees e ON i.sales_employee_id = e.employee_id
        WHERE cn.organization_id = ?";

$params = [$organization_id];
$types = "i";

// Apply Filters
if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND cn.credit_note_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

if ($customer_id > 0) {
    $sql .= " AND cn.customer_id = ?";
    $params[] = $customer_id;
    $types .= "i";
}

if ($salesperson_id > 0) {
    $sql .= " AND i.sales_employee_id = ?";
    $params[] = $salesperson_id;
    $types .= "i";
}

if (!empty($status)) {
    $sql .= " AND cn.status = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($search_query)) {
    $sql .= " AND (cn.credit_note_number LIKE ? OR i.invoice_number LIKE ?)";
    $like = "%$search_query%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

$sql .= " ORDER BY cn.credit_note_date DESC, cn.credit_note_id DESC";

// Execute Query
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$credit_notes = [];
$total_credit_amount = 0;
$count_notes = 0;

while ($row = $result->fetch_assoc()) {
    $credit_notes[] = $row;
    $total_credit_amount += floatval($row['total_amount']);
    $count_notes++;
}
$stmt->close();

// --- Prefill Filter Data (Customer Name) ---
$customer_name_prefill = '';
$customer_avatar_prefill = '';
if ($customer_id > 0) {
    // Note: Assuming customers_listing has avatar column as per sales_report
    $custSql = "SELECT customer_name, company_name, customer_code, avatar FROM customers_listing WHERE customer_id = ?";
    $custStmt = $conn->prepare($custSql);
    $custStmt->bind_param("i", $customer_id);
    $custStmt->execute();
    $custStmt->bind_result($cName, $compName, $code, $avatar);
    if($custStmt->fetch()){
        $customer_name_prefill = $compName ? "$compName ($cName)" : "$cName ($code)";
        $customer_avatar_prefill = $avatar;
    }
    $custStmt->close();
}

// --- Prefill Sales Person Name for Filter ---
$salesperson_name_prefill = '';
$salesperson_avatar_prefill = '';
if ($salesperson_id > 0) {
    // Matching sales_report logic for specific employee image path if needed, 
    // or just generic if we don't have org code easily here (we do, in session or DB)
    // using simplified fetch for now or copying sales_report exact logic
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
