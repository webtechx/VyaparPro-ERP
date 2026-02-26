<?php
require_once '../../config/auth_guard.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

if (!isset($_SESSION['organization_id'])) {
    die("Unauthorized");
}

$spreadsheet = new Spreadsheet();

// --- 1. Wallet Balances Sheet ---
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('Wallet Balances');

$headers1 = ['Employee Name', 'Employee Code', 'Role', 'Total Earned (₹)', 'Current Balance (₹)'];
$sheet1->fromArray($headers1, NULL, 'A1');

$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet1->getStyle('A1:E1')->applyFromArray($headerStyle);
foreach(range('A','E') as $col) $sheet1->getColumnDimension($col)->setAutoSize(true);

$emp_filter = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
$role_filter = isset($_GET['role_id']) ? (int)$_GET['role_id'] : 0;
$month_filter = isset($_GET['month']) ? $_GET['month'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';

$walletSql = "SELECT e.first_name, e.last_name, e.employee_code, e.current_incentive_balance, e.total_incentive_earned, r.role_name 
              FROM employees e
              LEFT JOIN roles_listing r ON e.role_id = r.role_id
              WHERE e.is_active = 1";

if ($emp_filter > 0) $walletSql .= " AND e.employee_id = $emp_filter";
if ($role_filter > 0) $walletSql .= " AND e.role_id = $role_filter";

$walletResult = $conn->query($walletSql);
$rowIdx = 2;
if ($walletResult && $walletResult->num_rows > 0) {
    while ($row = $walletResult->fetch_assoc()) {
        $sheet1->setCellValue('A' . $rowIdx, $row['first_name'] . ' ' . $row['last_name']);
        $sheet1->setCellValue('B' . $rowIdx, $row['employee_code']);
        $sheet1->setCellValue('C' . $rowIdx, $row['role_name']);
        $sheet1->setCellValue('D' . $rowIdx, $row['total_incentive_earned']);
        $sheet1->setCellValue('E' . $rowIdx, $row['current_incentive_balance']);
        $rowIdx++;
    }
}

// --- 2. Ledger History Sheet ---
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Ledger History');

$headers2 = ['Date', 'Employee Name', 'Employee Code', 'Role', 'Period', 'Type', 'Amount (₹)', 'Notes'];
$sheet2->fromArray($headers2, NULL, 'A1');
$sheet2->getStyle('A1:H1')->applyFromArray($headerStyle);
foreach(range('A','H') as $col) $sheet2->getColumnDimension($col)->setAutoSize(true);

$ledgerSql = "SELECT il.*, e.first_name, e.last_name, e.employee_code, r.role_name, mt.month, mt.year 
              FROM incentive_ledger il 
              JOIN employees e ON il.employee_id = e.employee_id 
              LEFT JOIN roles_listing r ON e.role_id = r.role_id 
              LEFT JOIN monthly_targets mt ON il.monthly_target_id = mt.id 
              WHERE 1=1";

if ($emp_filter > 0) $ledgerSql .= " AND il.employee_id = $emp_filter";
if ($month_filter && $year_filter) $ledgerSql .= " AND mt.month = '$month_filter' AND mt.year = '$year_filter'";

// Get all for export instead of limit 100
$ledgerSql .= " ORDER BY il.distribution_date DESC";
$ledgerResult = $conn->query($ledgerSql);

$rowIdx = 2;
if ($ledgerResult && $ledgerResult->num_rows > 0) {
    while ($row = $ledgerResult->fetch_assoc()) {
        $sheet2->setCellValue('A' . $rowIdx, date('d M Y h:i A', strtotime($row['distribution_date'])));
        $sheet2->setCellValue('B' . $rowIdx, $row['first_name'] . ' ' . $row['last_name']);
        $sheet2->setCellValue('C' . $rowIdx, $row['employee_code']);
        $sheet2->setCellValue('D' . $rowIdx, $row['role_name']);
        $period = ($row['month'] && $row['year']) ? $row['month'] . ' ' . $row['year'] : '-';
        $sheet2->setCellValue('E' . $rowIdx, $period);
        $sheet2->setCellValue('F' . $rowIdx, ucfirst($row['distribution_type']));
        
        $amtPrefix = $row['amount'] < 0 ? '-' : '+';
        $sheet2->setCellValue('G' . $rowIdx, $amtPrefix . abs($row['amount']));
        $sheet2->setCellValue('H' . $rowIdx, $row['notes']);
        $rowIdx++;
    }
}

$spreadsheet->setActiveSheetIndex(0);

// --- Output ---

// -- Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="employee_incentive_wallet.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
