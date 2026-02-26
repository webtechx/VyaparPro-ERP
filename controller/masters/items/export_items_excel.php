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
    'Item Name', 
    'Brand', 
    'SKU Code', 
    'Unit', 
    'HSN Code', 
    'GST Rate (%)', 
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
$sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
foreach(range('A','J') as $col) {
    if($col == 'A' || $col == 'J') {
         $sheet->getColumnDimension($col)->setWidth(30);
    } else {
         $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

// --- Fetch Data ---
$sql = "SELECT i.item_name, i.brand, i.stock_keeping_unit, u.unit_name, 
        h.hsn_code, h.gst_rate, i.opening_stock, i.mrp, i.selling_price, i.description
        FROM items_listing i 
        LEFT JOIN units_listing u ON i.unit_id = u.unit_id 
        LEFT JOIN hsn_listing h ON i.hsn_id = h.hsn_id
        WHERE i.organization_id = ?
        ORDER BY i.item_id DESC";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['organization_id']);
$stmt->execute();
$result = $stmt->get_result();

$rowIndex = 2;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowIndex, $row['item_name']);
        $sheet->setCellValue('B' . $rowIndex, $row['brand']);
        $sheet->setCellValue('C' . $rowIndex, $row['stock_keeping_unit']);
        $sheet->setCellValue('D' . $rowIndex, $row['unit_name']);
        $sheet->setCellValue('E' . $rowIndex, $row['hsn_code']);
        $sheet->setCellValue('F' . $rowIndex, $row['gst_rate']);
        $sheet->setCellValue('G' . $rowIndex, $row['opening_stock']);
        $sheet->setCellValue('H' . $rowIndex, $row['mrp']);
        $sheet->setCellValue('I' . $rowIndex, $row['selling_price']);
        $sheet->setCellValue('J' . $rowIndex, $row['description']);
        $rowIndex++;
    }
}

$stmt->close();

// --- Output ---

// -- Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="items_list_export.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
