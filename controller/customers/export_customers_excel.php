<?php
require_once '../../config/auth_guard.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Customers');
$lastCol = 'J';

$sheet->mergeCells("A1:{$lastCol}1");
$sheet->setCellValue('A1', 'CUSTOMERS LIST');
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(30);

$sheet->mergeCells("A2:{$lastCol}2");
$sheet->setCellValue('A2', 'Generated on: ' . date('d M Y, h:i A'));
$sheet->getStyle('A2')->applyFromArray(['font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '5D282A']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5EBD0']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]]);

// No URL filters for customers list - show All
$sheet->mergeCells("A3:{$lastCol}3");
$sheet->setCellValue('A3', '  Filter: Report Scope  →  All Customers');
$sheet->getStyle('A3')->applyFromArray(['font' => ['size' => 9, 'italic' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0FF']]]);

$filterRow = 5; // title=1, generated=2, filter=3, blank=4, headers=5

$headers = ['Customer Code', 'Customer Name', 'Company Name', 'Type', 'Email', 'Phone', 'GST No', 'City', 'State', 'Balance Due (₹)'];
$sheet->fromArray($headers, NULL, "A{$filterRow}");
$sheet->getStyle("A{$filterRow}:{$lastCol}{$filterRow}")->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']]]);
foreach (range('A', $lastCol) as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

$org_id = $_SESSION['organization_id'] ?? 0;
$sql = "SELECT c.customer_code, c.customer_name, c.company_name, t.customers_type_name,
               c.email, c.phone, c.gst_number, c.city, c.state, c.current_balance_due
        FROM customers_listing c
        LEFT JOIN customers_type_listing t ON c.customers_type_id = t.customers_type_id
        WHERE c.organization_id = ? ORDER BY c.customer_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $org_id);
$stmt->execute();
$result = $stmt->get_result();

$rowIndex = $filterRow + 1;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A'.$rowIndex, $row['customer_code']);
    $sheet->setCellValue('B'.$rowIndex, ucwords(strtolower($row['customer_name'])));
    $sheet->setCellValue('C'.$rowIndex, $row['company_name']);
    $sheet->setCellValue('D'.$rowIndex, $row['customers_type_name']);
    $sheet->setCellValue('E'.$rowIndex, $row['email']);
    $sheet->setCellValue('F'.$rowIndex, $row['phone']);
    $sheet->setCellValue('G'.$rowIndex, $row['gst_number']);
    $sheet->setCellValue('H'.$rowIndex, $row['city']);
    $sheet->setCellValue('I'.$rowIndex, $row['state']);
    $sheet->setCellValue('J'.$rowIndex, number_format($row['current_balance_due'] ?? 0, 2, '.', ''));
    $rowIndex++;
}
$stmt->close();


// -- Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="customers_'.date('Y_m_d').'.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
