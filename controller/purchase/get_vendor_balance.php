<?php
include __DIR__ . '/../../config/conn.php';

header('Content-Type: application/json');

if (!isset($_GET['vendor_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing vendor_id']);
    exit;
}

$vendor_id = intval($_GET['vendor_id']);
$sql = "SELECT current_balance_due FROM vendors_listing WHERE vendor_id = $vendor_id";
$res = $conn->query($sql);

if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    echo json_encode(['status' => 'success', 'balance' => number_format($row['current_balance_due'], 2, '.', '')]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Vendor not found']);
}
?>
