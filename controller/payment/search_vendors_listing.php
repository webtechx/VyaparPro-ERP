<?php
require_once '../../config/auth_guard.php';

header('Content-Type: application/json');

$q = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

$org_id = $_SESSION['organization_id'];

// Check available columns to avoid errors
$cols = "vendor_id as id, display_name as text, display_name, vendor_code, company_name, email, gst_no, mobile, avatar, current_balance_due";

$sql = "SELECT $cols 
        FROM vendors_listing 
        WHERE organization_id = '$org_id' 
        AND status = 'Active'
        AND (display_name LIKE '%$q%' OR company_name LIKE '%$q%' OR vendor_code LIKE '%$q%' OR mobile LIKE '%$q%') 
        ORDER BY vendor_id DESC 
        LIMIT 20";

if ($result = $conn->query($sql)) {
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Ensure GST is set (it might be null or varying column names)
        $row['gst_number'] = $row['gst_no'];
        
        $data[] = $row;
    }
    echo json_encode($data);
} else {
    // If error, return empty array to avoid breaking select2
    echo json_encode(['error' => $conn->error]);
}
?>
