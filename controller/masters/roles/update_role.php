<?php
include __DIR__ . '/../../../config/auth_guard.php';

if (isset($_POST['update_role'])) {

    $role_id = (int) $_POST['role_id'];
    $role_name = trim($_POST['role_name']);

    if (empty($role_id) || empty($role_name)) {
        header("Location: ../../../roles?error=Role Name is required");
        exit;
    }

    // Generate slug
    $role_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $role_name)));

    $stmt = $conn->prepare("UPDATE roles_listing SET role_name = ?, role_slug = ? WHERE role_id = ?");
    $stmt->bind_param("ssi", $role_name, $role_slug, $role_id);

    if ($stmt->execute()) {
        header("Location: ../../../roles?success=Role updated successfully");
    } else {
        header("Location: ../../../roles?error=Update failed");
    }
    exit;
} else {
    header("Location: ../../../roles");
    exit;
}
