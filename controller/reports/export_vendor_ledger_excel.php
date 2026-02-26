<?php
require_once '../../config/auth_guard.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

require_once __DIR__ . '/vendor_ledger_report.php';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Vendor Ledger');
$lastCol = 'H';

// ── TITLE ──
$sheet->mergeCells("A1:{$lastCol}1");
$sheet->setCellValue('A1', 'VENDOR LEDGER REPORT');
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(30);

// ── GENERATED ON ──
$sheet->mergeCells("A2:{$lastCol}2");
$sheet->setCellValue('A2', 'Generated on: ' . date('d M Y, h:i A'));
$sheet->getStyle('A2')->applyFromArray([
    'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '555555']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEEEFF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
]);

// ── ACTIVE FILTERS ──
$activeFilters = [];
if (!empty($start_date))            $activeFilters[] = ['Date From', date('d M Y', strtotime($start_date))];
if (!empty($end_date))              $activeFilters[] = ['Date To',   date('d M Y', strtotime($end_date))];
if (!empty($vendor_name_prefill))   $activeFilters[] = ['Vendor',    $vendor_name_prefill];
if (!empty($search_query))          $activeFilters[] = ['Search',    $search_query];

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
$headers = ['Date', 'Vendor', 'Ref No', 'Type', 'Particulars', 'Debit (₹)', 'Credit (₹)', 'Balance (₹)'];
$sheet->fromArray($headers, NULL, "A{$filterRow}");
$sheet->getStyle("A{$filterRow}:{$lastCol}{$filterRow}")->applyFromArray([
    'font'    => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);
foreach (range('A', $lastCol) as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

// ── DATA ──
$rowIndex = $filterRow + 1;
$running_balance = ($vendor_id > 0) ? $opening_balance : 0;

if ($vendor_id > 0) {
    $sheet->setCellValue('A'.$rowIndex, 'Opening Balance');
    $sheet->setCellValue('H'.$rowIndex, number_format(abs($running_balance), 2, '.', '') . ' ' . ($running_balance >= 0 ? 'Dr' : 'Cr'));
    $sheet->getStyle("A{$rowIndex}:{$lastCol}{$rowIndex}")->getFont()->setBold(true);
    $rowIndex++;
}
if (!empty($transactions)) {
    foreach ($transactions as $row) {
        $debit  = floatval($row['debit_amount']);
        $credit = floatval($row['credit_amount']);
        $running_balance = $running_balance + $debit - $credit;
        $particulars = '';
        if ($row['type_code'] === 'GRN') $particulars = 'Receive GRN' . (!empty($row['external_ref']) ? ' (PO: '.$row['external_ref'].')' : '');
        elseif ($row['type_code'] === 'PAY') $particulars = 'Payment Made' . (!empty($row['payment_mode']) ? ' - '.$row['payment_mode'] : '');
        elseif ($row['type_code'] === 'DN') $particulars = 'Debit Note Adjustment';
        $sheet->setCellValue('A'.$rowIndex, date('d M Y', strtotime($row['trans_date'])));
        $sheet->setCellValue('B'.$rowIndex, ucwords(strtolower($row['vendor_name'] ?? '')));
        $sheet->setCellValue('C'.$rowIndex, $row['ref_no']);
        $sheet->setCellValue('D'.$rowIndex, $row['type_label']);
        $sheet->setCellValue('E'.$rowIndex, $particulars);
        $sheet->setCellValue('F'.$rowIndex, $debit  > 0 ? number_format($debit,  2, '.', '') : '');
        $sheet->setCellValue('G'.$rowIndex, $credit > 0 ? number_format($credit, 2, '.', '') : '');
        $sheet->setCellValue('H'.$rowIndex, number_format(abs($running_balance), 2, '.', '') . ' ' . ($running_balance >= 0 ? 'Dr' : 'Cr'));
        $rowIndex++;
    }
}
$rowIndex++;
$sheet->setCellValue('A'.$rowIndex, 'Closing Balance');
$sheet->setCellValue('F'.$rowIndex, number_format($total_debit,  2, '.', ''));
$sheet->setCellValue('G'.$rowIndex, number_format($total_credit, 2, '.', ''));
$sheet->setCellValue('H'.$rowIndex, number_format(abs($running_balance), 2, '.', '') . ' ' . ($running_balance >= 0 ? 'Dr' : 'Cr'));
$sheet->getStyle("A{$rowIndex}:{$lastCol}{$rowIndex}")->getFont()->setBold(true);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="vendor_ledger_'.date('Y_m_d').'.xlsx"');
header('Cache-Control: max-age=0');
(new Xlsx($spreadsheet))->save('php://output');
exit;
?>
