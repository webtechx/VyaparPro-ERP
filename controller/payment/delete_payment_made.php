<?php
include __DIR__ . '/../../config/conn.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['id'])) {
    
    $payment_id = intval($_GET['id']);
    $org_id = $_SESSION['organization_id'] ?? 1;

    $conn->begin_transaction();

    try {
        // 1. Get Payment Details to Revert Balance
        $stmt = $conn->prepare("SELECT vendor_id, amount FROM payment_made WHERE payment_id = ? AND organization_id = ?");
        $stmt->bind_param("ii", $payment_id, $org_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows === 0) {
            throw new Exception("Payment not found or access denied.");
        }

        $payment = $res->fetch_assoc();
        $vendor_id = $payment['vendor_id'];
        $amount = $payment['amount'];
        $stmt->close();

        // 2. Revert Vendor Balance (Increase)
        $updRes = $conn->query("UPDATE vendors_listing SET current_balance_due = current_balance_due + $amount WHERE vendor_id = $vendor_id");
        if (!$updRes) {
            throw new Exception("Error reverting vendor balance: " . $conn->error);
        }

        // 3. Delete Payment
        $delRes = $conn->query("DELETE FROM payment_made WHERE payment_id = $payment_id");
        if (!$delRes) {
             throw new Exception("Error deleting payment: " . $conn->error);
        }

        $conn->commit();
        
        // Redirect
        if(isset($_SERVER['HTTP_REFERER'])) {
             header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
             // Go to route
             header("Location: ../../payment_made?success=Payment Deleted");
        }
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        die("Delete failed: " . $e->getMessage());
    }

} else {
    die("Invalid request.");
}
?>
