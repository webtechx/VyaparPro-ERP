<?php
require_once '../../config/auth_guard.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

if (!isset($_SESSION['organization_id'])) {
    die("Unauthorized");
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Current Stock');

// --- Set Headers ---
$headers = [
    'Brand', 
    'SKU', 
    'Item Name', 
    'Unit', 
    'Avg Cost Value (₹)',
    'Stock Qty', 
    'Total Value (₹)', 
    'Last Updated'
];

$sheet->fromArray($headers, NULL, 'A1');

// --- Styling ---
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
foreach(range('A','H') as $col) {
    if($col == 'C') {
         $sheet->getColumnDimension($col)->setWidth(30);
    } else {
         $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

// --- Fetch Data ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$org_id = $_SESSION['organization_id'];
                                
$sql = "SELECT i.item_name, i.brand, i.stock_keeping_unit, i.current_stock, i.update_at, u.unit_name,
        (SELECT AVG(poi.rate) FROM purchase_order_items poi WHERE poi.item_id = i.item_id) as avg_rate
        FROM items_listing i 
        LEFT JOIN units_listing u ON i.unit_id = u.unit_id 
        WHERE i.organization_id = ?";

if(!empty($search)) {
    $s = $conn->real_escape_string($search);
    $sql .= " AND (i.item_name LIKE '%$s%' OR i.brand LIKE '%$s%' OR i.stock_keeping_unit LIKE '%$s%')";
}

$sql .= " ORDER BY i.item_name ASC";

$stmt_cs = $conn->prepare($sql);
$stmt_cs->bind_param("i", $org_id);
$stmt_cs->execute();
$result = $stmt_cs->get_result();
$rowIndex = 2;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $stock = floatval($row['current_stock']);
        $avg_rate = floatval($row['avg_rate']);
        $total_value = $stock * $avg_rate;
        
        $sheet->setCellValue('A' . $rowIndex, $row['brand'] ?: '-');
        $sheet->setCellValue('B' . $rowIndex, $row['stock_keeping_unit'] ?: '-');
        $sheet->setCellValue('C' . $rowIndex, $row['item_name']);
        $sheet->setCellValue('D' . $rowIndex, $row['unit_name']);
        
        $sheet->setCellValue('E' . $rowIndex, $avg_rate);
        $sheet->setCellValue('F' . $rowIndex, $stock);
        $sheet->setCellValue('G' . $rowIndex, $total_value);
        
        $last_update = $row['update_at'] ? date('d M Y', strtotime($row['update_at'])) : '-';
        $sheet->setCellValue('H' . $rowIndex, $last_update);
        
        $rowIndex++;
    }
}

// --- Output ---

// -- Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="current_stock_export.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
