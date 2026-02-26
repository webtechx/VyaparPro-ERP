<?php
require_once '../../config/auth_guard.php';

header('Content-Type: application/json');

$q = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

$org_id = $_SESSION['organization_id'];


// Real Database Query
$sql = "SELECT customer_id as id, cl.customers_type_id, customer_name, customer_code, customers_type_name, company_name, email, phone, gst_number, loyalty_point_balance, avatar, state_code, current_balance_due,
        CONCAT(customer_name, ' (', customer_code, ')') as text 
        FROM customers_listing cl
        LEFT JOIN customers_type_listing ctl ON cl.customers_type_id = ctl.customers_type_id
        WHERE cl.organization_id = '$org_id' 
        AND (cl.customer_name LIKE '%$q%' OR cl.company_name LIKE '%$q%' OR cl.customer_code LIKE '%$q%' OR cl.phone LIKE '%$q%') 
        ORDER BY customer_id DESC 
        LIMIT 20";

if ($result = $conn->query($sql)) {
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['display_name'] = $row['company_name'] ? $row['company_name'] . ' (' . $row['customer_code'] . ' - ' . $row['customers_type_name'] . ')' : $row['customer_code'];
        
        // Check for Loyalty Point Expiry
        $cid = $row['id'];
        
        // 1. Check if user has any points history
        $checkHistory = $conn->query("SELECT 1 FROM loyalty_points_earned WHERE customer_id = $cid LIMIT 1");
        
        if($checkHistory && $checkHistory->num_rows > 0) {
             // 2. Sum up ONLY the points that are NOT expired
             $validSql = "SELECT SUM(points_earned) as valid_total FROM loyalty_points_earned 
                          WHERE customer_id = $cid 
                          AND (valid_till >= CURDATE() OR valid_till IS NULL)";
             
             $validRes = $conn->query($validSql);
             $validTotal = 0;
             if($validRes && $validRes->num_rows > 0){
                 $validTotal = floatval($validRes->fetch_assoc()['valid_total']);
             }
             
             // If valid total is 0 (meaning all earned points are expired), hide the option
             if($validTotal <= 0) {
                 $row['loyalty_point_balance'] = 0;
             } else {
                 // Optional: Limit the usable balance to the valid total
                 if(floatval($row['loyalty_point_balance']) > $validTotal) {
                     $row['loyalty_point_balance'] = $validTotal; 
                 }
             }
        }
        
        $data[] = $row;
    }
    echo json_encode($data);
} else {
    echo json_encode(['error' => $conn->error]);
}
?>
