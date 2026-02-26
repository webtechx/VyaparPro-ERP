<?php
require_once '../../../config/auth_guard.php';
require '../../../vendor/autoload.php'; // Ensure autoload is loaded for PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Check permissions if using auth guard logic, or assume auth_guard handles session
if (!isset($_SESSION['organization_id'])) {
    die("Unauthorized");
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// --- Set Headers ---
$headers = [
    'Item Name', 
    'Brand', 
    'SKU', 
    'Unit Name', 
    'HSN Code', 
    'Opening Stock', 
    'MRP', 
    'Selling Price', 
    'Description'
];

$sheet->fromArray($headers, NULL, 'A1');

// --- Styling ---
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']], // Indigo
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
foreach(range('A','I') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// --- Add Sample Data ---
$sampleData = [
    ['Plywood 18mm', 'Century', 'PLY-001', 'Square Feet', '4412', '100', '150.00', '120.00', 'Premium plywood'],
    ['Fevicol 5kg', 'Pidilite', 'ADH-55', 'Kilogram', '3506', '50', '2000.00', '1800.00', 'Adhesive bucket'],
];
$sheet->fromArray($sampleData, NULL, 'A2');

// --- Output ---
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="items_sample_template.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
