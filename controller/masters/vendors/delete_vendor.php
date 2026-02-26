<?php
include __DIR__ . '/../../../config/auth_guard.php';

if (isset($_GET['id'])) {
    $organization_id = $_SESSION['organization_id'];
    $vendor_id = intval($_GET['id']);
    
    if($vendor_id <= 0){
        header("Location: ../../../vendors?error=Invalid Vendor ID");
        exit;
    }

    $conn->begin_transaction();

    try {
        // 1. Fetch and unlink avatar (Confirm ownership first)
        $imgSql = "SELECT avatar FROM vendors_listing WHERE vendor_id = ? AND organization_id = ?";
        $stmt = $conn->prepare($imgSql);
        $stmt->bind_param("ii", $vendor_id, $organization_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Vendor not found or access denied.");
        }

        $row = $result->fetch_assoc();
        $stmt->close();

        // 2. Delete Child Records (Addresses, Bank, Contacts, Remarks)
        // Using Prepared Statements with organization_id check for extra safety
        $tables = ['vendors_addresses', 'vendors_bank_accounts', 'vendors_contacts', 'vendors_remarks'];
        foreach($tables as $table){
            $delSql = "DELETE FROM $table WHERE vendor_id = ? AND organization_id = ?";
            $delStmt = $conn->prepare($delSql);
            $delStmt->bind_param("ii", $vendor_id, $organization_id);
            $delStmt->execute();
            $delStmt->close();
        }

        // 3. Delete Main Record
        $sql = "DELETE FROM vendors_listing WHERE vendor_id = ? AND organization_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $vendor_id, $organization_id);
        
        if ($stmt->execute()) {
             // Unlink avatar ONLY after successful DB transaction logic (delayed action)
             // But usually safe to do here before commit or after. 
             // If we rollback, we shouldn't delete file. Ideally.
             // But existing code did it inside catch-prone block. 
             // We'll move unlink AFTER commit to be atomic-ish (file stays if DB fails).
             
             $conn->commit();

             // Now delete file
             if(!empty($row['avatar'])){
                $avatarPath = '../../../' . $row['avatar'];
                if(file_exists($avatarPath)){
                    unlink($avatarPath);
                }
             }

             header("Location: ../../../vendors?success=Vendor deleted successfully");
             exit;
        } else {
             throw new Exception("Error deleting vendor: " . $stmt->error);
        }
        $stmt->close();
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete Vendor Failed: " . $e->getMessage()); 
        header("Location: ../../../vendors?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: ../../../vendors");
    exit;
}
?>
