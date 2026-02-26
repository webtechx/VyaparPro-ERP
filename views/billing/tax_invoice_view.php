<?php
// Professional Tax Invoice View (Matches Proforma Style)
$title = 'Tax Invoice - ' . (isset($_GET['id']) ? $_GET['id'] : '');

// Dynamic Sidebar Menu Highlighting
$activeMenu = 'tax_invoice'; // Default
if (isset($_GET['ref']) && !empty($_GET['ref'])) {
    $activeMenu = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['ref']);
} elseif (isset($_SERVER['HTTP_REFERER'])) {
    $path = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
    if ($path) {
        $parts = explode('/', rtrim($path, '/'));
        $last_part = end($parts);
        if (!empty($last_part) && $last_part !== 'view_invoice' && strpos($last_part, '.php') === false) {
            $activeMenu = $last_part;
        }
    }
}

require_once __DIR__ . '/../../config/conn.php';

$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($invoice_id <= 0) { echo "Invalid Invoice ID"; return; }

// --- Data Fetching ---
$sql = "SELECT i.*, 
        c.customer_name, c.company_name, c.address as c_address, c.city as c_city, c.state as c_state, c.pincode as c_pincode, c.gst_number as c_gst, c.phone as c_phone,
        e.first_name as sales_fname, e.last_name as sales_lname
        FROM sales_invoices i 
        JOIN customers_listing c ON i.customer_id = c.customer_id 
        LEFT JOIN employees e ON i.sales_employee_id = e.employee_id
        WHERE i.invoice_id = $invoice_id";
$inv = $conn->query($sql)->fetch_assoc();

if(!$inv) { echo "Invoice not found"; exit; }

// Org Details
$org_id = $inv['organization_id'];
$org = $conn->query("SELECT * FROM organizations WHERE organization_id = " . intval($org_id))->fetch_assoc();

// Defaults
$org_email = $org['email'] ?? ''; 
$org_phone = $org['phone'] ?? '--'; 
$org_address = $org['address'] ?? '';
$org_city = $org['city'] ?? '';
$org_state = $org['state'] ?? '---';
$org_pincode = $org['pincode'] ?? '';
$org_gst = $org['gst_number'] ?? '';
$org_bank = $org['bank_name'] ?? '';
$org_acc = $org['account_number'] ?? '';
$org_ifsc = $org['ifsc_code'] ?? '';
$org_acc_holder = $org['account_holder_name'] ?? '';
$org_branch = $org['branch_name'] ?? '';
$org_upi = $org['upi_id'] ?? '';
$org_qr = $org['qr_code'] ?? '';

// QR Code Processing
$qrDataUri = '';
if(!empty($org_qr)){
    $orgCode = $org['organizations_code']; 
    $qrPath = __DIR__ . "/../../uploads/$orgCode/bank_details/$org_qr";
    if(file_exists($qrPath)){
         $type = pathinfo($qrPath, PATHINFO_EXTENSION);
         $data = file_get_contents($qrPath);
         $base64 = base64_encode($data);
         $qrDataUri = 'data:image/' . $type . ';base64,' . $base64;
    }
}

// Items Fetch
$items = [];
$itemSql = "SELECT sii.*, il.item_name as item_desc, h.hsn_code, u.unit_name 
            FROM sales_invoice_items sii 
            LEFT JOIN items_listing il ON sii.item_id = il.item_id
            LEFT JOIN hsn_listing h ON il.hsn_id = h.hsn_id 
            LEFT JOIN units_listing u ON sii.unit_id = u.unit_id
            WHERE sii.invoice_id = $invoice_id";
$itemRes = $conn->query($itemSql);

// Recalculate Grand Total for consistency across views
$total_taxable = 0;
$grand_gst = 0;
$loyalty_redeemed = floatval($inv['reward_points_redeemed'] ?? 0);
$adjustment = floatval($inv['adjustment'] ?? 0);

// Helper: Amount in Words
function getIndianCurrency($number) {
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundred = null;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(0 => '', 1 => 'One', 2 => 'Two',
        3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six',
        7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
        13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
        19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
        40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty',
        70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety');
    $digits = array('', 'Hundred','Thousand','Lakh', 'Crore');
    while( $i < $digits_length ) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += $divider == 10 ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number].' '. $digits[$counter]. $plural.' '.$hundred:$words[floor($number / 10) * 10].' '.$words[$number % 10]. ' '.$digits[$counter].$plural.' '.$hundred;
        } else $str[] = null;
    }
    $Rupees = implode('', array_reverse($str));
    $paise = ($decimal > 0) ? " and " . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
    return ($Rupees ? $Rupees . ' Rupees' : '') . $paise . " Only";
}

// We need to calculate grand total before showing it in words. 
$calc_items = [];
while($row = $itemRes->fetch_assoc()) {
    $row['qty'] = floatval($row['quantity']);
    $row['rate'] = floatval($row['rate']);
    $row['taxable'] = floatval($row['amount']);
    
    // Tax
    // Use item gst_rate if present, else invoice master rate
    $g_rate = (isset($row['gst_rate']) && floatval($row['gst_rate']) > 0) ? floatval($row['gst_rate']) : floatval($inv['gst_rate']);
    $row['gst_percent'] = $g_rate;
    
    $gst_amt = $row['taxable'] * ($g_rate / 100);
    $row['gst_amt'] = $gst_amt;
    $row['total'] = $row['taxable'] + $gst_amt; // Line Total
    
    $total_taxable += $row['taxable'];
    $grand_gst += $gst_amt;
    
    $calc_items[] = $row;
}
$display_grand_total = $total_taxable + $grand_gst + $adjustment - $loyalty_redeemed;
if($display_grand_total < 0) $display_grand_total = 0;

$grand_total_words = getIndianCurrency($display_grand_total);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?></title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Shared Styles from Proforma */
        .controls { text-align: center; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 8px 16px; margin: 0 5px; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 14px; border: none; cursor: pointer; }
        .btn-print { background: #333; }
        .btn-pdf { background: #198754; } 
        .btn-back { background: #f0ad4e; }

        .row { display: flex; flex-wrap: wrap; margin-right: -15px; margin-left: -15px; }
        .col-6 { flex: 0 0 50%; max-width: 50%; padding-right: 15px; padding-left: 15px; box-sizing: border-box; }
        
        .header-section { padding: 30px 40px; border-bottom: 2px solid #333; }
        .company-info h2 { margin: 0 0 5px 0; color: #1a1a1a; font-size: 22px; text-transform: uppercase; }
        .company-info p { margin: 2px 0; font-size: 12px; color: #555; }

        .invoice-title { font-size: 18px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #333; }
        .meta-table td { padding: 3px 10px; font-size: 13px; }
        .meta-label { font-weight: 600; color: #555; text-align: right; }
        .meta-val { font-weight: 700; color: #000; }

        .address-section { display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1px solid #ddd; }
        .address-box { padding: 20px 40px; font-size: 13px; }
        .address-box:first-child { border-right: 1px solid #ddd; }
        .addr-header { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #777; margin-bottom: 8px; letter-spacing: 0.5px; }
        .addr-name { font-size: 15px; font-weight: 700; margin-bottom: 5px; color: #000; }
        .addr-text { line-height: 1.5; color: #333; }

        .items-section { padding: 20px 40px; }
        table.invoice-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        table.invoice-table th { background: #333; color: #fff; padding: 8px 10px; text-align: left; text-transform: uppercase; font-weight: 600; font-size: 11px; border: 1px solid #333; }
        table.invoice-table td { padding: 8px 10px; border: 1px solid #e0e0e0; vertical-align: top; color: #333; }
        
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .text-bold { font-weight: 700; }
        .row-stripe:nth-child(even) { background-color: #fafafa; }

        .totals-section { display: flex; justify-content: space-between; padding: 0 40px 30px 40px; }
        .notes-area { width: 55%; font-size: 11px; color: #555; }
        .notes-area h5 { margin: 0 0 5px 0; font-size: 12px; color: #333; }
        
        .totals-table-area { width: 40%; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 6px 0; border-bottom: 1px solid #eee; }
        .totals-table .total-label { text-align: right; padding-right: 15px; color: #666; }
        .totals-table .total-val { text-align: right; font-weight: 600; color: #000; }
        .grand-total-row td { border-top: 2px solid #333; border-bottom: 2px solid #333; padding: 10px 0; font-size: 16px; font-weight: 700; color: #000; }
        .net-total-row td { border-top: 2px solid #28a745; border-bottom: 2px solid #28a745; padding: 10px 0; font-size: 16px; font-weight: 700; color: #28a745; background: #f8fff9; }

        .amount-words-bar { margin: 0 40px 20px 40px; padding: 8px 12px; background: #f5f5f5; border-left: 4px solid #333; font-size: 12px; font-weight: 600; }
        .amount-words-bar span { font-weight: 400; color: #666; margin-right: 5px; }

        .footer-section { padding: 20px 40px 40px 40px; display: flex; justify-content: space-between; align-items: flex-start; border-top: 1px solid #ddd; margin: 0 0 0 0; }
        .qr-box { width: 18%; text-align: center; background: #f9f9f9; border: 1px solid #ddd; padding: 10px; border-radius: 4px; }
        .qr-box .qr-label { font-size: 11px; font-weight: 700; color: #333; margin-bottom: 4px; }
        .qr-box .qr-sublabel { font-size: 10px; color: #777; margin-top: 4px; }
        .bank-details { width: 46%; font-size: 12px; color: #555; line-height: 1.8; padding: 0 15px; }
        .bank-details strong { font-size: 13px; color: #333; }
        .signature-box { text-align: right; width: 30%; }
        .signature-line { margin-top: 50px; border-top: 1px solid #333; width: 160px; display: inline-block; }

        @media print {
            body { background: #fff; padding: 0; margin: 0; print-color-adjust: exact; }
            .invoice-box { width: 100%; border: none; }
            .controls { display: none; }
            @page { margin: 0; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    <div class="controls d-print-none mt-2">
        <button onclick="window.history.back()" class="btn btn-back">Back</button>
        <button onclick="window.print()" class="btn btn-print">Print Invoice</button>
        <a href="<?= $basePath ?>/controller/billing/download_invoice_pdf.php?id=<?= $invoice_id ?>" class="btn btn-success" target="_blank">Download PDF</a>
    </div>

    <div class="invoice-box">
        
        <!-- Header -->
        <div class="header-section">
            <div class="row">
                <div class="col-6">
                    <div class="company-info">
                        <?php if(!empty($org['organization_logo'])): ?>
                            <img src="<?= $basePath ?>/uploads/<?= $_SESSION['organization_code'] ?>/organization_logo/<?= $org['organization_logo'] ?>" alt="Logo" style="margin-bottom: 10px; height: 50px;">
                        <?php endif; ?>
                        <h2><?= htmlspecialchars($org['organization_name'] ?? '') ?></h2>
                        <p><?= htmlspecialchars($org_address) ?>, <?= htmlspecialchars($org_city) ?>, <?= htmlspecialchars($org_state) ?> - <?= htmlspecialchars($org_pincode) ?></p>
                        <p>GSTIN: <strong><?= htmlspecialchars($org_gst) ?></strong> &nbsp;|&nbsp; State Code: <?= (isset($org_gst) && strlen($org_gst)>=2) ? substr($org_gst, 0, 2) : '' ?></p>
                        <p>Email: <?= htmlspecialchars($org_email) ?> &nbsp;|&nbsp; Phone: <?= htmlspecialchars($org_phone) ?></p>
                    </div>
                </div>

                <div class="col-6" style="text-align: right;">
                    <div style="text-align: right;">
                        <div class="invoice-title" style="margin-bottom: 15px; font-size: 24px; color: #333;">TAX INVOICE</div>
                        <table class="meta-table" style="margin-left: auto; border-collapse: separate; border-spacing: 0 5px;">
                            <tr>
                                <td class="meta-label" style="padding-right: 15px;">Invoice No:</td>
                                <td class="meta-val"><?= htmlspecialchars($inv['invoice_number'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <td class="meta-label" style="padding-right: 15px;">Date:</td>
                                <td class="meta-val"><?= date('d-M-Y', strtotime($inv['invoice_date'])) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
 
        <!-- Address Section -->
        <div class="address-section">
            <div class="address-box">
                <div class="addr-header">Billed To</div>
                <div class="addr-name"><?= htmlspecialchars($inv['company_name'] ?: $inv['customer_name'] ?? '') ?></div>
                <div class="addr-text">
                    <?= htmlspecialchars($inv['c_address'] ?? '') ?><br>
                    <?= htmlspecialchars($inv['c_city'] ?? '') ?>, <?= htmlspecialchars($inv['c_state'] ?? '') ?> - <?= htmlspecialchars($inv['c_pincode'] ?? '') ?><br>
                    GSTIN: <strong><?= htmlspecialchars($inv['c_gst'] ?? '') ?></strong><br>
                    State Code: <?= (isset($inv['c_gst']) && strlen($inv['c_gst'])>=2) ? substr($inv['c_gst'], 0, 2) : '-' ?>
                </div>
            </div>
            <div class="address-box">
                <div class="addr-header">Shipped To / Delivery Details</div>
                <div class="addr-name"><?= htmlspecialchars($inv['company_name'] ?: $inv['customer_name'] ?? '') ?></div>
                <div class="addr-text">
                    <i>(Same as Billing Address)</i><br>
                    <br>
                    Mode of Delivery: <strong><?= htmlspecialchars($inv['delivery_mode'] ?: 'NA') ?></strong><br>
                    Sales Person: <strong><?= htmlspecialchars(($inv['sales_fname'] ?? '') . ' ' . ($inv['sales_lname'] ?? '')) ?></strong>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="items-section">
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">#</th>
                        <th width="35%">Item Description</th>
                        <th width="10%" class="text-center">HSN</th>
                        <th width="10%" class="text-center">Qty</th>
                        <th width="12%" class="text-right">Rate</th>
                        <th width="13%" class="text-right">Taxable</th>
                        <th width="5%" class="text-center">GST</th>
                        <th width="10%" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    foreach($calc_items as $row): 
                    ?>
                    <tr class="row-stripe">
                        <td class="text-center"><?= $counter++ ?></td>
                        <td>
                            <div class="text-bold"><?= htmlspecialchars($row['item_name'] ?? '') ?></div>
                            <?php if(!empty($row['item_desc']) && $row['item_desc'] !== $row['item_name']) : ?>
                            <div style="font-size: 10px; color: #666;"><?= htmlspecialchars($row['item_desc']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= $row['hsn_code'] ?: '-' ?></td>
                        <td class="text-center"><?= $row['qty'] + 0 ?> <?= $row['unit_name'] ?></td>
                        <td class="text-right"><?= number_format($row['rate'], 2) ?></td>
                        <td class="text-right"><?= number_format($row['taxable'], 2) ?></td>
                        <td class="text-center"><?= $row['gst_percent'] + 0 ?>%</td>
                        <td class="text-right text-bold"><?= number_format($row['total'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Amount Word -->
        <div class="amount-words-bar">
            <span>Amount in Words:</span> <?= $grand_total_words ?>
        </div>

        <!-- Totals & Notes -->
        <div class="totals-section">
            <div class="notes-area">
                <?php if(!empty($inv['terms_conditions'])): ?>
                    <h5>Terms & Conditions:</h5>
                    <p><?= htmlspecialchars($inv['terms_conditions'] ?? '') ?></p>
                <?php endif; ?>
                <br>
                <?php if(!empty($inv['notes'])): ?>
                    <h5>Notes:</h5>
                    <p><?= htmlspecialchars($inv['notes'] ?? '') ?></p>
                <?php endif; ?>
            </div>

            <div class="totals-table-area">
                <table class="totals-table">
                    <tr>
                        <td class="total-label">Taxable Amount</td>
                        <td class="total-val"><?= number_format($total_taxable, 2) ?></td>
                    </tr>
                    
                    <?php 
                    // Calculate Tax Spits for Display
                    // For invoices, usually aggregated. We will show aggregated if not inter-state splitting logic is needed just for display.
                    // But usually, we just show CGST+SGST or IGST based on inv['gst_type']
                    // Note: We aggregated grand_gst above. Now we split it for display based on type.
                    
                    // Simple Logic: If IGST, full amount. ELSE split 50/50.
                    $is_igst = ($inv['gst_type'] === 'IGST' || $inv['gst_type'] === 'inter_state');
                    if($is_igst):
                    ?>
                    <tr>
                        <td class="total-label">IGST Output</td>
                        <td class="total-val"><?= number_format($grand_gst, 2) ?></td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td class="total-label">CGST Output</td>
                        <td class="total-val"><?= number_format($grand_gst / 2, 2) ?></td>
                    </tr>
                    <tr>
                        <td class="total-label">SGST Output</td>
                        <td class="total-val"><?= number_format($grand_gst / 2, 2) ?></td>
                    </tr>
                    <?php endif; ?>

                    <?php if($adjustment != 0): ?>
                    <tr>
                        <td class="total-label">Round Off / Adj</td>
                        <td class="total-val"><?= number_format($adjustment, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if($loyalty_redeemed > 0): ?>
                    <tr>
                        <td class="total-label" style="color: green;">Loyalty Redeemed</td>
                        <td class="total-val" style="color: green;">- <?= number_format($loyalty_redeemed, 2) ?></td>
                    </tr>
                    <?php endif; ?>

                    <tr class="grand-total-row">
                        <td style="text-align: right; padding-right: 15px;">Grand Total</td>
                        <td style="text-align: right;">₹ <?= number_format($total_taxable + $grand_gst + $adjustment, 2) ?></td>
                    </tr>
                    
                    <?php if($loyalty_redeemed > 0): ?>
                    <tr class="net-total-row">
                        <td style="text-align: right; padding-right: 15px; font-weight: bold; color: #28a745;">Net Amount Payable</td>
                        <td style="text-align: right; font-weight: bold; color: #28a745;">₹ <?= number_format($display_grand_total, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-section">
            <!-- QR Code (Left) -->
            <div class="qr-box">
                <?php if (!empty($qrDataUri)): ?>
                    <div class="qr-label"><?= htmlspecialchars($org['organization_name'] ?? '') ?></div>
                    <div class="qr-sublabel">Scan and Pay</div>
                    <img src="<?= $qrDataUri ?>" alt="QR Code" style="height: 100px; width: 100px; display: block; margin: 6px auto 0 auto;">
                <?php endif; ?>
            </div>
            <!-- Bank Details (Middle) -->
            <div class="bank-details">
                <strong>Bank Details:</strong><br>
                Account Name: <?= htmlspecialchars($org_acc_holder) ?><br>
                Bank: <?= htmlspecialchars($org_bank) ?><br>
                Branch: <?= htmlspecialchars($org_branch) ?><br>
                A/c No: <?= htmlspecialchars($org_acc) ?><br>
                IFSC: <?= htmlspecialchars($org_ifsc) ?><br>
                UPI: <?= htmlspecialchars($org_upi) ?>
            </div>
            <!-- Signature (Right) -->
            <div class="signature-box">
                <p style="margin-bottom: 50px; font-weight: 600;">For <?= htmlspecialchars($org['organization_name'] ?? '') ?></p>
                <div class="signature-line"></div>
                <div style="font-size: 11px; margin-top: 5px;">Authorized Signatory</div>
            </div>
        </div>

    </div>

</body>
</html>
