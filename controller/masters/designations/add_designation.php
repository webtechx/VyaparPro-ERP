<?php
include __DIR__ . '/../../../config/auth_guard.php';

if (isset($_POST['add_designation'])) {
    $organization_id = $_SESSION['organization_id'];
    $designation_name = trim($_POST['designation_name']);

    if (empty($designation_name)) {
        header("Location: ../../../designation_listing?error=Designation Name is required");
        exit;
    }

    // Generate slug: lowercase, replace spaces with hyphens, remove special chars
    $designation_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $designation_name)));

    // Check if designation name duplicate
    $checkStmt = $conn->prepare("SELECT designation_id FROM designation_listing WHERE organization_id = ? AND (designation_name = ? OR designation_slug = ?)");
    $checkStmt->bind_param("iss", $organization_id, $designation_name, $designation_slug);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if($checkStmt->num_rows > 0){
        $checkStmt->close();
        header("Location: ../../../designation_listing?error=Designation name already exists");
        exit;
    }
    $checkStmt->close();

    $stmt = $conn->prepare("INSERT INTO designation_listing (organization_id, designation_name, designation_slug) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $organization_id, $designation_name, $designation_slug);

    if ($stmt->execute()) {
        header("Location: ../../../designation_listing?success=Designation added successfully");
    } else {
        header("Location: ../../../designation_listing?error=Something went wrong");
    }
    exit;
} else {
    header("Location: ../../../designation_listing");
    exit;
}
