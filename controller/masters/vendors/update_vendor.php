<?php
session_start();
include __DIR__ . '/../../../config/auth_guard.php';


if (isset($_POST['update_vendor'])) {
    $organization_id = $_SESSION['organization_id'];
    $organization_code = $_SESSION['organization_code'];

    $vendor_id = $_POST['vendor_id'] ?? 0;

    if ($vendor_id <= 0) {
        header("Location: ../../../vendors?error=Invalid Vendor ID");
        exit;
    }

    // 1. Sanitize Main Vendor Data
    $salutation = $_POST['salutation'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $company_name = $_POST['company_name'] ?? '';
    $display_name = $_POST['display_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $work_phone = $_POST['work_phone'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $vendor_type = $_POST['vendor_type'] ?? '';
    $vendor_account_type = $_POST['vendor_account_type'] ?? '';
    $currency = $_POST['currency'] ?? 'INR';
    $payment_terms = $_POST['payment_terms'] ?? 'Due on Receipt';
    $status = isset($_POST['status']) ? 'active' : 'inactive';
    $pan = $_POST['pan'] ?? '';
    $gst_no = $_POST['gst_no'] ?? '';
    $opening_balance = $_POST['opening_balance'] ?? 0.00;
    $raw_obt = $_POST['opening_balance_type'] ?? 'DR';
    $opening_balance_type = (strtoupper($raw_obt) === 'CREDIT' || strtoupper($raw_obt) === 'CR') ? 'CR' : 'DR';
    $tds_tax_id = $_POST['tds_tax_id'] ?? '';
    $is_msme_registered = isset($_POST['is_msme_registered']) ? 1 : 0;
    
    // Handle Avatar Update
    $avatarPath = null;
    if(isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0){
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['avatar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)){
             // Create directory if not exists
            $uploadDir = "../../../uploads/$organization_code/vendors_avatars/";
            if(!is_dir($uploadDir)){
                mkdir($uploadDir, 0777, true);
            }
            $newFilename = 'vendor_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            $uploadPath = $uploadDir . $newFilename;
            
            if(move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadPath)){
                $avatarPath = "uploads/$organization_code/vendors_avatars/" . $newFilename;
            }
        }
    }

    // Check for duplicates (excluding current vendor)
    // Use a static query to avoid bind_param reference issues with array unpacking
    $checkQuery = "SELECT vendor_id, mobile, email, pan, gst_no FROM vendors_listing 
                   WHERE organization_id = ? 
                   AND vendor_id != ? 
                   AND (
                       (? != '' AND mobile = ?) OR
                       (? != '' AND email = ?) OR
                       (? != '' AND pan = ?) OR
                       (? != '' AND gst_no = ?)
                   )";
    
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("iissssssss", 
        $organization_id, 
        $vendor_id, 
        $mobile, $mobile, 
        $email, $email, 
        $pan, $pan, 
        $gst_no, $gst_no
    );
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $existingVendor = $result->fetch_assoc();
        
        // precise conflict detection
        $conflicts = [];
        if (!empty($mobile) && $mobile == $existingVendor['mobile']) $conflicts[] = "Mobile ($mobile)";
        if (!empty($email) && $email == $existingVendor['email']) $conflicts[] = "Email ($email)";
        if (!empty($pan) && $pan == $existingVendor['pan']) $conflicts[] = "PAN ($pan)";
        if (!empty($gst_no) && $gst_no == $existingVendor['gst_no']) $conflicts[] = "GST ($gst_no)";

        $conflictStr = implode(', ', $conflicts);
        
        $stmt->close();
        
        // Delete newly uploaded avatar if exists (since update failed)
        if ($avatarPath && file_exists("../../../" . $avatarPath)) {
            unlink("../../../" . $avatarPath);
        }

        $errorMsg = "Update Failed. You are updating Vendor ID $vendor_id, but the following fields match existing Vendor ID " . $existingVendor['vendor_id'] . ": " . $conflictStr;
        header("Location: ../../../vendors?error=" . urlencode($errorMsg));
        exit;
    }
    $stmt->close();

    $conn->begin_transaction();

    try {
        // Update Avatar if new one uploaded
        // Update Avatar if new one uploaded
        $oldAvatarToDelete = null;
        if($avatarPath){
            // Fetch old avatar to delete later
            $oldImgSql = "SELECT avatar FROM vendors_listing WHERE vendor_id = ? AND organization_id = ?";
            $stmto = $conn->prepare($oldImgSql);
            $stmto->bind_param("ii", $vendor_id, $organization_id);
            $stmto->execute();
            $resOld = $stmto->get_result();
            if($resOld->num_rows > 0){
                $oldRow = $resOld->fetch_assoc();
                if(!empty($oldRow['avatar'])){
                    $oldAvatarToDelete = $oldRow['avatar'];
                }
            }
            $stmto->close();

            $avSql = "UPDATE vendors_listing SET avatar = ? WHERE vendor_id = ?";
            $stmt = $conn->prepare($avSql);
            $stmt->bind_param("si", $avatarPath, $vendor_id);
            $stmt->execute();
            $stmt->close();
        }
        // 2. Update vendors_listing
        $sql = "UPDATE vendors_listing SET 
                organization_id=?, salutation=?, first_name=?, last_name=?, company_name=?, display_name=?, email=?, 
                work_phone=?, mobile=?, vendor_type=?, vendor_account_type=?, pan=?, gst_no=?, currency=?, 
                opening_balance=?, opening_balance_type=?, payment_terms=?, status=?, 
                updated_at=NOW() 
                WHERE vendor_id=?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssssssssssdsssi", 
            $organization_id, $salutation, $first_name, $last_name, $company_name, $display_name, $email, $work_phone, $mobile,
            $vendor_type, $vendor_account_type, $pan, $gst_no, $currency, 
            $opening_balance, $opening_balance_type, $payment_terms, $status,
            $vendor_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating vendor: " . $stmt->error);
        }
        $stmt->close();

        // 3. Update Addresses (Delete then Insert)
        $conn->query("DELETE FROM vendors_addresses WHERE vendor_id = $vendor_id AND organization_id = $organization_id");
        
        // Billing
        if (!empty($_POST['billing_address_line1']) || !empty($_POST['billing_city'])) {
            $addrSql = "INSERT INTO vendors_addresses 
                (organization_id, vendor_id, address_type, attention, country, address_line1, address_line2, city, state, pin_code, phone, fax, created_at, updated_at)
                VALUES (?, ?, 'billing', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($addrSql);
            $stmt->bind_param("iisssssssss", 
                $organization_id, $vendor_id, $_POST['billing_attention'], $_POST['billing_country'], $_POST['billing_address_line1'],
                $_POST['billing_address_line2'], $_POST['billing_city'], $_POST['billing_state'],
                $_POST['billing_pin_code'], $_POST['billing_phone'], $_POST['billing_fax']
            );
            $stmt->execute();
            $stmt->close();
        }
        
        // Shipping
        if (!empty($_POST['shipping_address_line1']) || !empty($_POST['shipping_city'])) {
            $addrSql = "INSERT INTO vendors_addresses 
                (organization_id, vendor_id, address_type, attention, country, address_line1, address_line2, city, state, pin_code, phone, fax, created_at, updated_at)
                VALUES (?, ?, 'shipping', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($addrSql);
            $stmt->bind_param("iisssssssss", 
                $organization_id, $vendor_id, $_POST['shipping_attention'], $_POST['shipping_country'], $_POST['shipping_address_line1'],
                $_POST['shipping_address_line2'], $_POST['shipping_city'], $_POST['shipping_state'],
                $_POST['shipping_pin_code'], $_POST['shipping_phone'], $_POST['shipping_fax']
            );
            $stmt->execute();
            $stmt->close();
        }

        // 4. Update Bank Account
        $conn->query("DELETE FROM vendors_bank_accounts WHERE vendor_id = $vendor_id AND organization_id = $organization_id");
        if (!empty($_POST['bank_account_number'])) {
            $bankSql = "INSERT INTO vendors_bank_accounts 
                (organization_id, vendor_id, account_holder_name, bank_name, account_number, ifsc_code, is_default, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
            $stmt = $conn->prepare($bankSql);
            $stmt->bind_param("iissss", 
                $organization_id, $vendor_id, $_POST['bank_account_holder_name'], $_POST['bank_name'], 
                $_POST['bank_account_number'], $_POST['bank_ifsc_code']
            );
            $stmt->execute();
            $stmt->close();
        }

        // 5. Update Contacts
        $conn->query("DELETE FROM vendors_contacts WHERE vendor_id = $vendor_id AND organization_id = $organization_id");
        if (isset($_POST['contact_first_name']) && is_array($_POST['contact_first_name'])) {
            $contactSql = "INSERT INTO vendors_contacts 
                (organization_id, vendor_id, salutation, first_name, last_name, email, mobile, role, is_primary, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())";
            $stmt = $conn->prepare($contactSql);

            foreach ($_POST['contact_first_name'] as $index => $fname) {
                if(empty($fname)) continue;
                $c_sal = $_POST['contact_salutation'][$index] ?? '';
                $c_lname = $_POST['contact_last_name'][$index] ?? '';
                $c_email = $_POST['contact_email'][$index] ?? '';
                $c_mobile = $_POST['contact_mobile'][$index] ?? '';
                $c_role = $_POST['contact_role'][$index] ?? '';
                
                $stmt->bind_param("iissssss", $organization_id, $vendor_id, $c_sal, $fname, $c_lname, $c_email, $c_mobile, $c_role);
                $stmt->execute();
            }
            $stmt->close();
        }



        // 8. Update Remarks
        $conn->query("DELETE FROM vendors_remarks WHERE vendor_id = $vendor_id AND organization_id = $organization_id");
        if (!empty($_POST['remarks'])) {
            $remSql = "INSERT INTO vendors_remarks (organization_id, vendor_id, remarks, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($remSql);
            $stmt->bind_param("iis", $organization_id, $vendor_id, $_POST['remarks']);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();

         // Remove old avatar file if update was successful
        if ($oldAvatarToDelete && file_exists("../../../" . $oldAvatarToDelete)) {
            unlink("../../../" . $oldAvatarToDelete);
        }

        header("Location: ../../../vendors?success=Vendor updated successfully");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../../../vendors?error=" . urlencode($e->getMessage()));
        exit;
    }

} else {
    header("Location: ../../../vendors");
    exit;
}
?>
