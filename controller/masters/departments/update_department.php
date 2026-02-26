<?php
include __DIR__ . '/../../../config/auth_guard.php';

if (isset($_POST['update_department'])) {

    $department_id = (int) $_POST['department_id'];
    $department_name = trim($_POST['department_name']);

    if (empty($department_id) || empty($department_name)) {
        header("Location: ../../../department?error=Department Name is required");
        exit;
    }

    // Generate slug
    $department_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $department_name)));

    $stmt = $conn->prepare("UPDATE department_listing SET department_name = ?, department_slug = ? WHERE department_id = ?");
    $stmt->bind_param("ssi", $department_name, $department_slug, $department_id);

    if ($stmt->execute()) {
        header("Location: ../../../department?success=Department updated successfully");
    } else {
        header("Location: ../../../department?error=Update failed");
    }
    exit;
} else {
    header("Location: ../../../department");
    exit;
}
