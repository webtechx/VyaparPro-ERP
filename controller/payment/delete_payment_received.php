<?php
require_once __DIR__ . '/../../config/auth_guard.php';

if (isset($_GET['id'])) {
    $payment_id = intval($_GET['id']);
    $organization_id = $_SESSION['organization_id'];

    $conn->begin_transaction();
    try {
        // 1. Fetch Payment Details to reverse stats
        $sql = "SELECT * FROM payment_received WHERE payment_id = ? AND organization_id = ? FOR UPDATE";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $payment_id, $organization_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Payment not found or access denied");
        }
        
        $payment = $result->fetch_assoc();
        $amount = floatval($payment['amount']);
        $customer_id = $payment['customer_id'];
        $invoice_id = $payment['invoice_id'];
        $item_type = $payment['item_type'];
        
        // 2. Reverse Invoice Allocation
        if ($item_type === 'invoice' && $invoice_id > 0) {
            // Fetch Invoice current state
            $invSql = "SELECT balance_due, total_amount, status FROM sales_invoices WHERE invoice_id = ? FOR UPDATE";
            $invStmt = $conn->prepare($invSql);
            $invStmt->bind_param("i", $invoice_id);
            $invStmt->execute();
            $invRes = $invStmt->get_result();
            
            if ($invRes->num_rows > 0) {
                $inv = $invRes->fetch_assoc();
                $currentDue = floatval($inv['balance_due']);
                $totalAmount = floatval($inv['total_amount']);
                
                // Add back the amount
                $newDue = $currentDue + $amount;
                
                // Cap due at total amount (prevention of weird calculation issues)
                if($newDue > $totalAmount) $newDue = $totalAmount;
                
                // Determine Status
                // If it was paid, it might become sent/partial. 
                // Simple logic: if newDue > 0.01 it is not fully paid.
                $newStatus = $inv['status'];
                if ($newDue > 0.01) {
                    $newStatus = 'sent'; // Default back to sent or partial.
                    // Ideally check due date vs now for 'overdue' but 'sent' is safer fallback.
                }

                $upSql = "UPDATE sales_invoices SET balance_due = ?, status = ? WHERE invoice_id = ?";
                $upStmt = $conn->prepare($upSql);
                $upStmt->bind_param("dsi", $newDue, $newStatus, $invoice_id);
                $upStmt->execute();
            }
        }
        
        // 3. Reverse Customer Balance Update
        // Increase the customer's outstanding balance (debt increases as payment is removed)
        $custSql = "UPDATE customers_listing SET current_balance_due = current_balance_due + ? WHERE customer_id = ?";
        $custStmt = $conn->prepare($custSql);
        $custStmt->bind_param("di", $amount, $customer_id);
        $custStmt->execute();

        // 4. Delete the Payment
        $delSql = "DELETE FROM payment_received WHERE payment_id = ?";
        $delStmt = $conn->prepare($delSql);
        $delStmt->bind_param("i", $payment_id);
        $delStmt->execute();
        
        $conn->commit();
        header("Location: ../../payment_received?success=Payment deleted successfully");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../../payment_received?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: ../../payment_received");
    exit;
}
?>
