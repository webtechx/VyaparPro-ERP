<?php
require_once '../../config/auth_guard.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// ── Include controller (sets: $credit_notes, $total_credit_amount, $count_notes,
//    $start_date, $end_date, $customer_name_prefill, $salesperson_name_prefill,
//    $status, $search_query)
require_once __DIR__ . '/credit_note_report.php';

// ── Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet       = $spreadsheet->getActiveSheet();
$sheet->setTitle('Credit Note Report');
$lastCol = 'H';

// ─────────────────────────────────────────────
// ROW 1 — Report Title
// ─────────────────────────────────────────────
$sheet->mergeCells("A1:{$lastCol}1");
$sheet->setCellValue('A1', 'CREDIT NOTE REPORT');
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(30);

// ─────────────────────────────────────────────
// ROW 2 — Period + Generated on
// ─────────────────────────────────────────────
$sheet->mergeCells("A2:{$lastCol}2");
$sheet->setCellValue('A2',
    'Period: ' . date('d M Y', strtotime($start_date)) .
    ' to '     . date('d M Y', strtotime($end_date))   .
    '   |   Generated: ' . date('d M Y, h:i A')
);
$sheet->getStyle('A2')->applyFromArray([
    'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '5D282A']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5EBD0']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
]);

// ─────────────────────────────────────────────
// ROWS 3+ — Active Filter lines
// ─────────────────────────────────────────────
$activeFilters = [];
if (!empty($start_date))               $activeFilters[] = ['Date From',    date('d M Y', strtotime($start_date))];
if (!empty($end_date))                 $activeFilters[] = ['Date To',      date('d M Y', strtotime($end_date))];
if (!empty($customer_name_prefill))    $activeFilters[] = ['Customer',     $customer_name_prefill];
if (!empty($salesperson_name_prefill)) $activeFilters[] = ['Sales Person', $salesperson_name_prefill];
if (!empty($status))                   $activeFilters[] = ['Status',       ucfirst($status)];
if (!empty($search_query))             $activeFilters[] = ['Search',       $search_query];

$filterRow = 3;
foreach ($activeFilters as $f) {
    $sheet->mergeCells("A{$filterRow}:{$lastCol}{$filterRow}");
    $sheet->setCellValue("A{$filterRow}", '  Filter: ' . $f[0] . '  →  ' . $f[1]);
    $sheet->getStyle("A{$filterRow}")->applyFromArray([
        'font' => ['size' => 9, 'italic' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF0F5']],
    ]);
    $filterRow++;
}
$filterRow++; // blank gap

// ─────────────────────────────────────────────
// KPI Summary Row — Total Notes | Total Amount | Avg Value
// ─────────────────────────────────────────────
$avgValue = $count_notes > 0 ? $total_credit_amount / $count_notes : 0;

$sheet->mergeCells("A{$filterRow}:C{$filterRow}");
$sheet->setCellValue("A{$filterRow}", 'Total Credit Notes: ' . $count_notes);

$sheet->mergeCells("D{$filterRow}:F{$filterRow}");
$sheet->setCellValue("D{$filterRow}", 'Total Amount: ₹' . number_format($total_credit_amount, 2));

$sheet->mergeCells("G{$filterRow}:{$lastCol}{$filterRow}");
$sheet->setCellValue("G{$filterRow}", 'Avg Value: ₹' . number_format($avgValue, 2));

$sheet->getStyle("A{$filterRow}:{$lastCol}{$filterRow}")->applyFromArray([
    'font' => ['bold' => true, 'size' => 10],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8E8FF']],
]);
$filterRow += 2; // gap before column headers

// ─────────────────────────────────────────────
// Column Headers
// ─────────────────────────────────────────────
$headers = ['Date', 'CN Number', 'Invoice #', 'Customer', 'Sales Person', 'Reason', 'Status', 'Amount (₹)'];
$sheet->fromArray($headers, NULL, "A{$filterRow}");
$sheet->getStyle("A{$filterRow}:{$lastCol}{$filterRow}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
]);
foreach (range('A', $lastCol) as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

// ─────────────────────────────────────────────
// Data Rows
// ─────────────────────────────────────────────
$r = $filterRow + 1;
foreach ($credit_notes as $row) {
    $custName = $row['company_name']
        ? $row['company_name'] . ' (' . $row['customer_name'] . ')'
        : $row['customer_name'];

    $sheet->setCellValue('A'.$r, date('d M Y', strtotime($row['credit_note_date'])));
    $sheet->setCellValue('B'.$r, $row['credit_note_number']);
    $sheet->setCellValue('C'.$r, $row['invoice_number'] ?: '-');
    $sheet->setCellValue('D'.$r, $custName);
    $sheet->setCellValue('E'.$r, $row['salesperson_name'] ?? '-');
    $sheet->setCellValue('F'.$r, $row['reason'] ?? '');
    $sheet->setCellValue('G'.$r, ucfirst($row['status']));
    $sheet->setCellValue('H'.$r, number_format($row['total_amount'], 2, '.', ''));

    // Right-align amount
    $sheet->getStyle('H'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    // Alternating row colour
    if ($r % 2 === 0) {
        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F7F7FF');
    }
    $r++;
}

// ─────────────────────────────────────────────
// Totals Footer Row
// ─────────────────────────────────────────────
$r++;
$sheet->mergeCells("A{$r}:G{$r}");
$sheet->setCellValue('A'.$r, 'TOTAL');
$sheet->setCellValue('H'.$r, number_format($total_credit_amount, 2, '.', ''));
$sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
    'font' => ['bold' => true, 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8E8FF']],
]);
$sheet->getStyle('H'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// ─────────────────────────────────────────────
// Output
// ─────────────────────────────────────────────

// -- Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="credit_note_report_' . date('Y_m_d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
