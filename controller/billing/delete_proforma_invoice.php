<?php
require_once __DIR__ . '/../../config/auth_guard.php';

if (!isset($_GET['id'])) {
    header("Location: ../../proforma_invoice?error=Invalid Request");
    exit;
}

$invoice_id = intval($_GET['id']);
$organization_id = $_SESSION['organization_id'];

// Check if exists
$checkSql = "SELECT proforma_invoice_id FROM proforma_invoices WHERE proforma_invoice_id = ? AND organization_id = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param("ii", $invoice_id, $organization_id);
$stmt->execute();
if($stmt->get_result()->num_rows === 0){
    $stmt->close();
    header("Location: ../../proforma_invoice?error=Invoice not found or permission denied");
    exit;
}
$stmt->close();

$conn->begin_transaction();
try {
    // 1. Delete Items
    $itemSql = "DELETE FROM proforma_invoice_items WHERE proforma_invoice_id = ?";
    $itemStmt = $conn->prepare($itemSql);
    $itemStmt->bind_param("i", $invoice_id);
    if(!$itemStmt->execute()){
         throw new Exception("Failed to delete items.");
    }
    $itemStmt->close();

    // 2. Delete Invoice
    $delSql = "DELETE FROM proforma_invoices WHERE proforma_invoice_id = ? AND organization_id = ?";
    $delStmt = $conn->prepare($delSql);
    $delStmt->bind_param("ii", $invoice_id, $organization_id);
    if(!$delStmt->execute()){
        throw new Exception("Failed to delete invoice.");
    }
    $delStmt->close();

    $conn->commit();
    header("Location: ../../proforma_invoice?success=Proforma Invoice deleted successfully");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    header("Location: ../../proforma_invoice?error=" . urlencode($e->getMessage()));
    exit;
}
?>
