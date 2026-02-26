<?php
ob_start();
require_once '../../config/auth_guard.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// ── Params (same as controller)
$organization_id = $_SESSION['organization_id'];
$start_date      = $_GET['start_date']  ?? date('Y-m-01');
$end_date        = $_GET['end_date']    ?? date('Y-m-d');
$vendor_id       = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
$search_query    = isset($_GET['q'])         ? trim($_GET['q'])           : '';

// ── Fetch data
$sql = "SELECT
            dn.debit_note_id, dn.debit_note_number, dn.debit_note_date, dn.remarks,
            v.display_name as vendor_name, v.company_name, po.po_number,
            (
                SELECT SUM(dni.return_qty * poi.rate)
                FROM debit_note_items dni
                JOIN purchase_order_items poi ON dni.po_item_id = poi.id
                WHERE dni.debit_note_id = dn.debit_note_id
            ) as total_amount
        FROM debit_notes dn
        LEFT JOIN vendors_listing v  ON dn.vendor_id = v.vendor_id
        LEFT JOIN purchase_orders po ON dn.po_id = po.purchase_orders_id
        WHERE dn.organization_id = ?";

$params = [$organization_id];
$types  = "i";

if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND dn.debit_note_date BETWEEN ? AND ?";
    $params[] = $start_date; $params[] = $end_date; $types .= "ss";
}
if ($vendor_id > 0) {
    $sql .= " AND dn.vendor_id = ?";
    $params[] = $vendor_id; $types .= "i";
}
if (!empty($search_query)) {
    $sql .= " AND (dn.debit_note_number LIKE ? OR po.po_number LIKE ?)";
    $like = "%$search_query%";
    $params[] = $like; $params[] = $like; $types .= "ss";
}
$sql .= " ORDER BY dn.debit_note_date DESC, dn.debit_note_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$debit_notes        = [];
$total_debit_amount = 0;
$count_notes        = 0;
while ($row = $result->fetch_assoc()) {
    $row['total_amount'] = floatval($row['total_amount'] ?? 0);
    $debit_notes[]       = $row;
    $total_debit_amount += $row['total_amount'];
    $count_notes++;
}
$stmt->close();

// Prefill vendor name for filter row
$vendor_name_prefill = '';
if ($vendor_id > 0) {
    $vS = $conn->prepare("SELECT display_name, company_name, vendor_code FROM vendors_listing WHERE vendor_id = ?");
    $vS->bind_param("i", $vendor_id);
    $vS->execute();
    $vS->bind_result($vN, $cN, $vC);
    if ($vS->fetch()) $vendor_name_prefill = ($cN ? "$cN ($vN)" : $vN) . ($vC ? " - $vC" : '');
    $vS->close();
}

// ── Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet       = $spreadsheet->getActiveSheet();
$sheet->setTitle('Debit Note Report');
$lastCol = 'F';

// ── Row 1: Report Title
$sheet->mergeCells("A1:{$lastCol}1");
$sheet->setCellValue('A1', 'DEBIT NOTE REPORT');
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(30);

// ── Row 2: Period + Generated
$sheet->mergeCells("A2:{$lastCol}2");
$sheet->setCellValue('A2',
    'Period: ' . date('d M Y', strtotime($start_date)) .
    ' to '     . date('d M Y', strtotime($end_date)) .
    '   |   Generated: ' . date('d M Y, h:i A')
);
$sheet->getStyle('A2')->applyFromArray([
    'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '5D282A']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5EBD0']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
]);

// ── Rows 3+: Active Filter rows
$activeFilters = [];
if (!empty($start_date))         $activeFilters[] = ['Date From', date('d M Y', strtotime($start_date))];
if (!empty($end_date))           $activeFilters[] = ['Date To',   date('d M Y', strtotime($end_date))];
if (!empty($vendor_name_prefill))$activeFilters[] = ['Vendor',    $vendor_name_prefill];
if (!empty($search_query))       $activeFilters[] = ['Search',    $search_query];

$filterRow = 3;
foreach ($activeFilters as $f) {
    $sheet->mergeCells("A{$filterRow}:{$lastCol}{$filterRow}");
    $sheet->setCellValue("A{$filterRow}", '  Filter: ' . $f[0] . '  →  ' . $f[1]);
    $sheet->getStyle("A{$filterRow}")->applyFromArray([
        'font' => ['size' => 9, 'italic' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF8F0']],
    ]);
    $filterRow++;
}
$filterRow++; // blank gap before headers

// ── Summary row (mini KPIs in cells)
$sheet->mergeCells("A{$filterRow}:B{$filterRow}");
$sheet->setCellValue("A{$filterRow}", 'Total Debit Notes: ' . $count_notes);
$sheet->mergeCells("C{$filterRow}:D{$filterRow}");
$sheet->setCellValue("C{$filterRow}", 'Total Amount: ₹' . number_format($total_debit_amount, 2));
$sheet->mergeCells("E{$filterRow}:{$lastCol}{$filterRow}");
$sheet->setCellValue("E{$filterRow}", 'Avg Value: ₹' . number_format($count_notes > 0 ? $total_debit_amount / $count_notes : 0, 2));
$sheet->getStyle("A{$filterRow}:{$lastCol}{$filterRow}")->applyFromArray([
    'font' => ['bold' => true, 'size' => 10],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8E8FF']],
]);
$filterRow += 2; // gap before column headers

// ── Column Headers
$headers = ['Date', 'DN Number', 'PO #', 'Vendor', 'Remarks', 'Amount (₹)'];
$sheet->fromArray($headers, NULL, "A{$filterRow}");
$sheet->getStyle("A{$filterRow}:{$lastCol}{$filterRow}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
]);
foreach (range('A', $lastCol) as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

// ── Data Rows
$r = $filterRow + 1;
foreach ($debit_notes as $row) {
    $vendorDisplay = $row['company_name']
        ? $row['company_name'] . ' (' . $row['vendor_name'] . ')'
        : $row['vendor_name'];

    $sheet->setCellValue('A'.$r, date('d M Y', strtotime($row['debit_note_date'])));
    $sheet->setCellValue('B'.$r, $row['debit_note_number']);
    $sheet->setCellValue('C'.$r, $row['po_number'] ?? '-');
    $sheet->setCellValue('D'.$r, $vendorDisplay);
    $sheet->setCellValue('E'.$r, $row['remarks'] ?? '');
    $sheet->setCellValue('F'.$r, number_format($row['total_amount'], 2, '.', ''));

    // Right-align the amount column
    $sheet->getStyle('F'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    // Alternating row color
    if ($r % 2 === 0) {
        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F7F7FF');
    }
    $r++;
}

// ── Totals Footer Row
$r++;
$sheet->setCellValue('A'.$r, 'TOTAL');
$sheet->mergeCells("A{$r}:E{$r}");
$sheet->setCellValue('F'.$r, number_format($total_debit_amount, 2, '.', ''));
$sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
    'font' => ['bold' => true, 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8E8FF']],
]);
$sheet->getStyle('F'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// ── Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="debit_note_report_' . date('Y_m_d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
