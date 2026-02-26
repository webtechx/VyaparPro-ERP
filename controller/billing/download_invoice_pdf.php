<?php
require_once '../../config/auth_guard.php';
require_once '../../vendor/autoload.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Invoice ID");
}

$invoice_id = intval($_GET['id']);
$organization_id = $_SESSION['organization_id'];

// 1. Fetch Invoice Header & Customer Details (UPDATED to match view)
$sql = "SELECT i.*, 
        c.customer_name, c.company_name, c.address as c_address, c.city as c_city, c.state as c_state, c.pincode as c_pincode, c.gst_number as c_gst, c.phone as c_phone, c.email as c_email,
        e.first_name as sales_fname, e.last_name as sales_lname, e.employee_code as sales_code
        FROM sales_invoices i 
        LEFT JOIN customers_listing c ON i.customer_id = c.customer_id 
        LEFT JOIN employees e ON i.sales_employee_id = e.employee_id
        WHERE i.invoice_id = ? AND i.organization_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $invoice_id, $organization_id);
$stmt->execute();
$inv = $stmt->get_result()->fetch_assoc();

if (!$inv) {
    die("Invoice not found or access denied.");
}

// 2. Fetch Organization Details
$orgQ = $conn->query("SELECT * FROM organizations WHERE organization_id = $organization_id");
$org = $orgQ->fetch_assoc();

// Org Details
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
$org_email = $org['email'] ?? '';
$org_phone = $org['phone'] ?? '';

// 3. Fetch Invoice Items
$itemSql = "SELECT sii.*, il.item_name as item_desc, h.hsn_code, u.unit_name, h.gst_rate as master_gst_rate 
            FROM sales_invoice_items sii 
            LEFT JOIN items_listing il ON sii.item_id = il.item_id
            LEFT JOIN hsn_listing h ON il.hsn_id = h.hsn_id 
            LEFT JOIN units_listing u ON sii.unit_id = u.unit_id
            WHERE sii.invoice_id = ?";
$itemStmt = $conn->prepare($itemSql);
$itemStmt->bind_param("i", $invoice_id);
$itemStmt->execute();
$itemRes = $itemStmt->get_result();

// 4. Calculations (Match View Logic)
$calc_items = [];
$total_taxable = 0;
$grand_gst = 0;
$loyalty_redeemed = floatval($inv['reward_points_redeemed'] ?? 0);
$adjustment = floatval($inv['adjustment'] ?? 0);

while($row = $itemRes->fetch_assoc()) {
    $row['qty'] = floatval($row['quantity']);
    $row['rate'] = floatval($row['rate']);
    $row['taxable'] = floatval($row['amount']);
    
    // Tax Logic
    $g_rate = (isset($row['gst_rate']) && floatval($row['gst_rate']) > 0) ? floatval($row['gst_rate']) : floatval($inv['gst_rate']);
    if($g_rate <= 0 && isset($row['master_gst_rate'])) $g_rate = floatval($row['master_gst_rate']);
    
    $row['gst_percent'] = $g_rate;
    
    $gst_amt = $row['taxable'] * ($g_rate / 100);
    $row['gst_amt'] = $gst_amt;
    $row['total'] = $row['taxable'] + $gst_amt; 
    
    $total_taxable += $row['taxable'];
    $grand_gst += $gst_amt;
    
    $calc_items[] = $row;
}

$display_grand_total = $total_taxable + $grand_gst + $adjustment - $loyalty_redeemed;
if($display_grand_total < 0) $display_grand_total = 0;

// Helper: Amount in Words
function getIndianCurrency($number) {
    if($number <= 0) return 'Zero Only';
    $no = floor($number);
    $point = round($number - $no, 2) * 100;
    $hundred = null;
    $digits_1 = strlen($no);
    $i = 0;
    $str = array();
    $words = array('0' => '', '1' => 'One', '2' => 'Two',
        '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six',
        '7' => 'Seven', '8' => 'Eight', '9' => 'Nine',
        '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve',
        '13' => 'Thirteen', '14' => 'Fourteen',
        '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen',
        '18' => 'Eighteen', '19' => 'Nineteen', '20' => 'Twenty',
        '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty',
        '60' => 'Sixty', '70' => 'Seventy',
        '80' => 'Eighty', '90' => 'Ninety');
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    while ($i < $digits_1) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? '' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred
                : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
        } else $str[] = null;
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    $points = ($point > 0) ? " and " . $words[$point / 10] . " " . $words[$point % 10] . " Paise" : '';
    return $result . " Rupees" . $points . " Only";
}
$grand_total_words = getIndianCurrency($display_grand_total);

// Logo processing
$logo = $org['organization_logo'];
$logoDataUri = '';
if(!empty($logo)){
    // We already have org details in $org
    $orgCode = $org['organizations_code']; // Using fetched org data code
    
    $baseDir = __DIR__ . "/../../uploads/$orgCode/organization_logo/$logo";
    if(file_exists($baseDir)){
         $type = pathinfo($baseDir, PATHINFO_EXTENSION);
         $data = file_get_contents($baseDir);
         $base64 = base64_encode($data);
         $logoDataUri = 'data:image/' . $type . ';base64,' . $base64;
    }
}

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

// Prepare HTML Components
$itemsHtml = '';
$counter = 1;
foreach($calc_items as $row) {
    $itemsHtml .= '
    <tr class="row-stripe">
        <td class="text-center">'.$counter++.'</td>
        <td>
            <div class="text-bold">'.htmlspecialchars($row['item_name'] ?? '').'</div>
            ' . ((!empty($row['item_desc']) && $row['item_desc'] !== $row['item_name']) ? '<div style="font-size: 10px; color: #666;">'.htmlspecialchars($row['item_desc']).'</div>' : '') . '
        </td>
        <td class="text-center">'.($row['hsn_code'] ?: '-').'</td>
        <td class="text-center">'.($row['qty']+0).' '.$row['unit_name'].'</td>
        <td class="text-right">'.number_format($row['rate'], 2).'</td>
        <td class="text-right">'.number_format($row['taxable'], 2).'</td>
        <td class="text-center">'.($row['gst_percent']+0).'%</td>
        <td class="text-right text-bold">'.number_format($row['total'], 2).'</td>
    </tr>';
}

// Tax Breakdown Logic
$taxRows = '';
$is_igst = ($inv['gst_type'] === 'IGST' || $inv['gst_type'] === 'inter_state');
if($is_igst) {
    $taxRows .= '
    <tr>
        <td class="total-label">IGST Output</td>
        <td class="total-val">'.number_format($grand_gst, 2).'</td>
    </tr>';
} else {
    $taxRows .= '
    <tr>
        <td class="total-label">CGST Output</td>
        <td class="total-val">'.number_format($grand_gst / 2, 2).'</td>
    </tr>
    <tr>
        <td class="total-label">SGST Output</td>
        <td class="total-val">'.number_format($grand_gst / 2, 2).'</td>
    </tr>';
}

$logoImg = !empty($logoDataUri) ? '<img src="'.$logoDataUri.'" alt="Logo" style="margin-bottom: 10px; height: 50px;">' : '';

// --- BUILD HTML ---
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; line-height: 1.3; color: #333; }
        .row { width: 100%; clear: both; }
        .col-6 { width: 50%; float: left; }
        
        .header-section { padding: 15px 30px; border-bottom: 2px solid #333; overflow: hidden; }
        .company-info h2 { margin: 0 0 3px 0; color: #1a1a1a; font-size: 16px; text-transform: uppercase; }
        .company-info p { margin: 1px 0; font-size: 10px; color: #555; }

        .invoice-title { font-size: 18px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #333; margin-bottom: 5px; }
        .meta-table td { padding: 2px 8px; font-size: 11px; }
        .meta-label { font-weight: 600; color: #555; text-align: right; }
        .meta-val { font-weight: 700; color: #000; }

        .address-section { border-bottom: 1px solid #ddd; overflow: hidden; display: table; width: 100%; table-layout: fixed; }
        .address-box { display: table-cell; width: 50%; padding: 15px 30px; font-size: 11px; vertical-align: top; border-right: 1px solid #ddd; box-sizing: border-box; }
        .address-box:last-child { border-right: none; }
        
        .addr-header { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #777; margin-bottom: 5px; letter-spacing: 0.5px; }
        .addr-name { font-size: 12px; font-weight: 700; margin-bottom: 3px; color: #000; }
        .addr-text { line-height: 1.4; color: #333; }

        .items-section { padding: 15px 30px; }
        table.invoice-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        table.invoice-table th { background: #333; color: #fff; padding: 5px 8px; text-align: left; text-transform: uppercase; font-weight: 600; font-size: 9px; border: 1px solid #333; }
        table.invoice-table td { padding: 5px 8px; border: 1px solid #e0e0e0; vertical-align: top; color: #333; }
        
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .text-bold { font-weight: 700; }
        .row-stripe { background-color: #fafafa; }
        
        /* Updated CSS for Table-based Layout (DOMPDF Friendly) */
        .wrapper-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .wrapper-td { vertical-align: top; }
        
        .totals-section { margin: 0 30px 20px 30px; }
        .notes-area { width: 55%; font-size: 10px; color: #555; vertical-align: top; padding-right: 20px; }
        .notes-area h5 { margin: 0 0 3px 0; font-size: 11px; color: #333; }
        .totals-table-area { width: 45%; vertical-align: top; }
        
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 3px 0; border-bottom: 1px solid #eee; font-size: 11px; }
        .totals-table .total-label { text-align: right; padding-right: 15px; color: #666; }
        .totals-table .total-val { text-align: right; font-weight: 600; color: #000; }
        .grand-total-row td { border-top: 2px solid #333; border-bottom: 2px solid #333; padding: 8px 0; font-size: 14px; font-weight: 700; color: #000; }
        .net-total-row td { border-top: 2px solid #28a745; border-bottom: 2px solid #28a745; padding: 8px 0; font-size: 14px; font-weight: 700; color: #28a745; }

        .amount-words-bar { margin: 0 30px 15px 30px; padding: 6px 10px; background: #f5f5f5; border-left: 4px solid #333; font-size: 10px; font-weight: 600; }
        .amount-words-bar span { font-weight: 400; color: #666; margin-right: 5px; }
        
        .footer-section { margin: 0 30px 30px 30px; }
        .footer-table { width: 100%; }
        .bank-details-td { width: 50%; vertical-align: bottom; font-size: 10px; color: #555; }
        .qr-td { width: 15%; vertical-align: bottom; text-align: center; }
        .signature-td { width: 35%; vertical-align: bottom; text-align: right; }
    </style>
</head>
<body>
    <div class="invoice-box">
        
        <!-- Header (Table Layout) -->
        <div class="header-section">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 60%; vertical-align: top;">
                         <div class="company-info">
                            '.$logoImg.'
                            <h2 style="margin: 5px 0 2px 0; font-size: 20px; font-weight: bold; color: #1a1a1a;">'.htmlspecialchars($org['organization_name'] ?? '').'</h2>
                            <p style="margin: 2px 0; line-height: 1.3;">
                                '.htmlspecialchars($org_address).'<br>
                                '.htmlspecialchars($org_city).', '.htmlspecialchars($org_state).' - '.htmlspecialchars($org_pincode).'
                            </p>
                            <p style="margin: 2px 0; line-height: 1.3;">
                                GSTIN: <strong>'.htmlspecialchars($org_gst).'</strong><br>
                                Email: '.htmlspecialchars($org_email).'<br>
                                Phone: '.htmlspecialchars($org_phone).'
                            </p>
                        </div>
                    </td>
                    <td style="width: 40%; vertical-align: top; text-align: right;">
                        <div style="text-align: right;">
                            <div class="invoice-title" style="font-size: 24px; font-weight: bold; color: #333; margin-bottom: 15px; margin-top: 20px;">TAX INVOICE</div>
                            <table class="meta-table" style="margin-left: auto; border-collapse: separate; border-spacing: 0 5px;">
                                <tr>
                                    <td class="meta-label" style="padding-right: 15px; text-align: right; color: #555; font-weight: 600;">Invoice No:</td>
                                    <td class="meta-val" style="text-align: right; font-weight: bold;">'.htmlspecialchars($inv['invoice_number'] ?? '').'</td>
                                </tr>
                                <tr>
                                    <td class="meta-label" style="padding-right: 15px; text-align: right; color: #555; font-weight: 600;">Date:</td>
                                    <td class="meta-val" style="text-align: right; font-weight: bold;">'.date('d-M-Y', strtotime($inv['invoice_date'])).'</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
 
        <!-- Address Section -->
        <table style="width: 100%; border-bottom: 1px solid #ddd; table-layout: fixed;">
            <tr>
                <td style="width: 50%; padding: 20px 40px; border-right: 1px solid #ddd; vertical-align: top;">
                    <div class="addr-header">Billed To</div>
                    <div class="addr-name">'.htmlspecialchars($inv['company_name'] ?: $inv['customer_name'] ?? '').'</div>
                    <div class="addr-text">
                        '.htmlspecialchars($inv['c_address'] ?? '').'<br>
                        '.htmlspecialchars($inv['c_city'] ?? '').', '.htmlspecialchars($inv['c_state'] ?? '').' - '.htmlspecialchars($inv['c_pincode'] ?? '').'<br>
                        GSTIN: <strong>'.htmlspecialchars($inv['c_gst'] ?? '').'</strong><br>
                        State Code: '.( (isset($inv['c_gst']) && strlen($inv['c_gst'])>=2) ? substr($inv['c_gst'], 0, 2) : '-' ).'
                    </div>
                </td>
                <td style="width: 50%; padding: 20px 40px; vertical-align: top;">
                    <div class="addr-header">Shipped To / Delivery Details</div>
                    <div class="addr-name">'.htmlspecialchars($inv['company_name'] ?: $inv['customer_name'] ?? '').'</div>
                    <div class="addr-text">
                        <i>(Same as Billing Address)</i><br>
                        <br>
                        Mode of Delivery: <strong>'.htmlspecialchars($inv['delivery_mode'] ?: 'NA').'</strong><br>
                        Sales Person: <strong>'.htmlspecialchars(($inv['sales_fname'] ?? '') . ' ' . ($inv['sales_lname'] ?? '')).'</strong>
                    </div>
                </td>
            </tr>
        </table>

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
                    '.$itemsHtml.'
                </tbody>
            </table>
        </div>

        <!-- Amount Word -->
        <div class="amount-words-bar">
            <span>Amount in Words:</span> '.$grand_total_words.'
        </div>

        <!-- Totals & Notes (Using Table for Layout) -->
        <div class="totals-section">
            <table class="wrapper-table">
                <tr>
                    <td class="notes-area">
                        '.(!empty($inv['terms_conditions']) ? '<h5 style="margin: 0 0 5px 0;">Terms & Conditions:</h5><p style="margin-top:0;">'.htmlspecialchars($inv['terms_conditions']).'</p>' : '').'
                        <br>
                        '.(!empty($inv['notes']) ? '<h5 style="margin: 0 0 5px 0;">Notes:</h5><p style="margin-top:0;">'.htmlspecialchars($inv['notes']).'</p>' : '').'
                    </td>
                    <td class="totals-table-area">
                        <table class="totals-table">
                            <tr>
                                <td class="total-label">Taxable Amount</td>
                                <td class="total-val">'.number_format($total_taxable, 2).'</td>
                            </tr>
                            
                            '.$taxRows.'

                            '.($adjustment != 0 ? '
                            <tr>
                                <td class="total-label">Round Off / Adj</td>
                                <td class="total-val">'.number_format($adjustment, 2).'</td>
                            </tr>' : '').'
                            
                            '.($loyalty_redeemed > 0 ? '
                            <tr>
                                <td class="total-label" style="color: green;">Loyalty Redeemed</td>
                                <td class="total-val" style="color: green;">- '.number_format($loyalty_redeemed, 2).'</td>
                            </tr>' : '').'

                            <tr class="grand-total-row">
                                <td style="text-align: right; padding-right: 15px;">Grand Total</td>
                                <td style="text-align: right;">₹ '.number_format($total_taxable + $grand_gst + $adjustment, 2).'</td>
                            </tr>
                            '.($loyalty_redeemed > 0 ? '
                            <tr class="net-total-row">
                                <td style="text-align: right; padding-right: 15px; font-weight: bold; color: #28a745;">Net Amount Payable</td>
                                <td style="text-align: right; font-weight: bold; color: #28a745;">₹ '.number_format($display_grand_total, 2).'</td>
                            </tr>' : '').'
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer (Using Table for Layout) -->
        <div class="footer-section">
            <table class="footer-table">
                <tr>
                    <td class="qr-td" style="text-align:center; vertical-align:top; width:20%; border:1px solid #ddd; background:#f9f9f9; padding:8px; border-radius:4px;">
                        <!-- QR_PLACEHOLDER -->
                    </td>
                    <td class="bank-details-td" style="vertical-align:top; padding-left:15px; font-size:10px; color:#555; width:48%;">
                         <strong>Bank Details:</strong><br>
                        Account Name: '.htmlspecialchars($org_acc_holder).'<br>
                        Bank: '.htmlspecialchars($org_bank).'<br>
                        Branch: '.htmlspecialchars($org_branch).'<br>
                        A/c No: '.htmlspecialchars($org_acc).'<br>
                        IFSC: '.htmlspecialchars($org_ifsc).'<br>
                        UPI: '.htmlspecialchars($org_upi).'
                    </td>
                    <td class="signature-td" style="text-align:right; vertical-align:bottom; width:32%;">
                        <p style="margin-bottom: 40px; font-weight: 600;">For '.htmlspecialchars($org['organization_name'] ?? '').'</p>
                        <div class="signature-line"></div>
                        <div style="font-size: 11px; margin-top: 5px;">Authorized Signatory</div>
                    </td>
                </tr>
            </table>
        </div>

    </div>
</body>
</html>';

$qrHtml = !empty($qrDataUri) ? '<div style="font-size:9px; font-weight:700; color:#333; margin-bottom:3px;">'.htmlspecialchars($org['organization_name'] ?? '').'</div><div style="font-size:8px; color:#777; margin-bottom:4px;">Scan and Pay</div><img src="'.$qrDataUri.'" alt="QR Code" style="height:80px; width:80px; display:block; margin:0 auto;">' : '';

$html = str_replace('<!-- QR_PLACEHOLDER -->', $qrHtml, $html);

// 5. Render
$options = new \Dompdf\Options();
$options->set('isRemoteEnabled', true);

$dompdf = new \Dompdf\Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'Tax_Invoice_' . $inv['invoice_number'] . '.pdf';
$dompdf->stream($filename, ["Attachment" => true]);
?>
