<?php
require_once '../../config/auth_guard.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$start_date        = $_GET['start_date']         ?? date('Y-m-01');
$end_date          = $_GET['end_date']           ?? date('Y-m-d');
$customers_type_id = isset($_GET['customers_type_id']) ? intval($_GET['customers_type_id']) : 0;
$customer_id       = isset($_GET['customer_id'])       ? intval($_GET['customer_id'])       : 0;

require_once __DIR__ . '/commission_report.php';

// Prefill type name
$type_name = '';
if ($customers_type_id > 0) {
    $tS = $conn->prepare("SELECT customers_type_name FROM customers_type_listing WHERE customers_type_id = ?");
    $tS->bind_param("i", $customers_type_id);
    $tS->execute();
    $tS->bind_result($tn);
    if ($tS->fetch()) $type_name = $tn;
    $tS->close();
}
$entity_name = '';
if ($customer_id > 0) {
    $eS = $conn->prepare("SELECT customer_name, company_name FROM customers_listing WHERE customer_id = ?");
    $eS->bind_param("i", $customer_id);
    $eS->execute();
    $eS->bind_result($cn, $co);
    if ($eS->fetch()) $entity_name = $co ? "$co ($cn)" : $cn;
    $eS->close();
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Commission Report');
$lastCol = 'F';

$sheet->mergeCells("A1:{$lastCol}1");
$sheet->setCellValue('A1', 'COMMISSION REPORT');
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
if (!empty($type_name))  $activeFilters[] = ['Type',      $type_name];
if (!empty($entity_name))$activeFilters[] = ['Entity',    $entity_name];

$filterRow = 3;
foreach ($activeFilters as $f) {
    $sheet->mergeCells("A{$filterRow}:{$lastCol}{$filterRow}");
    $sheet->setCellValue("A{$filterRow}", '  Filter: ' . $f[0] . '  →  ' . $f[1]);
    $sheet->getStyle("A{$filterRow}")->applyFromArray(['font' => ['size' => 9, 'italic' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0FF']]]);
    $filterRow++;
}
$filterRow++;

$headers = ['Entity', 'Type', 'Invoices', 'Earned (Period, ₹)', 'Paid (Period, ₹)', 'Current Wallet Balance (₹)'];
$sheet->fromArray($headers, NULL, "A{$filterRow}");
$sheet->getStyle("A{$filterRow}:{$lastCol}{$filterRow}")->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']]]);
foreach (range('A', $lastCol) as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

$rowIndex = $filterRow + 1;
foreach ($reportData as $row) {
    $custName = $row['company_name'] ? $row['company_name'] . ' (' . $row['customer_name'] . ')' : $row['customer_name'];
    $sheet->setCellValue('A'.$rowIndex, ucwords(strtolower($custName)));
    $sheet->setCellValue('B'.$rowIndex, $row['customers_type_name']);
    $sheet->setCellValue('C'.$rowIndex, $row['invoice_count']);
    $sheet->setCellValue('D'.$rowIndex, number_format($row['total_earned'], 2, '.', ''));
    $sheet->setCellValue('E'.$rowIndex, number_format($row['total_paid'], 2, '.', ''));
    $sheet->setCellValue('F'.$rowIndex, number_format($row['global_balance'], 2, '.', ''));
    $rowIndex++;
}


// -- Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="commission_report_'.date('Y_m_d').'.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
