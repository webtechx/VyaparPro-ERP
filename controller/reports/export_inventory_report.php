<?php
require_once '../../config/auth_guard.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Include the existing query logic to get $inventory_items, etc.
require_once __DIR__ . '/inventory_report.php';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Inventory Report');

// --- Set Headers ---
$headers = [
    'Item Name', 
    'SKU', 
    'Brand', 
    'Unit', 
    'Created Date',
    'MRP (₹)',
    'Purchase Price (₹)',
    'Stock Qty', 
    'Total Value (₹)'
];

$sheet->fromArray($headers, NULL, 'A1');

// --- Styling ---
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
foreach(range('A','I') as $col) {
    if($col == 'A') {
         $sheet->getColumnDimension($col)->setWidth(30);
    } else {
         $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

// --- Fetch Data ---
$rowIndex = 2;
if (!empty($inventory_items)) {
    foreach ($inventory_items as $row) {
        $sheet->setCellValue('A' . $rowIndex, $row['item_name']);
        $sheet->setCellValue('B' . $rowIndex, $row['stock_keeping_unit'] ?: '-');
        $sheet->setCellValue('C' . $rowIndex, $row['brand'] ?: '-');
        $sheet->setCellValue('D' . $rowIndex, $row['unit_name'] ?: '-');
        
        $create_date = $row['create_at'] ? date('d M Y', strtotime($row['create_at'])) : '-';
        $sheet->setCellValue('E' . $rowIndex, $create_date);
        
        $sheet->setCellValue('F' . $rowIndex, number_format($row['mrp'], 2, '.', ''));
        $sheet->setCellValue('G' . $rowIndex, number_format($row['purchase_price'], 2, '.', ''));
        $sheet->setCellValue('H' . $rowIndex, $row['current_stock'] + 0);
        $sheet->setCellValue('I' . $rowIndex, number_format($row['total_value'], 2, '.', ''));
        
        $rowIndex++;
    }
}

// Add Summary Row
$rowIndex++;
$sheet->setCellValue('A' . $rowIndex, 'TOTALS');
$sheet->getStyle('A' . $rowIndex)->getFont()->setBold(true);

$sheet->setCellValue('H' . $rowIndex, $total_stock_qty);
$sheet->getStyle('H' . $rowIndex)->getFont()->setBold(true);

$sheet->setCellValue('I' . $rowIndex, number_format($total_inventory_value, 2, '.', ''));
$sheet->getStyle('I' . $rowIndex)->getFont()->setBold(true);

// --- Output ---

// -- Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="inventory_report_export.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
