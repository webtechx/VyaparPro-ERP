<?php
session_start();
include __DIR__ . '/../../../config/conn.php';

if (isset($_POST['add_employee'])) {
    // 1. Collect Primary Data
    $organization_id = (int)($_SESSION['organization_id'] ?? 0);
    
    // STRICT VALIDATION for Organization ID
    if ($organization_id <= 0) {
        header("Location: ../../../employees?error=Organization context missing. Please login again.");
        exit;
    }

    $salutation = $_POST['salutation'] ?? '';
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $name = $first_name . ' ' . $last_name; 
    $primary_email = trim($_POST['primary_email']);
    $department_id = (int)$_POST['department_id'];
    $role_id = (int)$_POST['role_id'];
    $designation_id = isset($_POST['designation_id']) && $_POST['designation_id'] !== '' ? (int)$_POST['designation_id'] : NULL;
    

        $orgStmt = $conn->prepare("
            SELECT organization_short_code , organizations_code
            FROM organizations 
            WHERE organization_id = ?
        ");
        $orgStmt->bind_param("i", $organization_id);
        $orgStmt->execute();
        $orgData = $orgStmt->get_result()->fetch_assoc();

    // STRICT CHECK for organization existence/code
    if(!$orgData || empty($orgData['organization_short_code'])) {
        header("Location: ../../../employees?error=Invalid Organization or Short Code missing.");
        exit;
    }

        $org_short_code = $orgData['organization_short_code']; 
        $org_code = $orgData['organizations_code']; 

        $empStmt = $conn->prepare("
            SELECT employee_code 
            FROM employees 
            WHERE employee_code LIKE ?
            ORDER BY employee_id DESC 
            LIMIT 1
        ");
        $like = "EMP-$org_short_code-%";
        $empStmt->bind_param("s", $like);
        $empStmt->execute();
        $lastEmp = $empStmt->get_result()->fetch_assoc();

        $next_serial = 1;
        if ($lastEmp) {
            $parts = explode('-', $lastEmp['employee_code']);
            if(isset($parts[2])) {
                $next_serial = intval($parts[2]) + 1;
            }
        }

        $employee_code = "EMP-$org_short_code-" . str_pad($next_serial, 4, '0', STR_PAD_LEFT);


    $enrollment_type = $_POST['enrollment_type'] ?? '';
    $employment_status = $_POST['employment_status'] ?? 'Joined';
    
    // Login Details - Auto Generated
    $primary_email = trim($_POST['primary_email']);
    $user_name = $primary_email; // Use email as username by default since field is removed
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Personal
    $primary_phone = $_POST['primary_phone'] ?? '';
    $alternate_email = $_POST['alternate_email'] ?? '';
    $alternate_phone = $_POST['alternate_phone'] ?? '';
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
    
    // Validate Mandatory
    if (empty($first_name) || empty($primary_email) || empty($role_id) || empty($department_id) || empty($dob)) {
        header("Location: ../../../employees?error=Missing required fields (Name, Email, Department, Role, DOB)");
        exit;
    }

    // Default Password Logic: DOB (ddmmyyyy)
    // format YYYY-MM-DD -> ddmmyyyy
    $password = '';
    if ($dob) {
        $timestamp = strtotime($dob);
        $password = date('dmY', $timestamp);
    }
    
    // Hash Password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Check Duplicates (Email)
    $check = $conn->prepare("SELECT employee_id FROM employees WHERE primary_email = ?");
    $check->bind_param("s", $primary_email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        header("Location: ../../../employees?error=Email already exists");
        exit;
    }

    // Image Upload
    // Image Upload
    $employee_image = '';
    if (!empty($_FILES['employee_image']['name'])) {
        $uploadDir = "../../../uploads/$org_code/employees/avatars/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        // Sanitize code for filename
        $safeCode = preg_replace('/[^A-Za-z0-9]/', '', $employee_code);
        $ext = pathinfo($_FILES['employee_image']['name'], PATHINFO_EXTENSION);
        $fileName = $safeCode . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['employee_image']['tmp_name'], $uploadDir . $fileName)) {
            $employee_image = $fileName;
        }
    }

    // Document Attachment Upload
    $document_attachment = '';
    if (!empty($_FILES['document_attachment']['name'])) {
        $docUploadDir = "../../../uploads/$org_code/employees/docs/";
        if (!is_dir($docUploadDir)) mkdir($docUploadDir, 0777, true);
        
        // Sanitize code for filename
        $safeCode = preg_replace('/[^A-Za-z0-9]/', '', $employee_code);
        $ext = pathinfo($_FILES['document_attachment']['name'], PATHINFO_EXTENSION);
        $docFileName = $safeCode . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['document_attachment']['tmp_name'], $docUploadDir . $docFileName)) {
            $document_attachment = $docFileName;
        }   
    }

    // 2. Insert into `employees`
    $sql = "INSERT INTO employees (
        organization_id, department_id, role_id, designation_id, employee_code, salutation, first_name, last_name, 
        primary_email, alternate_email, primary_phone, alternate_phone, 
        date_of_birth, joined_on, gender, father_name, mother_name, 
        pan, aadhar, voter_id, emergency_contact_name, emergency_contact_phone, 
        enrollment_type, employment_status, notes, employee_image, 
        ref_phone_no, blood_group, document_attachment,
        password, password_view, is_active, created_at
    ) VALUES (?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // Output error to help debugging (in production logs would be better)
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    
    // Bind Params
    // Total 32 params: 4 integers (org, dept, role, desig), 27 strings, 1 integer (is_active)
    // "iiii" + 27 "s" + "i"
    $bindParamsType = "iiii" . str_repeat("s", 27) . "i";
    $stmt->bind_param(
        $bindParamsType,
        $organization_id, $department_id, $role_id, $designation_id, $employee_code, $salutation, $first_name, $last_name,
        $primary_email, $alternate_email, $primary_phone, $alternate_phone,
        $dob, $joined_on, $gender, $father_name, $mother_name,
        $pan, $aadhar, $voter_id, $ec_name, $ec_phone,
        $enrollment_type, $employment_status, $notes, $employee_image,
        $ref_phone_no, $blood_group, $document_attachment,
        $password_hash, $password, $is_active
    );

    if ($stmt->execute()) {
        $employee_id = $conn->insert_id;

        // 3. Insert Addresses
        // Current
        $c_street = $_POST['current_street'] ?? '';
        $c_city = $_POST['current_city'] ?? '';
        $c_dist = $_POST['current_district'] ?? '';
        $c_state = $_POST['current_state'] ?? '';
        $c_pin = $_POST['current_pincode'] ?? '';
        $c_country = $_POST['current_country'] ?? '';
        
        // Permanent
        $p_street = $_POST['permanent_street'] ?? '';
        $p_city = $_POST['permanent_city'] ?? '';
        $p_dist = $_POST['permanent_district'] ?? '';
        $p_state = $_POST['permanent_state'] ?? '';
        $p_pin = $_POST['permanent_pincode'] ?? '';
        $p_country = $_POST['permanent_country'] ?? '';

        if($c_street || $p_street) {
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

        // 4. Insert Bank Details
        $bank_name = $_POST['bank_name'] ?? '';
        $branch = $_POST['branch_name'] ?? '';
        $ifsc = $_POST['ifsc_code'] ?? '';
        $acc_no = $_POST['account_number'] ?? '';
        $acc_type = $_POST['account_type'] ?? '';

        if($bank_name || $acc_no) {
            $bankSql = "INSERT INTO employee_bank_details (
                employee_id, bank_name, branch_name, ifsc_code, account_number, account_type
            ) VALUES (?, ?, ?, ?, ?, ?)";
            $bankStmt = $conn->prepare($bankSql);
            $bankStmt->bind_param("isssss", $employee_id, $bank_name, $branch, $ifsc, $acc_no, $acc_type);
            $bankStmt->execute();
        }

        header("Location: ../../../employees?success=Employee added successfully");
    } else {
        header("Location: ../../../employees?error=Database Error: " . $stmt->error);
    }

} else {
    header("Location: ../../../employees");
}
