<?php
require_once '../../../config/auth_guard.php';

if (isset($_GET['id'])) {
    $organization_id = $_SESSION['organization_id'];
    $hsn_id = intval($_GET['id']);

    if ($hsn_id > 0) {
        
        // Strict Backend Check: Dependencies
        $check = $conn->query("SELECT COUNT(item_id) as count FROM items_listing WHERE hsn_id = $hsn_id");
        $usage = $check->fetch_assoc();
        
        if($usage['count'] > 0) {
             header("Location: ../../../hsn_list?error=Cannot delete: HSN is assigned to used items.");
             exit;
        }

        // For now, simple delete
        $stmt = $conn->prepare("DELETE FROM hsn_listing WHERE organization_id = ? AND hsn_id = ?");
        $stmt->bind_param("ii", $organization_id, $hsn_id);
        
        if ($stmt->execute()) {
             header("Location: ../../../hsn_list?success=HSN Deleted Successfully");
        } else {
             header("Location: ../../../hsn_list?error=Delete Failed");
        }
        $stmt->close();
    } else {
        header("Location: ../../../hsn_list?error=Invalid ID");
    }
} else {
    header("Location: ../../../hsn_list");
}
exit;
