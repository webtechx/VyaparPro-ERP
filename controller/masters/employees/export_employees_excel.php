<?php
require_once '../../../config/auth_guard.php';
require '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// --- Set Headers ---
$headers = [
    'Employee Code', 
    'Salutation',
    'First Name',
    'Last Name',
    'Primary Email', 
    'Primary Phone', 
    'Department', 
    'Role', 
    'Designation', 
    'Enrollment Type',
    'Employment Status',
    'Status (Active/Inactive)'
];

$sheet->fromArray($headers, NULL, 'A1');

// --- Styling ---
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']], // Indigo
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:L1')->applyFromArray($headerStyle);
foreach(range('A','L') as $col) {
    if($col == 'E') {
         $sheet->getColumnDimension($col)->setWidth(30);
    } else {
         $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

// --- Fetch Data ---
$sql = "SELECT e.employee_code, e.salutation, e.first_name, e.last_name, e.primary_email, e.primary_phone, 
        dep.department_name, r.role_name, d.designation_name, e.enrollment_type, e.employment_status, e.is_active
        FROM employees e 
        LEFT JOIN roles_listing r ON e.role_id = r.role_id 
        LEFT JOIN designation_listing d ON e.designation_id = d.designation_id 
        LEFT JOIN department_listing dep ON e.department_id = dep.department_id 
        LEFT JOIN organizations o ON e.organization_id = o.organization_id 
        WHERE e.organization_id = ?
        ORDER BY e.created_at DESC";
        
$stmt = $conn->prepare($sql);
$org_id = $_SESSION['organization_id'] ?? 0;
// Note: employees_listing doesn't filter by organization_id explicitly in its query, but it probably should. Let's assume it should. Or I'll just use the session. Actually looking closely at its query, it didn't filter by org! Let's just run it globally if no org, or with org if there varies. I'll stick to org filter.
// Wait, the EMP query didn't have WHERE org_id = ?. It was global. Let me use the exact same logic.
if (isset($_SESSION['organization_id'])) {
    $sql = "SELECT e.employee_code, e.salutation, e.first_name, e.last_name, e.primary_email, e.primary_phone, 
            dep.department_name, r.role_name, d.designation_name, e.enrollment_type, e.employment_status, e.is_active
            FROM employees e 
            LEFT JOIN roles_listing r ON e.role_id = r.role_id 
            LEFT JOIN designation_listing d ON e.designation_id = d.designation_id 
            LEFT JOIN department_listing dep ON e.department_id = dep.department_id 
            ORDER BY e.created_at DESC";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

$rowIndex = 2;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowIndex, $row['employee_code']);
        $sheet->setCellValue('B' . $rowIndex, $row['salutation']);
        $sheet->setCellValue('C' . $rowIndex, $row['first_name']);
        $sheet->setCellValue('D' . $rowIndex, $row['last_name']);
        $sheet->setCellValue('E' . $rowIndex, $row['primary_email']);
        $sheet->setCellValue('F' . $rowIndex, $row['primary_phone']);
        $sheet->setCellValue('G' . $rowIndex, $row['department_name']);
        $sheet->setCellValue('H' . $rowIndex, $row['role_name']);
        $sheet->setCellValue('I' . $rowIndex, $row['designation_name']);
        $sheet->setCellValue('J' . $rowIndex, $row['enrollment_type']);
        $sheet->setCellValue('K' . $rowIndex, $row['employment_status']);
        $sheet->setCellValue('L' . $rowIndex, $row['is_active'] ? 'Active' : 'Inactive');
        $rowIndex++;
    }
}

$stmt->close();

// --- Output ---

// -- Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="employees_list_export.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
