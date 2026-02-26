<?php
include __DIR__ . '/../../../config/auth_guard.php';

if (isset($_GET['id'])) {
    $department_id = (int) $_GET['id'];
    $organization_id = $_SESSION['organization_id'];

    $stmt = $conn->prepare("DELETE FROM department_listing WHERE department_id = ? AND organization_id = ?");
    $stmt->bind_param("ii", $department_id, $organization_id);

    if ($stmt->execute()) {
        header("Location: ../../../department?success=Department deleted successfully");
    } else {
        header("Location: ../../../department?error=Failed to delete department");
    }
    exit;
} else {
    header("Location: ../../../department?error=Invalid request");
    exit;
}
