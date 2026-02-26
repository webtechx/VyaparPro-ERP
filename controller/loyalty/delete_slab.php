<?php
include __DIR__ . '/../../config/auth_guard.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $organization_id = $_SESSION['organization_id'];
    $slab_id = intval($_POST['slab_id'] ?? 0);

    if ($slab_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Slab ID']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM loyalty_point_slabs WHERE slab_id = ? AND organization_id = ?");
    $stmt->bind_param("ii", $slab_id, $organization_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Slab deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
