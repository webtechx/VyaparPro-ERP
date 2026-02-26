<?php
include __DIR__ . '/../../../config/auth_guard.php';

if (isset($_POST['add_vendor'])) {
    $organization_id = $_SESSION['organization_id'];
    $organization_code = $_SESSION['organization_code'];

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
    $vendor_language = 'en'; // Default or add to form

    $avatar = '';
    // Handle Avatar Upload
    if(isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0){
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['avatar']['name'];
        $filetype = $_FILES['avatar']['type'];
        $filesize = $_FILES['avatar']['size'];
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
                $avatar = "uploads/$organization_code/vendors_avatars/" . $newFilename;
            }
        }
    }

    // Check for duplicates
    $checkConditions = [];
    $checkParams = ["i", $organization_id];
    $checkTypes = "i";
    
    if (!empty($mobile)) {
        $checkConditions[] = "mobile = ?";
        $checkParams[] = $mobile;
        $checkTypes .= "s";
    }
    if (!empty($email)) {
        $checkConditions[] = "email = ?";
        $checkParams[] = $email;
        $checkTypes .= "s";
    }
    if (!empty($pan)) {
        $checkConditions[] = "pan = ?";
        $checkParams[] = $pan;
        $checkTypes .= "s";
    }
    if (!empty($gst_no)) {
        $checkConditions[] = "gst_no = ?";
        $checkParams[] = $gst_no;
        $checkTypes .= "s";
    }

    if (!empty($checkConditions)) {
        $checkQuery = "SELECT vendor_id FROM vendors_listing WHERE organization_id = ? AND (" . implode(" OR ", $checkConditions) . ")";
        $stmt = $conn->prepare($checkQuery);
        
        // Dynamic binding
        $stmt->bind_param($checkTypes, ...array_slice($checkParams, 1));
        
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->close();
            
            // Delete uploaded avatar if exists
            if (!empty($avatar) && file_exists("../../../" . $avatar)) {
                unlink("../../../" . $avatar);
            }

            $errorMsg = "Vendor already exists (Mobile, Email, PAN, or GST match found).";
            
            if(isset($_POST['ajax']) && $_POST['ajax'] == 'true'){
                echo json_encode(['success' => false, 'error' => $errorMsg]);
                exit;
            }

            header("Location: ../../../vendors?error=" . urlencode($errorMsg));
            exit;
        }
        $stmt->close();
    }

    // Get Organization Short Code
    $orgSql = "SELECT organization_short_code FROM organizations WHERE organization_id = ?";
    $stmtOrg = $conn->prepare($orgSql);
    $stmtOrg->bind_param("i", $organization_id);
    $stmtOrg->execute();
    $resOrg = $stmtOrg->get_result();
    $organization_short_code = 'ORG'; // Default fallback
    if($resOrg->num_rows > 0){
        $organization_short_code = $resOrg->fetch_assoc()['organization_short_code'];
    }
    $stmtOrg->close();

    // Generate Vendor Code
    $vCodeSql = "SELECT vendor_code FROM vendors_listing WHERE organization_id = ? ORDER BY vendor_id DESC LIMIT 1";
    $stmtC = $conn->prepare($vCodeSql);
    $stmtC->bind_param("i", $organization_id);
    $stmtC->execute();
    $resC = $stmtC->get_result();
    $lastCode = null;
    if($resC->num_rows > 0){
        $rowC = $resC->fetch_assoc();
        $lastCode = $rowC['vendor_code'];
    }
    $stmtC->close();

    if($lastCode){
        $parts = explode('-', $lastCode);
        if(count($parts) >= 3){
            $num = intval(end($parts));
            $newNum = $num + 1;
            $vendor_code = 'VEN-' . $organization_short_code . '-' . str_pad($newNum, 3, '0', STR_PAD_LEFT);
        } else {
            $vendor_code = 'VEN-' . $organization_short_code . '-001';
        }
    } else {
        $vendor_code = 'VEN-' . $organization_short_code . '-001';
    }

    $conn->begin_transaction();

    try {
    // 2. Insert into vendors_listing
        $sql = "INSERT INTO vendors_listing 
                (organization_id, vendor_code, salutation, first_name, last_name, company_name, display_name, email, work_phone, mobile, 
                 vendor_type, vendor_account_type, vendor_language, pan, gst_no, currency,
                 opening_balance, opening_balance_type, payment_terms, status, avatar, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssssssssssdsssss", 
            $organization_id, $vendor_code, $salutation, $first_name, $last_name, $company_name, $display_name, $email, $work_phone, $mobile,
            $vendor_type, $vendor_account_type, $vendor_language, $pan, $gst_no, $currency,
            $opening_balance, $opening_balance_type, $payment_terms, $status, $avatar
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error inserting vendor: " . $stmt->error);
        }
        $vendor_id = $conn->insert_id;
        $stmt->close();


        // 3. Insert Addresses
        // Billing
        // 3. Insert Addresses
        // Billing
        if (!empty($_POST['billing_address_line1']) || !empty($_POST['billing_city'])) {
            $addrSql = "INSERT INTO vendors_addresses 
                (organization_id, vendor_id, address_type, attention, country, address_line1, address_line2, city, state, pin_code, phone, fax, created_at, updated_at)
                VALUES (?, ?, 'billing', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"; // Corrected to 10 ?s
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
                VALUES (?, ?, 'shipping', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"; // Corrected to 10 ?s
            $stmt = $conn->prepare($addrSql);
            $stmt->bind_param("iisssssssss", 
                $organization_id, $vendor_id, $_POST['shipping_attention'], $_POST['shipping_country'], $_POST['shipping_address_line1'],
                $_POST['shipping_address_line2'], $_POST['shipping_city'], $_POST['shipping_state'],
                $_POST['shipping_pin_code'], $_POST['shipping_phone'], $_POST['shipping_fax']
            );
            $stmt->execute();
            $stmt->close();
        }

        // 4. Insert Bank Account
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

        // 5. Insert Contacts
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



        // 8. Insert Remarks
        if (!empty($_POST['remarks'])) {
            $remSql = "INSERT INTO vendors_remarks (organization_id, vendor_id, remarks, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($remSql);
            $stmt->bind_param("iis", $organization_id, $vendor_id, $_POST['remarks']);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        
        if(isset($_POST['ajax']) && $_POST['ajax'] == 'true'){
            echo json_encode([
                'success' => true, 
                'vendor_id' => $vendor_id, 
                'display_name' => $display_name,
                'avatar' => $avatar
            ]);
            exit;
        }

        header("Location: ../../../vendors?success=Vendor added successfully");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        
        if(isset($_POST['ajax']) && $_POST['ajax'] == 'true'){
             error_log("Add Vendor Ajax Error: " . $e->getMessage()); // Debug Log
             http_response_code(500);
             echo json_encode(['success' => false, 'error' => $e->getMessage()]);
             exit;
        }

        header("Location: ../../../vendors?error=" . urlencode($e->getMessage()));
        exit;
    }

} else {
    header("Location: ../../../vendors");
    exit;
}
?>
