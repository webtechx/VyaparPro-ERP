<?php
require_once '../../config/auth_guard.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// ── Params
$organization_id   = $_SESSION['organization_id'];
$start_date        = $_GET['start_date']        ?? date('Y-m-01');
$end_date          = $_GET['end_date']          ?? date('Y-m-d');
$customers_type_id = isset($_GET['customers_type_id']) ? intval($_GET['customers_type_id']) : 0;
$customer_id       = isset($_GET['customer_id'])       ? intval($_GET['customer_id'])       : 0;

// ── Fetch data (same logic as loyalty_point_report.php controller)
$sql = "SELECT
            c.customer_id, c.customer_name, c.company_name, c.customer_code,
            c.phone, c.email, ct.customers_type_name,
            c.loyalty_point_balance as global_balance,
            SUM(CASE WHEN t.transaction_type = 'EARN'    THEN t.points ELSE 0 END) as total_earned,
            SUM(CASE WHEN t.transaction_type = 'REDEEM'  THEN t.points ELSE 0 END) as total_redeemed,
            SUM(CASE WHEN t.transaction_type = 'EXPIRED' THEN t.points ELSE 0 END) as total_expired,
            COUNT(DISTINCT CASE WHEN t.transaction_type = 'EARN' THEN t.invoice_id END) as invoice_count
        FROM customers_listing c
        LEFT JOIN customers_type_listing ct ON c.customers_type_id = ct.customers_type_id
        LEFT JOIN loyalty_point_transactions t
               ON c.customer_id = t.customer_id
              AND t.organization_id = ?
              AND DATE(t.created_at) BETWEEN ? AND ?
        WHERE c.organization_id = ?";

$params = [$organization_id, $start_date, $end_date, $organization_id];
$types  = "issi";

if ($customers_type_id > 0) { $sql .= " AND c.customers_type_id = ?"; $params[] = $customers_type_id; $types .= "i"; }
if ($customer_id > 0)       { $sql .= " AND c.customer_id = ?";       $params[] = $customer_id;       $types .= "i"; }

$sql .= " GROUP BY c.customer_id
          HAVING (total_earned > 0 OR total_redeemed > 0 OR total_expired > 0 OR global_balance > 0)
          ORDER BY c.loyalty_point_balance DESC, c.customer_name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result     = $stmt->get_result();
$reportData = [];
$tEarned = $tRedeemed = $tExpired = $tBalance = 0;
while ($row = $result->fetch_assoc()) {
    $reportData[] = $row;
    $tEarned   += $row['total_earned'];
    $tRedeemed += $row['total_redeemed'];
    $tExpired  += $row['total_expired'];
    $tBalance  += $row['global_balance'];
}
$stmt->close();

// Prefill type name for filter info
$type_name = '';
if ($customers_type_id > 0) {
    $tS = $conn->prepare("SELECT customers_type_name FROM customers_type_listing WHERE customers_type_id = ?");
    $tS->bind_param("i", $customers_type_id);
    $tS->execute();
    $tS->bind_result($tn);
    if ($tS->fetch()) $type_name = $tn;
    $tS->close();
}
$cust_name_label = '';
if ($customer_id > 0) {
    $cS = $conn->prepare("SELECT customer_name, company_name FROM customers_listing WHERE customer_id = ?");
    $cS->bind_param("i", $customer_id);
    $cS->execute();
    $cS->bind_result($cn, $co);
    if ($cS->fetch()) $cust_name_label = $co ? "$co ($cn)" : $cn;
    $cS->close();
}

// ── Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet       = $spreadsheet->getActiveSheet();
$sheet->setTitle('Loyalty Point Report');
$lastCol = 'H';

// ── Title row
$sheet->mergeCells("A1:{$lastCol}1");
$sheet->setCellValue('A1', 'CUSTOMER LOYALTY POINT REPORT');
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(30);

// ── Generated on
$sheet->mergeCells("A2:{$lastCol}2");
$sheet->setCellValue('A2', 'Period: ' . date('d M Y', strtotime($start_date)) . ' to ' . date('d M Y', strtotime($end_date)) . '   |   Generated: ' . date('d M Y, h:i A'));
$sheet->getStyle('A2')->applyFromArray([
    'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '5D282A']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5EBD0']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
]);

// ── Filter rows
$activeFilters = [];
if (!empty($start_date))    $activeFilters[] = ['Date From',       date('d M Y', strtotime($start_date))];
if (!empty($end_date))      $activeFilters[] = ['Date To',         date('d M Y', strtotime($end_date))];
if (!empty($type_name))     $activeFilters[] = ['Customer Type',   $type_name];
if (!empty($cust_name_label))$activeFilters[] = ['Customer',       $cust_name_label];

$filterRow = 3;
foreach ($activeFilters as $f) {
    $sheet->mergeCells("A{$filterRow}:{$lastCol}{$filterRow}");
    $sheet->setCellValue("A{$filterRow}", '  Filter: ' . $f[0] . '  →  ' . $f[1]);
    $sheet->getStyle("A{$filterRow}")->applyFromArray(['font' => ['size' => 9, 'italic' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0FF']]]);
    $filterRow++;
}
$filterRow++;

// ── Header row
$headers = ['Customer', 'Company', 'Code', 'Type', 'Invoices', 'Earned (pts)', 'Redeemed (pts)', 'Current Wallet Balance (pts)'];
$sheet->fromArray($headers, NULL, "A{$filterRow}");
$sheet->getStyle("A{$filterRow}:{$lastCol}{$filterRow}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
]);
foreach (range('A', $lastCol) as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

// ── Data rows
$r = $filterRow + 1;
foreach ($reportData as $row) {
    $sheet->setCellValue('A'.$r, $row['customer_name']);
    $sheet->setCellValue('B'.$r, $row['company_name']          ?? '');
    $sheet->setCellValue('C'.$r, $row['customer_code']         ?? '');
    $sheet->setCellValue('D'.$r, $row['customers_type_name']   ?? 'N/A');
    $sheet->setCellValue('E'.$r, $row['invoice_count']);
    $sheet->setCellValue('F'.$r, number_format($row['total_earned'],    2, '.', ''));
    $sheet->setCellValue('G'.$r, number_format($row['total_redeemed'],  2, '.', ''));
    $sheet->setCellValue('H'.$r, number_format($row['global_balance'],  2, '.', ''));
    $r++;
}

// ── Totals row
$r++;
$sheet->setCellValue('A'.$r, 'TOTALS');
$sheet->setCellValue('F'.$r, number_format($tEarned,   2, '.', ''));
$sheet->setCellValue('G'.$r, number_format($tRedeemed, 2, '.', ''));
$sheet->setCellValue('H'.$r, number_format($tBalance,  2, '.', ''));
$sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFont()->setBold(true);
$sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8FF');


// -- Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="loyalty_point_report_' . date('Y_m_d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
