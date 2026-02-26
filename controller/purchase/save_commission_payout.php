<?php
require_once '../../config/conn.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $organization_id = $_SESSION['organization_id'];
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $payment_mode = trim($_POST['payment_mode'] ?? 'Cash');
    $notes = trim($_POST['notes'] ?? '');
    $created_by = $_SESSION['user_id'];

    if ($customer_id <= 0 || $amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid customer or amount']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // 1. Deduct from Customer Balance
        // Check current balance first to prevent negative if strict (optional, but good practice)
        /*
        $balSql = "SELECT commissions_amount FROM customers_listing WHERE customer_id = $customer_id FOR UPDATE";
        $balRes = $conn->query($balSql);
        $currBal = $balRes->fetch_assoc()['commissions_amount'];
        if ($currBal < $amount) {
            throw new Exception("Insufficient commission balance.");
        }
        */
        
        $upd = $conn->prepare("UPDATE customers_listing SET commissions_amount = commissions_amount - ? WHERE customer_id = ? AND organization_id = ?");
        $upd->bind_param("dii", $amount, $customer_id, $organization_id);
        if (!$upd->execute()) {
            throw new Exception("Failed to update customer balance.");
        }
        $upd->close();

        // 2. Insert Payout Record
        $payoutSql = "INSERT INTO customers_commissions_payouts (organization_id, customer_id, amount, payment_date, payment_mode, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt1 = $conn->prepare($payoutSql);
        $stmt1->bind_param("iidsssi", $organization_id, $customer_id, $amount, $payment_date, $payment_mode, $notes, $created_by);
        if (!$stmt1->execute()) {
            throw new Exception("Failed to save payout record.");
        }
        $stmt1->close();

        // 3. Insert Ledger Entry (Negative Amount)
        $ledgerNote = "Payout (" . $payment_mode . ")";
        if (!empty($notes)) {
            $ledgerNote .= " - " . $notes;
        }
        
        // We use negative amount to signify debit in a single-column ledger, or just store it.
        // The ledger view logic needs to handle this. Since it's a generic ledger table, storing negative is often easiest for SUM().
        $negAmount = -1 * $amount;
        $ledgerSql = "INSERT INTO customers_commissions_ledger (organization_id, customer_id, invoice_id, commission_amount, notes, created_at) VALUES (?, ?, 0, ?, ?, NOW())";
        $stmt2 = $conn->prepare($ledgerSql);
        $stmt2->bind_param("iids", $organization_id, $customer_id, $negAmount, $ledgerNote);
        if (!$stmt2->execute()) {
            throw new Exception("Failed to update ledger.");
        }
        $stmt2->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Payout recorded successfully']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
