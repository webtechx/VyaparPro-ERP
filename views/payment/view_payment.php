<?php
$title = 'View Payment Receipt';
require_once __DIR__ . '/../../config/auth_guard.php'; // Auth + Config
require_once __DIR__ . '/../../config/conn.php';     // DB just in case

// --- Layout Handling ---
// Define Base Path for assets
$basePath = '../../';

// Include Head (CSS, Meta)
include_once __DIR__ . '/../../views/layouts/components/head.php';

// Include Topbar
include_once __DIR__ . '/../../views/layouts/components/TobbarHeader.php';

// Include Sidebar
include_once __DIR__ . '/../../views/layouts/components/SidebarHeader.php';
?>

<!-- HELPER FUNCTION -->
<?php
if (!function_exists('getIndianCurrency')) {
    function getIndianCurrency($number) {
        $no = floor($number);
        $point = round($number - $no, 2) * 100;
        $point = (int)$point;
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
        
        $points = '';
        if($point > 0){
            $points .= " and ";
            $points .= ($point < 21) ? $words[$point] : $words[floor($point / 10) * 10] . " " . $words[$point % 10];
            $points .= " Paise";
        }
        
        return $result . " Rupees" . $points . " Only";
    }
}

// Get Payment ID
$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($payment_id <= 0){
    echo "<div class='container mt-5'><div class='alert alert-danger'>Invalid Payment ID</div></div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Fetch Payment Details
$sql = "SELECT p.*, c.customer_name, c.company_name, c.address as c_address, c.city as c_city, c.state as c_state,  c.pincode as c_pincode, c.gst_number as c_gst, c.phone as c_phone,
        e.first_name as created_first_name, e.last_name as created_last_name, e.employee_code as created_code,
        si.invoice_number, si.invoice_date
        FROM payment_received p 
        LEFT JOIN customers_listing c ON p.customer_id = c.customer_id 
        LEFT JOIN employees e ON p.created_by = e.employee_id
        LEFT JOIN sales_invoices si ON p.invoice_id = si.invoice_id
        WHERE p.payment_id = $payment_id";
$result = $conn->query($sql);

if(!$result || $result->num_rows === 0){
    echo "<div class='container mt-5'><div class='alert alert-danger'>Payment not found</div></div>";
    require_once __DIR__ . '/../../includes/footer.php'; 
    exit;
}

$pay = $result->fetch_assoc();

// Initialize variables
$payment_no = $pay['payment_number'];
$payment_date = date("d-M-Y", strtotime($pay['payment_date']));

// Organization Details
$orgSql = "SELECT * FROM organizations LIMIT 1";
$orgRes = $conn->query($orgSql);
if($orgRes && $orgRes->num_rows > 0){
    $org = $orgRes->fetch_assoc();
    $seller = [
        "name" => $org['organization_name'],
        "address" => $org['address'],
        "city" => $org['city'],
        "state" => $org['state'],
        "pincode" => $org['pincode'],
        "gstin" => $org['gst_number'],
        "logo" => $org['organization_logo'],
        "phone" => $org['organization_mobile']
    ];
} else {
    // Fallback
    $seller = [
        "name" => "Samadhan ERP", "address" => "", "city" => "", "state" => "", "pincode" => "", "gstin" => "", "logo" => "", "phone" => ""
    ];
}

// Payer Details
$payer = [
    "name" => $pay['company_name'] ?: $pay['customer_name'],
    "address" => $pay['c_address'],
    "city" => $pay['c_city'],
    "state" => $pay['c_state'],
    "pincode" => $pay['c_pincode'],
    "gstin" => $pay['c_gst'],
    "phone" => $pay['c_phone']
];
?>

<div class="container-fluid">
 


    <div class="d-print-none text-center mb-4 mt-3">
        <a href="<?= $basePath ?>/controller/payment/download_payment_pdf.php?id=<?= $payment_id ?>" target="_blank" class="btn btn-success me-2"><i class="ti ti-download me-1"></i> Download PDF</a>
        <a href="<?= $basePath ?>/controller/payment/print_payment.php?id=<?= $payment_id ?>" target="_blank" class="btn btn-primary me-2"><i class="ti ti-printer me-1"></i> Print</a>
        <a href="javascript:history.back()" class="btn btn-secondary"><i class="ti ti-arrow-left me-1"></i> Back</a>
    </div>

    <!-- MAIN RECEIPT CONTAINER -->
    <div class="container-fluid d-flex justify-content-center" id="payment_container">
        <!-- Reusing standard styles from previous steps -->
        <div class="payment-page bg-white shadow-sm p-4 p-md-5" style="max-width: 900px; width: 100%; min-height: 600px; color: #333; font-family: 'Inter', sans-serif;">
            
            <!-- Header -->
            <div class="row mb-5 border-bottom pb-4" style="border-color: #5d87ff !important;">
                <div class="col-md-7">
                    <?php if(!empty($seller['logo'])): ?>
                        <img src="<?= $basePath ?>/uploads/<?= $_SESSION['organization_code'] ?>/organization_logo/<?= $seller['logo'] ?>" alt="logo" style="max-height: 50px; margin-bottom: 10px;">
                    <?php endif; ?>
                    <h4 class="fw-bold text-dark mb-1"><?= $seller['name'] ?></h4>
                    <p class="text-muted small mb-0 lh-sm">
                        <?= $seller['address'] ?><br>
                        <?= $seller['city'] ?>, <?= $seller['state'] ?> - <?= $seller['pincode'] ?><br>
                        GSTIN: <?= $seller['gstin'] ?>
                    </p>
                </div>
                <div class="col-md-5 text-end">
                    <h2 class="text-uppercase text-primary fw-bold mb-3" style="letter-spacing: 1px; color: #5d87ff !important;">Payment Receipt</h2>
                    <div class="d-inline-block text-end">
                        <div class="d-flex justify-content-end mb-1">
                            <span class="text-muted small me-3">Receipt No:</span>
                            <span class="fw-bold text-dark"><?= $payment_no ?></span>
                        </div>
                        <div class="d-flex justify-content-end">
                            <span class="text-muted small me-3">Date:</span>
                            <span class="fw-bold text-dark"><?= $payment_date ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="row mb-5">
                <!-- Received From -->
                <div class="col-md-6 mb-4 mb-md-0">
                    <h6 class="text-uppercase text-secondary small fw-bold border-bottom pb-2 mb-3">Received From</h6>
                    <div class="p-3 bg-light rounded">
                        <h5 class="fw-bold text-dark mb-1"><?= $payer['name'] ?></h5>
                        <p class="mb-0 text-secondary small lh-sm">
                            <?= $payer['address'] ?><br>
                            GSTIN: <?= $payer['gstin'] ?>
                        </p>
                    </div>
                </div>
                
                <!-- Payment Details -->
                <div class="col-md-6 ps-md-5">
                    <h6 class="text-uppercase text-secondary small fw-bold border-bottom pb-2 mb-3">Payment Details</h6>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Payment Mode:</span>
                        <span class="fw-semibold text-dark"><?= $pay['payment_mode'] ?></span>
                    </div>
                    
                    <?php if(!empty($pay['reference_no'])): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Reference No:</span>
                        <span class="fw-semibold text-dark"><?= $pay['reference_no'] ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Type:</span>
                        <span class="fw-semibold text-dark"><?= $pay['item_type'] == 'invoice' ? 'Against Invoice' : 'Advance Payment' ?></span>
                    </div>

                    <?php if(!empty($pay['invoice_number'])): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Invoice Ref:</span>
                        <span class="fw-semibold text-dark"><?= $pay['invoice_number'] ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($pay['notes'])): ?>
                    <div class="mt-1">
                        <span class="text-muted small d-block">Note: <?= $pay['notes'] ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Amount Banner -->
            <div class="py-4 my-4 text-center rounded" style="background-color: #ecf2ff; border: 1px dashed #5d87ff;">
                <div class="text-uppercase fw-bold text-primary small mb-1" style="letter-spacing: 1px;">Amount Received</div>
                <h1 class="display-6 fw-bold text-dark mb-1">₹<?= number_format($pay['amount'], 2) ?></h1>
                <div class="text-secondary small fst-italic">(<?= getIndianCurrency($pay['amount']) ?>)</div>
            </div>

            <!-- Footer -->
            <div class="row mt-5 pt-4 align-items-end">
                <div class="col-6">
                    <!-- Empty for balance or design -->
                </div>
                <div class="col-6 text-end">
                    <div class="d-inline-block text-center">
                        <div class="fw-bold text-dark small mb-1">For <?= $seller['name'] ?></div>
                        <div style="height: 40px;"></div> <!-- Space for wet signature -->
                        <div class="border-top pt-2 text-muted small" style="min-width: 200px;">Authorized Signatory</div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5 text-muted small text-uppercase" style="letter-spacing: 2px; font-size: 10px;">
                Thank You For Your Business
            </div>

        </div>
    </div>
</div>

<?php 
// Include Footer
include __DIR__ . '/../../views/layouts/components/footer.php';

// Include Scripts (JS)
include __DIR__ . '/../../views/layouts/components/scripts.php';
?>
</body>
</html>


<style>
    @media print {
        @page {
            size: A5 landscape;
            margin: 0;
        }
        
        /* Global Reset for Print */
        body {
            margin: 0;
            padding: 10mm; /* Safe print margin */
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
            color: #000;
            background: #fff;
            -webkit-print-color-adjust: exact;
        }

        /* Hiding non-print elements */
        .d-print-none { display: none !important; }
        
        /* Container Overrides */
        #payment_container {
            padding: 0 !important; margin: 0 !important;
            display: block !important; width: 100% !important; height: auto !important;
        }
        .payment-page {
            width: 100% !important; height: auto !important;
            box-shadow: none !important; padding: 0 !important;
            margin: 0 !important; max-width: none !important;
        }

        /* -----------------------
           LAYOUT SECTIONS 
           ----------------------- */
        
        /* Header: Use Flex for side-by-side */
        .row.border-bottom {
            display: flex !important;
            justify-content: space-between !important;
            align-items: flex-start !important;
            border-bottom: 2px solid #000 !important;
            padding-bottom: 10px !important;
            margin-bottom: 15px !important;
        }
        /* Left: Logo + Address */
        .col-md-7 {
            width: 60% !important; flex: 0 0 60%;
        }
        /* Right: Title + Meta */
        .col-md-5 {
            width: 40% !important; flex: 0 0 40%;
            text-align: right !important;
        }

        /* Typography sizing */
        h4, h2 { color: #000 !important; margin: 0 !important; }
        h4 { font-size: 14pt !important; margin-bottom: 5px !important; }
        h2 { font-size: 16pt !important; margin-bottom: 10px !important; }
        p, span, div { color: #000 !important; }
        
        /* Content Row: Flex side-by-side */
        .row.mb-5 {
            display: flex !important;
            margin-bottom: 15px !important;
        }
        
        /* 1. Received From (Boxed with Border, not BG) */
        .col-md-6 { width: 48% !important; flex: 0 0 48%; }
        
        /* Target the "Received From" container specifically */
        .col-md-6:first-child .bg-light {
            background: transparent !important;
            border: 1px solid #000 !important;
            border-radius: 4px !important;
            padding: 10px !important;
        }
        /* Remove Bootstrap bottoms margins */
        .mb-2, .mb-3, .mb-4 { margin-bottom: 5px !important; }

        /* 2. Payment Details */
        .col-md-6:last-child {
            padding-left: 20px !important;
        }
        /* Ensure lines are compact */
        .d-flex.justify-content-between {
            margin-bottom: 4px !important;
            border-bottom: 1px dotted #ccc; /* Guide lines for readability */
        }

        /* Amount Banner (Slim Stripe) */
        .py-4.my-4 {
            background: transparent !important; /* Fallback if no bg graphics */
            border: 2px solid #000 !important;
            border-radius: 6px !important;
            padding: 10px !important;
            margin: 15px 0 !important;
            text-align: center !important;
        }
        .display-6 {
            font-size: 18pt !important;
            font-weight: bold !important;
            margin: 5px 0 !important;
        }

        /* Footer / Signature */
        .row.mt-5 {
            display: flex !important;
            justify-content: flex-end !important;
            margin-top: 20px !important;
        }
        .text-center.mt-5 { display: none !important; } /* Hide 'Thank you' footer if space tight */
        
        .col-6.text-end {
            width: 40% !important;
            text-align: right !important;
        }
        /* Wet Signature Space */
        div[style*="height: 40px"] { height: 30px !important; }
        
        /* Font adjustments */
        small, .small { font-size: 8pt !important; }
    }
</style>
<?php
// End view
?>
