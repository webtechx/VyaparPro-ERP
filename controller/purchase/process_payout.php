<?php
// controller/customers/process_payout.php
require_once __DIR__ . '/../../config/conn.php';
header('Content-Type: application/json');

if (isset($_POST['confirm_payout'])) {
    $empId = (int)$_POST['employee_id'];
    $amount = (float)$_POST['amount'];
    $notes = trim($_POST['notes']);
    
    if (!$empId || $amount <= 0) {
        header("Location: ../../employee_incentive_wallet?error=Invalid employee ID or amount.");
        exit;
    }
    
    // Check balance first
    $stmt = $conn->prepare("SELECT current_incentive_balance FROM employees WHERE employee_id = ? FOR UPDATE");
    $stmt->bind_param("i", $empId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: ../../employee_incentive_wallet?error=Employee not found.");
        exit;
    }
    
    $emp = $result->fetch_assoc();
    $balance = $emp['current_incentive_balance'];
    
    if ($balance < $amount) {
        header("Location: ../../employee_incentive_wallet?error=Insufficient balance.");
        exit;
    }
    
    $conn->begin_transaction();
    try {
        $payoutAmount = -1 * abs($amount); // Negative
        
        // Ensure 'payout' is valid in ENUM/VARCHAR. I assume I will fix the column type.
        // Ensure 'payout' is valid in ENUM/VARCHAR.
        // Use 0 instead of NULL to avoid "Column cannot be null" error if schema fix didn't propagate.
        $insSql = "INSERT INTO incentive_ledger (employee_id, monthly_target_id, amount, distribution_type, notes, distribution_date) VALUES (?, 0, ?, 'payout', ?, NOW())";
        
        $stmt = $conn->prepare($insSql);
        $stmt->bind_param("ids", $empId, $payoutAmount, $notes);
        $stmt->execute();
        
        // Update Employee Balance
        $updSql = "UPDATE employees SET current_incentive_balance = current_incentive_balance - ? WHERE employee_id = ?";
        $stmt = $conn->prepare($updSql);
        $stmt->bind_param("di", $amount, $empId);
        $stmt->execute();
        
        $conn->commit();
        header("Location: ../../employee_incentive_wallet?success=Payout of ₹$amount processed successfully.");
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../../employee_incentive_wallet?error=Error: " . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: ../../employee_incentive_wallet");
    exit;
}
?>
