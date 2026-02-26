<?php
require_once '../../../config/auth_guard.php';

if (isset($_POST['add_hsn'])) {
    $organization_id = $_SESSION['organization_id'];
    
    // Check if table exists (Self-Healing / Auto-Migration Pattern)
    // Handled in view, but redundancy is safe here or just skip.
    
    $hsn_code = trim($_POST['hsn_code']);
    $gst_rate = floatval($_POST['gst_rate']);
    $description = trim($_POST['description']);

    if (empty($hsn_code)) {
        header("Location: ../../../hsn_list?error=HSN Code is required");
        exit;
    }

    // Check if HSN code already exists
    $checkStmt = $conn->prepare("SELECT hsn_id FROM hsn_listing WHERE organization_id = ? AND hsn_code = ?");
    $checkStmt->bind_param("is", $organization_id, $hsn_code);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if($checkStmt->num_rows > 0){
        $checkStmt->close();
        header("Location: ../../../hsn_list?error=HSN Code already exists");
        exit;
    }
    $checkStmt->close();

    $stmt = $conn->prepare("INSERT INTO hsn_listing (organization_id,hsn_code, gst_rate, description) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isds", $organization_id, $hsn_code, $gst_rate, $description);
        
        try {
            if ($stmt->execute()) {
                header("Location: ../../../hsn_list?success=HSN Added Successfully");
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            if ($conn->errno == 1062) { // Duplicate entry
                header("Location: ../../../hsn_list?error=HSN Code already exists");
            } else {
                header("Location: ../../../hsn_list?error=Database Error: " . $e->getMessage());
            }
        }
        $stmt->close();
    } else {
        header("Location: ../../../hsn_list?error=Prepare Failed");
    }

} else {
    header("Location: ../../../hsn_list");
}
exit;
