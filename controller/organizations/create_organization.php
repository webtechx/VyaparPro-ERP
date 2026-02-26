<?php
require_once '../../config/conn.php';

// Definition of the requested function
function generateOrganizationCode($organization_name, $lastNumber = 0) {
    $prefix = "ORG";
    $namePrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $organization_name), 0, 3));
    // Ensure at least 3 chars if name is very short (though mostly not an issue)
    if(strlen($namePrefix) < 3) $namePrefix = str_pad($namePrefix, 3, 'X'); 

    $year = date('Y');
    $serial = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

    return $prefix . $namePrefix . $year . $serial;
}

function shortCodeExists($code, $conn) {
    if (!$conn) return true; // Safety
    $stmt = $conn->prepare("SELECT COUNT(*) FROM organizations WHERE organization_short_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_row();
    return $row[0] > 0;
}

function generateOrgShortCode($organization_name, $conn) {

    // Clean name
    $clean = preg_replace('/[^A-Za-z ]/', '', $organization_name);
    $words = explode(" ", trim($clean));

    // STEP 1: First 3 letters of first word
    $base = strtoupper(substr($words[0], 0, 3));
    if (!shortCodeExists($base, $conn)) {
        return $base;
    }

    // STEP 2: First 2 letters of first word + first letter of next word
    if (isset($words[1])) {
        $code = strtoupper(substr($words[0], 0, 2) . substr($words[1], 0, 1));
        if (!shortCodeExists($code, $conn)) {
            return $code;
        }
    }

    // STEP 3: First 2 letters + first letter of the next available word
    for ($i = 2; $i < count($words); $i++) {
        $code = strtoupper(substr($words[0], 0, 2) . substr($words[$i], 0, 1));
        if (!shortCodeExists($code, $conn)) {
            return $code;
        }
    }

    // STEP 4 FINAL FALLBACK -> Loop A–Z
    foreach (range('A', 'Z') as $letter) {
        $code = strtoupper(substr($words[0], 0, 2) . $letter);
        if (!shortCodeExists($code, $conn)) {
            return $code;
        }
    }

    return null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Sanitize Inputs
    $org_name = trim($_POST['organization_name']);
    $industry = $_POST['industry'] ?? '';
    $country = $_POST['country'] ?? '';
    $state = $_POST['state'] ?? '';
    $city = $_POST['city'] ?? '';
    $address = $_POST['address'] ?? '';
    $pincode = $_POST['pincode'] ?? '';
    $currency_code = $_POST['currency_code'] ?? 'INR';
    $language = $_POST['language'] ?? 'en';
    $timezone = $_POST['timezone'] ?? 'Asia/Kolkata';
    $gst_registered = Isset($_POST['gst_registered']) ? intval($_POST['gst_registered']) : 0;
    $gst_number = $gst_registered ? trim($_POST['gst_number']) : NULL;

    $bank_name = $_POST['bank_name'] ?? '';
    $account_number = $_POST['account_number'] ?? '';
    $ifsc_code = $_POST['ifsc_code'] ?? '';
    $account_holder_name = $_POST['account_holder_name'] ?? '';
    $branch_name = $_POST['branch_name'] ?? '';
    $upi_id = $_POST['upi_id'] ?? '';

    if (empty($org_name)) {
        header("Location: ../../organizations_register?error=Organization Name is required");
        exit;
    }

    // 2. Generate Code
    $sqlLast = "SELECT MAX(organization_id) as last_id FROM organizations";
    $resLast = $conn->query($sqlLast);
    $lastNumber = 0;
    if ($resLast && $row = $resLast->fetch_assoc()) {
        $lastNumber = intval($row['last_id']);
    }
    
    $org_code = generateOrganizationCode($org_name, $lastNumber);

    // Generate Short Code
    $org_short_code = generateOrgShortCode($org_name, $conn);
    if (!$org_short_code) {
        // Fallback or Error if even A-Z exhausted (unlikely)
         $org_short_code = substr(strtoupper(preg_replace('/[^A-Z]/', '', $org_name)), 0, 3);
    }

    // 3. Handle File Upload
    $logo_sql_val = NULL;
    if (isset($_FILES['organization_logo']) && $_FILES['organization_logo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['organization_logo']['name'];
        $file_size = $_FILES['organization_logo']['size'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Validate Size (500KB)
        if ($file_size > 512000) { // 500 * 1024 = 512,000 bytes
            header("Location: ../../organizations_register?error=Logo size must be less than 500KB");
            exit;
        }

        // Validate Type
        if (!in_array($ext, $allowed)) {
            header("Location: ../../organizations_register?error=Only JPG and PNG files are allowed for logo");
            exit;
        }

        // $org_code is the Organizations Code (e.g. ORGSAM20260006)
        $target_dir = "../../uploads/" . $org_code . "/organization_logo/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Generate unique filename
        $unique_name = uniqid('logo_', true) . '.' . $ext;
        $target_file = $target_dir . $unique_name;
        $source_file = $_FILES['organization_logo']['tmp_name'];
        $saved = false;

        // Compress if size > 200KB (204800 bytes)
        if ($file_size > 204800) { 
            if ($ext == 'jpg' || $ext == 'jpeg') {
                $img = @imagecreatefromjpeg($source_file);
                if ($img) {
                    // Compress to 70% quality (Decent trade-off)
                    $saved = imagejpeg($img, $target_file, 70);
                    imagedestroy($img);
                }
            } elseif ($ext == 'png') {
                $img = @imagecreatefrompng($source_file);
                if ($img) {
                    imagealphablending($img, false);
                    imagesavealpha($img, true);
                    // Max compression 9 (Lossless for PNG, just tighter packing)
                    $saved = imagepng($img, $target_file, 9);
                    imagedestroy($img);
                }
            }
        }

        // If not compressed (small file or GD failed), just move it
        if (!$saved) {
            if (move_uploaded_file($source_file, $target_file)) {
                $saved = true;
            }
        }

        if ($saved) {
            $logo_sql_val = "uploads/" . $org_code . "/organization_logo/" . $unique_name;
        } else {
            header("Location: ../../organizations_register?error=Failed to upload logo file");
            exit;
        }
    }

    // 3.B Handle QR Code Upload
    $qr_sql_val = NULL;
    if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['qr_code']['name'];
        $file_size = $_FILES['qr_code']['size'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if ($file_size > 512000) {
            header("Location: ../../organizations_register?error=QR Image size must be less than 500KB");
            exit;
        }

        if (!in_array($ext, $allowed)) {
            header("Location: ../../organizations_register?error=Only JPG and PNG files are allowed for QR");
            exit;
        }

        $target_dir = "../../uploads/" . $org_code . "/bank_details/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $unique_name = uniqid('qrcode_', true) . '.' . $ext;
        $target_file = $target_dir . $unique_name;
        $source_file = $_FILES['qr_code']['tmp_name'];
        $saved = false;

        if (move_uploaded_file($source_file, $target_file)) {
            $saved = true;
        }

        if ($saved) {
            $qr_sql_val = $unique_name; // just filename
        } else {
            header("Location: ../../organizations_register?error=Failed to upload QR file");
            exit;
        }
    }

    // 4. Insert into Database
    $sql = "INSERT INTO organizations (
        organization_name, organizations_code, organization_short_code, organization_logo, industry, 
        country, state, city, address, pincode, 
        currency_code, language, timezone, 
        gst_registered, gst_number,
        bank_name, account_number, ifsc_code, account_holder_name, branch_name, upi_id, qr_code
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sssssssssssssisssssss", 
            $org_name, $org_code, $org_short_code, $logo_sql_val, $industry,
            $country, $state, $city, $address, $pincode,
            $currency_code, $language, $timezone,
            $gst_registered, $gst_number,
            $bank_name, $account_number, $ifsc_code, $account_holder_name, $branch_name, $upi_id, $qr_sql_val
        );

        if ($stmt->execute()) {
            header("Location: ../../organizations_register?success=Organization Created Successfully. Code: $org_code (Short: $org_short_code)");
        } else {
            header("Location: ../../organizations_register?error=Database Error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        header("Location: ../../organizations_register?error=Prepare Failed: " . $conn->error);
    }

} else {
    // Redirect if accessed directly
    header("Location: ../../organizations_register");
}
exit;
