<?php
require_once '../../config/auth_guard.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$start_date     = $_GET['start_date']        ?? date('Y-m-01');
$end_date       = $_GET['end_date']          ?? date('Y-m-d');
$salesperson_id = isset($_GET['sales_employee_id']) ? intval($_GET['sales_employee_id']) : 0;

require_once __DIR__ . '/sales_by_salesperson_report.php';

// Prefill salesperson name
$sp_name = '';
if ($salesperson_id > 0) {
    $spS = $conn->prepare("SELECT CONCAT(first_name,' ',last_name) as name, employee_code FROM employees WHERE employee_id = ?");
    $spS->bind_param("i", $salesperson_id);
    $spS->execute();
    $spS->bind_result($_n, $_c);
    if ($spS->fetch()) $sp_name = "$_n ($_c)";
    $spS->close();
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Sales by Salesperson');
$lastCol = 'D';

$sheet->mergeCells("A1:{$lastCol}1");
$sheet->setCellValue('A1', 'SALES BY SALESPERSON REPORT');
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(30);

$sheet->mergeCells("A2:{$lastCol}2");
$sheet->setCellValue('A2', 'Generated on: ' . date('d M Y, h:i A'));
$sheet->getStyle('A2')->applyFromArray(['font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '5D282A']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5EBD0']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]]);

$activeFilters = [];
if (!empty($start_date)) $activeFilters[] = ['Date From', date('d M Y', strtotime($start_date))];
if (!empty($end_date))   $activeFilters[] = ['Date To',   date('d M Y', strtotime($end_date))];
if (!empty($sp_name))    $activeFilters[] = ['Sales Person', $sp_name];

$filterRow = 3;
foreach ($activeFilters as $f) {
    $sheet->mergeCells("A{$filterRow}:{$lastCol}{$filterRow}");
    $sheet->setCellValue("A{$filterRow}", '  Filter: ' . $f[0] . '  →  ' . $f[1]);
    $sheet->getStyle("A{$filterRow}")->applyFromArray(['font' => ['size' => 9, 'italic' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0FF']]]);
    $filterRow++;
}
$filterRow++;

$headers = ['Sales Person', 'Employee Code', 'Invoices', 'Total Sales (₹)'];
$sheet->fromArray($headers, NULL, "A{$filterRow}");
$sheet->getStyle("A{$filterRow}:{$lastCol}{$filterRow}")->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']]]);
foreach (range('A', $lastCol) as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

$rowIndex = $filterRow + 1;
$grandTotal = 0;
foreach ($reportData as $row) {
    $empName = $row['first_name'] ? ($row['first_name'] . ' ' . $row['last_name']) : 'Unassigned / Direct Sales';
    $sheet->setCellValue('A'.$rowIndex, ucwords(strtolower($empName)));
    $sheet->setCellValue('B'.$rowIndex, $row['employee_code'] ?? '');
    $sheet->setCellValue('C'.$rowIndex, $row['invoice_count']);
    $sheet->setCellValue('D'.$rowIndex, number_format($row['total_sales'], 2, '.', ''));
    $grandTotal += $row['total_sales'];
    $rowIndex++;
}
$rowIndex++;
$sheet->setCellValue('A'.$rowIndex, 'TOTAL');
$sheet->setCellValue('D'.$rowIndex, number_format($grandTotal, 2, '.', ''));
$sheet->getStyle("A{$rowIndex}:{$lastCol}{$rowIndex}")->getFont()->setBold(true);


// -- Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="sales_by_salesperson_'.date('Y_m_d').'.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
