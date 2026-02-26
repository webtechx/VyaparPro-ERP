<?php
require_once __DIR__ . '/../../config/auth_guard.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing ID']);
    exit;
}

$invoice_id = intval($_GET['id']);
$org_id = $_SESSION['organization_id'];

// 1. Fetch Invoice Header with Customer Details
$sql = "SELECT i.*, c.customer_name, c.company_name, c.customer_code, c.email, c.phone, c.avatar, ct.customers_type_name,
        e.first_name as sales_first_name, e.last_name as sales_last_name, e.employee_code as sales_employee_code, e.employee_image as sales_avatar,
        creator.first_name as creator_first_name, creator.last_name as creator_last_name
        FROM proforma_invoices i
        LEFT JOIN customers_listing c ON i.customer_id = c.customer_id
        LEFT JOIN customers_type_listing ct ON c.customers_type_id = ct.customers_type_id
        LEFT JOIN employees e ON i.sales_employee_id = e.employee_id
        LEFT JOIN employees creator ON i.make_employee_id = creator.employee_id
        WHERE i.proforma_invoice_id = ? AND i.organization_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $invoice_id, $org_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Invoice not found']);
    exit;
}

$invoice = $result->fetch_assoc();
$stmt->close();

// Fix Sales Avatar Path
if (!empty($invoice['sales_avatar'])) {
    $orgCode = $_SESSION['organization_code'];
    $invoice['sales_avatar'] = "uploads/$orgCode/employees/avatars/" . $invoice['sales_avatar'];
}

// 2. Fetch Invoice Items
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
$itemStmt->close();

echo json_encode([
    'success' => true,
    'invoice' => $invoice,
    'items' => $items
]);
?>
