<?php
require_once __DIR__ . '/../../config/auth_guard.php';
header('Content-Type: application/json');

$orgId = (int)($_SESSION['organization_id'] ?? 0);
if ($orgId <= 0) die(json_encode(['error' => 'No org ID']));

$search = $conn->real_escape_string($_GET['q'] ?? '');

$sql = "SELECT e.employee_id, e.first_name, e.last_name, e.employee_code, e.primary_email, e.primary_phone, e.employee_image, o.organizations_code, d.designation_name 
        FROM employees e
        JOIN organizations o ON e.organization_id = o.organization_id
        LEFT JOIN designation_listing d ON e.designation_id = d.designation_id
        WHERE e.organization_id = $orgId 
        AND e.is_active = 1 
        AND (
            e.first_name LIKE '%$search%' OR 
            e.last_name LIKE '%$search%' OR 
            e.employee_code LIKE '%$search%' OR
            e.primary_email LIKE '%$search%' OR
            e.primary_phone LIKE '%$search%'
        )
        LIMIT 50";

$result = $conn->query($sql) or die(json_encode(['error' => $conn->error]));

$response = [];
while ($row = $result->fetch_assoc()) {
    $avatarPath = '';
    if (!empty($row['employee_image'])) {
        $avatarPath = "uploads/" . $row['organizations_code'] . "/employees/avatars/" . $row['employee_image'];
    }

    $response[] = [
        'id'           => $row['employee_id'],
        'text'         => trim($row['first_name'] . ' ' . $row['last_name']),
        'employee_code'=> $row['employee_code'] ?? '',
        'email'        => $row['primary_email'] ?? '',
        'phone'        => $row['primary_phone'] ?? '',
        'designation'  => $row['designation_name'] ?? '',
        'avatar'       => $avatarPath
    ];
}

echo json_encode($response);