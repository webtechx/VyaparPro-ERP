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

// --- Fetch Data ---
$sql = "SELECT hsn_code, description, gst_rate FROM hsn_listing WHERE organization_id = ? ORDER BY hsn_id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['organization_id']);
$stmt->execute();
$result = $stmt->get_result();

$rowIndex = 2;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowIndex, $row['hsn_code']);
        $sheet->setCellValue('B' . $rowIndex, $row['description']);
        $sheet->setCellValue('C' . $rowIndex, $row['gst_rate']);
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
header('Content-Disposition: attachment; filename="hsn_list_export.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
