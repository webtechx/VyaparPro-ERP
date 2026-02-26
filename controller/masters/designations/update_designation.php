<?php
include __DIR__ . '/../../../config/auth_guard.php';

if (isset($_POST['update_designation'])) {

    $designation_id = (int) $_POST['designation_id'];
    $designation_name = trim($_POST['designation_name']);

    if (empty($designation_id) || empty($designation_name)) {
        header("Location: ../../../designation_listing?error=Designation Name is required");
        exit;
    }

    // Generate slug
    $designation_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $designation_name)));

    $stmt = $conn->prepare("UPDATE designation_listing SET designation_name = ?, designation_slug = ? WHERE designation_id = ?");
    $stmt->bind_param("ssi", $designation_name, $designation_slug, $designation_id);

    if ($stmt->execute()) {
        header("Location: ../../../designation_listing?success=Designation updated successfully");
    } else {
        header("Location: ../../../designation_listing?error=Update failed");
    }
    exit;
} else {
    header("Location: ../../../designation_listing");
    exit;
}
