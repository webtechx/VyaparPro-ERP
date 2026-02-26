<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/conn.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $conn->begin_transaction();
    try {
        // Update Status to 'sent'
        $sql = "UPDATE purchase_orders SET status = 'sent', updated_at = NOW() WHERE purchase_orders_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if($stmt->execute()){
            // Log it
            $logSql = "INSERT INTO purchase_order_activity_logs (purchase_order_id, action, description, performed_by, created_at) VALUES (?, 'status_update', 'Status updated to Sent', 1, NOW())";
            $logStmt = $conn->prepare($logSql);
            $logStmt->bind_param("i", $id);
            $logStmt->execute();
            
            // --- NOTIFICATION LOGIC START ---
            
            // 1. Get PO Details
            $poQ = $conn->query("SELECT po.po_number, po.total_amount, po.order_date, v.display_name as vendor FROM purchase_orders po LEFT JOIN vendors_listing v ON po.vendor_id = v.vendor_id WHERE po.purchase_orders_id = $id");
            $poData = $poQ->fetch_assoc();
            $po_number = $poData['po_number'];
            $venName = $poData['vendor'];
            $total_amount = $poData['total_amount'];
            $order_date = $poData['order_date'];

            // 2. Get Org Details
            $org_id = $_SESSION['organization_id'];
            $orgQ = $conn->query("SELECT organization_name, organizations_code, organization_logo FROM organizations WHERE organization_id = $org_id");
            $orgData = $orgQ->fetch_assoc();
            $orgName = $orgData['organization_name'] ?? $_SESSION['organization_name'];
            $orgCode = $orgData['organizations_code'];
            $orgLogo = $orgData['organization_logo'];

            $logoHtml = '';
            $logoPath = '';
            if(!empty($orgLogo)){
                $logoPath = "../../uploads/$orgCode/organization_logo/$orgLogo";
                if(file_exists($logoPath)){
                  $logoHtml = "<div style='text-align:center; margin-bottom:20px;'><img src='cid:org_logo' style='max-height:80px; width:auto;'></div>";
                }
            }

            $msgSent = "
                 <div style='font-family: Arial, sans-serif; max-width: 600px; color: #333; border:1px solid #e0e0e0; padding:30px; border-radius:8px; margin:0 auto;'>
                    $logoHtml
                    <div style='text-align:center; margin-bottom: 30px;'>
                        <h2 style='color: #333; margin:0; font-size: 24px;'>$orgName</h2>
                        <p style='color: #777; margin:5px 0 0; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;'>Purchase Order Notification</p>
                    </div>
                    
                    <h3 style='color: #0d6efd; border-bottom: 2px solid #eee; padding-bottom: 15px;'>Order Sent for Approval</h3>
                    
                    <p style='margin-bottom: 20px;'>Dear Super Admin,</p>
                    <p>Purchase Order <strong>$po_number</strong> has been updated to status <strong>Sent</strong> and requires your review.</p>
                    
                    <table style='width: 100%; border-collapse: collapse; margin: 25px 0;'>
                        <tr style='border-bottom: 1px solid #eee;'>
                            <td style='padding: 12px 0; color: #666; width: 40%;'>PO Number</td>
                            <td style='padding: 12px 0; font-weight: bold;'>$po_number</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #eee;'>
                            <td style='padding: 12px 0; color: #666;'>Vendor</td>
                            <td style='padding: 12px 0; font-weight: bold;'>$venName</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #eee;'>
                            <td style='padding: 12px 0; color: #666;'>Total Amount</td>
                            <td style='padding: 12px 0; font-weight: bold; color: #28a745;'>" . number_format($total_amount, 2) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px 0; color: #666;'>Order Date</td>
                            <td style='padding: 12px 0; font-weight: bold;'>$order_date</td>
                        </tr>
                    </table>

                    <div style='background-color: #f8f9fa; border-radius: 6px; padding: 15px; text-align: center; margin-top: 30px;'>
                         <p style='margin:0; font-size: 14px;'>Please login to the ERP system to approve or reject this order.</p>
                    </div>
                    
                    <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #999; text-align:center;'>
                        &copy; " . date('Y') . " $orgName. All rights reserved.
                    </div>
                </div>";

            // 3. Get Super Admins
            $saQ = $conn->query("SELECT primary_email, primary_phone FROM employees e JOIN roles_listing r ON e.role_id = r.role_id WHERE r.role_name = 'SUPER ADMIN' AND e.is_active = 1");
            
            require_once '../../public/phpmailer/src/Exception.php';
            require_once '../../public/phpmailer/src/PHPMailer.php';
            require_once '../../public/phpmailer/src/SMTP.php';

            while($admin = $saQ->fetch_assoc()){
                if(!empty($admin['primary_email'])){
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com'; 
                        $mail->SMTPAuth   = true;
                        $mail->Username   = $authentication_email; 
                        $mail->Password   = $authentication_password; 
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        $mail->setFrom($authentication_email, $orgName . ' - PO Notification');
                        $mail->addAddress($admin['primary_email']); 
                        
                        if(!empty($logoPath) && file_exists($logoPath)){
                             $mail->addEmbeddedImage($logoPath, 'org_logo', 'Logo.png');
                        }

                        $mail->isHTML(true);
                        $mail->Subject = 'PO Sent: ' . $po_number;
                        $mail->Body    = $msgSent;
                        $mail->send();
                    } catch (\Exception $e) {
                         error_log("Mail Error: " . $mail->ErrorInfo);
                    }
                }
                if(!empty($admin['primary_phone'])){
                     error_log("SMS to " . $admin['primary_phone'] . ": PO $po_number Sent");
                }
            }
            // --- NOTIFICATION LOGIC END ---

            $conn->commit();
            header("Location: ../../purchase_orders?success=Marked as Sent successfully. Notification sent.");
            exit;
        } else {
            throw new Exception("Update status failed");
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
