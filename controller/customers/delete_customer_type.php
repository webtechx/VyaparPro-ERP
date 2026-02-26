<?php
include __DIR__ . '/../../config/auth_guard.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $organization_id = $_SESSION['organization_id'];
    $customers_type_id = intval($_POST['customers_type_id'] ?? 0);

    if ($customers_type_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Type ID']);
        exit;
    }

    // Optional: Check if used in customers_listing before delete
    $check = $conn->query("SELECT COUNT(*) as count FROM customers_listing WHERE customers_type_id = $customers_type_id AND organization_id=$organization_id");
    if($check){
        $row = $check->fetch_assoc();
        if($row['count'] > 0){
             echo json_encode(['success' => false, 'message' => 'Cannot delete: This type is assigned to ' . $row['count'] . ' customer(s).']);
             exit;
        }
    }

    // Check if used in item_commissions
    $checkComms = $conn->query("SELECT COUNT(*) as count FROM item_commissions WHERE customers_type_id = $customers_type_id AND organization_id=$organization_id");
    if($checkComms){
        $rowComm = $checkComms->fetch_assoc();
        if($rowComm['count'] > 0){
             echo json_encode(['success' => false, 'message' => 'Cannot delete: This type is used in ' . $rowComm['count'] . ' item commission setting(s).']);
             exit;
        }
    }

    $stmt = $conn->prepare("DELETE FROM customers_type_listing WHERE customers_type_id=? AND organization_id=?");
    $stmt->bind_param("ii", $customers_type_id, $organization_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Customer Type deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
