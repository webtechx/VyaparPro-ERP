<?php
require_once '../../../config/auth_guard.php';

if (isset($_POST['add_unit'])) {
    $organization_id = $_SESSION['organization_id'];

    $unit_name = trim($_POST['unit_name']);

    if (empty($unit_name)) {
        header("Location: ../../../units?error=Unit Name is required");
        exit;
    }

    // Generate slug
    $unit_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $unit_name)));

    // Check if unit name already exists
    $checkStmt = $conn->prepare("SELECT unit_id FROM units_listing WHERE organization_id = ? AND (unit_name = ? OR unit_slug = ?)");
    $checkStmt->bind_param("iss", $organization_id, $unit_name, $unit_slug);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if($checkStmt->num_rows > 0){
        $checkStmt->close();
        header("Location: ../../../units?error=Unit name already exists");
        exit;
    }
    $checkStmt->close();

    $stmt = $conn->prepare("INSERT INTO units_listing (organization_id, unit_name, unit_slug ) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $organization_id, $unit_name, $unit_slug);

    if ($stmt->execute()) {
        header("Location: ../../../units?success=Unit added successfully");
    } else {
        header("Location: ../../../units?error=Something went wrong");
    }
    exit;
} else {
    header("Location: ../../../units");
    exit;
}
