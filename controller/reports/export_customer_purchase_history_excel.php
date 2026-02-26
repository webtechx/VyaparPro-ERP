<?php
require_once '../../config/auth_guard.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

require_once __DIR__ . '/customer_purchase_history_report.php';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Customer Purchase History');
$lastCol = 'F';

$sheet->mergeCells("A1:{$lastCol}1");
$sheet->setCellValue('A1', 'CUSTOMER PURCHASE HISTORY REPORT');
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
if (!empty($start_date))            $activeFilters[] = ['Date From', date('d M Y', strtotime($start_date))];
if (!empty($end_date))              $activeFilters[] = ['Date To',   date('d M Y', strtotime($end_date))];
if (!empty($customer_name_prefill)) $activeFilters[] = ['Customer',  $customer_name_prefill];
if (!empty($status))                $activeFilters[] = ['Status',    ucfirst($status)];
if (!empty($search_query))          $activeFilters[] = ['Search',    $search_query];

$filterRow = 3;
foreach ($activeFilters as $f) {
    $sheet->mergeCells("A{$filterRow}:{$lastCol}{$filterRow}");
    $sheet->setCellValue("A{$filterRow}", '  Filter: ' . $f[0] . '  →  ' . $f[1]);
    $sheet->getStyle("A{$filterRow}")->applyFromArray(['font' => ['size' => 9, 'italic' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0FF']]]);
    $filterRow++;
}
$filterRow++;

$headers = ['Date', 'Invoice #', 'Customer', 'Total Amount (₹)', 'Balance Due (₹)', 'Status'];
$sheet->fromArray($headers, NULL, "A{$filterRow}");
$sheet->getStyle("A{$filterRow}:{$lastCol}{$filterRow}")->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']]]);
foreach (range('A', $lastCol) as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

$rowIndex = $filterRow + 1;
$grandTotal = 0;
$grandDue   = 0;
foreach ($invoices as $row) {
    $custName = $row['company_name'] ? $row['company_name'] . ' (' . $row['customer_name'] . ')' : $row['customer_name'];
    $sheet->setCellValue('A'.$rowIndex, date('d M Y', strtotime($row['invoice_date'])));
    $sheet->setCellValue('B'.$rowIndex, $row['invoice_number']);
    $sheet->setCellValue('C'.$rowIndex, ucwords(strtolower($custName)));
    $sheet->setCellValue('D'.$rowIndex, number_format($row['total_amount'], 2, '.', ''));
    $sheet->setCellValue('E'.$rowIndex, number_format($row['balance_due'], 2, '.', ''));
    $sheet->setCellValue('F'.$rowIndex, ucfirst($row['status']));
    $grandTotal += $row['total_amount'];
    $grandDue   += $row['balance_due'];
    $rowIndex++;
}
$rowIndex++;
$sheet->setCellValue('A'.$rowIndex, 'TOTALS');
$sheet->setCellValue('D'.$rowIndex, number_format($grandTotal, 2, '.', ''));
$sheet->setCellValue('E'.$rowIndex, number_format($grandDue,   2, '.', ''));
$sheet->getStyle("A{$rowIndex}:{$lastCol}{$rowIndex}")->getFont()->setBold(true);


// -- Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="customer_purchase_history_'.date('Y_m_d').'.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
