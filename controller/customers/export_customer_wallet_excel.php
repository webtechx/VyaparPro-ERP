<?php
require_once '../../config/auth_guard.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$selected_customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

if (!$selected_customer_id) {
    http_response_code(400);
    echo "Customer ID required."; exit;
}

// ── Fetch customer
$custSql = "SELECT * FROM customers_listing WHERE customer_id = ? AND organization_id = ?";
$cStmt = $conn->prepare($custSql);
$cStmt->bind_param("ii", $selected_customer_id, $_SESSION['organization_id']);
$cStmt->execute();
$customer = $cStmt->get_result()->fetch_assoc();
$cStmt->close();

if (!$customer) { echo "Customer not found."; exit; }

// ── Fetch Ledger
$ledger_entries = [];
$lRes = $conn->query("SELECT * FROM customers_ledger WHERE customer_id = $selected_customer_id ORDER BY transaction_date ASC, created_at ASC");
while ($row = $lRes->fetch_assoc()) $ledger_entries[] = $row;

// ── Fetch Point Transactions
$transactions = [];
$tRes = $conn->query("SELECT * FROM loyalty_point_transactions WHERE customer_id = $selected_customer_id ORDER BY created_at DESC");
while ($row = $tRes->fetch_assoc()) $transactions[] = $row;

// ── Fetch Points Earned History
$earned_history = [];
$eRes = $conn->query("SELECT lpe.*, si.invoice_number FROM loyalty_points_earned lpe LEFT JOIN sales_invoices si ON lpe.invoice_id = si.invoice_id WHERE lpe.customer_id = $selected_customer_id ORDER BY lpe.created_at DESC");
while ($row = $eRes->fetch_assoc()) $earned_history[] = $row;

// ── Fetch Commission Ledger
$commission_ledger = [];
$clRes = $conn->query("SELECT cl.*, si.invoice_number FROM customers_commissions_ledger cl LEFT JOIN sales_invoices si ON cl.invoice_id = si.invoice_id WHERE cl.customer_id = $selected_customer_id ORDER BY cl.created_at DESC");
if ($clRes) while ($row = $clRes->fetch_assoc()) $commission_ledger[] = $row;

// ── Fetch Credit Notes
$credit_notes = [];
$cnRes = $conn->query("SELECT cn.*, si.invoice_number FROM credit_notes cn LEFT JOIN sales_invoices si ON cn.invoice_id = si.invoice_id WHERE cn.customer_id = $selected_customer_id ORDER BY cn.created_at DESC");
if ($cnRes) while ($row = $cnRes->fetch_assoc()) $credit_notes[] = $row;

// ── Helper: header style
function styleHeader($sheet, $range, $rgb = '5D282A') {
    $sheet->getStyle($range)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]],
    ]);
}

function addSheetTitle($sheet, $lastCol, $title, $custName, $custCode) {
    $sheet->mergeCells("A1:{$lastCol}1");
    $sheet->setCellValue('A1', $title);
    $sheet->getStyle('A1')->applyFromArray([
        'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D282A']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(26);

    $sheet->mergeCells("A2:{$lastCol}2");
    $sheet->setCellValue('A2', "Customer: {$custName}  |  Code: {$custCode}  |  Generated: " . date('d M Y, h:i A'));
    $sheet->getStyle('A2')->applyFromArray([
        'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '5D282A']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5EBD0']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
    ]);
    return 4; // start data row
}

$custName = $customer['company_name']
    ? $customer['company_name'] . ' (' . $customer['customer_name'] . ')'
    : $customer['customer_name'];
$custCode = $customer['customer_code'] ?? '';

$spreadsheet = new Spreadsheet();

// ═══════════════════════════════════════════════
// SHEET 1: Customer Ledger
// ═══════════════════════════════════════════════
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('Ledger');
$startRow = addSheetTitle($sheet1, 'F', 'CUSTOMER LEDGER', $custName, $custCode);
$headers  = ['Date', 'Particulars', 'Ref Type', 'Debit (₹)', 'Credit (₹)', 'Balance (₹)'];
$sheet1->fromArray($headers, NULL, "A{$startRow}");
styleHeader($sheet1, "A{$startRow}:F{$startRow}");
foreach (range('A','F') as $col) $sheet1->getColumnDimension($col)->setAutoSize(true);

$r = $startRow + 1;
foreach ($ledger_entries as $l) {
    $sheet1->setCellValue('A'.$r, date('d M Y', strtotime($l['transaction_date'])));
    $sheet1->setCellValue('B'.$r, $l['particulars']);
    $sheet1->setCellValue('C'.$r, ucwords($l['reference_type']));
    $sheet1->setCellValue('D'.$r, $l['debit'] > 0 ? number_format($l['debit'], 2, '.', '') : '');
    $sheet1->setCellValue('E'.$r, $l['credit'] > 0 ? number_format($l['credit'], 2, '.', '') : '');
    $sheet1->setCellValue('F'.$r, number_format($l['balance'], 2, '.', ''));
    $r++;
}

// ═══════════════════════════════════════════════
// SHEET 2: Point Transactions
// ═══════════════════════════════════════════════
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Point Transactions');
$startRow = addSheetTitle($sheet2, 'F', 'LOYALTY POINT TRANSACTIONS', $custName, $custCode);
$headers  = ['Date', 'Type', 'Note', 'Points', 'Balance After', 'Expiry Date'];
$sheet2->fromArray($headers, NULL, "A{$startRow}");
styleHeader($sheet2, "A{$startRow}:F{$startRow}");
foreach (range('A','F') as $col) $sheet2->getColumnDimension($col)->setAutoSize(true);

$r = $startRow + 1;
foreach ($transactions as $t) {
    $sheet2->setCellValue('A'.$r, date('d M Y, h:i A', strtotime($t['created_at'])));
    $sheet2->setCellValue('B'.$r, strtoupper($t['transaction_type']));
    $sheet2->setCellValue('C'.$r, $t['note']);
    $sheet2->setCellValue('D'.$r, number_format($t['points'], 2, '.', ''));
    $sheet2->setCellValue('E'.$r, number_format($t['balance_after_transaction'], 2, '.', ''));
    $sheet2->setCellValue('F'.$r, $t['expiry_date'] ? date('d M Y', strtotime($t['expiry_date'])) : '-');
    $r++;
}

// ═══════════════════════════════════════════════
// SHEET 3: Points Earned History
// ═══════════════════════════════════════════════
$sheet3 = $spreadsheet->createSheet();
$sheet3->setTitle('Points Earned');
$startRow = addSheetTitle($sheet3, 'D', 'LOYALTY POINTS EARNED HISTORY', $custName, $custCode);
$headers  = ['Date', 'Invoice #', 'Bill Amount (₹)', 'Points Earned'];
$sheet3->fromArray($headers, NULL, "A{$startRow}");
styleHeader($sheet3, "A{$startRow}:D{$startRow}");
foreach (range('A','D') as $col) $sheet3->getColumnDimension($col)->setAutoSize(true);

$r = $startRow + 1;
foreach ($earned_history as $e) {
    $sheet3->setCellValue('A'.$r, date('d M Y', strtotime($e['created_at'])));
    $sheet3->setCellValue('B'.$r, $e['invoice_number'] ?: '#'.$e['invoice_id']);
    $sheet3->setCellValue('C'.$r, number_format($e['bill_amount'], 2, '.', ''));
    $sheet3->setCellValue('D'.$r, number_format($e['points_earned'], 2, '.', ''));
    $r++;
}

// ═══════════════════════════════════════════════
// SHEET 4: Commission Ledger
// ═══════════════════════════════════════════════
$sheet4 = $spreadsheet->createSheet();
$sheet4->setTitle('Commission Ledger');
$startRow = addSheetTitle($sheet4, 'D', 'COMMISSION LEDGER', $custName, $custCode);
$headers  = ['Date', 'Particulars / Ref', 'Amount (₹)', 'Notes'];
$sheet4->fromArray($headers, NULL, "A{$startRow}");
styleHeader($sheet4, "A{$startRow}:D{$startRow}");
foreach (range('A','D') as $col) $sheet4->getColumnDimension($col)->setAutoSize(true);

$r = $startRow + 1;
foreach ($commission_ledger as $c) {
    $amt = floatval($c['commission_amount']);
    $ref = $c['invoice_number'] ?: ($amt < 0 ? 'PAYOUT' : 'ADJUSTMENT');
    $sheet4->setCellValue('A'.$r, date('d M Y, h:i A', strtotime($c['created_at'])));
    $sheet4->setCellValue('B'.$r, $ref);
    $sheet4->setCellValue('C'.$r, number_format($amt, 2, '.', ''));
    $sheet4->setCellValue('D'.$r, $c['notes'] ?? '');
    $r++;
}

// ═══════════════════════════════════════════════
// SHEET 5: Credit Notes
// ═══════════════════════════════════════════════
$sheet5 = $spreadsheet->createSheet();
$sheet5->setTitle('Credit Notes');
$startRow = addSheetTitle($sheet5, 'F', 'CREDIT NOTES', $custName, $custCode);
$headers  = ['Date', 'Credit Note #', 'Invoice #', 'Amount (₹)', 'Reason', 'Status'];
$sheet5->fromArray($headers, NULL, "A{$startRow}");
styleHeader($sheet5, "A{$startRow}:F{$startRow}");
foreach (range('A','F') as $col) $sheet5->getColumnDimension($col)->setAutoSize(true);

$r = $startRow + 1;
foreach ($credit_notes as $cn) {
    $sheet5->setCellValue('A'.$r, date('d M Y', strtotime($cn['created_at'])));
    $sheet5->setCellValue('B'.$r, $cn['credit_note_number'] ?? $cn['id']);
    $sheet5->setCellValue('C'.$r, $cn['invoice_number'] ?? '-');
    $sheet5->setCellValue('D'.$r, number_format($cn['total_amount'], 2, '.', ''));
    $sheet5->setCellValue('E'.$r, $cn['reason'] ?? '');
    $sheet5->setCellValue('F'.$r, ucfirst($cn['status'] ?? 'active'));
    $r++;
}

// Set Sheet 1 active
$spreadsheet->setActiveSheetIndex(0);

$safeCode = preg_replace('/[^A-Za-z0-9_-]/', '_', $custCode ?: 'customer');

// -- Output (safe, clean, no corruption)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="wallet_' . $safeCode . '_' . date('Y_m_d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
