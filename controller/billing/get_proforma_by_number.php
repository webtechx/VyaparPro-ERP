<?php
require_once __DIR__ . '/../../config/auth_guard.php';
require_once __DIR__ . '/../../config/conn.php';
header('Content-Type: application/json');

 try {
    $pNo = $_GET['proforma_number'] ?? $_GET['proforma_fetch_no'] ?? null;
    if (!$pNo) {
        throw new Exception('Missing Invoice Number');
    }

$proforma_number = trim($pNo);
$org_id = $_SESSION['organization_id'];


// 1. Fetch Proforma ID using Number
// Assuming `proforma_invoices` table and `proforma_invoice_number` column
$sql = "SELECT proforma_invoice_id FROM proforma_invoices WHERE proforma_invoice_number = ? AND organization_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $proforma_number, $org_id);
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Proforma Invoice not found']);
    exit;
}

$id_row = $res->fetch_assoc();
$invoice_id = $id_row['proforma_invoice_id'];
$res->free(); 

// 2. Fetch Header & Customer & Sales Person
// Explicitly selecting i.sales_employee_id and i.delivery_mode to ensure no joining collisions
$hSql = "SELECT i.*, 
         i.sales_employee_id as sales_emp_id_raw, i.delivery_mode as delivery_mode_raw, i.proforma_invoice_number,
         c.customer_name, c.company_name, c.customer_code, c.email, c.phone, c.avatar, ct.customers_type_name, c.loyalty_point_balance, c.state_code,
         e.first_name as sales_first_name, e.last_name as sales_last_name, e.employee_code as sales_employee_code, e.employee_image as sales_avatar,
         e.primary_email as sales_email, e.primary_phone as sales_phone, d.designation_name as sales_designation
        FROM proforma_invoices i
        LEFT JOIN customers_listing c ON i.customer_id = c.customer_id
        LEFT JOIN customers_type_listing ct ON c.customers_type_id = ct.customers_type_id
        LEFT JOIN employees e ON i.sales_employee_id = e.employee_id
        LEFT JOIN designation_listing d ON e.designation_id = d.designation_id
        WHERE i.proforma_invoice_id = ?";
        
$hStmt = $conn->prepare($hSql);
$hStmt->bind_param("i", $invoice_id);
if(!$hStmt->execute()){
    throw new Exception("Query Failed: " . $hStmt->error);
}
$hRes = $hStmt->get_result();
$invoice = $hRes->fetch_assoc();
$hRes->free();
$hStmt->close();

if(!$invoice) throw new Exception("Proforma details not found (ID: $invoice_id)");

// Check Loyalty Expiry
if (!empty($invoice['customer_id'])) {
    $cid = $invoice['customer_id'];
    $hasValidPoints = false;

    // Check if user has ANY points history first
    $checkHistory = $conn->query("SELECT 1 FROM loyalty_points_earned WHERE customer_id = $cid LIMIT 1");
    if($checkHistory && $checkHistory->num_rows > 0) {
         // Sum up ONLY the points that are NOT expired
         $validSql = "SELECT SUM(points_earned) as valid_total FROM loyalty_points_earned 
                      WHERE customer_id = $cid 
                      AND (valid_till >= CURDATE() OR valid_till IS NULL)";
         
         $validRes = $conn->query($validSql);
         $validTotal = 0;
         if($validRes && $validRes->num_rows > 0){
             $validTotal = floatval($validRes->fetch_assoc()['valid_total']);
         }
         
         // If valid total is 0, zero out the balance
         if($validTotal <= 0) {
             $invoice['loyalty_point_balance'] = 0;
         } else {
             // Limit usable balance
             if(floatval($invoice['loyalty_point_balance']) > $validTotal) {
                 $invoice['loyalty_point_balance'] = $validTotal; 
             }
         }
    }
}

// Fix Sales Avatar Path
if (!empty($invoice['sales_avatar'])) {
    $orgCode = $_SESSION['organization_code'];
    $invoice['sales_avatar'] = "uploads/$orgCode/employees/avatars/" . $invoice['sales_avatar'];
}

if(!$invoice) throw new Exception("Proforma details not found (ID: $invoice_id)");

// 3. Fetch Items
$itemSql = "SELECT pii.*, u.unit_name, h.hsn_code, i.stock_keeping_unit, i.current_stock
            FROM proforma_invoice_items pii
            LEFT JOIN items_listing i ON pii.item_id = i.item_id
            LEFT JOIN units_listing u ON i.unit_id = u.unit_id
            LEFT JOIN hsn_listing h ON i.hsn_id = h.hsn_id
            WHERE pii.proforma_invoice_id = ?";
            
$itemStmt = $conn->prepare($itemSql);
$itemStmt->bind_param("i", $invoice_id);
$itemStmt->execute();
$itemResult = $itemStmt->get_result();

$items = [];
while ($row = $itemResult->fetch_assoc()) {
    $items[] = $row;
}
$itemResult->free();
$itemStmt->close();

    echo json_encode([
        'success' => true,
        'invoice' => $invoice,
        'items' => $items
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
