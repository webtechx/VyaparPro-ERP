<?php
require_once '../../../config/auth_guard.php';
require '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Vendors');

$lastCol = 'P';
$colCount = 16;

// ── TITLE ROW ──
$sheet->mergeCells("A1:{$lastCol}1");
$sheet->setCellValue('A1', 'VENDORS LIST');
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(30);

// ── GENERATED ON ──
$sheet->mergeCells("A2:{$lastCol}2");
$sheet->setCellValue('A2', 'Generated on: ' . date('d M Y, h:i A'));
$sheet->getStyle('A2')->applyFromArray([
    'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '555555']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEEEFF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
]);

// ── ACTIVE FILTERS ──
$activeFilters = [];
// Vendors listing has no URL filters — just show "All Vendors" 
$activeFilters[] = ['Report Scope', 'All Vendors'];

$filterRow = 3;
foreach ($activeFilters as $f) {
    $sheet->mergeCells("A{$filterRow}:{$lastCol}{$filterRow}");
    $sheet->setCellValue("A{$filterRow}", '  Filter: ' . $f[0] . '  →  ' . $f[1]);
    $sheet->getStyle("A{$filterRow}")->applyFromArray([
        'font' => ['size' => 9, 'italic' => true, 'color' => ['rgb' => '333333']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0FF']],
    ]);
    $filterRow++;
}

// ── BLANK SEPARATOR ──
$filterRow++;

// ── COLUMN HEADERS ──
$headers = ['Vendor Code','Display Name','Company Name','First Name','Last Name','Email','Work Phone','Mobile','Vendor Type','Account Type','GST No','PAN','Payment Terms','Opening Balance','Balance Due','Status'];
$sheet->fromArray($headers, NULL, "A{$filterRow}");
$sheet->getStyle("A{$filterRow}:{$lastCol}{$filterRow}")->applyFromArray([
    'font'    => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);
foreach (range('A', $lastCol) as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

// ── DATA ──
$org_id = $_SESSION['organization_id'] ?? 0;
$sql = "SELECT v.vendor_code, v.display_name, v.company_name, v.first_name, v.last_name,
               v.email, v.work_phone, v.mobile, v.vendor_type, v.vendor_account_type,
               v.gst_no, v.pan, v.payment_terms, v.opening_balance, v.current_balance_due, v.status
        FROM vendors_listing v
        WHERE v.organization_id = ?
        ORDER BY v.display_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $org_id);
$stmt->execute();
$result = $stmt->get_result();

$rowIndex = $filterRow + 1;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A'.$rowIndex, $row['vendor_code']);
    $sheet->setCellValue('B'.$rowIndex, $row['display_name']);
    $sheet->setCellValue('C'.$rowIndex, $row['company_name']);
    $sheet->setCellValue('D'.$rowIndex, $row['first_name']);
    $sheet->setCellValue('E'.$rowIndex, $row['last_name']);
    $sheet->setCellValue('F'.$rowIndex, $row['email']);
    $sheet->setCellValue('G'.$rowIndex, $row['work_phone']);
    $sheet->setCellValue('H'.$rowIndex, $row['mobile']);
    $sheet->setCellValue('I'.$rowIndex, $row['vendor_type']);
    $sheet->setCellValue('J'.$rowIndex, $row['vendor_account_type']);
    $sheet->setCellValue('K'.$rowIndex, $row['gst_no']);
    $sheet->setCellValue('L'.$rowIndex, $row['pan']);
    $sheet->setCellValue('M'.$rowIndex, $row['payment_terms']);
    $sheet->setCellValue('N'.$rowIndex, number_format($row['opening_balance'], 2, '.', ''));
    $sheet->setCellValue('O'.$rowIndex, number_format($row['current_balance_due'], 2, '.', ''));
    $sheet->setCellValue('P'.$rowIndex, ucfirst(strtolower($row['status'])));
    $rowIndex++;
}
$stmt->close();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="vendors_export_'.date('Y_m_d').'.xlsx"');
header('Cache-Control: max-age=0');
(new Xlsx($spreadsheet))->save('php://output');
exit;
?>
