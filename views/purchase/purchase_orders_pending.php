<?php
$title = 'Purchase Orders';
?>
 

<div class="container-fluid">
    <style>
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
        
        /* Fix Select2 Z-Index for Modal Overlap - TARGET DROPDOWN ONLY */
        .select2-container--open .select2-dropdown {
            z-index: 9999999 !important;
        }
        .select2-dropdown {
            z-index: 9999999 !important;
        }
    </style>
    <div class="row">
        <div class="col-xl-12">
            
            <!-- Add/Edit Purchase Order Form -->
            
            <?php
            // Fetch Next PO Number
            $next_po_number = 'PO-0001';
            $po_sql = "SELECT po_number FROM purchase_orders ORDER BY purchase_orders_id DESC LIMIT 1";
            $po_res = $conn->query($po_sql);
            if ($po_res && $po_res->num_rows > 0) {
                $last_po = $po_res->fetch_assoc()['po_number'];
                // Extract number, increment, format
                if (preg_match('/PO-(\d+)/', $last_po, $matches)) {
                    $next_num = intval($matches[1]) + 1;
                    $next_po_number = 'PO-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
                }
            }
            ?>

            <div class="card d-none" id="add_po_form">
                <div class="card-header">
                    <h5 class="card-title"> Purchase Order</h5>
                </div>

                <div class="card-body">
                    <form id="po_form" action="<?= $basePath ?>/controller/purchase/add_purchase_order.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="purchase_orders_id" id="edit_po_id">
                        
                        <!-- Header Details -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Vendor <span class="text-danger">*</span></label>
                               <select name="vendor_id" id="vendor_id" class="form-select" required></select>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-2">
                                <label class="form-label">PO Number <span class="text-danger">*</span></label>
                                <input type="text" name="po_number" class="form-control" required value="<?= $next_po_number ?>" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Reference #</label>
                                <input type="text" name="reference_no" class="form-control">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Order Date <span class="text-danger">*</span></label>
                                <input type="date" name="order_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Expected Delivery Date</label>
                                <input type="date" name="delivery_date" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Payment Terms</label>
                                <select name="payment_terms" class="form-select">
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
                                        <th style="width: 3%"></th>
                                    </tr>
                                </thead>
                                <tbody id="items_body">
                                    <!-- Rows added via JS -->
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-sm btn-soft-primary" onclick="addItemRow()">+ Add Item</button>
                        </div>

                        <!-- Footer Totals & Notes -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Terms & Conditions</label>
                                    <textarea name="terms_conditions" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Attach Files</label>
                                    <input type="file" name="attachments[]" class="form-control" multiple>
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
                                                    <input type="number" name="discount_value" id="discount_value" class="form-control" value="0" oninput="calculateTotal()">
                                                    <select name="discount_type" id="discount_type" class="form-select text-center" onchange="calculateTotal()" style="max-width: 60px; background-image: none; padding-right: 0.5rem;">
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
                                                    <select name="gst_type" id="gst_type" class="form-select" onchange="calculateTotal()">
                                                        <option value="">None</option>
                                                        <option value="IGST">IGST</option>
                                                        <option value="CGST_SGST">CGST/SGST</option>
                                                    </select>
                                                    <input type="number" name="gst_rate" id="gst_rate" class="form-control" placeholder="%" value="0" oninput="calculateTotal()" style="max-width: 70px;">
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
                                                    <input type="number" name="adjustment" id="adjustment" class="form-control" value="0" oninput="calculateTotal()">
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
                            <!-- Back/Close Button -->
                            <button type="button" id="back_btn" class="btn btn-secondary me-2">Back</button>
                            
                            <!-- Review Actions for Sent POs -->
                            <button type="submit" name="status_action" id="confirm_btn" value="confirmed" class="btn btn-success d-none" onclick="return confirm('Confirm and Approve this Order? This will send an email to the vendor.')">Confirm as Approved</button>
                            <button type="submit" name="status_action" id="cancel_order_btn" value="cancelled" class="btn btn-danger d-none" onclick="return confirm('Are you sure you want to Cancel this Order?')">Cancelled</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- List View -->
            <div class="card" id="po_list_card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Purchase Orders Pending</h5>
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
                                    WHERE po.status= 'sent' ORDER BY po.purchase_orders_id DESC";

                            $result = $conn->query($sql);
                            if($result && $result->num_rows > 0){
                                while($row = $result->fetch_assoc()){
                                    $statusClr = match($row['status']) {
                                        'draft' => 'secondary',
                                        'sent' => 'primary',
                                        'confirmed' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'light'
                                    };
                                    ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($row['order_date'])) ?></td>
                                        <td><a href="#" class="fw-bold text-dark"><?= htmlspecialchars($row['po_number']) ?></a></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php 
                                                $vendorAvatar = $row['avatar'] ?? '';
                                                $vendorName = $row['display_name'] ?? 'Unknown Vendor';
                                                
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
                                        <td><span class="badge bg-<?= $statusClr ?> text-uppercase"><?= htmlspecialchars(ucwords(strtolower($row['status']))) ?></span></td>
                                        <td><?= number_format($row['total_amount'], 2) ?></td>
                                        <td><?= $row['delivery_date'] ? date('d M Y', strtotime($row['delivery_date'])) : '-' ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">Action</button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" 
                                                           href="javascript:void(0)" 
                                                           <?= (can_access('purchase_orders_pending', 'edit')) ? 'onclick="editPO(' . $row['purchase_orders_id'] . ')"' : 'title="Access Denied"' ?>>
                                                            <i class="ti ti-eye me-2"></i>View
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
$iSql = "SELECT i.item_id, i.item_name, i.selling_price, i.stock_keeping_unit, i.current_stock, u.unit_name, h.hsn_code,
         (SELECT rate FROM purchase_order_items poi WHERE poi.item_id = i.item_id ORDER BY poi.created_at DESC LIMIT 1) as last_purchase_rate
         FROM items_listing i 
         LEFT JOIN units_listing u ON i.unit_id = u.unit_id
         LEFT JOIN hsn_listing h ON i.hsn_id = h.hsn_id"; // Removed status check for now as column might be missing
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
   BUTTON EVENTS
================================ */
document.addEventListener('DOMContentLoaded', function() {
    
    const addPoBtn = document.getElementById('add_po_btn');
    if(addPoBtn) {
        addPoBtn.addEventListener('click', () => {
            resetForm();
            toggleView(true);
            setTimeout(() => {
                initVendorSelect2();
            }, 100);
        });
    }

    const backBtn = document.getElementById('back_btn');
    if(backBtn) {
        backBtn.addEventListener('click', () => {
            toggleView(false);
            resetForm();
        });
    }
});


/* ================================
   VENDOR SELECT2 (FIXED)
================================ */
function initVendorSelect2() {
    const $vendor = $('#vendor_id');

    if ($vendor.hasClass('select2-hidden-accessible')) {
        $vendor.select2('destroy');
        $vendor.empty();
    }

    $vendor.select2({
        placeholder: 'Search vendor...',
        width: '100%',
        dropdownCssClass: 'vendor-select2-dropdown',
        allowClear: true,
        // dropdownParent: $('#add_po_form'), // Keep commented out to prevent clipping
        minimumInputLength: 0,
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
                
                // Fix Z-Index issues
                const $container = $('#vendor_id').next('.select2-container');
                $container.removeClass('select2-container--open select2-container--focus');
                $container.css('z-index', 'auto');
                
                if (document.activeElement) document.activeElement.blur();

                setTimeout(() => {
                    $('#vendor_id').val(null).trigger('change');
                    const modalEl = document.getElementById('addVendorModal');
                    if(modalEl){
                        let modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (!modalInstance) {
                            modalInstance = new bootstrap.Modal(modalEl, {
                                backdrop: 'static',
                                keyboard: false
                            });
                        }
                        modalInstance.show();
                        
                        const preview = document.getElementById('vendor_avatar_preview');
                        if(preview) preview.src = basePath + '/public/assets/images/users/default_user_image.png';
                    }
                }, 150);

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

    // Handle AJAX Vendor Form Submission (Ensure it's attached only once)
    $(document).off('submit', '#ajax_vendor_form').on('submit', '#ajax_vendor_form', function(e){
        e.preventDefault();
        
        const form = $(this);
        const btn = form.find('#save_vendor_btn');
        btn.prop('disabled', true).text('Saving...');

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: new FormData(this),
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response){
                if(response.success){
                    var modalEl = document.getElementById('addVendorModal');
                    var modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) modalInstance.hide();
                    else $(modalEl).modal('hide');
                    
                    form[0].reset();
                    const preview = document.getElementById('vendor_avatar_preview');
                    if(preview) preview.src = basePath + '/public/assets/images/users/default_user_image.png';
                    
                    var newOption = new Option(response.display_name, response.vendor_id, true, true);
                    if(response.avatar) newOption.setAttribute('data-avatar', response.avatar);
                    
                    $('#vendor_id').append(newOption).trigger('change');
                } else {
                    alert('Error: ' + (response.error || 'Unknown error'));
                }
            },
            error: function(xhr){
                console.error("AJAX Error Details:", xhr);
                alert('An error occurred while adding the vendor.');
            },
            complete: function(){
                btn.prop('disabled', false).text('Save Vendor');
            }
        });
    });
}
function previewVendorAvatar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('vendor_avatar_preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

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
        // Use last_purchase_rate if available, otherwise fallback to selling_price
        const rate = item.last_purchase_rate ? item.last_purchase_rate : item.selling_price;
        options += `<option value="${item.item_id}" 
                            data-hsn="${item.hsn_code || ''}"
                            data-rate="${rate}" 
                            data-unit="${item.unit_name || ''}" 
                            data-sku="${item.stock_keeping_unit || ''}"
                            data-stock="${item.current_stock || 0}"
                            ${selected}>
                            ${item.item_name}
                    </option>`;
    });

    row.innerHTML = `
        <td>
            <select name="items[${index}][item_id]" class="form-select form-select-sm item-select" required onchange="onItemSelect(this)">
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
        <td>
            <button type="button" class="btn btn-sm btn-ghost-danger" onclick="removeRow(this)">
                <i class="ti ti-x"></i>
            </button>
        </td>
    `;

    tbody.appendChild(row);

    $(row.querySelector('.item-select')).select2({
        width: '100%',
        dropdownParent: $('#add_po_form'),
        templateResult: function(item) {
            if (!item.id) return item.text;
            
            const element = $(item.element);
            const sku = element.data('sku');
            const stock = element.data('stock');
            const unit_name = element.data('unit');
            const rate = parseFloat(element.data('rate') || 0).toFixed(2);
            
            const stockVal = parseFloat(stock);
            const stockClass = stockVal > 0 ? 'text-success' : 'text-danger';
            
            return $(`
                <div class="d-flex justify-content-between align-items-center py-1">
                    <div class="text-dark">
                        <div class="fw-bold">${item.text}</div>
                        <div class="small text-dark">SKU: ${sku || '--'} &nbsp;&nbsp; Purchase Rate: ₹${rate}</div>
                    </div>
                    <div class="text-end text-dark">
                        <span class="badge text-dark mb-1">Stock on Hand</span><br>
                        <small class="${stockClass} fw-bold">${stock} ${unit_name || ''}</small>
                    </div>
                </div>
            `);
        },
        templateSelection: function(item) {
            return item.text;
        }
    });


    $(row.querySelector('.item-select')).on('select2:select', function () {
        onItemSelect(this);
    });

    if(!data) calculateRow(row.querySelector('.qty-input'));
}

function removeRow(btn){
    const row = btn.closest('tr');
    $(row.querySelector('.item-select')).select2('destroy');
    row.remove();
    calculateTotal();
}

function onItemSelect(select){
    const option = select.options[select.selectedIndex];
    const rate = option.getAttribute('data-rate');
    const discount = option.getAttribute('data-discount');
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
    
    // Taxable Amount
    let taxableAmount = subTotal - discountAmt;
    if(taxableAmount < 0) taxableAmount = 0;

    // GST Calculation
    const gstType = document.getElementById('gst_type').value;
    const gstRate = parseFloat(document.getElementById('gst_rate').value) || 0;
    let igst = 0, cgst = 0, sgst = 0;

    document.getElementById('igst_row').classList.add('d-none');
    document.getElementById('cgst_row').classList.add('d-none');
    document.getElementById('sgst_row').classList.add('d-none');

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
    
    document.getElementById('igst_amount').value = igst.toFixed(2);
    document.getElementById('cgst_amount').value = cgst.toFixed(2);
    document.getElementById('sgst_amount').value = sgst.toFixed(2);

    const adjustment = parseFloat(document.getElementById('adjustment').value) || 0;
    const total = taxableAmount + igst + cgst + sgst + adjustment;

    document.getElementById('total_amount').value = total;
    document.getElementById('total_amount_display').innerText = total.toFixed(2);
    document.getElementById('adjustment_display').innerText = adjustment.toFixed(2);
}
$(document).on('hidden.bs.modal', '#addVendorModal', function () {
    setTimeout(() => {
        if ($('#vendor_id').hasClass('select2-hidden-accessible')) {
            $('#vendor_id').select2('open');
        }
    }, 200);
});
/* =====================================
   LOCK BACKGROUND SELECT2 ON MODAL OPEN
===================================== */
$(document).on('show.bs.modal', '#addVendorModal', function () {

    // Close & disable vendor select
    if ($('#vendor_id').hasClass('select2-hidden-accessible')) {
        $('#vendor_id').select2('close');
    }

    // Close & disable ALL item select2
    $('.item-select').each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) {
            $(this).select2('close');
            $(this).prop('disabled', true);
        }
    });
});

/* ================================
   EDIT PO Logic
================================ */
function editPO(id) {
    resetForm();
    toggleView(true);
    
    // Ensure Vendor Select2 is initialized
    setTimeout(() => {
        initVendorSelect2();
    }, 50);

    // Update Form Action
    const form = document.getElementById('po_form');
    form.action = '<?= $basePath ?>/controller/purchase/update_purchase_order.php';
    
    // Update UI/Buttons
    document.querySelector('.card-title').innerText = 'Edit Purchase Order #' + id;
    document.getElementById('edit_po_id').value = id;
    
    // Safety checks for removed buttons
    const saveBtn = document.getElementById('save_sent_btn');
    if(saveBtn) saveBtn.classList.add('d-none');
    
    const submitBtn = document.getElementById('submit_btn');
    if(submitBtn) submitBtn.classList.add('d-none');
    // document.getElementById('update_btn').classList.remove('d-none'); // Handle in success based on status

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

            $('textarea[name="notes"]').val(po.notes);
            $('textarea[name="terms_conditions"]').val(po.terms_conditions);
            
            // Clear previous attachments
            $('#existing_attachments').remove();
            
            console.log("Files:", response.files); // Debug

            if(response.files && response.files.length > 0){
                 const orgCode = '<?= $_SESSION['organization_code'] ?? '' ?>'; 
                 // If orgCode is empty in session (ajax context?), we might need it from response or fallback.
                 // Assuming session is active.
                 
                 let fileLinks = '';
                 response.files.forEach(file => {
                     // The file_name is just the name. Path is /uploads/ORG_CODE/purchase_orders/FILE_NAME
                     // If we don't have orgCode in JS, we can't build path accurately unless we returned full path or org code in response.
                     // But let's try with what we have.
                     
                     // Fallback check: if add_purchase_order saved just name, we need org code.
                     // Let's rely on PHP session variable printed here.
                     
                     const filePath = '<?= $basePath ?>/uploads/' + orgCode + '/purchase_orders/' + file.file_name;
                     fileLinks += `<div>
                                    <a href="${filePath}" target="_blank" class="text-primary text-decoration-none">
                                        <i class="ti ti-paperclip me-1"></i> ${file.file_name} 
                                        <small class="text-muted ms-2">(${file.file_size} bytes)</small>
                                    </a>
                                   </div>`;
                 });

                 const container = `<div id="existing_attachments" class="mt-2 text-start small border rounded p-2 bg-light">
                                        <div class="fw-bold mb-1">Attached Files:</div>
                                        ${fileLinks}
                                    </div>`;
                 
                 $('input[name="attachments[]"]').parent().append(container);
            }

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
            } else {
                addItemRow();
            }

            calculateTotal();

            // Set Buttons based on Status
            if(po.status === 'sent') {
                document.querySelector('.card-title').innerText = 'Review Purchase Order #' + po.po_number;
                const updateBtn = document.getElementById('update_btn');
                if(updateBtn) updateBtn.classList.remove('d-none');
                
                document.getElementById('confirm_btn').classList.remove('d-none');
                document.getElementById('cancel_order_btn').classList.remove('d-none');
            } else {
                 const updateBtn = document.getElementById('update_btn');
                 if(updateBtn) updateBtn.classList.remove('d-none');
            }

        },
        error: function(err){
            console.error(err);
            alert('Failed to fetch PO details');
        }
    });
}

function resetForm(){
    const form = document.getElementById('po_form');
    if(form) {
        form.reset();
        form.action = '<?= $basePath ?>/controller/purchase/add_purchase_order.php';
    }
    
    document.getElementById('items_body').innerHTML = '';
    addItemRow();
    
    if(document.getElementById('edit_po_id')) document.getElementById('edit_po_id').value = '';
    const title = document.querySelector('.card-title');
    if(title) title.innerText = 'New Purchase Order';
    
    // Reset Buttons/UI
    const saveBtn = document.getElementById('save_sent_btn');
    if(saveBtn) saveBtn.classList.remove('d-none');
    
    const submitBtn = document.getElementById('submit_btn');
    if(submitBtn) submitBtn.classList.remove('d-none');
    
    const updateBtn = document.getElementById('update_btn');
    if(updateBtn) updateBtn.classList.add('d-none');

    const confirmBtn = document.getElementById('confirm_btn');
    if(confirmBtn) confirmBtn.classList.add('d-none');
    
    const cancelBtn = document.getElementById('cancel_order_btn');
    if(cancelBtn) cancelBtn.classList.add('d-none');
    
    $('#vendor_id').val(null).trigger('change');
    
    calculateTotal();
}
</script>

<!-- Add Vendor Modal -->
<div class="modal fade" id="addVendorModal" tabindex="-1" aria-hidden="true" style="z-index: 2055 !important;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white">Add New Vendor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="ajax_vendor_form" action="<?= $basePath ?>/controller/masters/vendors/add_vendor.php" method="post">
                    <input type="hidden" name="ajax" value="true">
                    
                        <!-- Primary Information -->
                        <div class="row  mb-4">
                            <div class="col-md-1">
                                <label class="form-label">Salutation</label>
                                <select name="salutation" class="form-select">
                                    <option value="Mr.">Mr.</option>
                                    <option value="Mrs.">Mrs.</option>
                                    <option value="Ms.">Ms.</option>
                                    <option value="Dr.">Dr.</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" class="form-control">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Display Name <span class="text-danger">*</span></label>
                                <input type="text" name="display_name" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Work Phone</label>
                                <input type="text" name="work_phone" class="form-control">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Mobile</label>
                                <input type="text" name="mobile" class="form-control">
                            </div>
                            <div class="col-lg-2 mt-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="form-check form-switch form-check-success fs-xxl mb-2">
                                    <input type="checkbox" name="status" value="Active" class="form-check-input mt-1" id="switchSuccess" checked>
                                    <label class="form-check-label fs-base" for="switchSuccess">Status</label>
                                </div>
                            </div>
                        </div>

                        <!-- Tabs for Details -->
                        <div class="row">
                            <div class="col-xl-12">
                                <ul class="nav nav-tabs nav-justified nav-bordered nav-bordered-primary mb-3" role="tablist">
                                    <li class="nav-item">
                                        <a href="#other-details" data-bs-toggle="tab" class="nav-link active">
                                            <i class="ti ti-file-description fs-lg me-1"></i> Other Details
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#addresses" data-bs-toggle="tab" class="nav-link">
                                            <i class="ti ti-map-pin fs-lg me-1"></i> Addresses
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#contact-persons" data-bs-toggle="tab" class="nav-link">
                                            <i class="ti ti-users fs-lg me-1"></i> Contact Persons
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#bank-details" data-bs-toggle="tab" class="nav-link">
                                            <i class="ti ti-building-bank fs-lg me-1"></i> Bank Details
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="#remarks" data-bs-toggle="tab" class="nav-link">
                                            <i class="ti ti-message fs-lg me-1"></i> Remarks
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <!-- Other Details Tab -->
                                    <div class="tab-pane show active" id="other-details">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Currency</label>
                                                <input type="text" name="currency" class="form-control" value="INR">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Payment Terms</label>
                                                <select name="payment_terms" class="form-select">
                                                    <option value="Due on Receipt">Due on Receipt</option>
                                                    <option value="Net 15">Net 15</option>
                                                    <option value="Net 30">Net 30</option>
                                                    <option value="Net 45">Net 45</option>
                                                    <option value="Net 60">Net 60</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label">PAN</label>
                                                <input type="text" name="pan" class="form-control">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Opening Balance</label>
                                                <input type="number" step="0.01" name="opening_balance" class="form-control">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Opening Balance Type</label>
                                                <select name="opening_balance_type" class="form-select">
                                                    <option value="Debit">Debit</option>
                                                    <option value="Credit">Credit</option>
                                                </select>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- Addresses Tab -->
                                    <div class="tab-pane" id="addresses">
                                        <div class="row g-3">
                                            <div class="col-md-6 border-end">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6>Billing Address</h6>
                                                    </div> 
                                                    <div class="col-md-6 mb-3">
                                                        &nbsp;
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <input type="text" name="billing_attention" class="form-control" placeholder="Attention">
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <input type="text" name="billing_phone" class="form-control" placeholder="Phone">
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <input type="text" name="billing_fax" class="form-control" placeholder="Fax">
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <input type="text" name="billing_city" class="form-control" placeholder="City" required>
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <input type="text" name="billing_state" class="form-control" placeholder="State" required>
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <input type="text" name="billing_pin_code" class="form-control" placeholder="Pin Code" required>
                                                    </div>

                                                    <div class="col-md-6 mb-2">
                                                        <input type="text" name="billing_country" class="form-control" placeholder="Country">
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <input type="text" name="billing_address_line1" class="form-control" placeholder="Address Line 1" required>
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <input type="text" name="billing_address_line2" class="form-control" placeholder="Address Line 2">
                                                    </div>
                                                </div>
                                            </div>
                                             
                                            <div class="col-md-6">
                                             

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6>Shipping Address</h6>
                                                    </div> 
                                                    <div class="col-md-6 text-end">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary mb-2" onclick="copyBillingToShipping()">Same as Billing</button>
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <input type="text" name="shipping_attention" class="form-control" placeholder="Attention">
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <input type="text" name="shipping_phone" class="form-control" placeholder="Phone">
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <input type="text" name="shipping_fax" class="form-control" placeholder="Fax">
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <input type="text" name="shipping_city" class="form-control" placeholder="City" required>
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <input type="text" name="shipping_state" class="form-control" placeholder="State" required>
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <input type="text" name="shipping_pin_code" class="form-control" placeholder="Pin Code" required>
                                                    </div>

                                                    <div class="col-md-6 mb-2">
                                                        <input type="text" name="shipping_country" class="form-control" placeholder="Country">
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <input type="text" name="shipping_address_line1" class="form-control" placeholder="Address Line 1" required>
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <input type="text" name="shipping_address_line2" class="form-control" placeholder="Address Line 2">
                                                    </div>
                                                </div>
 
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contact Persons Tab -->
                                    <div class="tab-pane" id="contact-persons">
                                        <div id="contacts_wrapper">
                                            <div class="row g-2 mb-2 contact-row">
                                                <div class="col-md-2">
                                                     <select name="contact_salutation[]" class="form-select">
                                                        <option value="Mr.">Mr.</option>
                                                        <option value="Mrs.">Mrs.</option>
                                                        <option value="Ms.">Ms.</option>
                                                        <option value="Dr.">Dr.</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2"><input type="text" name="contact_first_name[]" class="form-control" placeholder="First Name"></div>
                                                <div class="col-md-2"><input type="text" name="contact_last_name[]" class="form-control" placeholder="Last Name"></div>
                                                <div class="col-md-2"><input type="email" name="contact_email[]" class="form-control" placeholder="Email"></div>
                                                <div class="col-md-2"><input type="text" name="contact_mobile[]" class="form-control" placeholder="Mobile"></div>
                                                <div class="col-md-2"><input type="text" name="contact_role[]" class="form-control" placeholder="Role"></div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-soft-primary" onclick="addContactRow()">+ Add Contact Person</button>
                                    </div>

                                    <!-- Bank Details Tab -->
                                    <div class="tab-pane" id="bank-details">
                                        <div class="row">
                                            <div class="col-lg-3">
                                                <label class="form-label">Account Holder Name</label>
                                                <input type="text" name="bank_account_holder_name" class="form-control">
                                            </div>
                                            <div class="col-lg-3">
                                                <label class="form-label">Bank Name</label>
                                                <input type="text" name="bank_name" class="form-control">
                                            </div>
                                            <div class="col-lg-3">
                                                <label class="form-label">Account Number</label>
                                                <input type="text" name="bank_account_number" class="form-control">
                                            </div>
                                            <div class="col-lg-3">
                                                <label class="form-label">IFSC Code</label>
                                                <input type="text" name="bank_ifsc_code" class="form-control">
                                            </div>
                                        </div>
                                    </div>



                                    <!-- Remarks Tab -->
                                    <div class="tab-pane" id="remarks">
                                        <textarea name="remarks" class="form-control" rows="5" placeholder="Internal remarks..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="button" id="cancel_btn" class="btn btn-secondary me-2">Cancel</button>
                            <button type="submit" name="add_vendor" id="submit_btn" class="btn btn-primary">Save Vendor</button>
                            <button type="submit" name="update_vendor" id="update_btn" class="btn btn-success d-none">Update Vendor</button>
                        </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $extra_scripts = ob_get_clean(); ?>

