<?php
include __DIR__ . '/../../../config/auth_guard.php';

if (isset($_POST['add_role'])) {
    $organization_id = $_SESSION['organization_id'];
    $role_name = trim($_POST['role_name']);

    if (empty($role_name)) {
        header("Location: ../../../roles?error=Role Name is required");
        exit;
    }

    // Generate slug: lowercase, replace spaces with hyphens, remove special chars
    $role_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $role_name)));

    // Check if role name already exists
    $checkStmt = $conn->prepare("SELECT role_id FROM roles_listing WHERE organization_id = ? AND (role_name = ? OR role_slug = ?)");
    $checkStmt->bind_param("iss", $organization_id, $role_name, $role_slug);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if($checkStmt->num_rows > 0){
        $checkStmt->close();
        header("Location: ../../../roles?error=Role name already exists");
        exit;
    }
    $checkStmt->close();

    $stmt = $conn->prepare("INSERT INTO roles_listing (organization_id, role_name, role_slug) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $organization_id,  $role_name, $role_slug);

    if ($stmt->execute()) {
        header("Location: ../../../roles?success=Role added successfully");
    } else {
        header("Location: ../../../roles?error=Something went wrong");
    }
    exit;
} else {
    header("Location: ../../../roles");
    exit;
}
