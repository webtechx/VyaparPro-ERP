<?php
require_once '../../config/conn.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $conn->begin_transaction();
    try {
        // 1. Delete Files from Disk & DB
        $fileSql = "SELECT file_path FROM purchase_order_files WHERE purchase_order_id = ?";
        $stmt = $conn->prepare($fileSql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()){
            $fullPath = "../../" . $row['file_path'];
            if(file_exists($fullPath)) unlink($fullPath);
        }
        $stmt->close();

        // Database Deletions (Cascading usually handles this, but forcing clean up)
        $conn->query("DELETE FROM purchase_order_files WHERE purchase_order_id = $id");
        $conn->query("DELETE FROM purchase_order_items WHERE purchase_order_id = $id");
        $conn->query("DELETE FROM purchase_order_activity_logs WHERE purchase_order_id = $id");
        
        // Delete Header
        $delSql = "DELETE FROM purchase_orders WHERE purchase_orders_id = ?";
        $delStmt = $conn->prepare($delSql);
        $delStmt->bind_param("i", $id);
        
        if($delStmt->execute()){
             $conn->commit();
             header("Location: ../../purchase_orders?success=Deleted successfully");
             exit;
        } else {
             throw new Exception("Delete failed");
        }

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../../purchase_orders?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: ../../purchase_orders");
    exit;
}
?>
