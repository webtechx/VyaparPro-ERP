<?php
require_once __DIR__ . '/../../config/auth_guard.php';

if (isset($_POST['save_payment'])) {
    
    $organization_id = $_SESSION['organization_id'];
    $created_by = $_SESSION['user_id'] ?? 0;

    $customer_id = intval($_POST['customer_id']);
    $payment_date = $_POST['payment_date'];
    $payment_number = $_POST['payment_number'];
    $payment_mode = $_POST['payment_mode'];
    $reference_no = $_POST['reference_no'] ?? '';
    $amount = floatval($_POST['amount']);
    $payment_type = $_POST['payment_type']; // invoice or advance
    $invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
    $notes = $_POST['notes'] ?? '';

    if ($amount <= 0 || empty($customer_id)) {
        header("Location: ../../payment_received?error=Invalid amount or customer selected");
        exit;
    }

    $conn->begin_transaction();
    try {
        
        // 1. Insert Payment Record
        $sql = "INSERT INTO payment_received (organization_id, customer_id, payment_number, payment_date, payment_mode, reference_no, amount, item_type, invoice_id, notes, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissssdsisi", $organization_id, $customer_id, $payment_number, $payment_date, $payment_mode, $reference_no, $amount, $payment_type, $invoice_id, $notes, $created_by);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to record payment: " . $stmt->error);
        }
        $payment_id = $conn->insert_id;
        $stmt->close();

        // 2. Handle Allocations
        if ($payment_type === 'invoice' && $invoice_id > 0) {
            // Fetch Invoice
            $invSql = "SELECT balance_due, total_amount, status FROM sales_invoices WHERE invoice_id = ? FOR UPDATE";
            $invStmt = $conn->prepare($invSql);
            $invStmt->bind_param("i", $invoice_id);
            $invStmt->execute();
            $invRes = $invStmt->get_result();
            
            if ($invRes->num_rows > 0) {
                $inv = $invRes->fetch_assoc();
                $currentDue = floatval($inv['balance_due']);
                $newDue = $currentDue - $amount;
                
                // Determine Status
                // If paid fully or overpaid, status = paid.
                // If partially paid, status remains sent (or partial if we had that status, but schema says sent/paid/overdue)
                $newStatus = $inv['status'];
                if ($newDue <= 0.01) { // Floating point tolerance
                    $newStatus = 'paid';
                    if($newDue < 0) $newDue = 0; // Cap at 0 for balance due, don't show negative due on invoice row typically, unless we handle overpayment differently.
                }

                // Update Invoice
                $upSql = "UPDATE sales_invoices SET balance_due = ?, status = ? WHERE invoice_id = ?";
                $upStmt = $conn->prepare($upSql);
                $upStmt->bind_param("dsi", $newDue, $newStatus, $invoice_id);
                $upStmt->execute();
            }
        }

        // 3. Update Customer Overall Balance
        // Reduce the customer's outstanding balance ONLY if it's new money coming in (Cash/Bank etc).
        // If it's 'Advance Adjustment', we are just using existing credit, so Global Balance remains same (it was already reduced when Advance was received).
        if ($payment_mode !== 'Advance Adjustment') {
            $custSql = "UPDATE customers_listing SET current_balance_due = current_balance_due - ? WHERE customer_id = ?";
            $custStmt = $conn->prepare($custSql);
            $custStmt->bind_param("di", $amount, $customer_id);
            $custStmt->execute();
        }
        
        // --- LEDGER UPDATE START ---
        // Fetch valid new balance for ledger
        $balSql = "SELECT current_balance_due FROM customers_listing WHERE customer_id = $customer_id";
        $balRes = $conn->query($balSql);
        $newLedgerBal = ($balRes && $balRes->num_rows > 0) ? floatval($balRes->fetch_assoc()['current_balance_due']) : 0;
        
        // Prepare Particulars
        $ledgerParticulars = "Payment Received #$payment_number" . ($reference_no ? " (Ref: $reference_no)" : "");
        $ledgerRefType = 'payment';
        
        // Insert Credit Entry
        // Use 'payment_id' as reference_id
        $ledgerSql = "INSERT INTO customers_ledger (organization_id, customer_id, transaction_date, particulars, debit, credit, balance, reference_id, reference_type, created_at) 
                      VALUES (?, ?, ?, ?, 0.00, ?, ?, ?, ?, NOW())";
        
        $ls = $conn->prepare($ledgerSql);
        // Types: i (org), i (cust), s (date), s (part), d (credit), d (balance), i (ref_id), s (ref_type)
        $ls->bind_param("iissddis", $organization_id, $customer_id, $payment_date, $ledgerParticulars, $amount, $newLedgerBal, $payment_id, $ledgerRefType);
        $ls->execute();
        $ls->close();
        // --- LEDGER UPDATE END ---

        // --- EMAIL NOTIFICATION LOGIC START ---
        try {
            // Get Customer Email
            $custInfoQ = $conn->query("SELECT email, customer_name, company_name FROM customers_listing WHERE customer_id = $customer_id");
            if ($custInfoQ && $custInfoQ->num_rows > 0) {
                $custInfo = $custInfoQ->fetch_assoc();
                $custEmail = $custInfo['email'] ?? '';
                $custName = $custInfo['customer_name'];

                if (!empty($custEmail) && !empty($authentication_email)) {
                    // Get Org Details
                    $orgQ = $conn->query("SELECT * FROM organizations WHERE organization_id = $organization_id");
                    $orgData = $orgQ->fetch_assoc();
                    $orgName = $orgData['organization_name'] ?? 'ERP System';
                    $orgCode = $orgData['organizations_code'];
                    $orgLogo = $orgData['organization_logo'];
                    
                    // Prepare Logo
                    $logoHtml = '';
                    $logoPath = '';
                    if(!empty($orgLogo)){
                        $logoPath = __DIR__ . "/../../uploads/$orgCode/organization_logo/$orgLogo";
                        if(file_exists($logoPath)){
                          $logoHtml = "<div style='text-align:center; margin-bottom:20px;'><img src='cid:org_logo' style='max-height:80px; width:auto;'></div>";
                        }
                    }

                    // Email Body
                    $msgContent = "
                         <div style='font-family: Arial, sans-serif; max-width: 600px; color: #333; border:1px solid #e0e0e0; padding:30px; border-radius:8px; margin:0 auto;'>
                            $logoHtml
                            <div style='text-align:center; margin-bottom: 30px;'>
                                <h2 style='color: #333; margin:0; font-size: 24px;'>$orgName</h2>
                                <p style='color: #777; margin:5px 0 0; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;'>Payment Receipt</p>
                            </div>
                            
                            <p>Dear $custName,</p>
                            <p>Thank you for your payment. We have received the following amount:</p>
                            
                            <div style='background-color: #f1f8e9; border: 1px solid #dcedc8; padding: 20px; border-radius: 6px; text-align: center; margin: 20px 0;'>
                                 <div style='font-size: 14px; color: #558b2f; margin-bottom: 5px;'>Amount Received</div>
                                 <div style='font-size: 28px; font-weight: bold; color: #2e7d32;'>₹" . number_format($amount, 2) . "</div>
                            </div>
                            
                            <table style='width: 100%; border-collapse: collapse; margin: 25px 0;'>
                                <tr style='border-bottom: 1px solid #eee;'>
                                    <td style='padding: 12px 0; color: #666; width: 40%;'>Payment Number</td>
                                    <td style='padding: 12px 0; font-weight: bold;'>$payment_number</td>
                                </tr>
                                <tr style='border-bottom: 1px solid #eee;'>
                                    <td style='padding: 12px 0; color: #666;'>Payment Date</td>
                                    <td style='padding: 12px 0; font-weight: bold;'>" . date('d M Y', strtotime($payment_date)) . "</td>
                                </tr>
                                <tr style='border-bottom: 1px solid #eee;'>
                                    <td style='padding: 12px 0; color: #666;'>Payment Mode</td>
                                    <td style='padding: 12px 0; font-weight: bold;'>$payment_mode</td>
                                </tr>
                                 <tr style='border-bottom: 1px solid #eee;'>
                                    <td style='padding: 12px 0; color: #666;'>Reference No</td>
                                    <td style='padding: 12px 0; font-weight: bold;'>$reference_no</td>
                                </tr>
                            </table>

                            <p style='margin-top: 30px;'>If you have any questions, please contact us.</p>
                            
                            <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #999; text-align:center;'>
                                &copy; " . date('Y') . " $orgName. All rights reserved.
                            </div>
                        </div>";

                    // Setup PHPMailer
                    require_once __DIR__ . '/../../public/phpmailer/src/Exception.php';
                    require_once __DIR__ . '/../../public/phpmailer/src/PHPMailer.php';
                    require_once __DIR__ . '/../../public/phpmailer/src/SMTP.php';

                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; 
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $authentication_email; 
                    $mail->Password   = $authentication_password; 
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom($authentication_email, $orgName);
                    $mail->addAddress($custEmail, $custName);
                    
                    if(!empty($logoPath) && file_exists($logoPath)){
                         $mail->addEmbeddedImage($logoPath, 'org_logo', 'Logo.png');
                    }

                    // Attach PDF Receipt
                    require_once __DIR__ . '/pdf_helper.php';
                    $pdfContent = generatePaymentPDF($payment_id, $conn, $organization_id);
                    if ($pdfContent) {
                        $mail->addStringAttachment($pdfContent, "PaymentReceipt_$payment_number.pdf");
                    }

                    $mail->isHTML(true);
                    $mail->Subject = 'Payment Receipt: ' . $payment_number;
                    $mail->Body    = $msgContent;
                    $mail->send();
                } else {
                    error_log("Payment Email Skipped: No customer email or SMTP unavailable.");
                }
            }
        } catch (Exception $e) {
            // Swallow email errors so we don't block the transaction commit
             error_log("Payment Email Error: " . $e->getMessage());
        }
        // --- EMAIL NOTIFICATION LOGIC END ---

        $conn->commit();
        header("Location: ../../payment_received?success=Payment recorded successfully");
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
