<?php
include __DIR__ . '/../../../config/conn.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // 0. Check constraints
    $chk_query = "
        SELECT 1 FROM dual WHERE EXISTS (
            SELECT 1 FROM sales_invoices WHERE make_employee_id = $id OR sales_employee_id = $id
            UNION ALL
            SELECT 1 FROM proforma_invoices WHERE make_employee_id = $id OR sales_employee_id = $id
            UNION ALL
            SELECT 1 FROM incentive_ledger WHERE employee_id = $id
            UNION ALL
            SELECT 1 FROM payment_received WHERE created_by = $id
            UNION ALL
            SELECT 1 FROM payment_made WHERE created_by = $id
            UNION ALL
            SELECT 1 FROM credit_notes WHERE created_by = $id
        )
    ";
    $chk_res = $conn->query($chk_query);
    if ($chk_res && $chk_res->num_rows > 0) {
        header("Location: ../../../employees?error=Cannot delete: employee is referenced in other transactional records");
        exit;
    }

    // 1. Get Files to unlink
    $q = $conn->query("SELECT e.employee_image, e.document_attachment, o.organizations_code 
                       FROM employees e 
                       LEFT JOIN organizations o ON e.organization_id = o.organization_id 
                       WHERE e.employee_id=$id");
    
    if($r = $q->fetch_assoc()){
        $orgCode = $r['organizations_code'];

        // Unlink Image
        if(!empty($r['employee_image'])){
            $imgPath = "../../../uploads/" . $orgCode . "/employees/avatars/" . $r['employee_image'];
            if(file_exists($imgPath)) unlink($imgPath);
        }

        // Unlink Document
        if(!empty($r['document_attachment'])){
            $docPath = "../../../uploads/" . $orgCode . "/employees/docs/" . $r['document_attachment'];
            if(file_exists($docPath)) unlink($docPath);
        }
    }

    // 2. Delete (Cascade will handle sub-tables if configured, otherwise we delete manually or rely on DB)
    // Assuming DB has ON DELETE CASCADE for addresses/bank
    // But to be safe if no FK, delete children first
    $conn->query("DELETE FROM employee_addresses WHERE employee_id=$id");
    $conn->query("DELETE FROM employee_bank_details WHERE employee_id=$id");

    $stmt = $conn->prepare("DELETE FROM employees WHERE employee_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: ../../../employees?success=Employee deleted successfully");
    } else {
        header("Location: ../../../employees?error=Failed to delete employee: " . $conn->error);
    }
    exit;
} else {
    header("Location: ../../../employees?error=Invalid request");
    exit;
}
