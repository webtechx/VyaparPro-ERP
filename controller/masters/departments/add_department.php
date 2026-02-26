<?php
include __DIR__ . '/../../../config/auth_guard.php';

if (isset($_POST['add_department'])) {
    $organization_id = $_SESSION['organization_id'];
    $department_name = trim($_POST['department_name']);

    if (empty($department_name)) {
        header("Location: ../../../department?error=Department Name is required");
        exit;
    }

    // Generate slug: lowercase, replace spaces with hyphens, remove special chars
    $department_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $department_name)));

    // Check if department name duplicate
    $checkStmt = $conn->prepare("SELECT department_id FROM department_listing WHERE organization_id = ? AND (department_name = ? OR department_slug = ?)");
    $checkStmt->bind_param("iss", $organization_id, $department_name, $department_slug);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if($checkStmt->num_rows > 0){
        $checkStmt->close();
        header("Location: ../../../department?error=Department name already exists");
        exit;
    }
    $checkStmt->close();

    $stmt = $conn->prepare("INSERT INTO department_listing (organization_id, department_name, department_slug) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $organization_id, $department_name, $department_slug);

    if ($stmt->execute()) {
        header("Location: ../../../department?success=Department added successfully");
    } else {
        header("Location: ../../../department?error=Something went wrong");
    }
    exit;
} else {
    header("Location: ../../../department");
    exit;
}
