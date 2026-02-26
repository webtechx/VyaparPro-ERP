<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// --- Filters ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');
$user_id    = isset($_GET['user_id'])    ? intval($_GET['user_id']) : 0;
$search_query = isset($_GET['q'])        ? trim($_GET['q']) : '';

// --- Base Query ---
$sql = "SELECT 
            p.payment_id,
            p.payment_number,
            p.payment_date,
            p.payment_mode,
            p.reference_no,
            p.amount,
            p.created_at,
            c.customer_name,
            c.company_name
        FROM payment_received p
        LEFT JOIN customers_listing c ON p.customer_id = c.customer_id
        WHERE p.organization_id = ?";

$params = [$organization_id];
$types = "i";

// Apply Filters
if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND DATE(p.created_at) BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

if ($user_id > 0) {
    $sql .= " AND p.created_by = ?";
    $params[] = $user_id;
    $types .= "i";
}

if (!empty($search_query)) {
    $sql .= " AND (p.payment_number LIKE ? OR p.reference_no LIKE ? OR c.customer_name LIKE ? OR c.company_name LIKE ?)";
    $like = "%$search_query%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

$sql .= " ORDER BY p.created_at DESC";

// Execute
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$payments = [];
$total_amount = 0;
$count_transactions = 0;

while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
    $total_amount += floatval($row['amount']);
    $count_transactions++;
}
$stmt->close();

// --- Get User Details for Display ---
$user_name = 'Unknown User';
if ($user_id > 0) {
    $usrStmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM employees WHERE employee_id = ? AND organization_id = ?");
    $usrStmt->bind_param("ii", $user_id, $organization_id);
    $usrStmt->execute();
    $usrStmt->bind_result($uName);
    if ($usrStmt->fetch()) {
        $user_name = $uName ?: 'Unknown User';
    }
    $usrStmt->close();
}
?>
