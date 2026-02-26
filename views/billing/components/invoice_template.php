<?php
// Ensure database connection
require_once __DIR__ . '/../../../config/conn.php';

// Get Invoice ID
$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($invoice_id <= 0){
    echo "<div class='alert alert-danger'>Invalid Invoice ID</div>";
    return; // Use return instead of exit/die to allow layout to finish (though typically this is main content)
}

// Fetch Invoice Details
$sql = "SELECT i.*, c.customer_name, c.company_name, c.address as c_address, c.city as c_city, c.state as c_state, c.pincode as c_pincode, c.gst_number as c_gst, c.phone as c_phone 
        FROM sales_invoices i 
        JOIN customers c ON i.customer_id = c.customer_id 
        WHERE i.invoice_id = $invoice_id";
$result = $conn->query($sql);

if(!$result || $result->num_rows === 0){
    echo "<div class='alert alert-danger'>Invoice not found</div>";
    return;
}

$inv = $result->fetch_assoc();

// Initialize variables
$invoice_no = $inv['invoice_number'];
$invoice_date = date("d-M-Y", strtotime($inv['invoice_date']));
$irn = ""; 
$ack_no = "";
$ack_date = "";
$supply_type = "B2B"; 
$reverse_charge = "No";

// Seller Details - Fetch from Organizations Table
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
        "state_code" => substr($org['gst_number'], 0, 2), 
        "phone" => "" 
    ];
} else {
    // Fallback
    $seller = [
        "name" => "Samadhan ERP Solutions",
        "address" => "123, Tech Park, Sector 5",
        "city" => "Noida",
        "state" => "Uttar Pradesh",
        "pincode" => "201301",
        "gstin" => "09AAACS1234A1Z5",
        "state_code" => "09",
        "phone" => "+91 98765 43210"
    ];
}

// Buyer Details
$buyer = [
    "name" => $inv['company_name'] ?: $inv['customer_name'],
    "address" => $inv['c_address'],
    "city" => $inv['c_city'],
    "state" => $inv['c_state'],
    "pincode" => $inv['c_pincode'],
    "gstin" => $inv['c_gst'],
    "state_code" => substr($inv['c_gst'], 0, 2) ?: '',
    "phone" => $inv['c_phone']
];

// Fetch Invoice Items
$items = [];
$itemSql = "SELECT * FROM sales_invoice_items WHERE invoice_id = $invoice_id";
$itemRes = $conn->query($itemSql);
$counter = 1;
if($itemRes){
    while($row = $itemRes->fetch_assoc()){
        $gst_rate = $inv['gst_rate']; 
        $items[] = [
            "sno" => $counter++,
            "description" => $row['item_name'],
            "hsn" => "998313", 
            "qty" => $row['quantity'],
            "unit" => "NOS", 
            "rate" => $row['rate'],
            "taxable_value" => $row['amount'], 
            "gst_rate" => $gst_rate
        ];
    }
}
?>

<div class="row justify-content-center py-4 d-print-none">
    <div class="col-xxl-6 col-xl-8 text-center">
        <h3 class="fw-bold">Tax Invoice</h3>
        <p class="text-muted">Original for Recipient</p>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card" id="invoice_card">
            <div class="card-body px-0 pt-2 p-4"> <!-- Added p-4 for screen padding, will be overridden by print css if needed -->
                <!-- Invoice Header -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="auth-brand mb-0">
                            <h4 class="fw-bold text-primary">Samadhan ERP</h4>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <h4 class="fw-bold text-dark m-0">Invoice #<?= $invoice_no ?></h4>
                        <p class="text-muted mb-0">Date: <?= $invoice_date ?></p>

                    </div>
                </div>

                <!-- Seller & Buyer Details -->
                <div class="row mb-4">
                    <div class="col-5">
                        <h6 class="text-uppercase text-muted mb-2">Details of Supplier (From)</h6>
                        <h5 class="mb-1 fw-bold"><?= $seller['name'] ?></h5>
                        <p class="text-muted mb-1">
                            <?= $seller['address'] ?><br>
                            <?= $seller['city'] ?>, <?= $seller['state'] ?> - <?= $seller['pincode'] ?>
                        </p>
                        <p class="text-muted mb-1"><strong>GSTIN:</strong> <?= $seller['gstin'] ?></p>
                        <p class="text-muted mb-0"><strong>State Code:</strong> <?= $seller['state_code'] ?></p>
                    </div>

                    <div class="col-5">
                        <h6 class="text-uppercase text-muted mb-2">Details of Recipient (To)</h6>
                        <h5 class="mb-1 fw-bold"><?= $buyer['name'] ?></h5>
                        <p class="text-muted mb-1">
                            <?= $buyer['address'] ?><br>
                            <?= $buyer['city'] ?>, <?= $buyer['state'] ?> - <?= $buyer['pincode'] ?>
                        </p>
                        <p class="text-muted mb-1"><strong>GSTIN:</strong> <?= $buyer['gstin'] ?></p>
                        <p class="text-muted mb-0"><strong>State Code:</strong> <?= $buyer['state_code'] ?></p>
                    </div>

                    <div class="col-2 text-end">
                         <?php if($irn): ?>
                         <div class="border p-1 d-inline-block">
                             <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= $irn ?>" alt="QR Code" class="img-fluid" style="height: 100px;">
                             <p class="fs-xxs text-center mb-0 mt-1">Signed QR</p>
                         </div>
                         <?php endif; ?>
                    </div>
                </div>

                <!-- Item Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-sm text-center align-middle">
                        <thead class="bg-light align-middle">
                            <tr class="fs-xs fw-bold">
                                <th rowspan="2" style="width: 40px;">#</th>
                                <th rowspan="2" class="text-start">Description of Goods/Services</th>
                                <th rowspan="2">HSN/SAC</th>
                                <th rowspan="2">Qty</th>
                                <th rowspan="2">Unit</th>
                                <th rowspan="2">Rate</th>
                                <th rowspan="2">Taxable Value</th>
                                <th colspan="2">CGST</th>
                                <th colspan="2">SGST</th>
                                <th colspan="2">IGST</th>
                                <th rowspan="2">Total</th>
                            </tr>
                            <tr class="fs-xs">
                                <th>Rate</th>
                                <th>Amt</th>
                                <th>Rate</th>
                                <th>Amt</th>
                                <th>Rate</th>
                                <th>Amt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_taxable = 0;
                            $total_cgst = 0;
                            $total_sgst = 0;
                            $total_igst = 0;
                            $grand_total = 0;

                            $is_igst = ($inv['gst_type'] === 'IGST');

                            foreach ($items as $item) {
                                $gst_rate = $item['gst_rate'];
                                $taxable = $item['taxable_value'];
                                
                                $cgst_rate = 0; $cgst_amt = 0;
                                $sgst_rate = 0; $sgst_amt = 0;
                                $igst_rate = 0; $igst_amt = 0;

                                if ($is_igst) {
                                    $igst_rate = $gst_rate;
                                    $igst_amt = ($taxable * $igst_rate) / 100;
                                } else {
                                    if($gst_rate > 0) {
                                        $cgst_rate = $gst_rate / 2;
                                        $sgst_rate = $gst_rate / 2;
                                        $cgst_amt = ($taxable * $cgst_rate) / 100;
                                        $sgst_amt = ($taxable * $sgst_rate) / 100;
                                    }
                                }

                                $total_item_row = $taxable + $cgst_amt + $sgst_amt + $igst_amt;
                                
                                $total_taxable += $taxable;
                                $total_cgst += $cgst_amt;
                                $total_sgst += $sgst_amt;
                                $total_igst += $igst_amt;
                                $grand_total += $total_item_row;
                            ?>
                            <tr>
                                <td><?= $item['sno'] ?></td>
                                <td class="text-start fw-medium"><?= $item['description'] ?></td>
                                <td><?= $item['hsn'] ?></td>
                                <td><?= $item['qty'] ?></td>
                                <td><?= $item['unit'] ?></td>
                                <td class="text-end"><?= number_format($item['rate'], 2) ?></td>
                                <td class="text-end"><?= number_format($item['taxable_value'], 2) ?></td>
                                
                                <td><?= $cgst_rate > 0 ? $cgst_rate . '%' : '-' ?></td>
                                <td class="text-end"><?= $cgst_amt > 0 ? number_format($cgst_amt, 2) : '-' ?></td>
                                
                                <td><?= $sgst_rate > 0 ? $sgst_rate . '%' : '-' ?></td>
                                <td class="text-end"><?= $sgst_amt > 0 ? number_format($sgst_amt, 2) : '-' ?></td>
                                
                                <td><?= $igst_rate > 0 ? $igst_rate . '%' : '-' ?></td>
                                <td class="text-end"><?= $igst_amt > 0 ? number_format($igst_amt, 2) : '-' ?></td>
                                
                                <td class="text-end fw-bold"><?= number_format($total_item_row, 2) ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot class="bg-light fw-bold">
                            <tr>
                                <td colspan="6" class="text-end">Total</td>
                                <td class="text-end"><?= number_format($total_taxable, 2) ?></td>
                                <td></td>
                                <td class="text-end"><?= number_format($total_cgst, 2) ?></td>
                                <td></td>
                                <td class="text-end"><?= number_format($total_sgst, 2) ?></td>
                                <td></td>
                                <td class="text-end"><?= number_format($total_igst, 2) ?></td>
                                <td class="text-end"><?= number_format($grand_total, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-sm-6">
                        <div class="mt-3">
                            <h6 class="text-muted">Notes:</h6>
                            <p class="text-muted small"><?= nl2br($inv['notes'] ?? '') ?></p>
                            
                            <h6 class="text-muted">Terms & Conditions:</h6>
                             <p class="text-muted small"><?= nl2br($inv['terms_conditions'] ?? '') ?></p>
                        </div>
                    </div>
                    <div class="col-sm-6 text-end">
                        <table class="table table-borderless table-sm w-auto ms-auto text-end">
                            <tr>
                                <td>Taxable Amount:</td>
                                <td class="fw-medium"><?= number_format($total_taxable, 2) ?></td>
                            </tr>
                            <tr>
                                <td>Total Tax:</td>
                                <td class="fw-medium"><?= number_format($total_cgst + $total_sgst + $total_igst, 2) ?></td>
                            </tr>
                            <?php if($inv['adjustment'] != 0): ?>
                            <tr>
                                <td>Adjustment:</td>
                                <td class="fw-medium"><?= number_format($inv['adjustment'], 2) ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <tr class="border-top">
                                <td class="fs-5 fw-bold">Grand Total:</td>
                                <td class="fs-5 fw-bold text-primary">₹ <?= number_format($inv['total_amount'], 2) ?></td>
                            </tr>
                        </table>
                        
                        <div class="mt-5">
                            <p class="fw-bold mb-1">For Samadhan ERP Solutions</p>
                            <br>
                            <p class="text-muted fs-xs">Authorized Signatory</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
