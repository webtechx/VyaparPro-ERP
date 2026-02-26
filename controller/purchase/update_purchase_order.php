<?php
session_start();
require_once '../../config/conn.php';

if (isset($_POST['update_po']) || isset($_POST['status_action']) || isset($_POST['save_po'])) {
    $po_id = intval($_POST['purchase_orders_id']);
    
    // Sanitize inputs
    $vendor_id = intval($_POST['vendor_id']);
    $po_number = $_POST['po_number'];
    $reference_no = $_POST['reference_no'];
    $order_date = $_POST['order_date'];
    $delivery_date = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : NULL;
    $payment_terms = !empty($_POST['payment_terms']) ? $_POST['payment_terms'] : NULL;


    if($payment_terms == 'Due on Receipt'){
        $payment_date = NULL;
    }elseif($payment_terms == 'Net 15'){
        $payment_date = date('Y-m-d', strtotime($order_date . ' + 15 days'));
    }elseif($payment_terms == 'Net 30'){
        $payment_date = date('Y-m-d', strtotime($order_date . ' + 30 days'));
    }elseif($payment_terms == 'Net 45'){
        $payment_date = date('Y-m-d', strtotime($order_date . ' + 45 days'));
    }elseif($payment_terms == 'Net 60'){
        $payment_date = date('Y-m-d', strtotime($order_date . ' + 60 days'));
    }

    $discount_type = $_POST['discount_type'];
    $discount_value = floatval($_POST['discount_value']);
    $adjustment = floatval($_POST['adjustment']);
    
    // GST Fields
    $gst_type = $_POST['gst_type'] ?? '';
    $gst_rate = floatval($_POST['gst_rate'] ?? 0);
    $cgst_amount = floatval($_POST['cgst_amount'] ?? 0);
    $sgst_amount = floatval($_POST['sgst_amount'] ?? 0);
    $igst_amount = floatval($_POST['igst_amount'] ?? 0);

    $sub_total = floatval($_POST['sub_total']);
    $total_amount = floatval($_POST['total_amount']);
    $notes = $_POST['notes'];
    $terms_conditions = $_POST['terms_conditions'];
    
    $items = $_POST['items'] ?? [];

    $conn->begin_transaction();
    try {
        // 1. Update Header
        $sql = "UPDATE purchase_orders SET 
                vendor_id=?, po_number=?, reference_no=?, order_date=?, delivery_date=?, payment_terms=?, payment_date=?, 
                discount_type=?, discount_value=?, adjustment=?, gst_type=?, gst_rate=?, cgst_amount=?, 
                sgst_amount=?, igst_amount=?, sub_total=?, total_amount=?, 
                notes=?, terms_conditions=?, updated_at=NOW() 
                WHERE purchase_orders_id=?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssssddsddddddssi", $vendor_id, $po_number, $reference_no, $order_date, $delivery_date, $payment_terms, $payment_date, $discount_type, $discount_value, $adjustment, $gst_type, $gst_rate, $cgst_amount, $sgst_amount, $igst_amount, $sub_total, $total_amount, $notes, $terms_conditions, $po_id);
        
        if(!$stmt->execute()){
             throw new Exception("Update Failed: " . $stmt->error);
        }
        $stmt->close();

        // ---------------------------------------------------------
        // HANDLE STATUS ACTIONS (Confirm/Cancel/Draft/Sent)
        // ---------------------------------------------------------
        $new_status = '';
        if (isset($_POST['status_action']) && !empty($_POST['status_action'])) {
            $status_action = $_POST['status_action'];
            
            if ($status_action === 'confirmed') {
                $new_status = 'confirmed';
            } elseif ($status_action === 'cancelled') {
                $new_status = 'cancelled';
            }
        } elseif (isset($_POST['save_po']) && !empty($_POST['save_po'])) {
            $action = $_POST['save_po'];
            if ($action === 'draft') {
                $new_status = 'draft';
            } elseif ($action === 'sent') {
                $new_status = 'sent';
            }
        }

            if ($new_status) {
                // Update Status
                $conn->query("UPDATE purchase_orders SET status = '$new_status' WHERE purchase_orders_id = $po_id");

                // Log Status Change
                $logSql = "INSERT INTO purchase_order_activity_logs (purchase_order_id, action, description, performed_by, created_at) VALUES (?, 'status_update', ?, 1, NOW())";
                $logDesc = "Status updated to " . ucfirst($new_status);
                $logStmt = $conn->prepare($logSql);
                $logStmt->bind_param("is", $po_id, $logDesc);
                $logStmt->execute();

                // -----------------------------------------------------
                // SEND EMAIL IF SENT (Draft -> Sent)
                // -----------------------------------------------------
                if ($new_status === 'sent') {
                     // Notification Logic for SENT status
                     // 1. Get Org Details
                    $organization_id = $_SESSION['organization_id'];
                    $orgQ = $conn->query("SELECT organization_name, organizations_code, organization_logo FROM organizations WHERE organization_id = $organization_id");
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

                    // 2. Get Vendor Name
                    $venQ = $conn->query("SELECT display_name FROM vendors_listing WHERE vendor_id = $vendor_id");
                    $venName = $venQ->fetch_assoc()['display_name'] ?? 'Vendor';

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
                            $smsBody = "PO $po_number for $venName has been sent for approval.";
                            error_log("SMS to " . $admin['primary_phone'] . ": " . $smsBody);
                        }
                    }
                }

                // -----------------------------------------------------
                // SEND EMAIL IF CONFIRMED
                // -----------------------------------------------------
                if ($new_status === 'confirmed') {

                    // NOTE: Vendor current_balance_due is NOT updated here.
                    // It is updated ONLY when a GRN is created (actual goods received).
                    // See: controller/purchase/create_grn.php
                    
                    // 1. Get Org Details (Common for both)
                    $organization_id = $_SESSION['organization_id'];
                    $orgQ = $conn->query("SELECT organization_name, organizations_code, organization_logo FROM organizations WHERE organization_id = $organization_id");
                    $orgData = $orgQ->fetch_assoc();
                    $orgName = $orgData['organization_name'];
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

                    // 2. Fetch Vendor Email & Details
                    $vSql = "SELECT email, display_name FROM vendors_listing WHERE vendor_id = $vendor_id";
                    $vRes = $conn->query($vSql);
                    $vendor_email = '';
                    $vendor_name = 'Vendor';

                    if ($vRes && $vRes->num_rows > 0) {
                        $vData = $vRes->fetch_assoc();
                        $vendor_email = $vData['email'];
                        $vendor_name = $vData['display_name'];
                    }

                    // 3. Send Vendor Email
                    if (!empty($vendor_email)) {
                        require_once '../../public/phpmailer/src/Exception.php';
                        require_once '../../public/phpmailer/src/PHPMailer.php';
                        require_once '../../public/phpmailer/src/SMTP.php';

                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                        try {
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.gmail.com'; 
                            $mail->SMTPAuth   = true;
                            $mail->Username   = $authentication_email; 
                            $mail->Password   = $authentication_password; 
                            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port       = 587;

                            $mail->setFrom($authentication_email, $orgName . ' - PO Approved');
                            $mail->addAddress($vendor_email, $vendor_name);
                            
                            if(!empty($logoPath) && file_exists($logoPath)){
                                 $mail->addEmbeddedImage($logoPath, 'org_logo', 'Logo.png');
                            }

                            // Vendor Email Template
                            $msgVendor = "
                            <div style='font-family: Arial, sans-serif; max-width: 600px; color: #333; border:1px solid #e0e0e0; padding:30px; border-radius:8px; margin:0 auto;'>
                                $logoHtml
                                <div style='text-align:center; margin-bottom: 30px;'>
                                    <h2 style='color: #333; margin:0; font-size: 24px;'>$orgName</h2>
                                    <p style='color: #777; margin:5px 0 0; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;'>Purchase Order Approved</p>
                                </div>
                                
                                <h3 style='color: #198754; border-bottom: 2px solid #eee; padding-bottom: 15px;'>Order Successfully Confirmed</h3>
                                
                                <p style='margin-bottom: 20px;'>Dear $vendor_name,</p>
                                <p>We are pleased to inform you that Purchase Order <strong>$po_number</strong> has been approved and confirmed.</p>
                                
                                <table style='width: 100%; border-collapse: collapse; margin: 25px 0;'>
                                    <tr style='border-bottom: 1px solid #eee;'>
                                        <td style='padding: 12px 0; color: #666; width: 40%;'>PO Number</td>
                                        <td style='padding: 12px 0; font-weight: bold;'>$po_number</td>
                                    </tr>
                                    <tr style='border-bottom: 1px solid #eee;'>
                                        <td style='padding: 12px 0; color: #666;'>Total Amount</td>
                                        <td style='padding: 12px 0; font-weight: bold; color: #198754;'>" . number_format($total_amount, 2) . "</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 12px 0; color: #666;'>Order Date</td>
                                        <td style='padding: 12px 0; font-weight: bold;'>$order_date</td>
                                    </tr>
                                </table>

                                <div style='background-color: #f0fff4; border: 1px solid #b6efcd; border-radius: 6px; padding: 15px; text-align: center; margin-top: 30px;'>
                                    <p style='margin:0; font-size: 14px; color: #13653f;'>Please proceed with the processing and delivery of the order.</p>
                                </div>
                                
                                <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #999; text-align:center;'>
                                    &copy; " . date('Y') . " $orgName. All rights reserved.
                                </div>
                            </div>";

                            $mail->isHTML(true);
                            $mail->Subject = "Purchase Order Approved - $po_number";
                            $mail->Body    = $msgVendor;
                            $mail->send();
                        } catch (Exception $e) {
                            error_log("Vendor Mail Error: " . $mail->ErrorInfo);
                        }
                    }

                    // 4. Notify Super Admins
                    $msgApproved = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; color: #333; border:1px solid #e0e0e0; padding:30px; border-radius:8px; margin:0 auto;'>
                            $logoHtml
                            <div style='text-align:center; margin-bottom: 30px;'>
                                <h2 style='color: #333; margin:0; font-size: 24px;'>$orgName</h2>
                                <p style='color: #777; margin:5px 0 0; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;'>ORDER APPROVED</p>
                            </div>
                            
                            <h3 style='color: #198754; border-bottom: 2px solid #eee; padding-bottom: 15px;'>Purchase Order Confirmed</h3>
                            
                            <p style='margin-bottom: 20px;'>Dear Super Admin,</p>
                            <p>Purchase Order <strong>$po_number</strong> has been successfully <strong>Approved & Confirmed</strong>.</p>
                            
                            <table style='width: 100%; border-collapse: collapse; margin: 25px 0;'>
                                <tr style='border-bottom: 1px solid #eee;'>
                                    <td style='padding: 12px 0; color: #666; width: 40%;'>PO Number</td>
                                    <td style='padding: 12px 0; font-weight: bold;'>$po_number</td>
                                </tr>
                                <tr style='border-bottom: 1px solid #eee;'>
                                    <td style='padding: 12px 0; color: #666;'>Vendor</td>
                                    <td style='padding: 12px 0; font-weight: bold;'>$vendor_name</td>
                                </tr>
                                <tr style='border-bottom: 1px solid #eee;'>
                                    <td style='padding: 12px 0; color: #666;'>Total Amount</td>
                                    <td style='padding: 12px 0; font-weight: bold; color: #198754;'>" . number_format($total_amount, 2) . "</td>
                                </tr>
                                <tr>
                                    <td style='padding: 12px 0; color: #666;'>Order Date</td>
                                    <td style='padding: 12px 0; font-weight: bold;'>$order_date</td>
                                </tr>
                            </table>

                            <div style='background-color: #f0fff4; border: 1px solid #b6efcd; border-radius: 6px; padding: 15px; text-align: center; margin-top: 30px;'>
                                <p style='margin:0; font-size: 14px; color: #13653f;'>This order is now confirmed and ready for further processing.</p>
                            </div>
                            
                            <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #999; text-align:center;'>
                                &copy; " . date('Y') . " $orgName. All rights reserved.
                            </div>
                        </div>";

                    $saQ = $conn->query("SELECT primary_email, primary_phone FROM employees e JOIN roles_listing r ON e.role_id = r.role_id WHERE r.role_name = 'SUPER ADMIN' AND e.is_active = 1");
                    
                    while($admin = $saQ->fetch_assoc()){
                        if(!empty($admin['primary_email'])){
                            $mailAdmin = new \PHPMailer\PHPMailer\PHPMailer(true);
                            try {
                                $mailAdmin->isSMTP();
                                $mailAdmin->Host       = 'smtp.gmail.com'; 
                                $mailAdmin->SMTPAuth   = true;
                                $mailAdmin->Username   = $authentication_email; 
                                $mailAdmin->Password   = $authentication_password; 
                                $mailAdmin->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                                $mailAdmin->Port       = 587;

                                $mailAdmin->setFrom($authentication_email, $orgName . ' - PO Approved');
                                $mailAdmin->addAddress($admin['primary_email']); 
                                
                                if(!empty($logoPath) && file_exists($logoPath)){
                                     $mailAdmin->addEmbeddedImage($logoPath, 'org_logo', 'Logo.png');
                                }

                                $mailAdmin->isHTML(true);
                                $mailAdmin->Subject = "PO Approved: $po_number";
                                $mailAdmin->Body    = $msgApproved;
                                $mailAdmin->send();
                            } catch (\Exception $e) { 
                                error_log("Admin Mail Error: " . $mailAdmin->ErrorInfo); 
                            }
                        }
                        
                        if(!empty($admin['primary_phone'])){
                             $smsBody = "PO $po_number has been confirmed/approved. Total: " . number_format($total_amount, 2);
                             error_log("SMS to Super Admin (" . $admin['primary_phone'] . "): " . $smsBody);
                        }
                    }
                }
            }
        // ---------------------------------------------------------

        // 2. Update Items (Delete All & Re-Insert)
        $conn->query("DELETE FROM purchase_order_items WHERE purchase_order_id = $po_id");
        $organization_id = $_SESSION['organization_id'];

        if (!empty($items)) {
            // Include organization_id
            $itemSql = "INSERT INTO purchase_order_items (organization_id, purchase_order_id, item_id, item_name, unit_id, quantity, rate, discount, discount_type, amount, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $itemStmt = $conn->prepare($itemSql);

            foreach ($items as $item) {
                $item_id = intval($item['item_id']);
                $quantity = floatval($item['quantity']);
                $rate = floatval($item['rate']);
                $discount = floatval($item['discount'] ?? 0);
                $discount_type = $item['discount_type'] ?? 'amount'; // Capture
                $amount = floatval($item['amount']);

                // Fetch Item Details
                $detSql = "SELECT item_name, unit_id FROM items_listing WHERE item_id = $item_id";
                $detRes = $conn->query($detSql);
                $prod = $detRes->fetch_assoc();
                $item_name = $prod['item_name'] ?? 'Unknown Item';
                $unit_id = $prod['unit_id'] ?? 0;

                $itemStmt->bind_param("iiisidddsd", $organization_id, $po_id, $item_id, $item_name, $unit_id, $quantity, $rate, $discount, $discount_type, $amount);
                $itemStmt->execute();
            }
            $itemStmt->close();
        }

        // 3. Handle NEW Files (Delete Old & Insert New)
        if (!empty($_FILES['attachments']['name'][0])) {
            
            // Get Org Code
            $organization_id = $_SESSION['organization_id'];
            $orgQ = $conn->query("SELECT organizations_code FROM organizations WHERE organization_id=$organization_id");
            $orgRow = $orgQ->fetch_assoc();
            $org_code = $orgRow['organizations_code'];

            $uploadDir = "../../uploads/$org_code/purchase_orders/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            // DELETE EXISTING FILES
            $oldFiles = $conn->query("SELECT file_name FROM purchase_order_files WHERE purchase_order_id = $po_id");
            while($f = $oldFiles->fetch_assoc()){
                $oldPath = $uploadDir . $f['file_name'];
                if(file_exists($oldPath)) unlink($oldPath);
            }
            $conn->query("DELETE FROM purchase_order_files WHERE purchase_order_id = $po_id");


            // INSERT NEW FILES
            $fileSql = "INSERT INTO purchase_order_files (purchase_order_id, organization_id, file_name, file_size, uploaded_at) VALUES (?, ?, ?, ?, NOW())";
            $fileStmt = $conn->prepare($fileSql);

            foreach ($_FILES['attachments']['name'] as $key => $name) {
                if ($_FILES['attachments']['error'][$key] === 0) {
                    $tmpName = $_FILES['attachments']['tmp_name'][$key];
                    $size = $_FILES['attachments']['size'][$key];
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    $newName = "PO_" . $po_number . "_" . uniqid() . "." . $ext; // Use PO Number
                    $targetPath = $uploadDir . $newName;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $fileStmt->bind_param("iisd", $po_id, $organization_id, $newName, $size);
                        $fileStmt->execute();
                    }
                }
            }
            $fileStmt->close();
        }

        // 4. Log
        $logSql = "INSERT INTO purchase_order_activity_logs (organization_id, purchase_order_id, action, description, performed_by, created_at) VALUES (?, ?, 'updated', 'Purchase Order Updated', 1, NOW())";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param("ii", $organization_id, $po_id);
        $logStmt->execute();

        $conn->commit();
        
        // Redirect back to the page request came from
        if(isset($_SERVER['HTTP_REFERER'])) {
             $redirect_url = $_SERVER['HTTP_REFERER'];
             // Remove existing query params to avoid duplicate/stale messages
             $redirect_url = strtok($redirect_url, '?');
        } else {
             $redirect_url = '../../purchase_orders';
        }

        header("Location: " . $redirect_url . "?success=Updated successfully");
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        
        if(isset($_SERVER['HTTP_REFERER'])) {
             $redirect_url = $_SERVER['HTTP_REFERER'];
             $redirect_url = strtok($redirect_url, '?');
        } else {
             $redirect_url = '../../purchase_orders';
        }

        header("Location: " . $redirect_url . "?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: ../../purchase_orders");
    exit;
}
?>
