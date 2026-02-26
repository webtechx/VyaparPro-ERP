<?php
include __DIR__ . '/../../../config/conn.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

$id = (int)$_GET['id'];

// 1. Fetch Employee Data
$sql = "SELECT e.*, r.role_name, d.designation_name, o.organizations_code 
        FROM employees e 
        LEFT JOIN roles_listing r ON e.role_id = r.role_id 
        LEFT JOIN designation_listing d ON e.designation_id = d.designation_id 
        LEFT JOIN organizations o ON e.organization_id = o.organization_id 
        WHERE e.employee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $employee = $result->fetch_assoc();
    
    // Clear sensitive data if needed, but we need password logic
    // We send password_view if it exists, logic handled in frontend to display or not
    // Ideally we shouldn't send password, but user requested 'password_view' column usage

    // 2. Fetch Address
    $addrSql = "SELECT * FROM employee_addresses WHERE employee_id = ?";
    $addrStmt = $conn->prepare($addrSql);
    $addrStmt->bind_param("i", $id);
    $addrStmt->execute();
    $address = $addrStmt->get_result()->fetch_assoc();

    // 3. Fetch Bank
    $bankSql = "SELECT * FROM employee_bank_details WHERE employee_id = ?";
    $bankStmt = $conn->prepare($bankSql);
    $bankStmt->bind_param("i", $id);
    $bankStmt->execute();
    $bank = $bankStmt->get_result()->fetch_assoc();

    echo json_encode([
        'employee' => $employee,
        'address' => $address,
        'bank' => $bank
    ]);

} else {
    echo json_encode(['error' => 'Employee not found']);
}
