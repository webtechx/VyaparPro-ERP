<?php
session_start();
require_once '../../config/conn.php';

if (isset($_POST['save_po'])) {

        // Get Org Code
    $organization_id = $_SESSION['organization_id'];
    $orgQ = $conn->query("SELECT * FROM organizations WHERE organization_id=$organization_id");
    $orgRow = $orgQ->fetch_assoc();
    $org_code = $orgRow['organizations_code'];
    $orgName = $orgRow['organization_name'];
    $orgCode = $orgRow['organizations_code'];
    $orgLogo = $orgRow['organization_logo'];
 

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
    $status = $_POST['save_po']; // 'draft' or 'sent'

    $items = $_POST['items'] ?? [];

    $conn->begin_transaction();
    try {
        // 1. Insert PO Header
        $sql = "INSERT INTO purchase_orders (organization_id, vendor_id, po_number, reference_no, order_date, delivery_date, payment_terms, payment_date, discount_type, discount_value, adjustment, gst_type, gst_rate, cgst_amount, sgst_amount, igst_amount, sub_total, total_amount, status, notes, terms_conditions, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssssssddsddddddsss", $organization_id, $vendor_id, $po_number, $reference_no, $order_date, $delivery_date, $payment_terms, $payment_date, $discount_type, $discount_value, $adjustment, $gst_type, $gst_rate, $cgst_amount, $sgst_amount, $igst_amount, $sub_total, $total_amount, $status, $notes, $terms_conditions);
        
        if(!$stmt->execute()){
            throw new Exception("PO Insert Failed: " . $stmt->error);
        }
        $po_id = $conn->insert_id;
        $stmt->close();

        // 2. Insert Items
        if (!empty($items)) {
            $itemSql = "INSERT INTO purchase_order_items (organization_id, purchase_order_id, item_id, item_name, unit_id, quantity, rate, discount, discount_type, amount, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $itemStmt = $conn->prepare($itemSql);

            foreach ($items as $item) {
                $item_id = intval($item['item_id']);
                $quantity = floatval($item['quantity']);
                $rate = floatval($item['rate']);
                $discount = floatval($item['discount'] ?? 0);
                $discount_type = $item['discount_type'] ?? 'amount'; // Capture Type
                $amount = floatval($item['amount']);

                // Fetch Item Details (Name, Unit)
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

        // 3. Handle File Uploads
        if (!empty($_FILES['attachments']['name'][0])) {
            


            $uploadDir = "../../uploads/$org_code/purchase_orders/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileSql = "INSERT INTO purchase_order_files (purchase_order_id, organization_id, file_name, file_size, uploaded_at) VALUES (?, ?, ?, ?, NOW())";
            $fileStmt = $conn->prepare($fileSql);

            foreach ($_FILES['attachments']['name'] as $key => $name) {
                if ($_FILES['attachments']['error'][$key] === 0) {
                    $tmpName = $_FILES['attachments']['tmp_name'][$key];
                    $size = $_FILES['attachments']['size'][$key];
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    $newName = "PO_" . $po_number . "_" . uniqid() . "." . $ext; // Use PO Number in name
                    $targetPath = $uploadDir . $newName;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        // Store newName as file_name, removed file_path
                        $fileStmt->bind_param("iisd", $po_id, $organization_id, $newName, $size);
                        $fileStmt->execute();
                    }
                }
            }
            $fileStmt->close();
        }
        // SELECT `po_logs_id`, `organization_id`, `purchase_order_id`, `action`, `description`, `performed_by`, `created_at` FROM `purchase_order_activity_logs` WHERE 1
        // 4. Activity Log
        $logSql = "INSERT INTO purchase_order_activity_logs (organization_id, purchase_order_id, action, description, performed_by, created_at) VALUES (?, ?, 'created', 'Purchase Order Created', 1, NOW())";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param("ii", $organization_id, $po_id);
        $logStmt->execute();

        // 5. Notify Super Admins if Status is 'sent'
        if ($status === 'sent') {


             // 1. Get Vendor Name
             $venQ = $conn->query("SELECT display_name FROM vendors_listing WHERE vendor_id = $vendor_id");
             $venName = $venQ->fetch_assoc()['display_name'] ?? 'Vendor';
             
             $logoHtml = '';
             $logoPath = '';
             if(!empty($orgLogo)){
                 $logoPath = "../../uploads/$orgCode/organization_logo/$orgLogo";
                 if(file_exists($logoPath)){
                    $logoHtml = "<div style='text-align:center; margin-bottom:20px;'><img src='cid:org_logo' style='max-height:80px; width:auto;'></div>";
                 }
             }
             
             $msgBody = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; color: #333; border:1px solid #e0e0e0; padding:30px; border-radius:8px; margin:0 auto;'>
                    $logoHtml
                    <div style='text-align:center; margin-bottom: 30px;'>
                        <h2 style='color: #333; margin:0; font-size: 24px;'>$orgName</h2>
                        <p style='color: #777; margin:5px 0 0; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;'>Purchase Order Notification</p>
                    </div>
                    
                    <h3 style='color: #0d6efd; border-bottom: 2px solid #eee; padding-bottom: 15px;'>New Order Awaiting Approval</h3>
                    
                    <p style='margin-bottom: 20px;'>Dear Super Admin,</p>
                    <p>A new purchase order <strong>$po_number</strong> has been generated and currently has the status <strong>Sent</strong>. It requires your review.</p>
                    
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

             // 2. Get Super Admins
             $saQ = $conn->query("SELECT primary_email, primary_phone FROM employees e JOIN roles_listing r ON e.role_id = r.role_id WHERE r.role_name = 'SUPER ADMIN' AND e.is_active = 1");
             
             require_once '../../public/phpmailer/src/Exception.php';
             require_once '../../public/phpmailer/src/PHPMailer.php';
             require_once '../../public/phpmailer/src/SMTP.php';
             
             while($admin = $saQ->fetch_assoc()){
                // Email
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
                        
                        // Embed Logo if exists
                        if(!empty($logoPath) && file_exists($logoPath)){
                             $mail->addEmbeddedImage($logoPath, 'org_logo', 'Logo.png');
                        }

                        $mail->isHTML(true);
                        $mail->Subject = 'New PO Created: ' . $po_number;
                        $mail->Body    = $msgBody;
                        $mail->send();
                    } catch (\Exception $e) {
                         error_log("Mail Error: " . $mail->ErrorInfo);
                    }
                }
                // SMS
                if(!empty($admin['primary_phone'])){
                     error_log("SMS to " . $admin['primary_phone'] . ": " . $msgBody);
                }
             }
        }

        $conn->commit();
        header("Location: ../../purchase_orders?success=Purchase Order created successfully");
        exit;

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
