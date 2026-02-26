<?php
include __DIR__ . '/../../config/conn.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment'])) {
    
    $org_id = $_SESSION['organization_id'] ?? 1;
    $payment_id = isset($_POST['payment_id']) && !empty($_POST['payment_id']) ? intval($_POST['payment_id']) : 0;
    
    $vendor_id = intval($_POST['vendor_id']);
    $payment_date = $_POST['payment_date'];
    $payment_mode = $conn->real_escape_string($_POST['payment_mode']);
    $reference_no = $conn->real_escape_string($_POST['reference_no']);
    $amount = floatval($_POST['amount']);
    $notes = $conn->real_escape_string($_POST['notes']);
    $created_by = $_SESSION['user_id'] ?? 0;

    if ($vendor_id <= 0 || $amount <= 0) {
        die("Invalid vendor or amount.");
    }

    $conn->begin_transaction();

    try {
        if ($payment_id > 0) {
            // --- UPDATE EXISTING PAYMENT ---
            
            // 1. Fetch Old Payment Details
            $oldSql = "SELECT vendor_id, amount FROM payment_made WHERE payment_id = ? AND organization_id = ? FOR UPDATE";
            $stmt = $conn->prepare($oldSql);
            $stmt->bind_param("ii", $payment_id, $org_id);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($res->num_rows === 0) {
                throw new Exception("Payment record not found or access denied.");
            }
            $oldPay = $res->fetch_assoc();
            $oldVendorId = intval($oldPay['vendor_id']);
            $oldAmount = floatval($oldPay['amount']);
            $stmt->close();

            // 2. Revert Old Balance (Increase Due)
            // If we paid 100 before, we reduced due by 100. To revert, we add 100 back.
            $revertSql = "UPDATE vendors_listing SET current_balance_due = current_balance_due + ? WHERE vendor_id = ?";
            $revStmt = $conn->prepare($revertSql);
            $revStmt->bind_param("di", $oldAmount, $oldVendorId);
            if (!$revStmt->execute()) {
                throw new Exception("Error reverting old vendor balance: " . $revStmt->error);
            }
            $revStmt->close();

            // 3. Update Payment Record
            $updSql = "UPDATE payment_made SET 
                       vendor_id = ?, 
                       payment_date = ?, 
                       payment_mode = ?, 
                       reference_no = ?, 
                       amount = ?, 
                       notes = ? 
                       WHERE payment_id = ? AND organization_id = ?";
            $updStmt = $conn->prepare($updSql);
            $updStmt->bind_param("isssdsii", $vendor_id, $payment_date, $payment_mode, $reference_no, $amount, $notes, $payment_id, $org_id);
            if (!$updStmt->execute()) {
                throw new Exception("Error updating payment record: " . $updStmt->error);
            }
            $updStmt->close();

            // 4. Apply New Balance (Decrease Due)
            $newBalSql = "UPDATE vendors_listing SET current_balance_due = current_balance_due - ? WHERE vendor_id = ?";
            $nbStmt = $conn->prepare($newBalSql);
            $nbStmt->bind_param("di", $amount, $vendor_id);
            if (!$nbStmt->execute()) {
                 throw new Exception("Error applying new vendor balance: " . $nbStmt->error);
            }
            $nbStmt->close();

            $msg = "Payment Updated";

        } else {
            // --- INSERT NEW PAYMENT ---

            // 1. Generate Payment Number
            $org_short_code = $_SESSION['organization_short_code'] ?? 'ORG';
            if($org_short_code === 'ORG') {
                 // Try fetch
                 $oRes = $conn->query("SELECT organization_short_code FROM organizations WHERE organization_id = $org_id");
                 if($oRes && $oRes->num_rows > 0) $org_short_code = strtoupper($oRes->fetch_assoc()['organization_short_code']);
            }

            $payment_number = "PAY-PM-" . $org_short_code . "-0001";
            
            // Get last payment number for this org to increment
            $lastSql = "SELECT payment_number FROM payment_made WHERE organization_id = $org_id ORDER BY payment_id DESC LIMIT 1";
            $lRes = $conn->query($lastSql);
            if($lRes && $lRes->num_rows > 0){
                $lastPay = $lRes->fetch_assoc()['payment_number'];
                if (preg_match('/PAY-PM-' . preg_quote($org_short_code) . '-(\d+)/', $lastPay, $matches)) {
                    $next_num = intval($matches[1]) + 1;
                    $payment_number = 'PAY-PM-' . $org_short_code . '-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
                } 
                // Fallback for old formats if any
                else if (preg_match('/PAY-.*-(\d+)/', $lastPay, $matches)) {
                    $next_num = intval($matches[1]) + 1;
                    $payment_number = 'PAY-PM-' . $org_short_code . '-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
                }
            }

            // 2. Insert Payment
            $insSql = "INSERT INTO payment_made (organization_id, vendor_id, payment_number, payment_date, payment_mode, reference_no, amount, notes, created_by) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insSql);
            $stmt->bind_param("iissssdsi", $org_id, $vendor_id, $payment_number, $payment_date, $payment_mode, $reference_no, $amount, $notes, $created_by);
            
            if (!$stmt->execute()) {
                throw new Exception("Error inserting payment: " . $stmt->error);
            }
            $payment_id = $conn->insert_id; // For reference if needed
            $stmt->close();

            // 3. Update Vendor Balance (Decrease)
            $balSql = "UPDATE vendors_listing SET current_balance_due = current_balance_due - ? WHERE vendor_id = ?";
            $updStmt = $conn->prepare($balSql);
            $updStmt->bind_param("di", $amount, $vendor_id);
            
            if (!$updStmt->execute()) {
                throw new Exception("Error updating vendor balance: " . $updStmt->error);
            }
            $updStmt->close();

            $msg = "Payment Saved";
        }

        $conn->commit();
        
        // Redirect corrected to go to ROUTE
        header("Location: " . (isset($basePath) ? $basePath : '') . "../../payment_made?vendor_id=$vendor_id&success=" . urlencode($msg));
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        die("Transaction failed: " . $e->getMessage());
    }

} else {
    header("Location: " . (isset($basePath) ? $basePath : '') . "../../payment_made");
    exit;
}
?>
