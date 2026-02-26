<?php
// Credit Note View (Receipt)
$title = 'Credit Note - ' . (isset($_GET['id']) ? $_GET['id'] : '');

$cn_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($cn_id <= 0) { echo "Invalid Credit Note ID"; return; }

// --- Data Fetching ---
$sql = "SELECT cn.*, inv.invoice_number, inv.reward_points_redeemed as loyalty_redeemed,
        c.customer_name, c.company_name, c.address as c_address, c.city as c_city, c.state as c_state, c.pincode as c_pincode, c.gst_number as c_gst, c.phone as c_phone
        FROM credit_notes cn 
        JOIN customers_listing c ON cn.customer_id = c.customer_id 
        LEFT JOIN sales_invoices inv ON cn.invoice_id = inv.invoice_id
        WHERE cn.credit_note_id = $cn_id";
$cn = $conn->query($sql)->fetch_assoc();

if(!$cn) { echo "Credit Note not found"; exit; }

// Org Details
$org_id = $cn['organization_id'];
$org = $conn->query("SELECT * FROM organizations WHERE organization_id = " . intval($org_id))->fetch_assoc();

// Defaults
$org_email = $org['email'] ?? ''; 
$org_phone = $org['phone'] ?? '--'; 
$org_address = $org['address'] ?? '';
$org_city = $org['city'] ?? '';
$org_state = $org['state'] ?? '---';
$org_pincode = $org['pincode'] ?? '';
$org_gst = $org['gst_number'] ?? '';

// Items Fetch
$items = [];
$itemSql = "SELECT cni.*, u.unit_name 
            FROM credit_note_items cni 
            LEFT JOIN units_listing u ON cni.unit_id = u.unit_id
            WHERE cni.credit_note_id = $cn_id";
$itemRes = $conn->query($itemSql);

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

$grand_total_words = getIndianCurrency($cn['total_amount']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .controls { text-align: center; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 8px 16px; margin: 0 5px; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 14px; border: none; cursor: pointer; }
        .btn-print { background: #333; }
        .btn-back { background: #f0ad4e; }
        
        .invoice-box { max-width: 900px; margin: auto; background: #fff; box-shadow: 0 0 15px rgba(0,0,0,0.05); }
        
        .row { display: flex; flex-wrap: wrap; margin-right: -15px; margin-left: -15px; }
        .col-6 { flex: 0 0 50%; max-width: 50%; padding-right: 15px; padding-left: 15px; box-sizing: border-box; }
        
        .header-section { padding: 30px 40px; border-bottom: 2px solid #d9534f; }
        .company-info h2 { margin: 0 0 5px 0; color: #1a1a1a; font-size: 22px; text-transform: uppercase; }
        .company-info p { margin: 2px 0; font-size: 12px; color: #555; }

        .invoice-title { font-size: 18px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #d9534f; }
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
        table.invoice-table th { background: #d9534f; color: #fff; padding: 8px 10px; text-align: left; text-transform: uppercase; font-weight: 600; font-size: 11px; border: 1px solid #d9534f; }
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
        .grand-total-row td { border-top: 2px solid #d9534f; border-bottom: 2px solid #d9534f; padding: 10px 0; font-size: 16px; font-weight: 700; color: #d9534f; }

        .amount-words-bar { margin: 0 40px 20px 40px; padding: 8px 12px; background: #fff0f0; border-left: 4px solid #d9534f; font-size: 12px; font-weight: 600; }
        .amount-words-bar span { font-weight: 400; color: #666; margin-right: 5px; }

        .footer-section { padding: 0 40px 40px 40px; display: flex; justify-content: space-between; align-items: flex-end; }
        .signature-box { text-align: right; }
        .signature-line { margin-top: 50px; border-top: 1px solid #333; width: 200px; display: inline-block; }

        @media print {
            body { background: #fff; padding: 0; margin: 0; print-color-adjust: exact; }
            .invoice-box { width: 100%; border: none; box-shadow: none; }
            .controls { display: none; }
            @page { margin: 0; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    <div class="controls d-print-none mt-2">
        <a href="<?= $basePath ?>/tax_invoice" class="btn btn-back">Back to Invoices</a>
        <a href="<?= $basePath ?>/controller/billing/download_credit_note_pdf.php?id=<?= $cn['credit_note_id'] ?>" class="btn btn-print" style="background:#5bc0de;">Download PDF</a>
        <button onclick="window.print()" class="btn btn-print">Print Note</button>
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
                        <p>GSTIN: <strong><?= htmlspecialchars($org_gst) ?></strong></p>
                        <p>Email: <?= htmlspecialchars($org_email) ?> &nbsp;|&nbsp; Phone: <?= htmlspecialchars($org_phone) ?></p>
                    </div>
                </div>

                <div class="col-6" style="text-align: right;">
                    <div style="text-align: right;">
                        <div class="invoice-title" style="margin-bottom: 10px;">CREDIT NOTE</div>
                        <table class="meta-table" style="margin-left: auto; border-collapse: separate; border-spacing: 0 5px;">
                            <tr>
                                <td class="meta-label">Credit Note #:</td>
                                <td class="meta-val"><?= htmlspecialchars($cn['credit_note_number']) ?></td>
                            </tr>
                            <tr>
                                <td class="meta-label">Date:</td>
                                <td class="meta-val"><?= date('d-M-Y', strtotime($cn['credit_note_date'])) ?></td>
                            </tr>
                            <tr>
                                <td class="meta-label">Orig. Invoice #:</td>
                                <td class="meta-val"><?= htmlspecialchars($cn['invoice_number'] ?? '-') ?></td>
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
                <div class="addr-name"><?= htmlspecialchars($cn['company_name'] ?: $cn['customer_name'] ?? '') ?></div>
                <div class="addr-text">
                    <?= htmlspecialchars($cn['c_address'] ?? '') ?><br>
                    <?= htmlspecialchars($cn['c_city'] ?? '') ?>, <?= htmlspecialchars($cn['c_state'] ?? '') ?> - <?= htmlspecialchars($cn['c_pincode'] ?? '') ?><br>
                    GSTIN: <strong><?= htmlspecialchars($cn['c_gst'] ?? '') ?></strong>
                </div>
            </div>
            <div class="address-box">
                <div class="addr-header">Reference / Details</div>
                <div class="addr-text">
                   Reason: <strong><?= htmlspecialchars($cn['reason'] ?? 'Not Specified') ?></strong><br>
                   <br>
                   Status: <span style="text-transform: uppercase;"><b><?= htmlspecialchars($cn['status']) ?></b></span>
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
                        <th width="5%" class="text-center">GST %</th>
                        <th width="10%" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    while($row = $itemRes->fetch_assoc()): 
                    ?>
                    <tr class="row-stripe">
                        <td class="text-center"><?= $counter++ ?></td>
                        <td>
                            <div class="text-bold"><?= htmlspecialchars($row['item_name'] ?? '') ?></div>
                        </td>
                        <td class="text-center"><?= $row['hsn_code'] ?: '-' ?></td>
                        <td class="text-center"><?= floatval($row['quantity']) ?> <?= $row['unit_name'] ?></td>
                        <td class="text-right"><?= number_format($row['rate'], 2) ?></td>
                        <td class="text-right"><?= number_format($row['amount'], 2) ?></td>
                        <td class="text-center"><?= floatval($row['gst_rate']) ?>%</td>
                        <td class="text-right text-bold"><?= number_format($row['total_amount'], 2) ?></td>
                    </tr>
                    <?php endwhile; ?>
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
                <?php if(!empty($cn['notes'])): ?>
                    <h5>Notes:</h5>
                    <p><?= htmlspecialchars($cn['notes'] ?? '') ?></p>
                <?php endif; ?>
            </div>

            <div class="totals-table-area">
                <table class="totals-table">
                    <tr>
                        <td class="total-label">Sub Total (Taxable)</td>
                        <td class="total-val"><?= number_format($cn['sub_total'], 2) ?></td>
                    </tr>
                    
                    <tr>
                        <td class="total-label">CGST</td>
                        <td class="total-val"><?= number_format($cn['cgst_amount'], 2) ?></td>
                    </tr>
                    <tr>
                        <td class="total-label">SGST</td>
                        <td class="total-val"><?= number_format($cn['sgst_amount'], 2) ?></td>
                    </tr>
                    <tr>
                        <td class="total-label">IGST</td>
                        <td class="total-val"><?= number_format($cn['igst_amount'], 2) ?></td>
                    </tr>

                    <!-- Debug: Show adjustment value -->
                    <!-- <?php echo "DEBUG: Adjustment value = [" . $cn['adjustment'] . "] Type: " . gettype($cn['adjustment']) . " Floatval: " . floatval($cn['adjustment']); ?> -->
                    
                    <?php if(floatval($cn['adjustment']) != 0): ?>
                    <tr>
                        <td class="total-label">Round Off / Adj</td>
                        <td class="total-val"><?= number_format($cn['adjustment'], 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if(floatval($cn['loyalty_redeemed']) > 0): ?>
                    <tr>
                        <td class="total-label" style="color: green;">Loyalty Redeemed</td>
                        <td class="total-val" style="color: green;">- <?= number_format($cn['loyalty_redeemed'], 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <tr class="grand-total-row">
                        <td style="text-align: right; padding-right: 15px;">Credit Amount</td>
                        <td style="text-align: right;">₹ <?= number_format($cn['total_amount'], 2) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-section">
            <div></div> <!-- Spacer -->
            <div class="signature-box">
                <p style="margin-bottom: 40px; font-weight: 600;">For <?= htmlspecialchars($org['organization_name'] ?? '') ?></p>
                <div class="signature-line"></div>
                <div style="font-size: 11px; margin-top: 5px;">Authorized Signatory</div>
            </div>
        </div>

    </div>
</body>
</html>
