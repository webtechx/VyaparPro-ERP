<?php
require_once '../../config/auth_guard.php';
require_once '../../vendor/autoload.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Request");
}

$dn_id = intval($_GET['id']);
$organization_id = $_SESSION['organization_id'];

// 1. Fetch Debit Note Header
$sql = "SELECT dn.*, po.po_number, po.order_date as po_date, v.display_name as vendor_name, 
        v.work_phone, v.email, vad.address_line1, vad.city, vad.state, vad.pin_code,
        org.organization_name, org.organization_logo, org.address as org_address, org.city as org_city, org.state as org_state, org.pincode as org_pin, org.phone as org_phone, org.email as org_email
        FROM debit_notes dn
        LEFT JOIN purchase_orders po ON dn.po_id = po.purchase_orders_id
        LEFT JOIN vendors_listing v ON dn.vendor_id = v.vendor_id
        LEFT JOIN vendors_addresses vad ON v.vendor_id = vad.vendor_id AND vad.address_type = 'billing'
        CROSS JOIN organizations org ON org.organization_id = dn.organization_id
        WHERE dn.debit_note_id = ? AND dn.organization_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $dn_id, $organization_id);
$stmt->execute();
$dn = $stmt->get_result()->fetch_assoc();

if (!$dn) {
    die("Debit Note not found or access denied.");
}

// 2. Fetch Items
$itemSql = "SELECT dni.*, il.item_name, u.unit_name, poi.rate as po_rate, h.hsn_code 
            FROM debit_note_items dni
            LEFT JOIN items_listing il ON dni.item_id = il.item_id
            LEFT JOIN units_listing u ON il.unit_id = u.unit_id
            LEFT JOIN hsn_listing h ON il.hsn_id = h.hsn_id
            LEFT JOIN purchase_order_items poi ON dni.po_item_id = poi.id
            WHERE dni.debit_note_id = ?";
$itemStmt = $conn->prepare($itemSql);
$itemStmt->bind_param("i", $dn_id);
$itemStmt->execute();
$itemRes = $itemStmt->get_result();

$items = [];
$total_val = 0;
while($row = $itemRes->fetch_assoc()){
    $rate = floatval($row['po_rate']);
    $qty = floatval($row['return_qty']);
    $val = $qty * $rate;
    $row['val'] = $val;
    $total_val += $val;
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

$amountMs = getIndianCurrency($total_val);

// Logo processing
$logo = $dn['organization_logo'];
$logoDataUri = '';
if(!empty($logo)){
    $orgCode = $_SESSION['organization_code']; 
    $baseDir = __DIR__ . "/../../uploads/$orgCode/organization_logo/$logo";
    if(file_exists($baseDir)){
         $type = pathinfo($baseDir, PATHINFO_EXTENSION);
         $data = file_get_contents($baseDir);
         $base64 = base64_encode($data);
         $logoDataUri = 'data:image/' . $type . ';base64,' . $base64;
    }
}
$logoImg = !empty($logoDataUri) ? '<img src="'.$logoDataUri.'" alt="Logo" style="margin-bottom: 10px; height: 50px;">' : '<h2>'.htmlspecialchars($dn['organization_name']).'</h2>';


// Items HTML
$itemsHtml = '';
$counter = 1;
foreach($items as $row) {
    $itemsHtml .= '
    <tr class="row-stripe">
        <td class="text-center">'.$counter++.'</td>
        <td>
            <div class="text-bold">'.htmlspecialchars($row['item_name'] ?? '').'</div>
            <div style="font-size: 9px; color: #666;">'.htmlspecialchars($row['unit_name'] ?? '').'</div>
        </td>
        <td class="text-center">'.($row['hsn_code'] ?: '-').'</td>
        <td class="text-center">'.($row['return_qty']+0).'</td>
        <td class="text-right">'.number_format($row['po_rate'], 2).'</td>
        <td class="text-right text-bold">'.number_format($row['val'], 2).'</td>
    </tr>';
}

$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Debit Note</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; line-height: 1.3; color: #333; }
        .row { width: 100%; clear: both; }
        .col-6 { width: 50%; float: left; }
        
        .header-section { padding: 15px 30px; border-bottom: 2px solid #333; overflow: hidden; }
        
        .invoice-title { font-size: 18px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #dc3545; margin-bottom: 5px; }
        .meta-table td { padding: 2px 8px; font-size: 11px; }
        .meta-label { font-weight: 600; color: #555; text-align: right; }
        .meta-val { font-weight: 700; color: #000; }

        .address-section { border-bottom: 1px solid #ddd; overflow: hidden; display: table; width: 100%; table-layout: fixed; }
        
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
        
        .footer-section { margin: 0 30px 30px 30px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        
        <!-- Header -->
        <div class="header-section">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 60%; vertical-align: top;">
                         <div class="company-info">
                            '.$logoImg.'
                            <p style="margin: 2px 0;">
                                '.htmlspecialchars($dn['org_address']).'<br>
                                '.htmlspecialchars($dn['org_city']).', '.htmlspecialchars($dn['org_state']).' - '.htmlspecialchars($dn['org_pin']).'
                            </p>
                            <p style="margin: 2px 0;">
                                Email: '.htmlspecialchars($dn['org_email']).' | Phone: '.htmlspecialchars($dn['org_phone']).'
                            </p>
                        </div>
                    </td>
                    <td style="width: 40%; vertical-align: top; text-align: right;">
                        <div style="text-align: right;">
                            <div class="invoice-title">DEBIT NOTE</div>
                            <table class="meta-table" style="margin-left: auto; border-collapse: separate; border-spacing: 0 5px;">
                                <tr>
                                    <td class="meta-label">ID:</td>
                                    <td class="meta-val">'.htmlspecialchars($dn['debit_note_number']).'</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">Date:</td>
                                    <td class="meta-val">'.date('d M Y', strtotime($dn['debit_note_date'])).'</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">Ref PO:</td>
                                    <td class="meta-val">'.htmlspecialchars($dn['po_number']).'</td>
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
                    <div class="addr-header">Vendor Details</div>
                    <div class="addr-name">'.htmlspecialchars($dn['vendor_name'] ?? '').'</div>
                    <div class="addr-text">
                        '.htmlspecialchars($dn['address_line1'] ?? '').'<br>
                        '.htmlspecialchars($dn['city'] ?? '').', '.htmlspecialchars($dn['state'] ?? '').' - '.htmlspecialchars($dn['pin_code'] ?? '').'<br>
                        Phone: '.htmlspecialchars($dn['work_phone']).'
                    </div>
                </td>
                <td style="width: 50%; padding: 20px 40px; vertical-align: top;">
                    <div class="addr-header">Notes</div>
                    <div class="addr-text">
                        '.nl2br(htmlspecialchars($dn['remarks'])).'
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
                        <th width="40%">Item Description</th>
                        <th width="10%" class="text-center">HSN</th>
                        <th width="10%" class="text-center">Qty</th>
                        <th width="15%" class="text-right">Rate</th>
                        <th width="20%" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    '.$itemsHtml.'
                    <tr>
                         <td colspan="5" class="text-right text-bold">Total Amount</td>
                         <td class="text-right text-bold">'.number_format($total_val, 2).'</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="margin: 0 30px; background: #f5f5f5; padding: 10px; font-weight: bold; border-left: 4px solid #333;">
            Total Debit Amount (in words): '.$amountMs.'
        </div>

        <!-- Footer -->
        <div class="footer-section" style="margin-top: 50px;">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 60%; vertical-align: bottom;">
                        <i>This is a computer generated document.</i>
                    </td>
                    <td style="width: 40%; text-align: right; vertical-align: bottom;">
                        <p style="font-weight: 600;">For '.htmlspecialchars($dn['organization_name']).'</p>
                        <br><br>
                        <div style="border-top: 1px solid #333; width: 80%; float: right;">Authorized Signatory</div>
                    </td>
                </tr>
            </table>
        </div>

    </div>
</body>
</html>';

// Render
$options = new \Dompdf\Options();
$options->set('isRemoteEnabled', true);

$dompdf = new \Dompdf\Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'Debit_Note_' . $dn['debit_note_number'] . '.pdf';
$dompdf->stream($filename, ["Attachment" => true]);
?>
