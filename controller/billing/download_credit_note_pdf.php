<?php
require_once '../../config/auth_guard.php';
require_once '../../vendor/autoload.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Credit Note ID");
}

$cn_id = intval($_GET['id']);
$organization_id = $_SESSION['organization_id'];

// 1. Fetch Credit Note Header & Customer Details
$sql = "SELECT cn.*, inv.invoice_number,
        c.customer_name, c.company_name, c.address as c_address, c.city as c_city, c.state as c_state, c.pincode as c_pincode, c.gst_number as c_gst, c.phone as c_phone, c.email as c_email
        FROM credit_notes cn 
        JOIN customers_listing c ON cn.customer_id = c.customer_id 
        LEFT JOIN sales_invoices inv ON cn.invoice_id = inv.invoice_id
        WHERE cn.credit_note_id = ? AND cn.organization_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $cn_id, $organization_id);
$stmt->execute();
$cn = $stmt->get_result()->fetch_assoc();

if (!$cn) {
    die("Credit Note not found or access denied.");
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
$org_email = $org['email'] ?? '';
$org_phone = $org['phone'] ?? '';

// 3. Fetch Items
$itemSql = "SELECT cni.*, u.unit_name, h.hsn_code 
            FROM credit_note_items cni 
            LEFT JOIN units_listing u ON cni.unit_id = u.unit_id
            LEFT JOIN items_listing i ON cni.item_id = i.item_id
            LEFT JOIN hsn_listing h ON i.hsn_id = h.hsn_id
            WHERE cni.credit_note_id = ?";
$itemStmt = $conn->prepare($itemSql);
$itemStmt->bind_param("i", $cn_id);
$itemStmt->execute();
$itemRes = $itemStmt->get_result();

$items = [];
while($row = $itemRes->fetch_assoc()){
    $items[] = $row;
}

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

$grand_total_words = getIndianCurrency($cn['total_amount']);

// Logo Logic
$logo = $org['organization_logo'];
$logoDataUri = '';
if(!empty($logo)){
    $orgCode = $_SESSION['organization_code']; // Or $org['organization_code'] logic
    $baseDir = __DIR__ . "/../../uploads/$orgCode/organization_logo/$logo";
    if(file_exists($baseDir)){
         $type = pathinfo($baseDir, PATHINFO_EXTENSION);
         $data = file_get_contents($baseDir);
         $base64 = base64_encode($data);
         $logoDataUri = 'data:image/' . $type . ';base64,' . $base64;
    }
}
$logoImg = !empty($logoDataUri) ? '<img src="'.$logoDataUri.'" alt="Logo" style="margin-bottom: 10px; height: 50px;">' : '';


// Prepare Items HTML
$itemsHtml = '';
$counter = 1;

foreach($items as $row) {
    // HSN from DB might be null if not joined properly or empty, fallback
    $hsn = $row['hsn_code'] ?: '-';
    
    $itemsHtml .= '
    <tr class="row-stripe">
        <td class="text-center">'.$counter++.'</td>
        <td>
            <div class="text-bold">'.htmlspecialchars($row['item_name'] ?? '').'</div>
        </td>
        <td class="text-center">'.$hsn.'</td>
        <td class="text-center">'.(floatval($row['quantity'])).' '.($row['unit_name'] ?? '').'</td>
        <td class="text-right">'.number_format($row['rate'], 2).'</td>
        <td class="text-right">'.number_format($row['amount'], 2).'</td>
        <td class="text-center">'.(floatval($row['gst_rate'])).'%</td>
        <td class="text-right text-bold">'.number_format($row['total_amount'], 2).'</td>
    </tr>';
}

// Prepare HTML
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <title>Credit Note</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; line-height: 1.3; color: #333; }
        .row { width: 100%; clear: both; }
        
        /* Rupee symbol with fallback for Arial */
        .rupee-symbol::before { 
            content: "₹"; 
            font-family: "DejaVu Sans", "Arial Unicode MS", "Lucida Sans Unicode", sans-serif;
        }
        .rupee-fallback::before { content: "Rs."; }
        
        .header-section { padding: 15px 30px; border-bottom: 2px solid #d9534f; overflow: hidden; }
        .company-info h2 { margin: 0 0 3px 0; color: #1a1a1a; font-size: 16px; text-transform: uppercase; }
        .company-info p { margin: 1px 0; font-size: 10px; color: #555; }

        .invoice-title { font-size: 18px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #d9534f; margin-bottom: 5px; }
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
        table.invoice-table th { background: #d9534f; color: #fff; padding: 5px 8px; text-align: left; text-transform: uppercase; font-weight: 600; font-size: 9px; border: 1px solid #d9534f; }
        table.invoice-table td { padding: 5px 8px; border: 1px solid #e0e0e0; vertical-align: top; color: #333; }
        
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .text-bold { font-weight: 700; }
        .row-stripe { background-color: #fafafa; }
        
        .wrapper-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .totals-section { margin: 0 30px 20px 30px; }
        .notes-area { width: 55%; font-size: 10px; color: #555; vertical-align: top; padding-right: 20px; }
        .notes-area h5 { margin: 0 0 3px 0; font-size: 11px; color: #333; }
        .totals-table-area { width: 45%; vertical-align: top; }
        
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 3px 0; border-bottom: 1px solid #eee; font-size: 11px; }
        .totals-table .total-label { text-align: right; padding-right: 15px; color: #666; }
        .totals-table .total-val { text-align: right; font-weight: 600; color: #000; }
        .grand-total-row td { border-top: 2px solid #d9534f; border-bottom: 2px solid #d9534f; padding: 8px 0; font-size: 14px; font-weight: 700; color: #d9534f; }

        .amount-words-bar { margin: 0 30px 15px 30px; padding: 6px 10px; background: #fff0f0; border-left: 4px solid #d9534f; font-size: 10px; font-weight: 600; }
        .amount-words-bar span { font-weight: 400; color: #666; margin-right: 5px; }
        
        .footer-section { margin: 0 30px 30px 30px; }
        .signature-box { text-align: right; }
    </style>
</head>
<body>
    <div class="invoice-box">
        
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
                            <div class="invoice-title">CREDIT NOTE</div>
                            <table class="meta-table" style="margin-left: auto; border-collapse: separate; border-spacing: 0 5px;">
                                <tr>
                                    <td class="meta-label">Credit Note #:</td>
                                    <td class="meta-val">'.htmlspecialchars($cn['credit_note_number']).'</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">Date:</td>
                                    <td class="meta-val">'.date('d-M-Y', strtotime($cn['credit_note_date'])).'</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">Orig. Invoice #:</td>
                                    <td class="meta-val">'.htmlspecialchars($cn['invoice_number'] ?? '-').'</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <table style="width: 100%; border-bottom: 1px solid #ddd; table-layout: fixed;">
             <tr>
                <td style="width: 50%; padding: 20px 40px; border-right: 1px solid #ddd; vertical-align: top;">
                    <div class="addr-header">Billed To</div>
                    <div class="addr-name">'.htmlspecialchars($cn['company_name'] ?: $cn['customer_name'] ?? '').'</div>
                    <div class="addr-text">
                        '.htmlspecialchars($cn['c_address'] ?? '').'<br>
                        '.htmlspecialchars($cn['c_city'] ?? '').', '.htmlspecialchars($cn['c_state'] ?? '').' - '.htmlspecialchars($cn['c_pincode'] ?? '').'<br>
                        GSTIN: <strong>'.htmlspecialchars($cn['c_gst'] ?? '').'</strong>
                    </div>
                </td>
                <td style="width: 50%; padding: 20px 40px; vertical-align: top;">
                    <div class="addr-header">Details</div>
                    <div class="addr-text">
                        Reason: <strong>'.htmlspecialchars($cn['reason'] ?? 'Not Specified').'</strong><br>
                        <br>
                        Status: <span style="text-transform: uppercase;"><b>'.htmlspecialchars($cn['status']).'</b></span>
                    </div>
                </td>
            </tr>
        </table>
        
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

        <div class="amount-words-bar">
            <span>Amount in Words:</span> '.$grand_total_words.'
        </div>
        
        <div class="totals-section">
            <table class="wrapper-table">
                <tr>
                    <td class="notes-area">
                        '.(!empty($cn['notes']) ? '<h5 style="margin: 0 0 5px 0;">Notes:</h5><p style="margin-top:0;">'.htmlspecialchars($cn['notes']).'</p>' : '').'
                    </td>
                    <td class="totals-table-area">
                        <table class="totals-table">
                            <tr>
                                <td class="total-label">Sub Total (Taxable)</td>
                                <td class="total-val">'.number_format($cn['sub_total'], 2).'</td>
                            </tr>
                            <tr>
                                <td class="total-label">CGST</td>
                                <td class="total-val">'.number_format($cn['cgst_amount'], 2).'</td>
                            </tr>
                            <tr>
                                <td class="total-label">SGST</td>
                                <td class="total-val">'.number_format($cn['sgst_amount'], 2).'</td>
                            </tr>
                            <tr>
                                <td class="total-label">IGST</td>
                                <td class="total-val">'.number_format($cn['igst_amount'], 2).'</td>
                            </tr>
                            '.($cn['adjustment'] != 0 ? '
                            <tr>
                                <td class="total-label">Adjustment</td>
                                <td class="total-val">'.number_format($cn['adjustment'], 2).'</td>
                            </tr>' : '').'
                            <tr class="grand-total-row">
                                <td style="text-align: right; padding-right: 15px;">Credit Amount</td>
                                <td style="text-align: right;">
                                <span class="rupee-symbol"></span> '.number_format($cn['total_amount'], 2).'
                                <span class="rupee-fallback" style="display: none;">Rs. '.number_format($cn['total_amount'], 2).'</span>
                            </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
        
         <div class="footer-section">
             <div class="signature-box">
                <p style="margin-bottom: 40px; font-weight: 600;">For '.htmlspecialchars($org['organization_name'] ?? '').'</p>
                <div style="border-top: 1px solid #333; width: 150px; display: inline-block;"></div>
                <div style="font-size: 11px; margin-top: 5px;">Authorized Signatory</div>
            </div>
        </div>

    </div>
</body>
</html>
';

// Render
$options = new \Dompdf\Options();
$options->set('isRemoteEnabled', true);
$options->set('isFontSubsettingEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Arial');
$options->set('fontDir', '../../vendor/dompdf/dompdf/lib/fonts/');
$options->set('fontCache', '../../vendor/dompdf/dompdf/lib/fonts/cache/');

$dompdf = new \Dompdf\Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'Credit_Note_' . $cn['credit_note_number'] . '.pdf';
$dompdf->stream($filename, ["Attachment" => true]);
?>
