<?php
require_once '../../config/auth_guard.php';
require_once '../../vendor/autoload.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Invoice ID");
}

$invoice_id = intval($_GET['id']);
$organization_id = $_SESSION['organization_id'];

// 1. Fetch Invoice Header & Customer Details
$sql = "SELECT inv.*, c.customer_name, c.company_name, c.address as cust_address, c.gst_number as cust_gst, c.email as cust_email, c.phone as cust_phone,
        e.first_name as sales_first_name, e.last_name as sales_last_name, e.employee_code as sales_code,
        me.first_name as make_first_name, me.last_name as make_last_name, me.employee_code as make_code
        FROM proforma_invoices inv 
        LEFT JOIN customers_listing c ON inv.customer_id = c.customer_id 
        LEFT JOIN employees e ON inv.sales_employee_id = e.employee_id
        LEFT JOIN employees me ON inv.make_employee_id = me.employee_id
        WHERE inv.proforma_invoice_id = ? AND inv.organization_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $invoice_id, $organization_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("Invoice not found or access denied.");
}

$inv = $res->fetch_assoc();

// 2. Fetch Organization Details
$orgQ = $conn->query("SELECT * FROM organizations WHERE organization_id = $organization_id");
$orgData = $orgQ->fetch_assoc();

// 3. Fetch Invoice Items
$itemSql = "SELECT item.*, u.unit_name, h.hsn_code, h.gst_rate 
            FROM proforma_invoice_items item 
            LEFT JOIN items_listing i ON item.item_id = i.item_id 
            LEFT JOIN units_listing u ON i.unit_id = u.unit_id 
            LEFT JOIN hsn_listing h ON i.hsn_id = h.hsn_id 
            WHERE item.proforma_invoice_id = ? 
            ORDER BY item.item_row_id ASC";

$itemStmt = $conn->prepare($itemSql);
$itemStmt->bind_param("i", $invoice_id);
$itemStmt->execute();
$itemsRes = $itemStmt->get_result();

$items = [];
while($row = $itemsRes->fetch_assoc()){
    $items[] = $row;
}

// 4. Prepare Data for View
$invoice_number = $inv['proforma_invoice_number'];
$invoice_date = $inv['invoice_date'];
$sub_total = $inv['sub_total'];
$discount_value = $inv['discount_value'];
$adjustment = $inv['adjustment'];
$total_amount = $inv['total_amount'];
$cgst_amount = $inv['cgst_amount'];
$sgst_amount = $inv['sgst_amount'];
$igst_amount = $inv['igst_amount'];

// Customer State Code
$cust_gst = $inv['cust_gst'] ?? '';
$cust_state_code = (strlen($cust_gst) >= 2) ? substr($cust_gst, 0, 2) : '-';

// Meta Data
$sales_name = trim(($inv['sales_first_name'] ?? '') . ' ' . ($inv['sales_last_name'] ?? ''));
if (!empty($sales_name) && !empty($inv['sales_code'])) {
    $sales_name .= ' (' . $inv['sales_code'] . ')';
}

$maker_name = trim(($inv['make_first_name'] ?? '') . ' ' . ($inv['make_last_name'] ?? ''));
if (!empty($maker_name) && !empty($inv['make_code'])) {
    $maker_name .= ' (' . $inv['make_code'] . ')';
}

$delivery_mode = $inv['delivery_mode'] ?? '';

// Logo Processing
$logo = $orgData['organization_logo'];
$logoDataUri = '';
if(!empty($logo)){
    $orgCode = $orgData['organizations_code'];
    $baseDir = __DIR__ . "/../../uploads/$orgCode/organization_logo/$logo";
    if(file_exists($baseDir)){
         $type = pathinfo($baseDir, PATHINFO_EXTENSION);
         $data = file_get_contents($baseDir);
         $base64 = base64_encode($data);
         $logoDataUri = 'data:image/' . $type . ';base64,' . $base64;
    }
}

// QR Code Processing
$org_qr = $orgData['qr_code'] ?? '';
$qrDataUri = '';
if(!empty($org_qr)){
    $orgCode = $orgData['organizations_code']; 
    $qrPath = __DIR__ . "/../../uploads/$orgCode/bank_details/$org_qr";
    if(file_exists($qrPath)){
         $type = pathinfo($qrPath, PATHINFO_EXTENSION);
         $data = file_get_contents($qrPath);
         $base64 = base64_encode($data);
         $qrDataUri = 'data:image/' . $type . ';base64,' . $base64;
    }
}

$org_bank = $orgData['bank_name'] ?? '';
$org_acc = $orgData['account_number'] ?? '';
$org_ifsc = $orgData['ifsc_code'] ?? '';
$org_acc_holder = $orgData['account_holder_name'] ?? '';
$org_branch = $orgData['branch_name'] ?? '';
$org_upi = $orgData['upi_id'] ?? '';

// 5. Build Items HTML
$pdfItemsHtml = '';
$idx = 1;

foreach ($items as $item) {
    $item_name = $item['item_name']; // Saved name in item row
    $hsn_code = $item['hsn_code'] ?? '-';
    $quantity = floatval($item['quantity']);
    $unit_name = $item['unit_name'] ?? 'Unit';
    $rate = floatval($item['rate']);
    $amount = floatval($item['amount']);
    
    // Recalculate tax rates purely for display if needed, or use stored values if available.
    // Proforma items table might not store tax amounts per item if not updated recently, 
    // but the save logic I added calculates them for PDF. 
    // Wait, the `proforma_invoice_items` data we fetched doesn't have cgst/sgst columns in the DB structure implied by previous code?
    // In `save_proforma`, I inserted into `proforma_invoice_items` with column count matching query...
    // Let's look at `save_proforma_invoice.php` line 108:
    // INSERT INTO proforma_invoice_items (..., created_at) VALUES ...
    // It does NOT seem to save distinct tax amounts per item in the items table.
    // The PDF generation logic in `save_proforma` calculated them on the fly from `$items` array (POST data).
    // Here we have to calculate them on the fly from `proforma_invoice_items` + `gst_type` from header.

    $gst_type = $inv['gst_type'];
    $global_gst_rate = floatval($inv['gst_rate']);
    
    // However, usually detailed tax per item depends on the item's rate or global rate?
    // In `save_proforma`, I used:
    // $gst_type from header, $gst_rate from header (global).
    // And logically applied it to all items.
    
    $cgst_rate = 0; $cgst_amt = 0;
    $sgst_rate = 0; $sgst_amt = 0;
    $igst_rate = 0; $igst_amt = 0;

    if ($gst_type === 'CGST_SGST') {
        $cgst_rate = $global_gst_rate / 2;
        $sgst_rate = $global_gst_rate / 2;
        $cgst_amt = $amount * ($cgst_rate / 100);
        $sgst_amt = $amount * ($sgst_rate / 100);
    } else if ($gst_type === 'IGST') {
        $igst_rate = $global_gst_rate;
        $igst_amt = $amount * ($igst_rate / 100);
    }
    
    $item_total = $amount + $cgst_amt + $sgst_amt + $igst_amt;

    $pdfItemsHtml .= "
    <tr>
        <td class='text-center'>$idx</td>
        <td>$item_name</td>
        <td class='text-center'>$hsn_code</td>
        <td class='text-center'>$quantity</td>
        <td class='text-center'>$unit_name</td>
        <td class='text-right'>₹" . number_format($rate, 2) . "</td>
        <td class='text-right'>₹" . number_format($amount, 2) . "</td>
        <td class='text-right'>" . number_format($cgst_rate + $sgst_rate + $igst_rate, 1) . "%</td>
        <td class='text-right'><b>₹" . number_format($item_total, 2) . "</b></td>
    </tr>";
    
    $idx++;
}


// 6. PDF Template
$html = "
<html>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size:11px; color:#333; line-height:1.4; }
        .header { margin-bottom: 20px; }
        .col-left { float:left; width:48%; }
        .col-right { float:right; width:48%; text-align:right; }
        .clear { clear:both; }
        table { width:100%; border-collapse: collapse; margin-top:15px; table-layout: fixed; }
        th { background-color: #f1f1f1; padding:5px; border-bottom:1px solid #ccc; text-align:center; font-weight:bold; font-size:10px; overflow:hidden; }
        td { padding:5px; border-bottom:1px solid #eee; font-size:10px; vertical-align:top; overflow:hidden; word-wrap:break-word; }
        .text-left { text-align:left; }
        .text-right { text-align:right; }
        .text-center { text-align:center; }
        .totals { margin-top:20px; float:right; width:50%; }
        .totals td { border-bottom:none; padding:4px; }
        .row { width:100%; }
        .col { width:50%; float:left; }
        .header-logo { float: left; margin-bottom: 15px; }

        .footer-section { margin-top: 30px; }
        .footer-table { width: 100%; }
        .bank-details-td { width: 50%; vertical-align: bottom; font-size: 10px; color: #555; border: none; }
        .qr-td { width: 15%; vertical-align: bottom; text-align: center; border: none; }
        .signature-td { width: 35%; vertical-align: bottom; text-align: right; border: none; }
    </style>
</head>
<body>
    <div class='header'>
        " . (!empty($logoDataUri) ? "<div class='header-logo'><img src='$logoDataUri' style='max-height:60px;'></div><div class='clear'></div>" : "") . "
        
        <div class='col-left'>
            <h5 style='margin:0; color:#777; font-size:9px; text-transform:uppercase;'>Details of Supplier (From)</h5>
            <h3 style='margin:5px 0;'>{$orgData['organization_name']}</h3>
            <p style='margin:0;'>{$orgData['address']}<br>GSTIN: {$orgData['gst_number']}</p>
        </div>
        <div class='col-right'>
            <h5 style='margin:0; color:#777; font-size:9px; text-transform:uppercase;'>Details of Recipient (To)</h5>
            <h3 style='margin:5px 0;'>{$inv['company_name']}</h3>
            <p style='margin:0;'>{$inv['cust_address']}<br>GSTIN: $cust_gst<br>State Code: $cust_state_code</p>
        </div>
        <div class='clear'></div>
    </div>
    
    <div style='border-top:1px solid #ddd; border-bottom:1px solid #ddd; padding:5px 0; margin-bottom:15px; text-align:center;'>
        <h2 style='margin:5px 0;'>PROFORMA INVOICE</h2>
    </div>

    <div style='margin-bottom:15px;'>
        <strong>Invoice No:</strong> $invoice_number 
        <span style='float:right;'><strong>Date:</strong> " . date('d-m-Y', strtotime($invoice_date)) . "</span>
    </div>

    <div style='margin-bottom:15px; font-size:10px; border-bottom:1px solid #ddd; padding-bottom:5px; color:#555;'>
        <table style='width:100%; border-collapse:collapse; margin:0;'>
            <tr>
                <td style='border:none; padding:0; width:50%; text-align:left;'><strong>Sales Person:</strong> " . ($sales_name ?: '-') . "</td>
                <td style='border:none; padding:0; width:50%; text-align:right;'><strong>Delivery Mode:</strong> " . ($delivery_mode ?: '-') . "</td>
            </tr>
            <tr>
                <td style='border:none; padding-top:5px; width:50%; text-align:left;'><strong>Created By:</strong> " . ($maker_name ?: '-') . "</td>
                <td style='border:none; padding-top:5px; width:50%;'></td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th width='5%'>#</th>
                <th width='35%' class='text-left'>Description</th>
                <th width='10%'>HSN/SAC</th>
                <th width='8%'>Qty</th>
                <th width='8%'>Unit</th>
                <th width='12%' class='text-right'>Rate</th>
                <th width='12%' class='text-right'>Taxable</th>
                <th width='5%' class='text-right'>Tax %</th>
                <th width='15%' class='text-right'>Total</th>
            </tr>
        </thead>
        <tbody>
            $pdfItemsHtml
        </tbody>
    </table>

    <div class='totals'>
        <table>
            <tr>
                <td class='text-right'>Taxable Amount:</td>
                <td class='text-right'>₹" . number_format($sub_total - $discount_value, 2) . "</td>
            </tr>
            <tr>
                <td class='text-right'>Total Tax (IGST+CGST+SGST):</td>
                <td class='text-right'>₹" . number_format($igst_amount + $cgst_amount + $sgst_amount, 2) . "</td>
            </tr>
            <tr>
                <td class='text-right'>Adjustment:</td>
                <td class='text-right'>₹" . number_format($adjustment, 2) . "</td>
            </tr>
            <tr>
                <td class='text-right'><strong>Grand Total:</strong></td>
                <td class='text-right'><strong>₹" . number_format($total_amount, 2) . "</strong></td>
            </tr>
        </table>
    </div>
    <div class='clear'></div>
    
    <!-- Footer (Using Table for Layout) -->
    <div class=\"footer-section\">
        <table class=\"footer-table\" style=\"border: none;\">
            <tr>
                <td style=\"text-align:center; vertical-align:top; width:20%; border:1px solid #ddd; background:#f9f9f9; padding:8px; border-radius:4px;\">
                    " . (!empty($qrDataUri) ? "
                    <div style='font-size:9px; font-weight:700; color:#333; margin-bottom:3px;'>" . htmlspecialchars($orgData['organization_name'] ?? '') . "</div>
                    <div style='font-size:8px; color:#777; margin-bottom:4px;'>Scan and Pay</div>
                    <img src=\"$qrDataUri\" alt=\"QR Code\" style=\"height:80px; width:80px; display:block; margin:0 auto;\">
                    " : "") . "
                </td>
                <td class=\"bank-details-td\" style=\"vertical-align:top; padding-left:15px; font-size:10px; color:#555; width:48%;\">
                     <strong>Bank Details:</strong><br>
                    Account Name: " . htmlspecialchars($org_acc_holder) . "<br>
                    Bank: " . htmlspecialchars($org_bank) . "<br>
                    Branch: " . htmlspecialchars($org_branch) . "<br>
                    A/c No: " . htmlspecialchars($org_acc) . "<br>
                    IFSC: " . htmlspecialchars($org_ifsc) . "<br>
                    UPI: " . htmlspecialchars($org_upi) . "
                </td>
                <td class=\"signature-td\" style=\"text-align:right; vertical-align:bottom; width:32%; border:none;\">
                    <p style=\"margin-bottom: 40px; font-weight: 600;\">For " . htmlspecialchars($orgData['organization_name'] ?? '') . "</p>
                    <div style=\"font-size: 11px; margin-top: 5px;\">Authorized Signatory</div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>";

// 7. Render PDF
$options = new \Dompdf\Options();
$options->set('isRemoteEnabled', true);

$dompdf = new \Dompdf\Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Stream Download
$dompdf->stream("Proforma_Invoice_$invoice_number.pdf", ["Attachment" => true]);
?>
