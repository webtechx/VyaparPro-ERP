<?php
include __DIR__ . '/../../../config/auth_guard.php';

if (isset($_POST['update_unit'])) {

    $unit_id = (int) $_POST['unit_id'];
    $unit_name = trim($_POST['unit_name']);

    if (empty($unit_id) || empty($unit_name)) {
        header("Location: ../../../units?error=Unit Name is required");
        exit;
    }

    // Generate slug
    $unit_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $unit_name)));

    $stmt = $conn->prepare("UPDATE units_listing SET unit_name = ?, unit_slug = ? WHERE unit_id = ?");
    $stmt->bind_param("ssi", $unit_name, $unit_slug, $unit_id);

    if ($stmt->execute()) {
        header("Location: ../../../units?success=Unit updated successfully");
    } else {
        header("Location: ../../../units?error=Update failed");
    }
    exit;
} else {
    header("Location: ../../../units");
    exit;
}
