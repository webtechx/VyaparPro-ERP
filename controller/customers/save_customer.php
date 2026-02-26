<?php
include __DIR__ . '/../../config/auth_guard.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get Organization ID
    $organization_id = $_SESSION['organization_id'];

    $action = $_POST['action'] ?? 'add';
    $customer_id = intval($_POST['customer_id'] ?? 0);
    
    $customer_name = $_POST['customer_name'] ?? '';
    // Basic validation
    if (empty($customer_name)) {
        echo json_encode(['success' => false, 'message' => 'Customer Name is required']);
        exit;
    }

    $company_name = $_POST['company_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $gst_number = $_POST['gst_number'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $pincode = $_POST['pincode'] ?? '';
    // $opening_balance removed
    $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : NULL;
    $anniversary_date = !empty($_POST['anniversary_date']) ? $_POST['anniversary_date'] : NULL;

    // New Address Fields
    $state_code = $_POST['state_code'] ?? '';
    $shipping_address = $_POST['shipping_address'] ?? '';
    $shipping_city = $_POST['shipping_city'] ?? '';
    $shipping_state = $_POST['shipping_state'] ?? '';
    $shipping_state_code = $_POST['shipping_state_code'] ?? '';
    $shipping_pincode = $_POST['shipping_pincode'] ?? '';

    // AVATAR HANDLING (Keep existing logic)
    $avatar_path = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['avatar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $orgQ = $conn->query("SELECT organizations_code FROM organizations WHERE organization_id=$organization_id");
            $orgRow = $orgQ->fetch_assoc();
            $org_code = $orgRow['organizations_code'];

            $uploadDir = "../../uploads/$org_code/customer_avatars/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $newFilename = "cust_" . uniqid() . "." . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $newFilename)) {
                $avatar_path = "uploads/$org_code/customer_avatars/" . $newFilename;
            }
        }
    }


    if ($action === 'add') {
        // Fetch Org Short Code
        $orgSql = "SELECT organization_short_code FROM organizations WHERE organization_id = ?";
        $stmtOrg = $conn->prepare($orgSql);
        $stmtOrg->bind_param("i", $organization_id);
        $stmtOrg->execute();
        $resOrg = $stmtOrg->get_result();
        $organization_short_code = 'ORG'; 
        if($resOrg->num_rows > 0){
            $organization_short_code = $resOrg->fetch_assoc()['organization_short_code'];
        }
        $stmtOrg->close();

        // Generate Code
        $cCodeSql = "SELECT customer_code FROM customers_listing WHERE organization_id = ? ORDER BY customer_id DESC LIMIT 1";
        $stmtC = $conn->prepare($cCodeSql);
        $stmtC->bind_param("i", $organization_id);
        $stmtC->execute();
        $resC = $stmtC->get_result();
        $lastCode = null;
        if($resC->num_rows > 0){
            $rowC = $resC->fetch_assoc();
            $lastCode = $rowC['customer_code'];
        }
        $stmtC->close();

        if($lastCode){
            $parts = explode('-', $lastCode);
            if(count($parts) >= 3){
                $num = intval(end($parts));
                $newNum = $num + 1;
                $customer_code = 'CUS-' . $organization_short_code . '-' . str_pad($newNum, 3, '0', STR_PAD_LEFT);
            } else {
                $customer_code = 'CUS-' . $organization_short_code . '-001';
            }
        } else {
            $customer_code = 'CUS-' . $organization_short_code . '-001';
        }

        // Check duplicates
        $checkConditions = [];
        $checkParams = ["i", $organization_id];
        $checkTypes = "i";

        if (!empty($phone)) {
            $checkConditions[] = "phone = ?";
            $checkParams[] = $phone;
            $checkTypes .= "s";
        }
        if (!empty($gst_number)) {
            $checkConditions[] = "gst_number = ?";
            $checkParams[] = $gst_number;
            $checkTypes .= "s";
        }
        if (!empty($email)) {
            $checkConditions[] = "email = ?";
            $checkParams[] = $email;
            $checkTypes .= "s";
        }

        if (!empty($checkConditions)) {
            $checkQuery = "SELECT customer_id FROM customers_listing WHERE organization_id = ? AND (" . implode(" OR ", $checkConditions) . ")";
            $check_stmt = $conn->prepare($checkQuery);
            $check_stmt->bind_param($checkTypes, ...array_slice($checkParams, 1));
            $check_stmt->execute();
            $check_stmt->store_result();
            if ($check_stmt->num_rows > 0) {
                 echo json_encode(['success' => false, 'message' => 'Customer with this Phone, GST, or Email already exists']);
                 exit;
            }
            $check_stmt->close();
        }
        
        $customers_type_id = $_POST['customers_type_id'] ?? 1;

        $stmt = $conn->prepare("INSERT INTO customers_listing (organization_id, customer_code, customers_type_id, customer_name, company_name, email, phone, gst_number, address, city, state, state_code, pincode, shipping_address, shipping_city, shipping_state, shipping_state_code, shipping_pincode, avatar, date_of_birth, anniversary_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database Prepare Error (Add): ' . $conn->error]);
            exit;
        }
        // Params: 21
        // String: isissssssssssssssssss
        $stmt->bind_param("isissssssssssssssssss", $organization_id, $customer_code, $customers_type_id, $customer_name, $company_name, $email, $phone, $gst_number, $address, $city, $state, $state_code, $pincode, $shipping_address, $shipping_city, $shipping_state, $shipping_state_code, $shipping_pincode, $avatar_path, $date_of_birth, $anniversary_date);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Customer added successfully', 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database Error: ' . $stmt->error]);
        }
        $stmt->close();
    } elseif ($action === 'update') {
        if ($customer_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid Customer ID']);
            exit;
        }

        $customers_type_id = $_POST['customers_type_id'] ?? 1;

        // Check duplicates excluding self
        $checkConditions = [];
        $checkParams = ["ii", $organization_id, $customer_id];
        $checkTypes = "ii";
 
        if (!empty($phone)) {
             $checkConditions[] = "phone = ?";
             $checkParams[] = $phone;
             $checkTypes .= "s";
        }
        if (!empty($gst_number)) {
             $checkConditions[] = "gst_number = ?";
             $checkParams[] = $gst_number;
             $checkTypes .= "s";
        }
        if (!empty($email)) {
             $checkConditions[] = "email = ?";
             $checkParams[] = $email;
             $checkTypes .= "s";
        }
 
        if (!empty($checkConditions)) {
             $checkQuery = "SELECT customer_id FROM customers_listing WHERE organization_id = ? AND customer_id != ? AND (" . implode(" OR ", $checkConditions) . ")";
             $check_stmt = $conn->prepare($checkQuery);
             $check_stmt->bind_param($checkTypes, ...array_slice($checkParams, 1));
             
             $check_stmt->execute();
             $check_stmt->store_result();
             if ($check_stmt->num_rows > 0) {
                  // If update fails, del loaded avatar
                  if($avatar_path && file_exists("../../".$avatar_path)) unlink("../../".$avatar_path);

                  echo json_encode(['success' => false, 'message' => 'Customer with this Phone, GST, or Email already exists']);
                  exit;
             }
             $check_stmt->close();
        }

        if ($avatar_path) {
            // Unlink old
            $oldQ = $conn->query("SELECT avatar FROM customers_listing WHERE customer_id=$customer_id");
            if($oldQ && $oldQ->num_rows > 0){
                $oldRow = $oldQ->fetch_assoc();
                if(!empty($oldRow['avatar']) && file_exists("../../".$oldRow['avatar'])){
                    unlink("../../".$oldRow['avatar']);
                }
            }

            // Update with avatar
            $stmt = $conn->prepare("UPDATE customers_listing SET customers_type_id=?, customer_name=?, company_name=?, email=?, phone=?, gst_number=?, address=?, city=?, state=?, state_code=?, pincode=?, shipping_address=?, shipping_city=?, shipping_state=?, shipping_state_code=?, shipping_pincode=?, avatar=?, date_of_birth=?, anniversary_date=? WHERE customer_id=? AND organization_id=?");
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database Prepare Error (Update w/ Avatar): ' . $conn->error]);
                exit;
            }
            // Params: 21
            // String: issssssssssssssssiii
            $stmt->bind_param("issssssssssssssssssii", $customers_type_id, $customer_name, $company_name, $email, $phone, $gst_number, $address, $city, $state, $state_code, $pincode, $shipping_address, $shipping_city, $shipping_state, $shipping_state_code, $shipping_pincode, $avatar_path, $date_of_birth, $anniversary_date, $customer_id, $organization_id);
        } else {
            // Update without avatar (keep existing)
            $stmt = $conn->prepare("UPDATE customers_listing SET customers_type_id=?, customer_name=?, company_name=?, email=?, phone=?, gst_number=?, address=?, city=?, state=?, state_code=?, pincode=?, shipping_address=?, shipping_city=?, shipping_state=?, shipping_state_code=?, shipping_pincode=?, date_of_birth=?, anniversary_date=? WHERE customer_id=? AND organization_id=?");
             if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database Prepare Error (Update): ' . $conn->error]);
                exit;
            }
            // Params: 20
            // String: issssssssssssssssisii -> isssssssssssssssssii
            $stmt->bind_param("isssssssssssssssssii", $customers_type_id, $customer_name, $company_name, $email, $phone, $gst_number, $address, $city, $state, $state_code, $pincode, $shipping_address, $shipping_city, $shipping_state, $shipping_state_code, $shipping_pincode, $date_of_birth, $anniversary_date, $customer_id, $organization_id);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Customer updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database Error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
