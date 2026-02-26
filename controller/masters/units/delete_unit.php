<?php
include __DIR__ . '/../../../config/auth_guard.php';

if (isset($_GET['id'])) {
    $unit_id = (int) $_GET['id'];

    // Check if unit is in use
    $checkSql = "SELECT COUNT(*) as count FROM items_listing WHERE unit_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $unit_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $row = $checkResult->fetch_assoc();

    if ($row['count'] > 0) {
        header("Location: ../../../units?error=Cannot delete: Unit is associated with items.");
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM units_listing WHERE unit_id = ?");
    $stmt->bind_param("i", $unit_id);

    if ($stmt->execute()) {
        header("Location: ../../../units?success=Unit deleted successfully");
    } else {
        header("Location: ../../../units?error=Failed to delete unit");
    }
    exit;
} else {
    header("Location: ../../../units?error=Invalid request");
    exit;
}
