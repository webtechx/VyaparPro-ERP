<?php
session_start();
include __DIR__ . '/../../config/conn.php';

if (isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    
    if ($target_id > 0) {
        $conn->begin_transaction();
        try {
            // Delete from department_targets first (FK constraint)
            $stmt = $conn->prepare("DELETE FROM department_targets WHERE monthly_target_id = ?");
            $stmt->bind_param("i", $target_id);
            $stmt->execute();
            $stmt->close();
            
            // Delete from monthly_targets
            $stmt = $conn->prepare("DELETE FROM monthly_targets WHERE id = ?");
            $stmt->bind_param("i", $target_id);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            header("Location: ../../add_targets?success=Target deleted successfully.");
        } catch (Exception $e) {
            $conn->rollback();
            header("Location: ../../add_targets?error=Error deleting target: " . urlencode($e->getMessage()));
        }
    } else {
        header("Location: ../../add_targets?error=Invalid Target ID.");
    }
} else {
    header("Location: ../../add_targets");
}
?>
