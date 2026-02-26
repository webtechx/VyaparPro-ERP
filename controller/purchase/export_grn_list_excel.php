<?php
require_once '../../config/auth_guard.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$org_id = $_SESSION['organization_id'];

$sql = "SELECT grn.grn_number, grn.grn_date, po.po_number, v.display_name as vendor_name, grn.challan_no, grn.remarks
        FROM goods_received_notes grn
        LEFT JOIN purchase_orders po ON grn.po_id = po.purchase_orders_id
        LEFT JOIN vendors_listing v ON grn.vendor_id = v.vendor_id
        WHERE grn.organization_id = ?
        ORDER BY grn.grn_id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $org_id);
$stmt->execute();
$result = $stmt->get_result();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('GRN List');

// Title row
$sheet->mergeCells('A1:F1');
$sheet->setCellValue('A1', 'GOODS RECEIVED NOTES LIST');
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(26);

// Generated row
$sheet->mergeCells('A2:F2');
$sheet->setCellValue('A2', 'Generated on: ' . date('d M Y, h:i A'));
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '5D282A']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5EBD0']],
]);

// Headers
$headers = ['GRN Number', 'Date', 'PO Number', 'Vendor', 'Challan No', 'Remarks'];
$sheet->fromArray($headers, NULL, 'A4');
$sheet->getStyle('A4:F4')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
]);
foreach (range('A', 'F') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

$rowIndex = 5;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $rowIndex, $row['grn_number']);
    $sheet->setCellValue('B' . $rowIndex, date('d M Y', strtotime($row['grn_date'])));
    $sheet->setCellValue('C' . $rowIndex, $row['po_number'] ?: '-');
    $sheet->setCellValue('D' . $rowIndex, $row['vendor_name']);
    $sheet->setCellValue('E' . $rowIndex, $row['challan_no'] ?: '-');
    $sheet->setCellValue('F' . $rowIndex, $row['remarks'] ?: '');
    $rowIndex++;
}
$stmt->close();


// -- Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="grn_list_' . date('Y_m_d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
