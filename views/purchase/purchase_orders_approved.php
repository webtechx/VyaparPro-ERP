<?php
$title = 'Purchase Orders Approved';
?>
 

<div class="container-fluid">
    <style>
        .select2-results__option--highlighted .vendor-text-primary { color: white !important; }
        .select2-results__option--highlighted .vendor-text-secondary { color: #e9ecef !important; }
        .select2-results__option--highlighted .status-badge { background-color: rgba(255,255,255,0.2) !important; color: white !important; }
        
        /* Read-only specific styles */
        .form-control:disabled, .form-control[readonly] {
            background-color: #f8f9fa;
            opacity: 1;
        }

        .select2-results__option--highlighted .vendor-text-primary { color: white !important; }
        .select2-results__option--highlighted .vendor-text-secondary { color: #e9ecef !important; }
        .select2-results__option--highlighted .status-badge { background-color: rgba(255,255,255,0.2) !important; color: white !important; }
        .select2-results__option--highlighted .text-dark { color: white !important; }
        .select2-results__option--highlighted .text-muted { color: #e9ecef !important; }
        .select2-results__option--highlighted .badge { background-color: rgba(255,255,255,0.2) !important; color: white !important; }
        .select2-results__option--highlighted .text-success { color: white !important; }
        .select2-results__option--highlighted .text-danger { color: white !important; }
        .select2-results__option--highlighted .vendor-avatar { background-color: white !important; color: #5d87ff !important; }
        
        /* Custom Vendor Dropdown Styles */
        .vendor-select2-dropdown .select2-results__options {
            max-height: 400px !important;
        }
        .vendor-select2-dropdown .select2-results__options li:last-child {
            position: sticky;
            bottom: 0;
            background-color: #fff;
            z-index: 51; /* Above other items */
            box-shadow: 0 -2px 5px rgba(0,0,0,0.05);
        }
    </style>
    <div class="row">
        <div class="col-xl-12">
            
            <!-- View Purchase Order Form (Read Only) -->
            <div class="card d-none" id="add_po_form">
                <div class="card-header">
                    <h5 class="card-title">View Purchase Order</h5>
                </div>

                <div class="card-body">
                    <form id="po_form" action="<?= $basePath ?>/controller/purchase/update_purchase_order.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="purchase_orders_id" id="edit_po_id">
                        
                        <!-- Header Details -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Vendor</label>
                               <select name="vendor_id" id="vendor_id" class="form-select" disabled></select>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-2">
                                <label class="form-label">PO Number</label>
                                <input type="text" name="po_number" class="form-control" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Reference #</label>
                                <input type="text" name="reference_no" class="form-control" readonly>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Order Date</label>
                                <input type="date" name="order_date" class="form-control" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Expected Delivery Date</label>
                                <input type="date" name="delivery_date" class="form-control" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Payment Terms</label>
                                <select name="payment_terms" class="form-select" disabled>
                                    <option value="Due on Receipt">Due on Receipt</option>
                                    <option value="Net 15">Net 15</option>
                                    <option value="Net 30">Net 30</option>
                                    <option value="Net 45">Net 45</option>
                                    <option value="Net 60">Net 60</option>
                                </select>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-sm align-middle" id="items_table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50%">Item Details</th>
                                        <th style="width: 10%; text-align:center;">HSN</th>
                                        <th style="width: 10%; text-align:center;">Unit</th>
                                        <th style="width: 5%; text-align:center;">Quantity</th>
                                        <th style="width: 10%; text-align:right;">Rate</th>
                                        <th style="width: 10%; text-align:right;">Discount</th>
                                        <th style="width: 10%; text-align:right;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="items_body">
                                    <!-- Rows added via JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer Totals & Notes -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="3" readonly></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Terms & Conditions</label>
                                    <textarea name="terms_conditions" class="form-control" rows="3" readonly></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                    <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Sub Total</span>
                                            <span class="fw-bold" id="sub_total_display">0.00</span>
                                            <input type="hidden" name="sub_total" id="sub_total">
                                        </div>
                                        
                                        <!-- Discount Row -->
                                        <div class="row mb-2 align-items-center">
                                            <div class="col-8">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text" style="width: 80px;">Discount</span>
                                                    <input type="number" name="discount_value" id="discount_value" class="form-control" value="0" readonly>
                                                    <select name="discount_type" id="discount_type" class="form-select text-center" disabled style="max-width: 60px; background-image: none; padding-right: 0.5rem;">
                                                        <option value="amount">₹</option>
                                                        <option value="percentage">%</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-4 text-end">
                                                <span id="discount_amt_display">0.00</span>
                                            </div>
                                        </div>

                                        <!-- GST Row -->
                                        <div class="row mb-2 align-items-center">
                                            <div class="col-8">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text" style="width: 80px;">GST</span>
                                                    <select name="gst_type" id="gst_type" class="form-select" disabled>
                                                        <option value="">None</option>
                                                        <option value="IGST">IGST</option>
                                                        <option value="CGST_SGST">CGST/SGST</option>
                                                    </select>
                                                    <input type="number" name="gst_rate" id="gst_rate" class="form-control" placeholder="%" value="0" readonly style="max-width: 70px;">
                                                </div>
                                            </div>
                                            <div class="col-4 text-end">
                                                <div id="gst_display_block">
                                                    <span class="d-none" id="igst_row">IGST: <span id="igst_display">0.00</span><br></span>
                                                    <span class="d-none" id="cgst_row">CGST: <span id="cgst_display">0.00</span><br></span>
                                                    <span class="d-none" id="sgst_row">SGST: <span id="sgst_display">0.00</span></span>
                                                </div>
                                                <input type="hidden" name="igst_amount" id="igst_amount" value="0">
                                                <input type="hidden" name="cgst_amount" id="cgst_amount" value="0">
                                                <input type="hidden" name="sgst_amount" id="sgst_amount" value="0">
                                            </div>
                                        </div>

                                        <!-- Adjustment Row -->
                                        <div class="row mb-2 align-items-center">
                                            <div class="col-8">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text" style="width: 80px;">Adjustment</span>
                                                    <input type="number" name="adjustment" id="adjustment" class="form-control" value="0" readonly>
                                                </div>
                                            </div>
                                            <div class="col-4 text-end">
                                                <span id="adjustment_display">0.00</span>
                                            </div>
                                        </div>
                                        
                                        <hr>
                                        <div class="d-flex justify-content-between fs-lg text-primary">
                                            <strong>Total</strong>
                                            <strong id="total_amount_display">0.00</strong>
                                            <input type="hidden" name="total_amount" id="total_amount">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <!-- Back/Close Button Only -->
                            <!-- Back/Close Button Only -->
                            <button type="button" id="back_btn" class="btn btn-secondary me-2">Back</button>
                            <button type="submit" name="update_po" id="update_btn" class="btn btn-success d-none">Update Purchase Order</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- List View -->
            <div class="card" id="po_list_card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Approved Purchase Orders</h5>
                    <div>
                         <!-- Removed manual search for DataTables -->
                    </div>
                </div>
                <div class="card-body">
                    <table data-tables="basic" class="table table-hover table-striped dt-responsive align-middle mb-0" style="width: 100%;">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>PO Number</th>
                                <th>Vendor</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Expected Delivery</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT po.*, v.display_name, v.avatar, v.organization_id, o.organizations_code 
                                    FROM purchase_orders po 
                                    LEFT JOIN vendors_listing v ON po.vendor_id = v.vendor_id 
                                    LEFT JOIN organizations o ON v.organization_id = o.organization_id
                                    WHERE po.status = 'confirmed' OR po.status = 'partially_received' OR po.status = 'received' 
                                    ORDER BY po.purchase_orders_id DESC";

                            $result = $conn->query($sql);
                            if($result && $result->num_rows > 0){
                                while($row = $result->fetch_assoc()){
                                    $statusClr = 'success';
                                    ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($row['order_date'])) ?></td>
                                        <td><a href="#" class="fw-bold text-dark"><?= htmlspecialchars($row['po_number']) ?></a></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php 
                                                $vendorAvatar = $row['avatar'] ?? '';
                                                $vendorName = $row['display_name'] ?? 'Vendor';
                                                
                                                if (!empty($vendorAvatar)) {
                                                     if(strpos($vendorAvatar, 'uploads/') !== false) {
                                                         $avatarSrc = $basePath . '/' . $vendorAvatar;
                                                     } else {
                                                         $orgCode = $row['organizations_code'] ?? $_SESSION['organization_code'] ?? '';
                                                         $avatarSrc = $basePath . '/uploads/' . $orgCode . '/vendors_avatars/' . $vendorAvatar;
                                                     }
                                                     echo '<img src="' . $avatarSrc . '" class="rounded-circle me-2" width="32" height="32" style="object-fit:cover;">';
                                                } else {
                                                    $initial = strtoupper(substr($vendorName, 0, 1));
                                                    echo '<div class="rounded-circle bg-light text-primary d-flex align-items-center justify-content-center me-2 fw-bold" style="width:32px; height:32px;">' . $initial . '</div>';
                                                }
                                                ?>
                                                <div><?= htmlspecialchars(ucwords(strtolower($vendorName))) ?></div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-<?= $statusClr ?> text-uppercase"><?= htmlspecialchars(ucwords(strtolower(str_replace('_', ' ', $row['status'])))) ?></span></td>
                                        <td><?= number_format($row['total_amount'], 2) ?></td>
                                        <td><?= $row['delivery_date'] ? date('d M Y', strtotime($row['delivery_date'])) : '-' ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">Action</button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" 
                                                           href="<?= $basePath ?>/view_purchase_order?id=<?= $row['purchase_orders_id'] ?>">
                                                            <i class="ti ti-eye me-2"></i>View
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="javascript:void(0)" onclick="editPO(<?= $row['purchase_orders_id'] ?>)">
                                                            <i class="ti ti-edit me-2"></i>Edit
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="7" class="text-center py-4 text-muted"><i class="ti ti-folder-off fs-xxl mb-2 d-block"></i> No data found</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- DATA STORE FOR ITEMS (to use in JS) -->
<?php
// Pre-fetch items to populate JS options
$itemOptions = "";
$iSql = "SELECT i.item_id, i.item_name, i.selling_price, i.stock_keeping_unit, i.current_stock, u.unit_name, h.hsn_code 
         FROM items_listing i 
         LEFT JOIN units_listing u ON i.unit_id = u.unit_id
         LEFT JOIN hsn_listing h ON i.hsn_id = h.hsn_id"; 
$iRes = $conn->query($iSql);
$itemsData = [];
if($iRes){
    while($item = $iRes->fetch_assoc()){
        $itemsData[] = $item;
    }
}
?>
<?php ob_start(); ?>
<script>
/* ================================
   GLOBAL DATA
================================ */
const itemsList = <?= json_encode($itemsData) ?>;
const basePath = '<?= $basePath ?>';

/* ================================
   VIEW TOGGLE
================================ */
function toggleView(showForm){
    if(showForm){
        document.getElementById('add_po_form').classList.remove('d-none');
        document.getElementById('po_list_card').classList.add('d-none');
    } else {
        document.getElementById('add_po_form').classList.add('d-none');
        document.getElementById('po_list_card').classList.remove('d-none');
    }
}

/* ================================
   DOCUMENT READY
================================ */
$(document).ready(function () {
    $('.item-select').select2({
        width: '100%',
        dropdownParent: $('#add_po_form')
    });
});

/* ================================
   VENDOR SELECT2
================================ */
function initVendorSelect2() {
    const $vendor = $('#vendor_id');

    // If already initialized, just ensure it's clean and return
    if ($vendor.hasClass('select2-hidden-accessible')) {
        $vendor.empty(); 
        return;
    }

    $vendor.select2({
        placeholder: 'Search vendor...',
        width: '100%',
        dropdownCssClass: 'vendor-select2-dropdown',
        allowClear: true,
        // dropdownParent: $('#add_po_form'),
        minimumInputLength: 0,
        disabled: true, // Default to disabled
        ajax: {
            url: '<?= $basePath ?>/controller/masters/vendors/search_vendors.php',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term || '' };
            },
            processResults: function (data) {
                if (data.error) {
                    console.error('Vendor Search Error:', data.error);
                    return { results: [] };
                }
                if (!Array.isArray(data)) data = [];

                let results = data.map(v => ({
                    id: String(v.id),
                    text: v.display_name || ('Vendor ' + v.id),
                    display_name: v.display_name || '',
                    company_name: v.company_name || '',
                    email: v.email || '',
                    avatar: v.avatar || ''
                }));

                results.push({
                    id: 'new_vendor',
                    text: '+ New Vendor',
                    isNew: true
                });

                return { results };
            }
        },
        templateResult: function (vendor) {
            if (vendor.loading) return vendor.text;
            if (vendor.isNew) {
                return $('<div class="text-primary fw-bold p-2 border-top"><i class="ti ti-plus me-1"></i> New Vendor</div>');
            }
            
            // Rich HTML Render
            let text = vendor.display_name || vendor.text || 'Vendor';
            let letter = text.length > 0 ? text.charAt(0).toUpperCase() : '?';

            let avatarHtml = '';
            if(vendor.avatar && vendor.avatar.trim() !== ''){
                avatarHtml = `<img src="${basePath}/${vendor.avatar}" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">`;
            } else {
                 avatarHtml = `<div class="vendor-avatar rounded-circle bg-light text-dark d-flex align-items-center justify-content-center fw-bold"
                         style="width:32px;height:32px;min-width:32px;">
                        ${letter}
                    </div>`;
            }

            return $(`
                <div class="d-flex align-items-start gap-2 py-1">
                    ${avatarHtml}
                    <div class="flex-grow-1">
                        <div class="vendor-text-primary fw-semibold lh-sm">${text}</div>
                        <div class="vendor-text-primary small text-muted d-flex align-items-center gap-3">
                            ${vendor.email ? `<span><i class="ti ti-mail me-1"></i>${vendor.email}</span>` : ''}
                            ${vendor.company_name ? `<span><i class="ti ti-building me-1"></i>${vendor.company_name}</span>` : ''}
                        </div>
                    </div>
                </div>`);
        },
        templateSelection: function (vendor) {
            if (!vendor.id) return vendor.text;
            if (vendor.id === 'new_vendor') {
                $('#vendor_id').select2('close');
                // Logic to open modal would go here if we had the modal in this file. 
                // For now, just reset selection to avoid getting stuck or standard alert.
                alert('Add Vendor feature is limited in Approved View.'); 
                $('#vendor_id').val(null).trigger('change');
                return 'Adding Vendor...';
            }

            let name = vendor.display_name || vendor.text;
            if(!name) return '';

            // Try to get data from object OR element attributes
            let avatar = vendor.avatar;
            if(!avatar && vendor.element){
                avatar = $(vendor.element).data('avatar') || $(vendor.element).attr('data-avatar');
            }

            let avatarHtml = '';
            if(avatar && avatar.trim() !== ''){
                avatarHtml = `<img src="${basePath}/${avatar}" class="rounded-circle me-2" style="width:20px;height:20px;object-fit:cover;vertical-align:middle;">`;
            } else {
                 let letter = name.charAt(0).toUpperCase();
                 avatarHtml = `<span class="badge rounded-circle bg-primary text-white me-2 d-inline-flex align-items-center justify-content-center" style="width:20px;height:20px;font-size:10px;vertical-align:middle;">${letter}</span>`;
            }
            
            return $(`<span>${avatarHtml}${name}</span>`);
        },
        escapeMarkup: m => m
    });
}

/* ================================
   BUTTON EVENTS
================================ */
document.addEventListener('DOMContentLoaded', function() {
    const backBtn = document.getElementById('back_btn');
    if(backBtn) {
        backBtn.addEventListener('click', () => {
             // Confirm if editing? verify dirty? Nah, just back.
             toggleView(false);
             resetForm();
        });
    }
});

/* ================================
   ITEMS LOGIC
================================ */
function addItemRow(data = null){
    const tbody = document.getElementById('items_body');
    const index = tbody.children.length;
    const row = document.createElement('tr');

    let options = '<option value="">Select Item</option>';
    itemsList.forEach(item => {
        const selected = (data && data.item_id == item.item_id) ? 'selected' : '';
        // Use last_purchase_price if available, else selling_price
        let rate = parseFloat(item.last_purchase_price) || parseFloat(item.selling_price) || 0;
        
        options += `<option value="${item.item_id}" 
                            data-rate="${rate}"
                            data-sku="${item.stock_keeping_unit || ''}" 
                            data-stock="${item.current_stock || 0}"
                            data-unit="${item.unit_name || ''}" 
                            data-hsn="${item.hsn_code || ''}"
                            ${selected}>
                            ${item.item_name}
                    </option>`;
    });

    row.innerHTML = `
        <td>
            <select name="items[${index}][item_id]" class="form-select form-select-sm item-select" required>
                ${options}
            </select>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm hsn-display text-center" readonly value="${data ? (itemsList.find(i=>i.item_id == data.item_id)?.hsn_code || '') : ''}">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm unit-display text-center" readonly value="${data ? (itemsList.find(i=>i.item_id == data.item_id)?.unit_name || '') : ''}">
        </td>
        <td>
            <input type="number" name="items[${index}][quantity]" class="form-control form-control-sm qty-input text-center" value="${data ? data.quantity : 1}" min="1" oninput="calculateRow(this)">
        </td>
        <td>
            <input type="number" name="items[${index}][rate]" class="form-control form-control-sm rate-input text-end" value="${data ? data.rate : 0}" step="0.01" readonly oninput="calculateRow(this)">
        </td>
        <td>
            <div class="input-group input-group-sm">
                 <input type="number" name="items[${index}][discount]" class="form-control discount-input text-end" value="${data ? data.discount : 0}" step="0.01" oninput="calculateRow(this)">
                 <select name="items[${index}][discount_type]" class="form-select text-center p-0 discount-type-select" onchange="calculateRow(this)" style="max-width: 50px; background-image: none;">
                    <option value="amount" ${data && data.discount_type === 'amount' ? 'selected' : ''}>₹</option>
                    <option value="percentage" ${data && data.discount_type === 'percentage' ? 'selected' : ''}>%</option>
                 </select>
            </div>
        </td>
        <td>
            <input type="text" readonly class="form-control-plaintext form-control-sm amount-display text-end" value="${data ? data.amount : '0.00'}">
            <input type="hidden" name="items[${index}][amount]" class="amount-input" value="${data ? data.amount : 0}">
        </td>
    `;

    tbody.appendChild(row);

    // Initialize Select2 with Rich Template
    $(row.querySelector('.item-select')).select2({
        width: '100%',
        dropdownParent: $('#add_po_form'),
        templateResult: function(item) {
             if (item.loading) return item.text;
            
            const element = $(item.element);
            const sku = element.data('sku');
            const stock = parseFloat(element.data('stock')) || 0;
            const rate = parseFloat(element.data('rate')) || 0;
            const unit_name = element.data('unit');

            let stockClass = stock > 0 ? 'text-success' : 'text-danger';

            return $(`
                <div class="d-flex justify-content-between align-items-center py-1" style="font-size: 0.9em;">
                    <div>
                        <div class="fw-bold">${item.text}</div>
                        <div class="small text-dark">SKU: ${sku || '--'} &nbsp;&nbsp; Cost: ₹${rate}</div>
                    </div>
                    <div class="text-end text-dark">
                        <span class="badge text-dark mb-1">Stock on Hand</span><br>
                        <small class="${stockClass} fw-bold">${stock} ${unit_name || ''}</small>
                    </div>
                </div>
            `);
        },
        templateSelection: function(item) {
            return item.text; // Just show name when selected
        }
    }).on('select2:select', function () {
        onItemSelect(this);
    });
}

function onItemSelect(select){
    const option = select.options[select.selectedIndex];
    const rate = option.getAttribute('data-rate');
    const unit = option.getAttribute('data-unit');
    const hsn = option.getAttribute('data-hsn');
    const row = select.closest('tr');

    if(rate){
        row.querySelector('.rate-input').value = rate;
        row.querySelector('.unit-display').value = unit || '-';
        row.querySelector('.hsn-display').value = hsn || '-';
        calculateRow(select);
    }
}

function calculateRow(input){
    const row = input.closest('tr');
    if(!row) return;

    const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
    const rate = parseFloat(row.querySelector('.rate-input').value) || 0;
    const discountInput = parseFloat(row.querySelector('.discount-input').value) || 0;
    const discountType = row.querySelector('.discount-type-select').value;
    
    let amount = qty * rate;
    let discount = 0;

    if(discountType === 'percentage'){
        discount = amount * (discountInput / 100);
    } else {
        discount = discountInput;
    }
    
    amount = amount - discount;
    if(amount < 0) amount = 0;

    row.querySelector('.amount-display').value = amount.toFixed(2);
    row.querySelector('.amount-input').value = amount.toFixed(2);
    calculateTotal();
}

function calculateTotal(){
    let subTotal = 0;
    document.querySelectorAll('.amount-input').forEach(inp => {
        subTotal += parseFloat(inp.value) || 0;
    });

    document.getElementById('sub_total').value = subTotal;
    document.getElementById('sub_total_display').innerText = subTotal.toFixed(2);

    const discountType = document.getElementById('discount_type').value;
    const discountVal = parseFloat(document.getElementById('discount_value').value) || 0;
    let discountAmt = discountType === 'percentage' ? subTotal * (discountVal / 100) : discountVal;

    document.getElementById('discount_amt_display').innerText = discountAmt.toFixed(2);
    
    let taxableAmount = subTotal - discountAmt;
    if(taxableAmount < 0) taxableAmount = 0;
    
    // GST
    const gstType = document.getElementById('gst_type').value;
    const gstRate = parseFloat(document.getElementById('gst_rate').value) || 0;
    
    document.getElementById('igst_row').classList.add('d-none');
    document.getElementById('cgst_row').classList.add('d-none');
    document.getElementById('sgst_row').classList.add('d-none');

    let igst=0, cgst=0, sgst=0;
    if(gstType === 'IGST'){
         igst = taxableAmount * (gstRate / 100);
         document.getElementById('igst_display').innerText = igst.toFixed(2);
         document.getElementById('igst_row').classList.remove('d-none');
    } else if (gstType === 'CGST_SGST'){
         let taxAmt = taxableAmount * (gstRate / 100);
         cgst = taxAmt / 2;
         sgst = taxAmt / 2;
         document.getElementById('cgst_display').innerText = cgst.toFixed(2);
         document.getElementById('sgst_display').innerText = sgst.toFixed(2);
         document.getElementById('cgst_row').classList.remove('d-none');
         document.getElementById('sgst_row').classList.remove('d-none');
    }
    
    document.getElementById('igst_amount').value = igst;
    document.getElementById('cgst_amount').value = cgst;
    document.getElementById('sgst_amount').value = sgst;

    const adjustment = parseFloat(document.getElementById('adjustment').value) || 0;
    const total = taxableAmount + igst + cgst + sgst + adjustment;
    
    document.getElementById('total_amount_display').innerText = total.toFixed(2);
    document.getElementById('total_amount').value = total;
    document.getElementById('adjustment_display').innerText = adjustment.toFixed(2);
}

/* ================================
   VIEW / EDIT LOGIC
================================ */
function editPO(id) {
    loadPO(id, 'edit');
}

// Keep 'viewPO' for existing calls, simpler alias
function viewPO(id) {
    loadPO(id, 'view');
}

function loadPO(id, mode) {
    resetForm();
    toggleView(true);
    
    // Initialize Vendor Select2 immediately after reset, before fetching data
    initVendorSelect2();
    
    let title = (mode === 'edit') ? 'Edit Approved Purchase Order #' + id : 'View Approved Purchase Order #' + id;
    document.querySelector('.card-title').innerText = title;
    
    // Set Edit Flag for ID
    document.getElementById('edit_po_id').value = id;

    // Fetch Data
    $.ajax({
        url: '<?= $basePath ?>/controller/purchase/get_purchase_order_details.php',
        data: { id: id },
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.error){
                alert(response.error);
                toggleView(false);
                return;
            }

            const po = response.po;
            const items = response.items;

            // Populate Header
            $('input[name="po_number"]').val(po.po_number);
            $('input[name="reference_no"]').val(po.reference_no);
            $('input[name="order_date"]').val(po.order_date);
            $('input[name="delivery_date"]').val(po.delivery_date);
            $('select[name="payment_terms"]').val(po.payment_terms);
            $('select[name="discount_type"]').val(po.discount_type);
            $('input[name="discount_value"]').val(po.discount_value);
            $('input[name="adjustment"]').val(po.adjustment);
            $('textarea[name="notes"]').val(po.notes);
            $('textarea[name="terms_conditions"]').val(po.terms_conditions);

            // GST Population
            $('select[name="gst_type"]').val(po.gst_type || '');
            $('input[name="gst_rate"]').val(po.gst_rate || 0);
            
            // Populate Vendor Select2
            const $vendor = $('#vendor_id');
            $vendor.empty(); 
            let vText = po.vendor_name;

            // Prepare rich option
            let newOption = new Option(vText, po.vendor_id, true, true);
            
            // Attach data attributes for templateSelection to read
            if(po.avatar) $(newOption).attr('data-avatar', po.avatar);
            if(po.vendor_email) $(newOption).attr('data-email', po.vendor_email);
            if(po.vendor_company) $(newOption).attr('data-company', po.vendor_company);
            
            $vendor.append(newOption).trigger('change');

            // Populate Items
            const tbody = document.getElementById('items_body');
            tbody.innerHTML = '';
            
            if(items.length > 0){
                items.forEach(item => {
                    addItemRow(item);
                });
            }
            
            calculateTotal(); // Refresh totals
            
            // TOGGLE STATE
            toggleEditState(mode === 'edit');
        },
        error: function(err){
            console.error(err);
            alert('Failed to fetch PO details');
        }
    });
}

function toggleEditState(isEdit) {
    const form = document.getElementById('po_form');
    // Toggle Inputs
    const inputs = form.querySelectorAll('input:not([type="hidden"]), select, textarea');
    inputs.forEach(el => {
        // Special Case: PO Number is always Readonly
        if(el.name === 'po_number') return;
        
        // Readonly/Disabled
        if(isEdit) {
            el.removeAttribute('readonly');
            el.removeAttribute('disabled');
        } else {
            el.setAttribute('readonly', true);
            // Selects use disabled, inputs use readonly
            if(el.tagName === 'SELECT') el.setAttribute('disabled', true);
        }
    });
    
    // Vendor is special Select2
    $('#vendor_id').prop('disabled', !isEdit);
    
    // Toggle Buttons
    if(isEdit) {
        document.getElementById('update_btn').classList.remove('d-none');
    } else {
        document.getElementById('update_btn').classList.add('d-none');
    }
}

function resetForm(){
    document.getElementById('po_form').reset();
    document.getElementById('items_body').innerHTML = '';
    $('#vendor_id').empty().trigger('change');
}
</script>
<?php $extra_scripts = ob_get_clean(); ?>

<?php
// Since we are in an includes/routing file structure, we need to ensure this file is included correctly. 
// However, the typical pattern here seems to be simple includes.
?>
