<?php
// Ensure database connection
// Ensure database connection
require_once __DIR__ . '/../../config/conn.php';
// Set active menu for sidebar
$activeMenu = 'purchase_orders_approved';

// Get PO ID
$po_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($po_id <= 0){
    echo "<div class='alert alert-danger'>Invalid Purchase Order ID</div>";
    return;
}

// Fetch Purchase Order Details
$sql = "SELECT po.*, 
        v.display_name, v.company_name, v.gst_no as v_gst, v.mobile as v_phone, v.email as v_email,
        va.address_line1, va.address_line2, va.city as v_city, va.state as v_state, va.pin_code as v_pincode
        FROM purchase_orders po 
        JOIN vendors_listing v ON po.vendor_id = v.vendor_id 
        LEFT JOIN vendors_addresses va ON v.vendor_id = va.vendor_id AND va.address_type = 'billing'
        WHERE po.purchase_orders_id = $po_id";
$result = $conn->query($sql);

if(!$result || $result->num_rows === 0){
    echo "<div class='alert alert-danger'>Purchase Order not found</div>";
    return;
}

$po = $result->fetch_assoc();

// Initialize variables
$po_no = $po['po_number'];
$po_date = date("d-M-Y", strtotime($po['order_date']));
$delivery_date = $po['delivery_date'] ? date("d-M-Y", strtotime($po['delivery_date'])) : '-';

// Buyer Details - Fetch from Organizations Table (This is US)
$orgSql = "SELECT * FROM organizations LIMIT 1";
$orgRes = $conn->query($orgSql);
if($orgRes && $orgRes->num_rows > 0){
    $org = $orgRes->fetch_assoc();
    $buyer = [ // We are the buyer in a PO
        "name" => $org['organization_name'],
        "address" => $org['address'],
        "city" => $org['city'],
        "state" => $org['state'],
        "pincode" => $org['pincode'],
        "gstin" => $org['gst_number'],
        "logo" => $org['organization_logo'],
        "state_code" => !empty($org['gst_number']) ? substr($org['gst_number'], 0, 2) : '', 
        "phone" => "" 
    ];
} else {
    // Fallback
    $buyer = [
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

// Seller Details (The Vendor)
$seller_address = $po['address_line1'];
if(!empty($po['address_line2'])) $seller_address .= ", " . $po['address_line2'];

$seller = [
    "name" => $po['company_name'] ?: $po['display_name'],
    "address" => $seller_address,
    "city" => $po['v_city'],
    "state" => $po['v_state'],
    "pincode" => $po['v_pincode'],
    "gstin" => $po['v_gst'],
    "state_code" => !empty($po['v_gst']) ? substr($po['v_gst'], 0, 2) : '',
    "phone" => $po['v_phone'],
    "email" => $po['v_email']
];

// Fetch PO Items
$items = [];
$itemSql = "SELECT poi.*, il.item_name, il.stock_keeping_unit, u.unit_name, h.hsn_code 
            FROM purchase_order_items poi 
            LEFT JOIN items_listing il ON poi.item_id = il.item_id 
            LEFT JOIN units_listing u ON il.unit_id = u.unit_id
            LEFT JOIN hsn_listing h ON il.hsn_id = h.hsn_id
            WHERE poi.purchase_order_id = $po_id";
$itemRes = $conn->query($itemSql);
$counter = 1;

if($itemRes){
    while($row = $itemRes->fetch_assoc()){
        // PO usually applies GST on the total or per item, but let's assume per item for row correctness if data exists, 
        // or we re-calculate based on PO logic.
        // In purchase_orders_listing.php, GST seems to be global (applied on subtotal).
        // Let's check logic: "GST Row" in listing has a global GST rate/type.
        // So individual items essentially share that rate.
        
        $gst_rate = $po['gst_rate']; 
        
        $items[] = [
            "sno" => $counter++,
            "description" => $row['item_name'],
            "sku" => $row['stock_keeping_unit'],
            "hsn" => $row['hsn_code'] ?? '-',
            "qty" => $row['quantity'],
            "unit" => $row['unit_name'], 
            "rate" => $row['rate'],
            "discount" => $row['discount'],
            "discount_type" => $row['discount_type'], // percentage or amount
            "amount" => $row['amount'], // This includes discount but BEFORE tax in the form logic usually?
            // Actually in purchase_orders_listing.php: Amount = (Qty * Rate) - Discount. 
            // Tax is applied globally on Subtotal.
        ];
    }
}
?>

<div class="d-print-none mt-4 mb-4 text-center">
    <button onclick="window.print()" class="btn btn-primary me-2"><i class="ti ti-printer me-1"></i> Print</button>
    <a href="<?= $basePath ?>/purchase_orders" class="btn btn-secondary"><i class="ti ti-arrow-left me-1"></i> Back</a>
</div>

<div class="container-fluid" id="po_content">
    
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">View Purchase Order</h5>
                </div>

            <div class="card-body px-4 pt-4">
                <!-- PO Header -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="auth-brand mb-0">
                            <!-- Show Buyer (Our) Logo here usually? Or just text. Let's show Our Logo -->
                            <?php if(!empty($buyer['logo'])): ?>
                            <img src="<?= $basePath ?>/uploads/<?= $_SESSION['organization_code'] ?>/organization_logo/<?= $buyer['logo'] ?>" alt="logo" height="80">
                            <?php endif; ?>
                            <h4 class="fw-bold text-primary mt-2"><?= $buyer['name'] ?></h4>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <h4 class="fw-bold text-dark m-0">#<?= $po_no ?></h4>
                        <p class="text-muted mb-0">Date: <?= $po_date ?></p>
                        <p class="text-muted mb-0">Status: <span class="badge bg-secondary"><?= strtoupper($po['status']) ?></span></p>
                    </div>
                </div>

                <!-- Addresses -->
                <div class="row mb-4">
                    <div class="col-6">
                        <h6 class="text-uppercase text-muted mb-2">Vendor (From)</h6>
                        <h5 class="mb-1 fw-bold"><?= $seller['name'] ?></h5>
                        <p class="text-muted mb-1">
                            <?= $seller['address'] ?><br>
                            <?= $seller['city'] ?>, <?= $seller['state'] ?> - <?= $seller['pincode'] ?>
                        </p>
                        <?php if($seller['gstin']): ?><p class="text-muted mb-1"><strong>GSTIN:</strong> <?= $seller['gstin'] ?></p><?php endif; ?>
                        <?php if($seller['phone']): ?><p class="text-muted mb-1"><strong>Phone:</strong> <?= $seller['phone'] ?></p><?php endif; ?>
                        <?php if($seller['email']): ?><p class="text-muted mb-0"><strong>Email:</strong> <?= $seller['email'] ?></p><?php endif; ?>
                    </div>

                    <div class="col-6 text-end">
                        <h6 class="text-uppercase text-muted mb-2">Ship To / Bill To (Us)</h6>
                        <h5 class="mb-1 fw-bold"><?= $buyer['name'] ?></h5>
                        <p class="text-muted mb-1">
                            <?= $buyer['address'] ?><br>
                            <?= $buyer['city'] ?>, <?= $buyer['state'] ?> - <?= $buyer['pincode'] ?>
                        </p>
                        <?php if($buyer['gstin']): ?><p class="text-muted mb-1"><strong>GSTIN:</strong> <?= $buyer['gstin'] ?></p><?php endif; ?>
                    </div>
                </div>
                
                <!-- Additional PO Info -->
                <div class="row mb-4 p-2 bg-light rounded mx-1">
                    <div class="col-md-4">
                        <p class="mb-0"><strong>Expected Delivery:</strong> <?= $delivery_date ?></p>
                    </div>
                     <div class="col-md-4 text-center">
                        <p class="mb-0"><strong>Payment Terms:</strong> <?= $po['payment_terms'] ?: '-' ?></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <p class="mb-0"><strong>Reference #:</strong> <?= $po['reference_no'] ?: '-' ?></p>
                    </div>
                </div>

                <!-- Item Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-sm text-center align-middle">
                        <thead class="bg-light align-middle">
                            <tr class="fs-xs fw-bold">
                                <th style="width: 40px;">#</th>
                                <th class="text-start">Item Description</th>
                                <th class="text-center">HSN</th>
                                <th>Qty</th>
                                <th>Unit</th>
                                <th class="text-end">Rate</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sub_total = 0;

                            foreach ($items as $item) {
                                $sub_total += $item['amount'];
                                
                                // Format discount display
                                $disc_display = "-";
                                if($item['discount'] > 0){
                                    if($item['discount_type'] == 'percentage'){
                                        $disc_display = $item['discount'] . '%';
                                    } else {
                                        $disc_display = '₹' . number_format($item['discount'], 2);
                                    }
                                }
                            ?>
                            <tr>
                                <td><?= $item['sno'] ?></td>
                                <td class="text-start">
                                    <span class="fw-medium"><?= $item['description'] ?></span>
                                    <?php if($item['sku']): ?><br><small class="text-muted">SKU: <?= $item['sku'] ?></small><?php endif; ?>
                                </td>
                                <td class="text-center"><?= $item['hsn'] ?></td>
                                <td><?= $item['qty'] ?></td>
                                <td><?= $item['unit'] ?></td>
                                <td class="text-end"><?= number_format($item['rate'], 2) ?></td>
                                <td class="text-end"><?= $disc_display ?></td>
                                <td class="text-end fw-bold"><?= number_format($item['amount'], 2) ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot class="bg-light fw-bold">
                            <tr>
                                <td colspan="7" class="text-end">Sub Total</td>
                                <td class="text-end"><?= number_format($sub_total, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-sm-6">
                        <div class="mt-3">
                            <h6 class="text-muted">Notes:</h6>
                            <p class="text-muted small"><?= nl2br($po['notes'] ?? '-') ?></p>
                        </div>
                        <div class="mt-3">
                            <h6 class="text-muted">Terms & Conditions:</h6>
                            <p class="text-muted small"><?= nl2br($po['terms_conditions'] ?? '-') ?></p>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 text-end">
                        <table class="table table-borderless table-sm w-auto ms-auto text-end">
                            <tr>
                                <td>Sub Total:</td>
                                <td class="fw-medium"><?= number_format($sub_total, 2) ?></td>
                            </tr>
                            
                            <?php 
                            // Global Discount
                            $global_discount = 0;
                            if($po['discount_value'] > 0){
                                if($po['discount_type'] == 'percentage'){
                                    $global_discount = ($sub_total * $po['discount_value']) / 100;
                                    $disc_str = $po['discount_value'] . '%';
                                } else {
                                    $global_discount = $po['discount_value'];
                                    $disc_str = '₹' . number_format($po['discount_value'], 2);
                                }
                            }
                            ?>
                            <?php if($global_discount > 0): ?>
                            <tr>
                                <td>Discount (<?= $disc_str ?>):</td>
                                <td class="fw-medium">-<?= number_format($global_discount, 2) ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php
                            $taxable = $sub_total - $global_discount;
                            
                            // GST
                            $gst_amount = 0;
                            $gst_rate = $po['gst_rate'];
                            $gst_type = $po['gst_type'];
                            
                            if($gst_rate > 0 && !empty($gst_type)){
                                $gst_amount = ($taxable * $gst_rate) / 100;
                            }
                            ?>
                            
                             <?php if($gst_amount > 0): ?>
                                <?php if($gst_type == 'IGST'): ?>
                                    <tr>
                                        <td>IGST (<?= $gst_rate ?>%):</td>
                                        <td class="fw-medium"><?= number_format($gst_amount, 2) ?></td>
                                    </tr>
                                <?php else: // CGST + SGST ?>
                                    <tr>
                                        <td>CGST (<?= $gst_rate/2 ?>%):</td>
                                        <td class="fw-medium"><?= number_format($gst_amount/2, 2) ?></td>
                                    </tr>
                                    <tr>
                                        <td>SGST (<?= $gst_rate/2 ?>%):</td>
                                        <td class="fw-medium"><?= number_format($gst_amount/2, 2) ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if(!empty($po['adjustment']) && $po['adjustment'] != 0): ?>
                            <tr>
                                <td>Adjustment:</td>
                                <td class="fw-medium"><?= number_format($po['adjustment'], 2) ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <tr class="border-top">
                                <td class="fs-5 fw-bold">Grand Total:</td>
                                <td class="fs-5 fw-bold text-primary">₹ <?= number_format($po['total_amount'], 2) ?></td>
                            </tr>
                        </table>
                        
                        <div class="mt-5">
                            <p class="fw-bold mb-1">For <?= $buyer['name'] ?></p>
                            <br>
                            <p class="text-muted fs-xs">Authorized Signatory</p>
                        </div>
                    </div>
                </div>

            </div>
            </div>
        </div>
    </div>
</div>
