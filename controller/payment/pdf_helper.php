<?php
// controller/billing/pdf_helper.php
// Helper to generate Payment Receipt HTML and PDF

// Ensure Dompdf is loaded
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function getPaymentReceiptHTML($payment_id, $conn, $organization_id, $forPdf = true) {
    // 1. Fetch Payment Details
    $sql = "SELECT p.*, c.customer_name, c.company_name, c.address as c_address, c.city as c_city, c.state as c_state,  c.pincode as c_pincode, c.gst_number as c_gst, c.phone as c_phone,
            si.invoice_number, si.invoice_date
            FROM payment_received p 
            LEFT JOIN customers_listing c ON p.customer_id = c.customer_id 
            LEFT JOIN sales_invoices si ON p.invoice_id = si.invoice_id
            WHERE p.payment_id = ? AND p.organization_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $payment_id, $organization_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        return false;
    }

    $pay = $res->fetch_assoc();

    // 2. Organization Data
    $orgQ = $conn->query("SELECT * FROM organizations WHERE organization_id = $organization_id LIMIT 1");
    $orgData = $orgQ->fetch_assoc();

    // Prepare Data
    $payment_no = $pay['payment_number'];
    $payment_date = date("d-M-Y", strtotime($pay['payment_date']));
    $amount = floatval($pay['amount']);

    $mode = $pay['payment_mode'];
    $ref = $pay['reference_no'];
    $notes = $pay['notes'];

    // Logo handling
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

    // Amount Words Helper
    if (!function_exists('getIndianCurrencyInner')) {
        function getIndianCurrencyInner(float $number) {
            $no = floor($number);
            $point = round($number - $no, 2) * 100;
            $point = (int)$point;
            $hundred = null;
            $digits_1 = strlen($no);
            $i = 0;
            $str = array();
            $words = array('0' => '', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine', '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve', '13' => 'Thirteen', '14' => 'Fourteen', '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen', '18' => 'Eighteen', '19' => 'Nineteen', '20' => 'Twenty', '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty', '60' => 'Sixty', '70' => 'Seventy', '80' => 'Eighty', '90' => 'Ninety');
            $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
            while ($i < $digits_1) {
                $divider = ($i == 2) ? 10 : 100;
                $number = floor($no % $divider);
                $no = floor($no / $divider);
                $i += ($divider == 10) ? 1 : 2;
                if ($number) {
                    $plural = (($counter = count($str)) && $number > 9) ? '' : null;
                    $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                    $str [] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
                } else $str[] = null;
            }
            $str = array_reverse($str);
            $result = implode('', $str);
            $points = '';
            if($point > 0){
                $points .= " and ";
                $points .= ($point < 21) ? $words[$point] : $words[floor($point / 10) * 10] . " " . $words[$point % 10];
                $points .= " Paise";
            }
            return $result . " Rupees" . $points . " Only";
        }
    }
    $amtWords = getIndianCurrencyInner($amount);

    $custName = $pay['company_name'] ?: $pay['customer_name'];
    $custAddr = $pay['c_address'] . '<br>' . $pay['c_city'] . ', ' . $pay['c_state'] . ' - ' . $pay['c_pincode'];
    $custGst = $pay['c_gst'];

    // HTML Template
    return "
    <html>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
        <style>
            @page { margin: 0px; }
            body { 
                font-family: 'DejaVu Sans', sans-serif; 
                color: #000; 
                font-size: 10px; 
                margin: 0; 
                padding: 10mm;
            }
            
            /* Helpers */
            .text-right { text-align: right; }
            .text-uppercase { text-transform: uppercase; }
            .fw-bold { font-weight: bold; }
            .small { font-size: 8px; color: #333; }
            
            /* Layout */
            table { width: 100%; border-collapse: collapse; }
            td { vertical-align: top; padding: 2px 0; }
            
            /* Header */
            .header { border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
            .receipt-title { font-size: 16px; color: #000; font-weight: bold; margin: 0; }
            
            /* Content Boxes */
            .box-title { font-size: 9px; font-weight: bold; color: #555; text-transform: uppercase; border-bottom: 1px solid #eee; padding-bottom: 2px; margin-bottom: 5px; }
            
            .from-section { 
                padding: 10px; 
                border: 1px solid #000; 
                background-color: transparent; 
                border-radius: 4px; 
            }
            
            /* Payment Details */
            .details-row td { padding: 3px 0; border-bottom: 1px dotted #ccc; }
            .details-label { color: #555; }
            
            /* Amount Banner */
            .amount-banner { 
                border: 2px solid #000; 
                background-color: transparent;
                border-radius: 6px; 
                padding: 10px; 
                text-align: center; 
                margin-top: 20px;
            }
            .amount-val { font-size: 18px; font-weight: bold; color: #000; }
            .amount-label { color: #000; letter-spacing: 1px; margin-bottom: 2px; text-transform: uppercase; font-size: 9px; }
            
            /* Footer */
            .footer { margin-top: 30px; }
            .sig-line { border-top: 1px solid #000; width: 150px; float: right; text-align: right; padding-top: 5px; font-size: 9px; }
        </style>
    </head>
    <body>
        
        <!-- Header -->
        <table class='header'>
            <tr>
                <td width='60%'>
                    " . (!empty($logoDataUri) ? "<img src='$logoDataUri' style='height: 40px; margin-bottom: 5px; display: block;'>" : "") . "
                    <div style='font-size: 12px; font-weight: bold; color: #000;'>{$orgData['organization_name']}</div>
                    <div class='small'>
                        {$orgData['address']}<br>
                        {$orgData['city']}, {$orgData['state']} - {$orgData['pincode']}<br>
                        GSTIN: {$orgData['gst_number']}
                    </div>
                </td>
                <td width='40%' class='text-right'>
                    <div class='receipt-title'>PAYMENT RECEIPT</div>
                    <table style='margin-top: 5px;'>
                        <tr>
                            <td class='text-right small' style='padding-right: 10px;'>Receipt No:</td>
                            <td class='text-right fw-bold'>$payment_no</td>
                        </tr>
                        <tr>
                            <td class='text-right small' style='padding-right: 10px;'>Date:</td>
                            <td class='text-right fw-bold'>$payment_date</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Body Content -->
        <table>
            <tr>
                <td width='48%' style='padding-right: 2%'>
                    <div class='box-title'>Received From</div>
                    <div class='from-section'>
                        <div class='fw-bold' style='font-size: 11px; margin-bottom: 3px;'>$custName</div>
                        <div class='small' style='line-height: 1.4;'>
                            $custAddr<br>
                            GSTIN: $custGst
                        </div>
                    </div>
                </td>
                <td width='2%'></td>
                <td width='50%' style='padding-left: 10px;'>
                    <div class='box-title'>Payment Details</div>
                    <table class='details-row'>
                        <tr>
                            <td class='details-label'>Mode</td>
                            <td class='text-right fw-bold'>$mode</td>
                        </tr>
                        " . ($ref ? "
                        <tr>
                            <td class='details-label'>Reference</td>
                            <td class='text-right fw-bold'>$ref</td>
                        </tr>" : "") . "
                        <tr>
                            <td class='details-label'>Type</td>
                            <td class='text-right fw-bold'>" . ($pay['invoice_number'] ? "Against Invoice" : "Advance") . "</td>
                        </tr>
                        " . ($pay['invoice_number'] ? "
                        <tr>
                            <td class='details-label'>Invoice Ref</td>
                            <td class='text-right fw-bold'>{$pay['invoice_number']}</td>
                        </tr>" : "") . "
                    </table>
                    
                    " . ($notes ? "
                    <div style='margin-top: 5px; font-size: 9px; color: #333;'>
                        <span style='color: #666;'>Note:</span> $notes
                    </div>" : "") . "
                </td>
            </tr>
        </table>

        <!-- Amount Section -->
        <div class='amount-banner'>
            <div class='amount-label'>Amount Received</div>
            <div class='amount-val'>&#8377; " . number_format($amount, 2) . "</div>
            <div class='small' style='margin-top: 3px; font-style: italic;'>($amtWords)</div>
        </div>

        <!-- Footer -->
        <div class='footer'>
            <table width='100%'>
                <tr>
                    <td width='50%'></td>
                    <td width='50%' class='text-right'>
                        <div style='font-weight: bold; font-size: 10px; margin-bottom: 30px;'>For {$orgData['organization_name']}</div>
                        <div class='sig-line'>Authorized Signatory</div>
                    </td>
                </tr>
            </table>
        </div>

    </body>
    </html>";
}

function generatePaymentPDF($payment_id, $conn, $organization_id) {
    $html = getPaymentReceiptHTML($payment_id, $conn, $organization_id);
    if(!$html) return null;

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A5', 'landscape');
    $dompdf->render();
    
    return $dompdf->output();
}
