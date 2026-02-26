<?php
require_once '../../../config/conn.php';

header('Content-Type: application/json');

// Fetch the last employee code
$sql = "SELECT employee_code FROM employees ORDER BY employee_id DESC LIMIT 1";
$result = $conn->query($sql);

$lastCode = 'EMP-0000';

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (!empty($row['employee_code'])) {
        $lastCode = $row['employee_code'];
    }
}

// Extract number and increment
if (preg_match('/EMP-(\d+)/', $lastCode, $matches)) {
    $num = intval($matches[1]) + 1;
    $newCode = 'EMP-' . str_pad($num, 4, '0', STR_PAD_LEFT);
} else {
    // If pattern doesn't match or first record, start with 0001
    $newCode = 'EMP-0001';
}

echo json_encode(['success' => true, 'new_code' => $newCode]);
?>
