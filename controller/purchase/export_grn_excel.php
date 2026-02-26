<?php
require_once '../../config/conn.php';

// Check for Composer Autoloader
if (file_exists('../../vendor/autoload.php')) {
    require '../../vendor/autoload.php';
} else {
    die("Error: PhpSpreadsheet library not found. Please run 'composer require phpoffice/phpspreadsheet' in your project root.");
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

if (!isset($_GET['id'])) {
    die("Invalid Request");
}

$grn_id = intval($_GET['id']);

// 1. Fetch Data
// Header
$sql = "SELECT grn.*, po.po_number, v.display_name as vendor_name, v.work_phone, v.mobile, v.email
        FROM goods_received_notes grn
        LEFT JOIN purchase_orders po ON grn.po_id = po.purchase_orders_id
        LEFT JOIN vendors_listing v ON grn.vendor_id = v.vendor_id
        WHERE grn.grn_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $grn_id);
$stmt->execute();
$headRes = $stmt->get_result();

if ($headRes->num_rows === 0) {
    die("GRN Not Found");
}

$grn = $headRes->fetch_assoc();

// Items
$iSql = "SELECT gi.*, il.item_name, u.unit_name, poi.rate as po_rate, h.hsn_code
         FROM goods_received_note_items gi
         LEFT JOIN items_listing il ON gi.item_id = il.item_id
         LEFT JOIN units_listing u ON il.unit_id = u.unit_id
         LEFT JOIN hsn_listing h ON il.hsn_id = h.hsn_id
         LEFT JOIN purchase_order_items poi ON gi.po_item_id = poi.id
         WHERE gi.grn_id = ?";
$iStmt = $conn->prepare($iSql);
$iStmt->bind_param("i", $grn_id);
$iStmt->execute();
$iRes = $iStmt->get_result();

// 2. Create Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set Properties
$spreadsheet->getProperties()->setCreator('Samadhan ERP')
    ->setLastModifiedBy('Samadhan ERP')
    ->setTitle('GRN - ' . $grn['grn_number'])
    ->setSubject('Goods Received Note')
    ->setDescription('GRN Export');

// Styles
$styleHeader = [
    'font' => ['bold' => true, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
];
$styleSubHeader = [
    'font' => ['bold' => true, 'size' => 11],
];
$styleBorder = [
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
    ],
];
$styleCurrency = [
    'numberFormat' => ['formatCode' => '#,##0.00'],
];

// 3. Layout Data

// Title
$sheet->mergeCells('A1:I1');
$sheet->setCellValue('A1', 'GOODS RECEIVED NOTE (GRN)');
$sheet->getStyle('A1')->applyFromArray($styleHeader);

// Org/Company Info (Placeholder)
$sheet->mergeCells('A2:I2');
$sheet->setCellValue('A2', 'Samadhan ERP Solutions');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// GRN Details Header
$sheet->setCellValue('A4', 'GRN Number:');
$sheet->setCellValue('B4', $grn['grn_number']);
$sheet->setCellValue('D4', 'Date:');
$sheet->setCellValue('E4', date('d-M-Y', strtotime($grn['grn_date'])));

$sheet->setCellValue('A5', 'PO Number:');
$sheet->setCellValue('B5', $grn['po_number']);
$sheet->setCellValue('D5', 'Vendor:');
$sheet->setCellValue('E5', $grn['vendor_name']);

$sheet->setCellValue('A6', 'Challan No:');
$sheet->setCellValue('B6', $grn['challan_no']);
$sheet->setCellValue('D6', 'Remarks:');
$sheet->setCellValue('E6', $grn['remarks']);

$sheet->getStyle('A4:A6')->applyFromArray($styleSubHeader);
$sheet->getStyle('D4:D6')->applyFromArray($styleSubHeader);

// Items Table Header
$row = 9;
$headers = ['#', 'Item Name', 'HSN', 'Unit', 'Rate', 'Ord. Qty', 'Ord. Value', 'Rec. Qty', 'Rec. Value', 'Condition', 'Remarks'];
$col = 'A';
foreach ($headers as $h) {
    $sheet->setCellValue($col . $row, $h);
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $col++;
}
$lastCol = 'J';
$sheet->getStyle("A$row:$lastCol$row")->applyFromArray($styleSubHeader);
$sheet->getStyle("A$row:$lastCol$row")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');

// Items Data
$row++;
$startRow = $row;
$cnt = 1;
while ($item = $iRes->fetch_assoc()) {
    $rate = floatval($item['po_rate']);
    $ordQty = floatval($item['ordered_qty']);
    $recQty = floatval($item['received_qty']);
    $ordVal = $ordQty * $rate;
    $recVal = $recQty * $rate;

    $sheet->setCellValue('A' . $row, $cnt++);
    $sheet->setCellValue('B' . $row, $item['item_name']);
    $sheet->setCellValue('C' . $row, $item['hsn_code'] ?? '-');
    $sheet->setCellValue('D' . $row, $item['unit_name']);
    
    $sheet->setCellValue('E' . $row, $rate);
    $sheet->setCellValue('F' . $row, $ordQty);
    $sheet->setCellValue('G' . $row, $ordVal);
    $sheet->setCellValue('H' . $row, $recQty);
    $sheet->setCellValue('I' . $row, $recVal);
    
    $sheet->setCellValue('J' . $row, $item['condition_status']);
    $sheet->setCellValue('K' . $row, $item['remarks']);
    
    $row++;
}

// Formatting
// E, G, I are currencies/decimals (Shifted by 1 col due to HSN)
$sheet->getStyle("E$startRow:E" . ($row - 1))->applyFromArray($styleCurrency);
$sheet->getStyle("G$startRow:G" . ($row - 1))->applyFromArray($styleCurrency);
$sheet->getStyle("I$startRow:I" . ($row - 1))->applyFromArray($styleCurrency);

// Borders
$sheet->getStyle("A" . ($startRow - 1) . ":$lastCol" . ($row - 1))->applyFromArray($styleBorder);

// 4. Output
$filename = 'GRN_' . $grn['grn_number'] . '.xlsx';


// -- Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
