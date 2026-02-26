<?php
require_once '../../config/auth_guard.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date']   ?? date('Y-m-d');
$item_id    = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

require_once __DIR__ . '/product_wise_purchase_report.php';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Product Wise Purchase');
$lastCol = 'F';

// ── TITLE ──
$sheet->mergeCells("A1:{$lastCol}1");
$sheet->setCellValue('A1', 'PRODUCT WISE PURCHASE REPORT');
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
if (!empty($start_date)) $activeFilters[] = ['Date From', date('d M Y', strtotime($start_date))];
if (!empty($end_date))   $activeFilters[] = ['Date To',   date('d M Y', strtotime($end_date))];

// Item name prefill
if ($item_id > 0) {
    $iRes = $conn->query("SELECT item_name FROM items_listing WHERE item_id = " . intval($item_id));
    if ($iRes && $iRow = $iRes->fetch_assoc()) $activeFilters[] = ['Item', $iRow['item_name']];
}

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
$headers = ['Item Name', 'HSN/SAC', 'Avg. Rate (₹)', 'Qty Purchased', 'Received GRN Qty', 'Total Purchase (₹)'];
$sheet->fromArray($headers, NULL, "A{$filterRow}");
$sheet->getStyle("A{$filterRow}:{$lastCol}{$filterRow}")->applyFromArray([
    'font'    => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);
$sheet->getColumnDimension('A')->setWidth(35);
foreach (range('B', $lastCol) as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

// ── DATA ──
$rowIndex = $filterRow + 1;
if (!empty($reportData)) {
    foreach ($reportData as $row) {
        $sheet->setCellValue('A'.$rowIndex, ucwords(strtolower($row['item_name'])));
        $sheet->setCellValue('B'.$rowIndex, $row['hsn_code'] ?? '-');
        $sheet->setCellValue('C'.$rowIndex, number_format($row['avg_rate'], 2, '.', ''));
        $sheet->setCellValue('D'.$rowIndex, $row['total_qty']);
        $sheet->setCellValue('E'.$rowIndex, $row['total_received']);
        $sheet->setCellValue('F'.$rowIndex, number_format($row['total_purchase'], 2, '.', ''));
        $rowIndex++;
    }
}
$rowIndex++;
$sheet->setCellValue('A'.$rowIndex, 'TOTALS');
$sheet->setCellValue('D'.$rowIndex, $total_qty_purchased);
$sheet->setCellValue('E'.$rowIndex, $total_qty_received);
$sheet->setCellValue('F'.$rowIndex, number_format($total_purchase_all, 2, '.', ''));
$sheet->getStyle("A{$rowIndex}:{$lastCol}{$rowIndex}")->getFont()->setBold(true);


// -- Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="product_wise_purchase_'.date('Y_m_d').'.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
