<?php
ob_start();
require_once '../../config/auth_guard.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// ── Params (same as view)
$report_type  = isset($_GET['type'])   ? $_GET['type']               : 'contact';
$month        = isset($_GET['month'])  ? intval($_GET['month'])       : 0;
$customer_id  = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$search_query = isset($_GET['q'])      ? trim($_GET['q'])             : '';

// ── Report title map
$titles = [
    'contact'     => 'CUSTOMER CONTACT REPORT',
    'birthday'    => 'CUSTOMER BIRTHDAY REPORT',
    'anniversary' => 'CUSTOMER ANNIVERSARY REPORT',
];
$report_title = $titles[$report_type] ?? 'CUSTOMER REPORT';

// ── Months map
$months = [
    1=>'January',2=>'February',3=>'March',4=>'April',
    5=>'May',6=>'June',7=>'July',8=>'August',
    9=>'September',10=>'October',11=>'November',12=>'December'
];

// ── Fetch data (same logic as controller)
$organization_id = $_SESSION['organization_id'];
$sql = "SELECT c.customer_id, c.customer_name, c.company_name, c.customer_code,
               c.email, c.phone, c.date_of_birth, c.anniversary_date, c.avatar,
               ct.customers_type_name, c.address as address_line1, c.city, c.state
        FROM customers_listing c
        LEFT JOIN customers_type_listing ct ON c.customers_type_id = ct.customers_type_id
        WHERE c.organization_id = ?";

$params = [$organization_id];
$types  = "i";

if ($customer_id > 0) {
    $sql .= " AND c.customer_id = ?";
    $params[] = $customer_id; $types .= "i";
}
if (!empty($search_query)) {
    $sql .= " AND (c.customer_name LIKE ? OR c.company_name LIKE ? OR c.customer_code LIKE ? OR c.phone LIKE ?)";
    $like = "%$search_query%";
    array_push($params, $like, $like, $like, $like); $types .= "ssss";
}
if ($month > 0) {
    if ($report_type === 'birthday')     { $sql .= " AND MONTH(c.date_of_birth) = ?";    $params[] = $month; $types .= "i"; }
    elseif ($report_type === 'anniversary') { $sql .= " AND MONTH(c.anniversary_date) = ?"; $params[] = $month; $types .= "i"; }
}
if ($report_type === 'birthday') {
    $sql .= " AND c.date_of_birth IS NOT NULL ORDER BY MONTH(c.date_of_birth) ASC, DAY(c.date_of_birth) ASC";
} elseif ($report_type === 'anniversary') {
    $sql .= " AND c.anniversary_date IS NOT NULL ORDER BY MONTH(c.anniversary_date) ASC, DAY(c.anniversary_date) ASC";
} else {
    $sql .= " ORDER BY c.customer_name ASC";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$customers = [];
while ($row = $result->fetch_assoc()) $customers[] = $row;
$stmt->close();

// ── Build Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet       = $spreadsheet->getActiveSheet();
$sheet->setTitle(ucfirst($report_type) . ' Report');

// Determine columns based on report type
if ($report_type === 'contact') {
    $lastCol  = 'H';
    $colCount = 8;
} elseif ($report_type === 'birthday') {
    $lastCol  = 'F';
    $colCount = 6;
} else { // anniversary
    $lastCol  = 'F';
    $colCount = 6;
}

// ── Row 1: Title
$sheet->mergeCells("A1:{$lastCol}1");
$sheet->setCellValue('A1', $report_title);
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(30);

// ── Row 2: Generated on
$sheet->mergeCells("A2:{$lastCol}2");
$sheet->setCellValue('A2', 'Generated on: ' . date('d M Y, h:i A'));
$sheet->getStyle('A2')->applyFromArray([
    'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '5D282A']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5EBD0']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
]);

// ── Row 3: Active Filters
$activeFilters = [];
if ($month > 0 && isset($months[$month]))  $activeFilters[] = ['Month',    $months[$month]];
if (!empty($search_query))                 $activeFilters[] = ['Search',   $search_query];

$filterRow = 3;
foreach ($activeFilters as $f) {
    $sheet->mergeCells("A{$filterRow}:{$lastCol}{$filterRow}");
    $sheet->setCellValue("A{$filterRow}", '  Filter: ' . $f[0] . '  →  ' . $f[1]);
    $sheet->getStyle("A{$filterRow}")->applyFromArray(['font' => ['size' => 9, 'italic' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0FF']]]);
    $filterRow++;
}
$filterRow++; // blank gap

// ── Header Row
if ($report_type === 'contact') {
    $headers = ['Customer Name', 'Company', 'Code', 'Phone', 'Email', 'Address / City', 'Date of Birth', 'Anniversary Date'];
} elseif ($report_type === 'birthday') {
    $headers = ['Customer Name', 'Company', 'Code', 'Phone', 'Email', 'Date of Birth'];
} else {
    $headers = ['Customer Name', 'Company', 'Code', 'Phone', 'Email', 'Anniversary Date'];
}

$sheet->fromArray($headers, NULL, "A{$filterRow}");
$sheet->getStyle("A{$filterRow}:{$lastCol}{$filterRow}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
]);
foreach (range('A', $lastCol) as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

// ── Data Rows
$r = $filterRow + 1;
foreach ($customers as $row) {
    $dob         = $row['date_of_birth']    ? date('d M Y', strtotime($row['date_of_birth']))    : '-';
    $anniversary = $row['anniversary_date'] ? date('d M Y', strtotime($row['anniversary_date'])) : '-';
    $address     = trim(($row['address_line1'] ?? '') . ', ' . ($row['city'] ?? '') . ', ' . ($row['state'] ?? ''), ', ');

    $sheet->setCellValue('A'.$r, $row['customer_name']);
    $sheet->setCellValue('B'.$r, $row['company_name'] ?? '');
    $sheet->setCellValue('C'.$r, $row['customer_code'] ?? '');
    $sheet->setCellValue('D'.$r, $row['phone'] ?? '');
    $sheet->setCellValue('E'.$r, $row['email'] ?? '');

    if ($report_type === 'contact') {
        $sheet->setCellValue('F'.$r, $address);
        $sheet->setCellValue('G'.$r, $dob);
        $sheet->setCellValue('H'.$r, $anniversary);
    } elseif ($report_type === 'birthday') {
        $sheet->setCellValue('F'.$r, $dob);
    } else {
        $sheet->setCellValue('F'.$r, $anniversary);
    }
    $r++;
}

$filename = 'customer_' . $report_type . '_report_' . date('Y_m_d') . '.xlsx';
$tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
(new Xlsx($spreadsheet))->save($tmpFile);
ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"{$filename}\"");
header('Cache-Control: max-age=0');
readfile($tmpFile);
unlink($tmpFile);
exit;
