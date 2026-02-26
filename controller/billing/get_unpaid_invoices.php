<?php
require_once __DIR__ . '/../../config/auth_guard.php';

header('Content-Type: application/json');

if (!isset($_GET['customer_id'])) {
    echo json_encode([]);
    exit;
}

$customer_id = intval($_GET['customer_id']);
$organization_id = $_SESSION['organization_id'];

// Check table existence first to avoid errors
$check = $conn->query("SHOW TABLES LIKE 'sales_invoices'");
if(!$check || $check->num_rows == 0){
     echo json_encode([]);
     exit;
}

$sql = "SELECT invoice_id, invoice_number, invoice_date, balance_due, total_amount 
        FROM sales_invoices 
        WHERE organization_id = ? 
        AND customer_id = ? 
        AND status != 'cancelled' 
        AND balance_due > 0 
        ORDER BY invoice_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $organization_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$invoices = [];
while ($row = $result->fetch_assoc()) {
    $invoices[] = $row;
}

echo json_encode($invoices);
?>
