<?php
require_once '../../config/auth_guard.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Reuse the existing controller logic (sets $inventoryData, $total_* vars)
require_once __DIR__ . '/inventory_ageing_report.php';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Inventory Ageing');

// --- Set Headers ---
$headers = [
    'Item Name',
    'Brand',
    'SKU',
    'Unit',
    'Current Stock',
    'Total Value (₹)',
    '0-30 Days (₹)',
    '31-60 Days (₹)',
    '61-90 Days (₹)',
    '> 90 Days (₹)'
];

$sheet->fromArray($headers, NULL, 'A1');

// --- Styling ---
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
foreach(range('A','J') as $col) {
    if ($col == 'A') {
        $sheet->getColumnDimension($col)->setWidth(30);
    } else {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

// --- Fill Data ---
$rowIndex = 2;
foreach ($inventoryData as $row) {
    $sheet->setCellValue('A' . $rowIndex, $row['item_name']);
    $sheet->setCellValue('B' . $rowIndex, $row['brand'] ?: '-');
    $sheet->setCellValue('C' . $rowIndex, $row['sku'] ?: '-');
    $sheet->setCellValue('D' . $rowIndex, $row['unit_name'] ?: '-');
    $sheet->setCellValue('E' . $rowIndex, $row['current_stock'] + 0);
    $sheet->setCellValue('F' . $rowIndex, number_format($row['total_value'], 2, '.', ''));
    $sheet->setCellValue('G' . $rowIndex, number_format($row['age_0_30'] ?? 0, 2, '.', ''));
    $sheet->setCellValue('H' . $rowIndex, number_format($row['age_31_60'] ?? 0, 2, '.', ''));
    $sheet->setCellValue('I' . $rowIndex, number_format($row['age_61_90'] ?? 0, 2, '.', ''));
    $sheet->setCellValue('J' . $rowIndex, number_format($row['age_90_plus'] ?? 0, 2, '.', ''));
    $rowIndex++;
}

// --- Totals Row ---
$rowIndex++;
$totalsStyle = ['font' => ['bold' => true]];
$sheet->setCellValue('A' . $rowIndex, 'TOTALS');
$sheet->setCellValue('F' . $rowIndex, number_format($total_inventory_value, 2, '.', ''));
$sheet->setCellValue('G' . $rowIndex, number_format($total_0_30, 2, '.', ''));
$sheet->setCellValue('H' . $rowIndex, number_format($total_31_60, 2, '.', ''));
$sheet->setCellValue('I' . $rowIndex, number_format($total_61_90, 2, '.', ''));
$sheet->setCellValue('J' . $rowIndex, number_format($total_90_plus, 2, '.', ''));
$sheet->getStyle('A' . $rowIndex . ':J' . $rowIndex)->applyFromArray($totalsStyle);

// --- Colour Ageing Columns ---
$greenStyle = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C8E6C9']]];
$blueStyle  = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A8C8E8']]];
$yellowStyle = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5E6A3']]];
$redStyle   = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4A9A8']]];

$sheet->getStyle('G1')->applyFromArray($greenStyle);
$sheet->getStyle('H1')->applyFromArray($blueStyle);
$sheet->getStyle('I1')->applyFromArray($yellowStyle);
$sheet->getStyle('J1')->applyFromArray($redStyle);

// --- Output ---

// -- Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="inventory_ageing_report.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
