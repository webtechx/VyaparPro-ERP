<?php
require_once '../../config/auth_guard.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

require_once __DIR__ . '/sales_report.php';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Sales Report');
$lastCol = 'G';

// ── TITLE ──
$sheet->mergeCells("A1:{$lastCol}1");
$sheet->setCellValue('A1', 'SALES REPORT');
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(30);

// ── GENERATED ON ──
$sheet->mergeCells("A2:{$lastCol}2");
$sheet->setCellValue('A2', 'Generated on: ' . date('d M Y, h:i A'));
$sheet->getStyle('A2')->applyFromArray([
    'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '5D282A']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5EBD0']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
]);

// ── ACTIVE FILTERS ──
$activeFilters = [];
if (!empty($start_date))              $activeFilters[] = ['Date From',   date('d M Y', strtotime($start_date))];
if (!empty($end_date))                $activeFilters[] = ['Date To',     date('d M Y', strtotime($end_date))];
if (!empty($customer_name_prefill))   $activeFilters[] = ['Customer',    $customer_name_prefill];
if (!empty($salesperson_name_prefill))$activeFilters[] = ['Sales Person',$salesperson_name_prefill];
if ($department_id > 0 && !empty($departments)) {
    foreach ($departments as $dept) {
        if ($dept['department_id'] == $department_id) {
            $activeFilters[] = ['Department', $dept['department_name']];
            break;
        }
    }
}
if (!empty($status))                  $activeFilters[] = ['Status',      ucfirst($status)];
if (!empty($search_query))            $activeFilters[] = ['Search',      $search_query];

$filterRow = 3;
foreach ($activeFilters as $f) {
    $sheet->mergeCells("A{$filterRow}:{$lastCol}{$filterRow}");
    $sheet->setCellValue("A{$filterRow}", '  Filter: ' . $f[0] . '  →  ' . $f[1]);
    $sheet->getStyle("A{$filterRow}")->applyFromArray([
        'font' => ['size' => 9, 'italic' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0FF']],
    ]);
    $filterRow++;
}
$filterRow++;

// ── COLUMN HEADERS ──
$headers = ['Date', 'Invoice #', 'Customer', 'Sales Person', 'Status', 'Amount (₹)', 'Balance Due (₹)'];
$sheet->fromArray($headers, NULL, "A{$filterRow}");
$sheet->getStyle("A{$filterRow}:{$lastCol}{$filterRow}")->applyFromArray([
    'font'    => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);
foreach (range('A', $lastCol) as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

// ── DATA ──
$rowIndex = $filterRow + 1;
if (!empty($invoices)) {
    foreach ($invoices as $row) {
        $custName = $row['company_name'] ? $row['company_name'].' ('.$row['customer_name'].')' : $row['customer_name'];
        $sheet->setCellValue('A'.$rowIndex, date('d M Y', strtotime($row['invoice_date'])));
        $sheet->setCellValue('B'.$rowIndex, $row['invoice_number']);
        $sheet->setCellValue('C'.$rowIndex, ucwords(strtolower($custName)));
        $sheet->setCellValue('D'.$rowIndex, ucwords(strtolower($row['salesperson_name'] ?? '-')));
        $sheet->setCellValue('E'.$rowIndex, ucfirst($row['status']));
        $sheet->setCellValue('F'.$rowIndex, number_format($row['total_amount'],  2, '.', ''));
        $sheet->setCellValue('G'.$rowIndex, number_format($row['balance_due'],   2, '.', ''));
        $rowIndex++;
    }
}
$rowIndex++;
$sheet->setCellValue('A'.$rowIndex, 'TOTALS');
$sheet->setCellValue('F'.$rowIndex, number_format($total_sales,       2, '.', ''));
$sheet->setCellValue('G'.$rowIndex, number_format($total_balance_due, 2, '.', ''));
$sheet->getStyle("A{$rowIndex}:{$lastCol}{$rowIndex}")->getFont()->setBold(true);


// -- Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="sales_report_'.date('Y_m_d').'.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
