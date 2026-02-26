<?php
include __DIR__ . '/../../../config/auth_guard.php';

if (isset($_GET['id'])) {
    $organization_id = $_SESSION['organization_id'];
    $item_id = (int) $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM items_listing WHERE item_id = ? AND organization_id = ?");
    $stmt->bind_param("ii", $item_id, $organization_id);

    if ($stmt->execute()) {
        header("Location: ../../../items?success=Item deleted successfully");
    } else {
        header("Location: ../../../items?error=Failed to delete item");
    }
    exit;
} else {
    header("Location: ../../../items?error=Invalid request");
    exit;
}
