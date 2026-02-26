<?php
include __DIR__ . '/../../../config/auth_guard.php';

header('Content-Type: application/json');

if (!isset($_GET['item_id'])) {
    echo json_encode([]);
    exit;
}

$item_id = intval($_GET['item_id']);
$organization_id = $_SESSION['organization_id'];

$sql = "SELECT customers_type_id, commission_percentage FROM item_commissions 
        WHERE item_id = ? AND organization_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $item_id, $organization_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[$row['customers_type_id']] = $row['commission_percentage'];
}

echo json_encode($data);
?>
