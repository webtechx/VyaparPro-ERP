<?php
require_once '../../../config/auth_guard.php';
require '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Fetch types
$types = [];
$sql  = "SELECT ct.*,
         (SELECT COUNT(*) FROM customers_listing cl WHERE cl.customers_type_id = ct.customers_type_id) as customer_count,
         (SELECT COUNT(*) FROM item_commissions ic WHERE ic.customers_type_id = ct.customers_type_id) as commission_count
         FROM customers_type_listing ct
         WHERE ct.organization_id = " . intval($_SESSION['organization_id']) . "
         ORDER BY ct.customers_type_name ASC";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) $types[] = $row;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Customer Types');
$lastCol = 'C';

// Title row
$sheet->mergeCells("A1:{$lastCol}1");
$sheet->setCellValue('A1', 'CUSTOMER TYPES LIST');
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(30);

// Generated on
$sheet->mergeCells("A2:{$lastCol}2");
$sheet->setCellValue('A2', 'Generated on: ' . date('d M Y, h:i A'));
$sheet->getStyle('A2')->applyFromArray([
    'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '5D282A']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5EBD0']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
]);

// Header row
$headerRow = 4;
$headers   = ['Customer Type Name', 'Customers Count', 'Item Commissions Count'];
$sheet->fromArray($headers, NULL, "A{$headerRow}");
$sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
]);
foreach (range('A', $lastCol) as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

// Data rows
$r = $headerRow + 1;
foreach ($types as $row) {
    $sheet->setCellValue('A'.$r, ucwords(strtolower($row['customers_type_name'])));
    $sheet->setCellValue('B'.$r, $row['customer_count']);
    $sheet->setCellValue('C'.$r, $row['commission_count']);
    $r++;
}


// -- Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="customer_types_' . date('Y_m_d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
