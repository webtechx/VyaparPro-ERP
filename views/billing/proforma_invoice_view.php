<?php
// Professional Proforma Invoice View
$title = 'Proforma Invoice - ' . (isset($_GET['id']) ? $_GET['id'] : '');
require_once __DIR__ . '/../../config/conn.php';

$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($invoice_id <= 0) return;

// --- Data Fetching (Optimized) ---
$sql = "SELECT i.*, 
        c.customer_name, c.company_name, c.address as c_address, c.city as c_city, c.state as c_state, c.pincode as c_pincode, c.gst_number as c_gst, c.phone as c_phone,
        e.first_name as sales_fname, e.last_name as sales_lname,
        me.first_name as make_fname, me.last_name as make_lname
        FROM proforma_invoices i 
        JOIN customers_listing c ON i.customer_id = c.customer_id 
        LEFT JOIN employees e ON i.sales_employee_id = e.employee_id
        LEFT JOIN employees me ON i.make_employee_id = me.employee_id
        WHERE i.proforma_invoice_id = $invoice_id";
$inv = $conn->query($sql)->fetch_assoc();

if(!$inv) { echo "Invoice not found"; exit; }

// Org Details
// Assuming proforma_invoices has organization_id. If not, we might need to rely on session or specific column.
// Based on user schema, table column is organization_id.
$org_id_val = $inv['organization_id'] ?? $_SESSION['organization_id'] ?? 0;
$org = $conn->query("SELECT * FROM organizations WHERE organization_id = " . intval($org_id_val))->fetch_assoc();

// Defaults for keys that appear to be missing from the organizations table schema provided
$org_email = $org['email'] ?? ''; // Column not in provided list
$org_phone = $org['phone'] ?? '--'; // Column not in provided list
$org_desc = ''; // Column not in provided list

// Existing keys
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
$items = [];
$itemRes = $conn->query("SELECT sii.*, il.item_name as item_desc, h.hsn_code, u.unit_name 
                        FROM proforma_invoice_items sii 
                        LEFT JOIN items_listing il ON sii.item_id = il.item_id
                        LEFT JOIN hsn_listing h ON il.hsn_id = h.hsn_id 
                        LEFT JOIN units_listing u ON sii.unit_id = u.unit_id
                        WHERE sii.proforma_invoice_id = $invoice_id");

// Helper: Amount in Words (Indian Format)
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

$grand_total_words = getIndianCurrency($inv['total_amount']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?></title>
    <!-- Fonts: Standard Professional Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
 
 
        /* Controls (Print/Download) */
        .controls {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 5px;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
        .btn-print { background: #333; }
        .btn-pdf { background: #d9534f; }
        .btn-back { background: #f0ad4e; }

        /* Grid System */
        .row { display: flex; flex-wrap: wrap; margin-right: -15px; margin-left: -15px; }
        .col-6 { flex: 0 0 50%; max-width: 50%; padding-right: 15px; padding-left: 15px; box-sizing: border-box; }
        
        /* --- THE INVOICE LAYOUT --- */
        
        .header-section {
            padding: 30px 40px;
            border-bottom: 2px solid #333;
        }
        
        .logo-area img {
            max-width: 150px;
            max-height: 60px;
        }

        .company-info h2 {
            margin: 0 0 5px 0;
            color: #1a1a1a;
            font-size: 22px;
            text-transform: uppercase;
        }
        .company-info p {
            margin: 2px 0;
            font-size: 12px;
            color: #555;
        }

        .invoice-title-bar {
            background-color: #f8f9fa;
            border-bottom: 1px solid #ddd;
            padding: 10px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .invoice-title {
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #333;
        }
        .meta-table td {
            padding: 3px 10px;
            font-size: 13px;
        }
        .meta-label { font-weight: 600; color: #555; text-align: right; }
        .meta-val { font-weight: 700; color: #000; }

        /* Address Grid */
        .address-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-bottom: 1px solid #ddd;
        }
        .address-box {
            padding: 20px 40px;
            font-size: 13px;
        }
        .address-box:first-child { border-right: 1px solid #ddd; }
        
        .addr-header {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #777;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .addr-name { font-size: 15px; font-weight: 700; margin-bottom: 5px; color: #000; }
        .addr-text { line-height: 1.5; color: #333; }

        /* Items Table */
        .items-section {
            padding: 20px 40px;
        }
        table.invoice-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        table.invoice-table th {
            background: #333;
            color: #fff;
            padding: 8px 10px;
            text-align: left;
            text-transform: uppercase;
            font-weight: 600;
            font-size: 11px;
            border: 1px solid #333;
        }
        table.invoice-table td {
            padding: 8px 10px;
            border: 1px solid #e0e0e0;
            vertical-align: top;
            color: #333;
        }
        
        /* Specific Alignments */
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .text-bold { font-weight: 700; }
        .row-stripe:nth-child(even) { background-color: #fafafa; }

        /* Totals Area */
        .totals-section {
            display: flex;
            justify-content: space-between;
            padding: 0 40px 30px 40px;
        }
        .notes-area {
            width: 55%;
            font-size: 11px;
            color: #555;
        }
        .notes-area h5 { margin: 0 0 5px 0; font-size: 12px; color: #333; }
        
        .totals-table-area {
            width: 40%;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 6px 0;
            border-bottom: 1px solid #eee;
        }
        .totals-table .total-label { text-align: right; padding-right: 15px; color: #666; }
        .totals-table .total-val { text-align: right; font-weight: 600; color: #000; }
        
        .grand-total-row td {
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
            padding: 10px 0;
            font-size: 16px;
            font-weight: 700;
            color: #000;
        }

        /* Amount in Words Bar */
        .amount-words-bar {
            margin: 0 40px 20px 40px;
            padding: 8px 12px;
            background: #f5f5f5;
            border-left: 4px solid #333;
            font-size: 12px;
            font-weight: 600;
        }
        .amount-words-bar span { font-weight: 400; color: #666; margin-right: 5px; }

        /* Footer */
        .footer-section {
            padding: 20px 40px 40px 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-top: 1px solid #ddd;
        }
        .qr-box {
            width: 18%;
            text-align: center;
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
        }
        .qr-box .qr-label { font-size: 11px; font-weight: 700; color: #333; margin-bottom: 4px; }
        .qr-box .qr-sublabel { font-size: 10px; color: #777; margin-top: 4px; }
        .bank-details {
            width: 46%;
            font-size: 12px;
            color: #555;
            line-height: 1.8;
            padding: 0 15px;
        }
        .bank-details strong { font-size: 13px; color: #333; }
        .signature-box {
            text-align: right;
            width: 30%;
        }
        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #333;
            width: 160px;
            display: inline-block;
        }

        /* Print Override */
        @media print {
            body { 
                background: #fff; 
                padding: 0; 
                margin: 0; 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
            }
            .invoice-box { 
                box-shadow: none; 
                margin: 0; 
                width: 100%; 
                max-width: 100%; 
                border: none;
            }
            .controls { display: none; }
            @page { margin: 0; size: auto; }
            
            /* Prevent table rows from breaking */
            tr { page-break-inside: avoid; page-break-after: auto; }
            .invoice-box { display: block; width: 100%; }
        }

    </style>
</head>
<body>

    <div class="controls d-print-none mt-2">
        <button onclick="window.history.back()" class="btn btn-warning">Back</button>
        <button onclick="window.print()" class="btn btn-print">Print Invoice</button>
        <!-- Use URL to PDF script if exists -->
        <a href="<?= $basePath ?>/controller/billing/download_proforma_pdf.php?id=<?= $invoice_id ?>" class="btn btn-success" target="_blank">Download PDF</a>
    </div>

    <div class="invoice-box">
        
        <!-- Header -->
        <div class="header-section">
            <div class="row">
                <div class="col-6">
                    <div class="company-info">
                        <?php if(!empty($org) && !empty($org['organization_logo'])): ?>
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
                        <div class="invoice-title" style="margin-bottom: 15px; font-size: 24px; color: #333;">PROFORMA INVOICE</div>
                        <table class="meta-table" style="margin-left: auto; border-collapse: separate; border-spacing: 0 5px;">
                            <tr>
                                <td class="meta-label" style="padding-right: 15px;">Proforma Invoice No:</td>
                                <td class="meta-val"><?= htmlspecialchars($inv['proforma_invoice_number'] ?? '') ?></td>
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
                    GSTIN: <strong><?= htmlspecialchars($inv['c_gst'] ?? '') ?></strong>
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
                        <th width="40%">Item Description</th>
                        <th width="10%" class="text-center">HSN</th>
                        <th width="8%" class="text-center">Qty</th>
                        <th width="12%" class="text-right">Rate</th>
                        <th width="10%" class="text-right">Disc.</th>
                        <th width="5%" class="text-center">GST</th>
                        <th width="15%" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    $total_discount = 0;
                    if($itemRes) while($row = $itemRes->fetch_assoc()): 
                        // Calculations
                        $qty = floatval($row['quantity']);
                        $rate = floatval($row['rate']);
                        $gross = $qty * $rate;
                        $disc = ($row['discount_type'] == 'percentage') ? ($gross * $row['discount']/100) : $row['discount'];
                        $total_discount += $disc;
                        $taxable = $gross - $disc;
                        $gst_amt = $taxable * ($row['gst_rate']/100);
                        $line_total = $taxable + $gst_amt;
                    ?>
                    <tr class="row-stripe">
                        <td class="text-center"><?= $counter++ ?></td>
                        <td>
                            <div class="text-bold"><?= htmlspecialchars($row['item_name'] ?? '') ?></div>
                            <div style="font-size: 10px; color: #666;"><?= htmlspecialchars($row['description'] ?? '') ?></div>
                        </td>
                        <td class="text-center"><?= $row['hsn_code'] ?: '-' ?></td>
                        <td class="text-center"><?= $qty + 0 ?> <?= $row['unit_name'] ?></td>
                        <td class="text-right"><?= number_format($rate, 2) ?></td>
                        <td class="text-right"><?= $disc > 0 ? number_format($disc, 2) : '-' ?></td>
                        <td class="text-center"><?= $row['gst_rate'] + 0 ?>%</td>
                        <td class="text-right text-bold"><?= number_format($line_total, 2) ?></td>
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
                        <td class="total-label">Gross Amount</td>
                        <td class="total-val"><?= number_format($inv['sub_total'], 2) ?></td>
                    </tr>
                    <?php if($total_discount > 0): ?>
                    <tr>
                        <td class="total-label" style="color: #d9534f;">Discount</td>
                        <td class="total-val" style="color: #d9534f;">- <?= number_format($total_discount, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <tr>
                        <td class="total-label">Taxable Amount</td>
                        <td class="total-val"><?= number_format($inv['sub_total'] - $total_discount, 2) ?></td>
                    </tr>

                    <?php if($inv['gst_type'] == 'IGST'): ?>
                        <tr>
                            <td class="total-label">IGST Output</td>
                            <td class="total-val"><?= number_format($inv['igst_amount'], 2) ?></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td class="total-label">CGST Output</td>
                            <td class="total-val"><?= number_format($inv['cgst_amount'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="total-label">SGST Output</td>
                            <td class="total-val"><?= number_format($inv['sgst_amount'], 2) ?></td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td class="total-label">Round Off</td>
                        <td class="total-val"><?= number_format($inv['adjustment'], 2) ?></td>
                    </tr>

                    <tr class="grand-total-row">
                        <td style="text-align: right; padding-right: 15px;">Grand Total</td>
                        <td style="text-align: right;">₹ <?= number_format($inv['total_amount'], 2) ?></td>
                    </tr>
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
