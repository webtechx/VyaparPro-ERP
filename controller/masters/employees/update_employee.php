<?php
session_start();
include __DIR__ . '/../../../config/conn.php';

if (isset($_POST['update_employee'])) {

    $org_code = $_SESSION['organization_code'] ?? '';
    $organization_id = $_SESSION['organization_id'] ?? 0;

    if(empty($org_code) && $organization_id > 0) {
        $orgQ = $conn->query("SELECT organizations_code FROM organizations WHERE organization_id = $organization_id");
        if($orgQ && $orgR = $orgQ->fetch_assoc()){
            $org_code = $orgR['organizations_code'];
            $_SESSION['organization_code'] = $org_code; // Update session
        }
    }

    if(empty($org_code)){
         // Fallback or Error
         header("Location: ../../../employees?error=Organization Context Missing");
         exit;
    } 

    $employee_id = (int)$_POST['employee_id'];
    if(!$employee_id) {
        header("Location: ../../../employees?error=Invalid ID");
        exit;
    }

    // 1. Collect Primary Data
    $salutation = $_POST['salutation'] ?? '';
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $primary_email = trim($_POST['primary_email']);
    $department_id = (int)$_POST['department_id'];
    $role_id = (int)$_POST['role_id'];
    $designation_id = isset($_POST['designation_id']) && $_POST['designation_id'] !== '' ? (int)$_POST['designation_id'] : NULL;

    $employee_code = $_POST['employee_code'] ?? ''; 
    if(empty($employee_code)) {
         // Fallback fetch if missing
         $codeQ = $conn->query("SELECT employee_code FROM employees WHERE employee_id=$employee_id");
         $employee_code = ($codeQ && $r = $codeQ->fetch_assoc()) ? $r['employee_code'] : 'UNK';
    }

    $enrollment_type = $_POST['enrollment_type'] ?? '';
    $employment_status = $_POST['employment_status'] ?? 'Joined';
    // On update, we can sync username with email or leave it. Let's sync it.
    $user_name = $primary_email;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Personal
    $primary_phone = $_POST['primary_phone'] ?? '';
    $alternate_phone = $_POST['alternate_phone'] ?? '';
    $alternate_email = $_POST['alternate_email'] ?? '';
    $dob = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : NULL;
    $joined_on = !empty($_POST['joined_on']) ? $_POST['joined_on'] : NULL;
    $gender = $_POST['gender'] ?? '';
    $father_name = $_POST['father_name'] ?? '';
    $mother_name = $_POST['mother_name'] ?? '';
    $pan = $_POST['pan'] ?? '';
    $aadhar = $_POST['aadhar'] ?? '';
    $voter_id = $_POST['voter_id'] ?? '';
    $ec_name = $_POST['emergency_contact_name'] ?? '';
    $ec_phone = $_POST['emergency_contact_phone'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // New Fields
    $ref_phone_no = $_POST['ref_phone_no'] ?? '';
    $blood_group = $_POST['blood_group'] ?? '';

    // Handle Password
    $password_sql = "";
    $password_hash = ""; 
    $password_view = "";

    // Password Update Logic (Optional if sent via hidden means or future use)
    if (!empty($_POST['password'])) {
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $password_view = $_POST['password'];
        $password_sql = ", password=?, password_view=?";
    }

    // Image Update Logic
    $image_sql = "";
    $fileName = "";
    if (!empty($_FILES['employee_image']['name'])) {
        $uploadDir = "../../../uploads/$org_code/employees/avatars/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        // Sanitize code for filename
        $safeCode = preg_replace('/[^A-Za-z0-9]/', '', $employee_code);
        $ext = pathinfo($_FILES['employee_image']['name'], PATHINFO_EXTENSION);
        $fileName = $safeCode . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['employee_image']['tmp_name'], $uploadDir . $fileName)) {
            // Unlink old
            $oldQ = $conn->query("SELECT employee_image FROM employees WHERE employee_id=$employee_id");
            if($r = $oldQ->fetch_assoc()){
                if($r['employee_image'] && file_exists($uploadDir.$r['employee_image'])){
                    unlink($uploadDir.$r['employee_image']);
                }
            }
            $image_sql = ", employee_image=?";
        }
    }

    // Document Attachment Update Logic
    $doc_sql = "";
    $docFileName = "";
    if (!empty($_FILES['document_attachment']['name'])) {
        $docUploadDir = "../../../uploads/$org_code/employees/docs/";
        if (!is_dir($docUploadDir)) mkdir($docUploadDir, 0777, true);
        
        // Sanitize code for filename
        $safeCode = preg_replace('/[^A-Za-z0-9]/', '', $employee_code);
        $ext = pathinfo($_FILES['document_attachment']['name'], PATHINFO_EXTENSION);
        $docFileName = $safeCode . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['document_attachment']['tmp_name'], $docUploadDir . $docFileName)) {
            // Unlink old
            $oldDocQ = $conn->query("SELECT document_attachment FROM employees WHERE employee_id=$employee_id");
            if($dr = $oldDocQ->fetch_assoc()){
                if($dr['document_attachment'] && file_exists($docUploadDir.$dr['document_attachment'])){
                    unlink($docUploadDir.$dr['document_attachment']);
                }
            }
            $doc_sql = ", document_attachment=?";
        }
    }

    // Prepare SQL
    $sql = "UPDATE employees SET 
        department_id=?, role_id=?, designation_id=?, salutation=?, first_name=?, last_name=?, 
        primary_email=?, alternate_email=?, primary_phone=?, alternate_phone=?, 
        date_of_birth=?, joined_on=?, gender=?, father_name=?, mother_name=?, 
        pan=?, aadhar=?, voter_id=?, emergency_contact_name=?, emergency_contact_phone=?, 
        enrollment_type=?, employment_status=?, 
        is_active=?, notes=?, 
        ref_phone_no=?, blood_group=?,
        updated_at=NOW()
        $password_sql
        $image_sql
        $doc_sql
        WHERE employee_id=?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind Params
    // Base params count: 27.
    // 2(dept, role=i) + 21(middle words=s) + 1(is_active=i) + 1(notes=s) + 2(new fields=s) = 27 params
    // Then we append password/image/doc params and finally ID(i).
    $bindTypes = "ii" . str_repeat("s", 20) . "isss"; 
    
    // We used references for bind_param compatibility
    $bindParams = [];
    $bindParams[] = &$department_id;
    $bindParams[] = &$role_id;
    $bindParams[] = &$designation_id;
    $bindParams[] = &$salutation;
    $bindParams[] = &$first_name;
    $bindParams[] = &$last_name;
    $bindParams[] = &$primary_email;
    $bindParams[] = &$alternate_email;
    $bindParams[] = &$primary_phone;
    $bindParams[] = &$alternate_phone;
    $bindParams[] = &$dob;
    $bindParams[] = &$joined_on;
    $bindParams[] = &$gender;
    $bindParams[] = &$father_name;
    $bindParams[] = &$mother_name;
    $bindParams[] = &$pan;
    $bindParams[] = &$aadhar;
    $bindParams[] = &$voter_id;
    $bindParams[] = &$ec_name;
    $bindParams[] = &$ec_phone;
    $bindParams[] = &$enrollment_type;
    $bindParams[] = &$employment_status;
    $bindParams[] = &$is_active;
    $bindParams[] = &$notes;
    // New Fields
    $bindParams[] = &$ref_phone_no;
    $bindParams[] = &$blood_group;

    if(!empty($_POST['password'])){
        $bindTypes .= "ss";
        $bindParams[] = &$password_hash;
        $bindParams[] = &$password_view;
    }
    
    if($image_sql){
        $bindTypes .= "s";
        $bindParams[] = &$fileName;
    }

    if($doc_sql){
        $bindTypes .= "s";
        $bindParams[] = &$docFileName;
    }

    // Add ID at end
    $bindTypes .= "i";
    $bindParams[] = &$employee_id;

    $stmt->bind_param($bindTypes, ...$bindParams);
    
    if ($stmt->execute()) {
        
        // 3. Update Addresses
        $c_street = $_POST['current_street'] ?? '';
        $c_city = $_POST['current_city'] ?? '';
        $c_dist = $_POST['current_district'] ?? '';
        $c_state = $_POST['current_state'] ?? '';
        $c_pin = $_POST['current_pincode'] ?? '';
        $c_country = $_POST['current_country'] ?? '';
        
        $p_street = $_POST['permanent_street'] ?? '';
        $p_city = $_POST['permanent_city'] ?? '';
        $p_dist = $_POST['permanent_district'] ?? '';
        $p_state = $_POST['permanent_state'] ?? '';
        $p_pin = $_POST['permanent_pincode'] ?? '';
        $p_country = $_POST['permanent_country'] ?? '';

        // Check if exists
        $chkAddr = $conn->query("SELECT employee_addresses_id FROM employee_addresses WHERE employee_id=$employee_id");
        if ($chkAddr->num_rows > 0) {
            $addrSql = "UPDATE employee_addresses SET 
                current_street=?, current_city=?, current_district=?, current_state=?, current_pincode=?, current_country=?,
                permanent_street=?, permanent_city=?, permanent_district=?, permanent_state=?, permanent_pincode=?, permanent_country=?
                WHERE employee_id=?";
            $addrStmt = $conn->prepare($addrSql);
            $addrStmt->bind_param("ssssssssssssi", 
                $c_street, $c_city, $c_dist, $c_state, $c_pin, $c_country,
                $p_street, $p_city, $p_dist, $p_state, $p_pin, $p_country,
                $employee_id
            );
            $addrStmt->execute();
        } else {
             $addrSql = "INSERT INTO employee_addresses (
                employee_id, 
                current_street, current_city, current_district, current_state, current_pincode, current_country,
                permanent_street, permanent_city, permanent_district, permanent_state, permanent_pincode, permanent_country
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $addrStmt = $conn->prepare($addrSql);
            $addrStmt->bind_param("issssssssssss", 
                $employee_id, 
                $c_street, $c_city, $c_dist, $c_state, $c_pin, $c_country,
                $p_street, $p_city, $p_dist, $p_state, $p_pin, $p_country
            );
            $addrStmt->execute();
        }

        // 4. Update Bank Details
        $bank_name = $_POST['bank_name'] ?? '';
        $branch = $_POST['branch_name'] ?? '';
        $ifsc = $_POST['ifsc_code'] ?? '';
        $acc_no = $_POST['account_number'] ?? '';
        $acc_type = $_POST['account_type'] ?? '';

        $chkBank = $conn->query("SELECT id FROM employee_bank_details WHERE employee_id=$employee_id");
        if ($chkBank->num_rows > 0) {
            $bankSql = "UPDATE employee_bank_details SET bank_name=?, branch_name=?, ifsc_code=?, account_number=?, account_type=? WHERE employee_id=?";
            $bankStmt = $conn->prepare($bankSql);
            $bankStmt->bind_param("sssssi", $bank_name, $branch, $ifsc, $acc_no, $acc_type, $employee_id);
            $bankStmt->execute();
        } else {
             $bankSql = "INSERT INTO employee_bank_details (employee_id, bank_name, branch_name, ifsc_code, account_number, account_type) VALUES (?, ?, ?, ?, ?, ?)";
             $bankStmt = $conn->prepare($bankSql);
             $bankStmt->bind_param("isssss", $employee_id, $bank_name, $branch, $ifsc, $acc_no, $acc_type);
             $bankStmt->execute();
        }

        header("Location: ../../../employees?success=Employee updated successfully");
    } else {
        header("Location: ../../../employees?error=Database Error: " . $stmt->error);
    }
} else {
     header("Location: ../../../employees");
}
