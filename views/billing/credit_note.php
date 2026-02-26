<?php
$title = 'Create Credit Note';

// Validate Invoice ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="alert alert-danger m-3">Invalid Invoice ID specified</div>';
    exit;
}

$invoice_id = intval($_GET['id']);

// Check if Credit Note already exists
$check_cn = $conn->query("SELECT credit_note_id FROM credit_notes WHERE invoice_id = $invoice_id AND status != 'cancelled'");
if($check_cn && $check_cn->num_rows > 0) {
    echo '<div class="alert alert-warning m-3">
            <h4>Credit Note Already Exists</h4>
            <p>A credit note has already been generated for this invoice.</p>
            <a href="' . $basePath . '/tax_invoice" class="btn btn-secondary">Back to Invoices</a>
            <a href="' . $basePath . '/credit_note_view?id=' . $check_cn->fetch_assoc()['credit_note_id'] . '" class="btn btn-primary">View Existing Credit Note</a>
          </div>';
    exit;
}

// Fetch Invoice Details
$inv_sql = "SELECT inv.*, c.customer_name, c.company_name, c.email, c.phone 
            FROM sales_invoices inv 
            LEFT JOIN customers_listing c ON inv.customer_id = c.customer_id 
            WHERE inv.invoice_id = $invoice_id AND inv.organization_id = {$_SESSION['organization_id']}";
$inv_res = $conn->query($inv_sql);

if (!$inv_res || $inv_res->num_rows === 0) {
    echo '<div class="alert alert-danger m-3">Invoice not found</div>';
    exit;
}

$invoice = $inv_res->fetch_assoc();

// Fetch Invoice Items
$items_sql = "SELECT * FROM sales_invoice_items WHERE invoice_id = $invoice_id ORDER BY id ASC";
$items_res = $conn->query($items_sql);
$invoice_items = [];
while ($row = $items_res->fetch_assoc()) {
    $invoice_items[] = $row;
}

// Generate Credit Note Number
$org_short_code = isset($_SESSION['organization_short_code']) ? strtoupper($_SESSION['organization_short_code']) : 'ORG';
$next_cn_number = 'CN-' . $org_short_code . '-0001';

try {
    $cn_sql = "SELECT credit_note_number FROM credit_notes WHERE organization_id = {$_SESSION['organization_id']} ORDER BY credit_note_id DESC LIMIT 1";
    $cn_res = $conn->query($cn_sql);
    if ($cn_res && $cn_res->num_rows > 0) {
        $last_cn = $cn_res->fetch_assoc()['credit_note_number'];
        if (preg_match('/CN-' . preg_quote($org_short_code) . '-(\d+)/', $last_cn, $matches)) {
            $next_num = intval($matches[1]) + 1;
            $next_cn_number = 'CN-' . $org_short_code . '-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
        }
    }
} catch (Exception $e) { }
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">New Credit Note</h5>
                    <a href="<?= $basePath ?>/tax_invoice" class="btn btn-sm btn-outline-secondary">Back to Invoices</a>
                </div>

                <div class="card-body">
                    <form id="cr_note_form" action="<?= $basePath ?>/controller/billing/save_credit_note.php" method="post">
                        <input type="hidden" name="invoice_id" value="<?= $invoice['invoice_id'] ?>">
                        <input type="hidden" name="customer_id" value="<?= $invoice['customer_id'] ?>">
                        <input type="hidden" name="organization_id" value="<?= $_SESSION['organization_id'] ?>">

                        <!-- Header Info -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label text-muted">Customer</label>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($invoice['company_name'] ?: $invoice['customer_name']) ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted">Original Invoice #</label>
                                <input type="text" class="form-control bg-light" value="<?= $invoice['invoice_number'] ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted">Invoice Date</label>
                                <input type="text" class="form-control bg-light" value="<?= date('d M Y', strtotime($invoice['invoice_date'])) ?>" readonly>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Credit Note # <span class="text-danger">*</span></label>
                                <input type="text" name="credit_note_number" class="form-control fw-bold bg-light" value="<?= $next_cn_number ?>" required readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Credit Note Date <span class="text-danger">*</span></label>
                                <input type="date" name="credit_note_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Reason</label>
                                <input type="text" name="reason" class="form-control" list="reason_list" placeholder="Select or type reason">
                                <datalist id="reason_list">
                                    <option value="Goods Returned">
                                    <option value="Damaged Goods">
                                    <option value="Discount Application">
                                    <option value="Invoice Correction">
                                </datalist>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30%">Item</th>
                                        <th style="width: 10%; text-align:center;">HSN</th>
                                        <th style="width: 8%; text-align:center;">Inv Qty</th>
                                        <th style="width: 10%; text-align:center;">Return Qty</th>
                                        <th style="width: 12%; text-align:right;">Rate</th>
                                        <th style="width: 12%; text-align:center;">Discount</th>
                                        <th style="width: 8%; text-align:center;">GST %</th>
                                        <th style="width: 10%; text-align:right;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($invoice_items as $index => $item): ?>
                                    <tr class="item-row">
                                        <td>
                                            <?= htmlspecialchars($item['item_name']) ?>
                                            <input type="hidden" name="items[<?= $index ?>][item_id]" value="<?= $item['item_id'] ?>">
                                            <input type="hidden" name="items[<?= $index ?>][item_name]" value="<?= htmlspecialchars($item['item_name']) ?>">
                                            <input type="hidden" name="items[<?= $index ?>][hsn_code]" value="<?= $item['hsn_code'] ?>">
                                            <input type="hidden" name="items[<?= $index ?>][unit_id]" value="<?= $item['unit_id'] ?>">
                                        </td>
                                        <td class="text-center"><?= $item['hsn_code'] ?></td>
                                        <td class="text-center text-muted"><?= $item['quantity'] ?></td>
                                        <td>
                                            <input type="number" name="items[<?= $index ?>][quantity]" class="form-control form-control-sm text-center qty-input" 
                                                   value="0" max="<?= $item['quantity'] ?>" min="0" step="0.01">
                                        </td>
                                        <td class="text-end">
                                            <?= number_format($item['rate'], 2) ?>
                                            <input type="hidden" name="items[<?= $index ?>][rate]" class="rate-input" value="<?= $item['rate'] ?>">
                                        </td>
                                        <td class="text-center">
                                             <?php 
                                                $dVal = $item['discount'] ?? 0;
                                                $dType = $item['discount_type'] ?? 'amount';
                                                $dSym = ($dType == 'percentage') ? '%' : '₹';
                                             ?>
                                             <?= number_format($dVal, 2) ?> <?= $dSym ?>
                                             <input type="hidden" name="items[<?= $index ?>][discount]" class="discount-input" value="<?= $dVal ?>">
                                             <input type="hidden" name="items[<?= $index ?>][discount_type]" class="discount-type-select" value="<?= $dType ?>">
                                        </td>
                                        <td class="text-center">
                                            <?= $item['gst_rate'] ?>%
                                            <input type="hidden" name="items[<?= $index ?>][gst_rate]" class="gst-input" value="<?= $item['gst_rate'] ?>">
                                        </td>
                                        <td class="text-end fw-bold amount-display">0.00</td>
                                        
                                        <!-- Hidden Calculations -->
                                        <?php 
                                        $baseAmount = $item['quantity'] * $item['rate'];
                                        $discount = ($item['discount_type'] == 'percentage') ? $baseAmount * ($item['discount']/100) : $item['discount'];
                                        $taxable = $baseAmount - $discount;
                                        $gstAmount = $taxable * ($item['gst_rate']/100);
                                        $total = $taxable + $gstAmount;
                                        ?>
                                        <input type="hidden" name="items[<?= $index ?>][amount]" class="row-amount" value="0.00">
                                        <input type="hidden" name="items[<?= $index ?>][gst_amount]" class="row-gst" value="0.00">
                                        <input type="hidden" name="items[<?= $index ?>][total_amount]" class="row-total" value="0.00">
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer -->
                        <?php 
                        // For credit notes, start with 0 totals since return quantities start at 0
                        $init_sub_total = 0;
                        $init_total_tax = 0;
                        $init_raw_total = 0;
                        $init_round_off = 0;
                        $init_final_total = 0;
                        
                        // GST Split - start with 0
                        $init_cgst = 0; $init_sgst = 0; $init_igst = 0;
                        ?>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Sub Total (Taxable)</span>
                                            <span class="fw-bold" id="sub_total_display">₹<?= number_format($init_sub_total, 2) ?></span>
                                            <input type="hidden" name="sub_total" id="sub_total" value="<?= $init_sub_total ?>">
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Total Tax (GST)</span>
                                            <span class="fw-bold" id="total_tax_display">₹<?= number_format($init_total_tax, 2) ?></span>
                                            <input type="hidden" name="cgst_amount" id="cgst_amount" value="<?= $init_cgst ?>">
                                            <input type="hidden" name="sgst_amount" id="sgst_amount" value="<?= $init_sgst ?>">
                                            <input type="hidden" name="igst_amount" id="igst_amount" value="<?= $init_igst ?>">
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Round Off / Adj</span>
                                            <span class="fw-bold text-end"><?= number_format($init_round_off, 2) ?></span>
                                            <input type="hidden" name="adjustment" id="adjustment" value="<?= $init_round_off ?>">
                                        </div>
                                        <?php if(floatval($invoice['reward_points_redeemed']) > 0): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span style="color: green;">Original Loyalty Redeemed</span>
                                            <span style="color: green; font-weight: bold;">- <?= number_format($invoice['reward_points_redeemed'], 2) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <strong class="fs-5">Total Credit Amount</strong>
                                            <strong class="fs-5 text-primary" id="grand_total_display">₹<?= number_format($init_final_total, 2) ?></strong>
                                            <input type="hidden" name="total_amount" id="total_amount" value="<?= $init_final_total ?>">
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 mb-4 text-end">
                            <button type="button" onclick="window.history.back()" class="btn btn-secondary">Back to Invoices</button>
                            <button type="submit" class="btn btn-primary" id="save_btn">Create Credit Note</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// Simple direct calculation approach
console.log('Credit note script loaded');

function validateQty(input) {
    const maxQty = parseFloat(input.getAttribute('max')) || 0;
    const qty = parseFloat(input.value) || 0;
    
    if (qty > maxQty) {
        input.value = maxQty;
        alert('Return Qty cannot be greater than Invoice Qty (' + maxQty + ')');
        return false;
    }
    if (qty < 0) {
        input.value = 0;
        return false;
    }
    return true;
}

function calculateRow(input) {
    console.log('calculateRow called');
    const row = input.closest('tr');
    if (!row) {
        console.error('Row not found');
        return;
    }

    // Get values
    const qtyInput = row.querySelector('.qty-input');
    const rateInput = row.querySelector('.rate-input');
    const gstInput = row.querySelector('.gst-input');
    const discountInput = row.querySelector('.discount-input');
    const discountTypeSelect = row.querySelector('.discount-type-select');
    const amountDisplay = row.querySelector('.amount-display');
    const rowAmount = row.querySelector('.row-amount');
    const rowGst = row.querySelector('.row-gst');
    const rowTotal = row.querySelector('.row-total');

    if (!qtyInput || !rateInput || !gstInput || !amountDisplay) {
        console.error('Required elements not found');
        return;
    }

    const qty = parseFloat(qtyInput.value) || 0;
    const rate = parseFloat(rateInput.value) || 0;
    const gstRate = parseFloat(gstInput.value) || 0;
    const maxQty = parseFloat(qtyInput.getAttribute('max')) || 0;

    console.log('Values:', { qty, rate, gstRate, maxQty });

    // Validate quantity
    if (qty > maxQty) {
        qtyInput.value = maxQty;
        return;
    }

    // Simple calculation: Return Qty × Rate + GST
    const baseAmount = qty * rate;
    const gstAmount = baseAmount * (gstRate / 100);
    const total = baseAmount + gstAmount;

    console.log('Calculation:', { baseAmount, gstAmount, total });

    // Update display
    amountDisplay.textContent = total.toFixed(2);
    
    // Update hidden fields
    if (rowAmount) rowAmount.value = baseAmount.toFixed(2);
    if (rowGst) rowGst.value = gstAmount.toFixed(2);
    if (rowTotal) rowTotal.value = total.toFixed(2);

    // Update totals
    calculateTotal();
}

function calculateTotal() {
    console.log('calculateTotal called');
    let subTotal = 0;
    let totalTax = 0;

    document.querySelectorAll('.item-row').forEach(row => {
        const rowAmount = row.querySelector('.row-amount');
        const rowGst = row.querySelector('.row-gst');
        
        if (rowAmount && rowGst) {
            subTotal += parseFloat(rowAmount.value) || 0;
            totalTax += parseFloat(rowGst.value) || 0;
        }
    });

    const grandTotal = subTotal + totalTax;
    console.log('Totals:', { subTotal, totalTax, grandTotal });

    // Update display
    const subTotalDisplay = document.getElementById('sub_total_display');
    const totalTaxDisplay = document.getElementById('total_tax_display');
    const grandTotalDisplay = document.getElementById('grand_total_display');
    
    if (subTotalDisplay) subTotalDisplay.textContent = '₹' + subTotal.toFixed(2);
    if (totalTaxDisplay) totalTaxDisplay.textContent = '₹' + totalTax.toFixed(2);
    if (grandTotalDisplay) grandTotalDisplay.textContent = '₹' + grandTotal.toFixed(2);
    
    // Update hidden fields
    const subTotalInput = document.getElementById('sub_total');
    const totalAmountInput = document.getElementById('total_amount');
    
    if (subTotalInput) subTotalInput.value = subTotal.toFixed(2);
    if (totalAmountInput) totalAmountInput.value = grandTotal.toFixed(2);
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing...');
    
    setTimeout(function() {
        const qtyInputs = document.querySelectorAll('.qty-input');
        console.log('Found qty inputs:', qtyInputs.length);
        
        qtyInputs.forEach(function(input) {
            console.log('Setting up input:', input);
            
            // Remove existing listeners
            input.removeEventListener('input', handleQtyChange);
            input.removeEventListener('change', handleQtyChange);
            
            // Add listeners
            function handleQtyChange() {
                console.log('Qty changed:', this.value);
                validateQty(this);
                calculateRow(this);
            }
            
            input.addEventListener('input', handleQtyChange);
            input.addEventListener('change', handleQtyChange);
            
            // Initial calculation
            calculateRow(input);
        });
        
        // Initial total calculation
        calculateTotal();
    }, 500);
});
</script>
