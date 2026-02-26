<?php
include __DIR__ . '/../../../config/auth_guard.php';

if (isset($_GET['id'])) {
    $designation_id = (int) $_GET['id'];
    $organization_id = $_SESSION['organization_id'];

    $stmt = $conn->prepare("DELETE FROM designation_listing WHERE designation_id = ? AND organization_id = ?");
    $stmt->bind_param("ii", $designation_id, $organization_id);

    if ($stmt->execute()) {
        header("Location: ../../../designation_listing?success=Designation deleted successfully");
    } else {
        header("Location: ../../../designation_listing?error=Failed to delete designation");
    }
    exit;
} else {
    header("Location: ../../../designation_listing?error=Invalid request");
    exit;
}
