<?php
require_once '../../../config/auth_guard.php';

if (isset($_POST['update_hsn'])) {    
    $hsn_id = intval($_POST['hsn_id']);
    $hsn_code = trim($_POST['hsn_code']);
    $gst_rate = floatval($_POST['gst_rate']);
    $description = trim($_POST['description']);

    if ($hsn_id <= 0 || empty($hsn_code)) {
        header("Location: ../../../hsn_list?error=Invalid Request");
        exit;
    }
    // Check if HSN code already exists
    $checkStmt = $conn->prepare("SELECT hsn_id FROM hsn_listing WHERE hsn_id != ? AND hsn_code = ?");
    $checkStmt->bind_param("is", $hsn_id, $hsn_code);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if($checkStmt->num_rows > 0){
        $checkStmt->close();
        header("Location: ../../../hsn_list?error=HSN Code already exists");
        exit;
    }
    $checkStmt->close();

    $stmt = $conn->prepare("UPDATE hsn_listing SET  hsn_code = ?, gst_rate = ?, description = ? WHERE hsn_id = ?");
    if ($stmt) {
        $stmt->bind_param("dsii", $hsn_code, $gst_rate, $description, $hsn_id);
        
        try {
            if ($stmt->execute()) {
                header("Location: ../../../hsn_list?success=HSN Updated Successfully");
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            header("Location: ../../../hsn_list?error=Update Failed: " . $e->getMessage());
        }
        $stmt->close();
    } else {
        header("Location: ../../../hsn_list?error=Prepare Failed");
    }

} else {
    header("Location: ../../../hsn_list");
}
exit;
