<?php
require_once '../../../config/auth_guard.php';
require '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

if (!isset($_SESSION['organization_id'])) {
    die("Unauthorized");
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// --- Set Headers ---
$headers = [
    'HSN/SAC Code', 
    'Description', 
    'GST Rate (%)'
];

$sheet->fromArray($headers, NULL, 'A1');

// --- Styling ---
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']], // Indigo
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:C1')->applyFromArray($headerStyle);
foreach(range('A','C') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// --- Add Sample Data ---
$sampleData = [
    ['4412', 'Premium plywood description', '18'],
    ['3506', 'Adhesive bucket description', '18'],
];
$sheet->fromArray($sampleData, NULL, 'A2');

// --- Output ---
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="hsn_sample_template.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
