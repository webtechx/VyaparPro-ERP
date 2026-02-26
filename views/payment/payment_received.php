<?php
$title = 'Payment Received';
require_once __DIR__ . '/../../config/auth_guard.php';


// Generate Next Payment Number
$org_short_code = isset($_SESSION['organization_short_code']) ? strtoupper($_SESSION['organization_short_code']) : '';
if(empty($org_short_code)) {
    $orgRes = $conn->query("SELECT organization_short_code FROM organizations WHERE organization_id = {$_SESSION['organization_id']}");
    if($orgRes && $orgRes->num_rows > 0){
        $org_short_code = strtoupper($orgRes->fetch_assoc()['organization_short_code']);
        $_SESSION['organization_short_code'] = $org_short_code; 
    } else {
            $org_short_code = 'ORG'; 
    }
}

$next_pay_number = 'PAY-PR-' . $org_short_code . '-0001';
try {
    $pay_sql = "SELECT payment_number FROM payment_received WHERE organization_id = {$_SESSION['organization_id']} ORDER BY payment_id DESC LIMIT 1";
    $pay_res = $conn->query($pay_sql);
    if ($pay_res && $pay_res->num_rows > 0) {
        $last_pay = $pay_res->fetch_assoc()['payment_number'];
        
        if (preg_match('/PAY-PR-' . preg_quote($org_short_code) . '-(\d+)/', $last_pay, $matches)) {
            $next_num = intval($matches[1]) + 1;
            $next_pay_number = 'PAY-PR-' . $org_short_code . '-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
        }
        else if (preg_match('/PAY-' . preg_quote($org_short_code) . '-(\d+)/', $last_pay, $matches)) {
            // Migration support: if old format exists, switch to new but keep number sequence? Or restart?
            // Usually safer to increment from old number if possible, or just restart if format is completely different.
            // Let's assume we want to continue sequence but add PR.
            $next_num = intval($matches[1]) + 1;
            $next_pay_number = 'PAY-PR-' . $org_short_code . '-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
        }
    }
} catch (Exception $e) {}
?>

<div class="container-fluid">
    <style>
        .customer-text-primary { color: #2a3547; }
        
        /* Standard Highlight Theme (Matches Tax Invoice) */
        .select2-results__option--highlighted .customer-text-primary { color: white !important; }
        .select2-results__option--highlighted .customer-text-secondary { color: #e9ecef !important; }
        .select2-results__option--highlighted .status-badge { background-color: rgba(255,255,255,0.2) !important; color: white !important; }
        .select2-results__option--highlighted .text-dark { color: white !important; }
        .select2-results__option--highlighted .text-muted { color: #e9ecef !important; }
        .select2-results__option--highlighted .badge { background-color: rgba(255,255,255,0.2) !important; color: white !important; border-color: white !important; }
        .select2-results__option--highlighted .text-success { color: white !important; }
        .select2-results__option--highlighted .text-danger { color: white !important; }
        .select2-results__option--highlighted .customer-avatar { background-color: white !important; color: #5d87ff !important; }
        
        /* Custom Customer Dropdown Styles */
        .customer-select2-dropdown .select2-results__options {
            max-height: 400px !important;
        }
        .customer-select2-dropdown .select2-results__options li:last-child {
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
        <div class="col-12">
            

            <!-- ADD PAYMENT FORM -->
            <div class="card d-none mb-4" id="add_payment_form">
                <div class="card-header bg-light-subtle">
                    <h5 class="card-title mb-0">Payment Received</h5>
                </div>
                <div class="card-body">
                    <form action="<?= $basePath ?>/controller/payment/save_payment_received.php" method="POST">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Customer <span class="text-danger">*</span></label>
                                <select name="customer_id" id="customer_id" class="form-select" required></select>
                                <div id="customer_balance_display" class="form-text fw-bold mt-1 d-none"></div>
                                <input type="hidden" id="cust_current_bal" value="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                             <div class="col-md-2">
                                <label class="form-label">Payment Number</label>
                                <input type="text" name="payment_number" class="form-control" value="<?= $next_pay_number ?>" readonly>
                            </div>
                             <div class="col-md-2">
                                <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                                <select name="payment_mode" class="form-select" required>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="UPI">UPI</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Debit Card">Debit Card</option>
                                    <option value="Advance Adjustment">Advance Adjustment</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Reference #</label>
                                <input type="text" name="reference_no" class="form-control" placeholder="Cheque No, Transaction ID etc.">
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-2">
                                <label class="form-label">Payment Type</label>
                                <div class="d-flex gap-3">
                                     <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_type" id="type_invoice" value="invoice" checked>
                                        <label class="form-check-label" for="type_invoice">Invoice</label>
                                     </div>
                                     <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_type" id="type_advance" value="advance">
                                        <label class="form-check-label" for="type_advance">Advance</label>
                                     </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Select Invoice</label>
                                <select name="invoice_id" id="invoice_id" class="form-select">
                                    <option value="">Select Invoice...</option>
                                </select>
                                <div id="invoice_details" class="form-text text-primary d-none">
                                    Balance: ₹<span id="inv_balance">0.00</span>
                                </div>
                            </div>

                             <div class="col-md-2">
                                <label class="form-label">Amount (₹) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" id="amount_received" class="form-control fw-bold" step="0.01" min="0" required>
                            </div>
                                                
                            <div class="col-md-4">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="1"></textarea>
                            </div>
                        </div>
 
                        <div class="mt-4 text-end">
                            <button type="button" class="btn btn-secondary me-2" id="cancel_payment_btn">Cancel</button>
                            <button type="submit" name="save_payment" class="btn btn-success"><i class="ti ti-check me-1"></i> Save Payment</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- PAYMENT LIST -->
            <div class="card" id="payment_list_card">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Payment Received</h5>
               
                     <button type="button" class="btn btn-primary" id="add_payment_btn"><i class="ti ti-plus me-1"></i> Record Payment</button>
                </div>

 

                <div class="card-body">
                    <table data-tables="basic" id="payment_received_table" class="table table-hover table-striped mb-0 w-100 dt-responsive" style="width: 100%;">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Payment #</th>
                                <th>Customer</th>
                                <th>Reference</th>
                                <th>Mode</th>
                                <th>Type</th>
                                <th class="text-end">Amount</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT p.*, c.customer_name, c.company_name, si.invoice_number 
                                    FROM payment_received p 
                                    LEFT JOIN customers_listing c ON p.customer_id = c.customer_id 
                                    LEFT JOIN sales_invoices si ON p.invoice_id = si.invoice_id
                                    WHERE p.organization_id = ? 
                                    ORDER BY p.payment_date DESC, p.payment_id DESC limit 50";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $_SESSION['organization_id']);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if($result->num_rows > 0):
                                while($row = $result->fetch_assoc()):
                                    $typeBadge = ($row['item_type'] == 'advance') ? 'bg-info-subtle text-info' : 'bg-success-subtle text-success';
                            ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($row['payment_date'])) ?></td>
                                    <td><span class="fw-bold text-dark"><?= $row['payment_number'] ?></span></td>
                                    <td>
                                        <div class="fw-semibold"><?= $row['customer_name'] ?></div>
                                        <small class="text-muted"><?= $row['company_name'] ?></small>
                                    </td>
                                    <td><?= $row['reference_no'] ?: '-' ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= $row['payment_mode'] ?></span></td>
                                    <td>
                                        <span class="badge <?= $typeBadge ?> text-uppercase"><?= $row['item_type'] ?></span>
                                        <?php if($row['invoice_number']): ?>
                                            <div class="small text-muted mt-1"><?= $row['invoice_number'] ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold">₹<?= number_format($row['amount'], 2) ?></td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">Action</button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="<?= $basePath ?>/views/payment/view_payment.php?id=<?= $row['payment_id'] ?>"><i class="ti ti-eye me-2"></i> View</a></li>
                                                <li><a class="dropdown-item" href="<?= $basePath ?>/controller/payment/print_payment.php?id=<?= $row['payment_id'] ?>" target="_blank"><i class="ti ti-printer me-2"></i> Print</a></li>
                                                <li><a class="dropdown-item" href="<?= $basePath ?>/controller/payment/download_payment_pdf.php?id=<?= $row['payment_id'] ?>"><i class="ti ti-download me-2"></i> Download PDF</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="<?= $basePath ?>/controller/payment/delete_payment_received.php?id=<?= $row['payment_id'] ?>" onclick="return confirm('Are you sure you want to delete this payment?');"><i class="ti ti-trash me-2"></i> Delete</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true" style="z-index: 99999 !important;">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="ajax_customer_form" action="<?= $basePath ?>/controller/billing/add_customer.php" method="post" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-1 text-center">
                            <div class="position-relative d-inline-block ">
                                <img id="modal_avatar_preview" src="<?= $basePath ?>/public/assets/images/users/default_user_image.png" 
                                     class="rounded-circle object-fit-cover shadow-sm border border-2 border-white bg-secondary-subtle" 
                                     style="width: 80px; height: 80px;"
                                     alt="Avatar Preview">
                                <label for="modal_avatar_input" class="position-absolute bottom-0 end-0 bg-primary text-white p-1 rounded-circle cursor-pointer shadow-sm" 
                                       style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; transform: translate(10%, 10%);"
                                       title="Upload Avatar">
                                    <i class="ti ti-camera"></i>
                                </label>
                                <input type="file" name="avatar" id="modal_avatar_input" class="d-none" accept="image/*" onchange="previewModalAvatar(this)">
                            </div>
                        </div>
                        
                        <div class="col-md-11">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Customer Type <span class="text-danger">*</span></label>
                                    <select name="customers_type_id" class="form-select" required>
                                        <option value="">Select Customer Type</option>
                                        <?php
                                        $ctSql = "SELECT `customers_type_id`, `customers_type_name` FROM `customers_type_listing` WHERE organization_id = " . $_SESSION['organization_id'];
                                        $ctRes = $conn->query($ctSql);
                                        if ($ctRes && $ctRes->num_rows > 0) {
                                            while ($ctRow = $ctRes->fetch_assoc()) {
                                                echo "<option value='" . $ctRow['customers_type_id'] . "'>" . $ctRow['customers_type_name'] . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_name" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Company Name</label>
                                    <input type="text" name="company_name" class="form-control">
                                </div>
                                    <div class="col-md-4">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="row mt-4">
                        <div class="col-xl-12">
                            <ul class="nav nav-tabs nav-justified nav-bordered nav-bordered-primary mb-3" role="tablist">
                                <li class="nav-item">
                                    <a href="#modal-basic-details" data-bs-toggle="tab" class="nav-link active">
                                        <i class="ti ti-user fs-lg me-1"></i> Basic Details
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#modal-address-details" data-bs-toggle="tab" class="nav-link">
                                        <i class="ti ti-map-pin fs-lg me-1"></i> Address Details
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <!-- Basic Details Tab -->
                                <div class="tab-pane show active" id="modal-basic-details">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Opening Balance</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" name="opening_balance" class="form-control" value="0.00" step="0.01">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">GST Number</label>
                                            <input type="text" name="gst_number" class="form-control">
                                        </div>
                                         <div class="col-md-2">
                                            <label class="form-label">Date of Birth</label>
                                            <input type="date" name="date_of_birth" class="form-control">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Anniversary Date</label>
                                            <input type="date" name="anniversary_date" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <!-- Address Details Tab -->
                                <div class="tab-pane" id="modal-address-details">
                                    <div class="row g-3">
                                            <div class="col-md-12">
                                            <label class="form-label">Address</label>
                                            <textarea name="address" class="form-control" rows="2"></textarea>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">City</label>
                                            <input type="text" name="city" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">State</label>
                                            <input type="text" name="state" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Pincode</label>
                                            <input type="text" name="pincode" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Country</label>
                                            <input type="text" name="country" class="form-control" value="India">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer mt-4">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="save_customer_btn">Save Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



<script>
    const basePath = '<?= $basePath ?>';

    // UI Toggles
    const formCard = document.getElementById('add_payment_form');
    const listCard = document.getElementById('payment_list_card');
    const addBtn = document.getElementById('add_payment_btn');
    const cancelBtn = document.getElementById('cancel_payment_btn');

    addBtn.addEventListener('click', () => {
        formCard.classList.remove('d-none');
        listCard.classList.add('d-none');
        addBtn.classList.add('d-none');
        initCustomerSelect();
    });

    cancelBtn.addEventListener('click', () => {
        formCard.classList.add('d-none');
        listCard.classList.remove('d-none');
        addBtn.classList.remove('d-none');
    });

    // Payment Type Toggle
    const typeInvoice = document.getElementById('type_invoice');
    const typeAdvance = document.getElementById('type_advance');
    const invoiceSelect = document.getElementById('invoice_id');
    const invoiceDetails = document.getElementById('invoice_details');
    const amountInput = document.getElementById('amount_received');

    function toggleInvoiceSelect() {
        if(typeInvoice.checked) {
            invoiceSelect.disabled = false;
            invoiceSelect.required = true;
        } else {
            invoiceSelect.disabled = true;
            invoiceSelect.required = false;
            invoiceSelect.value = "";
            invoiceDetails.classList.add('d-none');
        }
    }

    typeInvoice.addEventListener('change', toggleInvoiceSelect);
    typeAdvance.addEventListener('change', toggleInvoiceSelect);

    // Initialize Customer Select2
    function initCustomerSelect() {
        const $customer = $('#customer_id');

        if ($customer.hasClass('select2-hidden-accessible')) {
            $customer.select2('destroy');
            $customer.empty();
        }

        $customer.select2({
            placeholder: 'Search Customer...',
            width: '100%',
            dropdownCssClass: 'customer-select2-dropdown',
            allowClear: true,
            // dropdownParent: $('#add_payment_form'), // Optional but good for modal use
            minimumInputLength: 0,
            ajax: {
                url: basePath + '/controller/billing/search_customers_listing.php',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term || '' }; },
                processResults: function (data) {
                    if (!Array.isArray(data)) data = [];
                    let results = data.map(c => ({
                        id: String(c.id),
                        text: c.customer_name,
                        display_name: c.display_name,
                        customer_name: c.customer_name || c.text,
                        company_name: c.company_name || '',
                        email: c.email || '',
                        avatar: c.avatar || '',
                        customer_code: c.customer_code || '',
                        customers_type_name: c.customers_type_name || '',
                        loyalty_point_balance: c.loyalty_point_balance || 0,
                        current_balance_due: c.current_balance_due || 0
                    }));
                    
                    // Add "New Customer" option
                    results.push({
                        id: 'new_customer',
                        text: '+ New Customer',
                        isNew: true
                    });

                    return { results };
                }
            },
            templateResult: function (customer) {
                if (customer.loading) return customer.text;
                
                // Handle "New Customer" Option Display
                if (customer.isNew) {
                    return `<div class="text-primary fw-bold p-2 border-top"><i class="ti ti-plus me-1"></i> New Customer</div>`;
                }

                let letter = (customer.display_name || customer.customer_name || customer.text || '').charAt(0).toUpperCase();

                let avatarHtml = '';
                if(customer.avatar && customer.avatar.trim() !== ''){
                    avatarHtml = `<img src="${basePath}/${customer.avatar}" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">`;
                } else {
                    avatarHtml = `<div class="customer-avatar rounded-circle bg-light text-dark d-flex align-items-center justify-content-center fw-bold"
                             style="width:32px;height:32px;min-width:32px;">
                            ${letter}
                        </div>`;
                }
                
                // Loyalty Badge Logic - Display same as Tax Invoice
                let points = parseFloat(customer.loyalty_point_balance || 0);
                let pointsBadge = '';
                if(points > 0){
                    pointsBadge = `<span class="badge bg-success-subtle text-success border border-success ms-auto"><i class="ti ti-gift me-1"></i>₹${points} </span>`;
                }

                return `
                    <div class="d-flex align-items-center gap-2 py-1">
                        ${avatarHtml}
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="customer-text-primary fw-semibold lh-sm">
                                    ${customer.customer_name || customer.display_name} 
                                    ${customer.customers_type_name ? `<span class="small text-muted fw-normal">(${customer.customer_code} - ${customer.customers_type_name})</span>` : ''}
                                </div>
                                ${pointsBadge}
                            </div>
                            <div class="customer-text-primary small text-muted d-flex align-items-center gap-3 mt-1">
                                ${customer.email ? `<span><i class="ti ti-mail me-1"></i>${customer.email}</span>` : ''}
                                ${customer.company_name ? `<span><i class="ti ti-building me-1"></i>${customer.company_name}</span>` : ''}
                            </div>
                        </div>
                    </div>`;
            },
            templateSelection: function (customer) {
                if (!customer.id) return customer.text;
                if (customer.id === 'new_customer') {
                    $('#customer_id').select2('close');
                    
                    // Forcefully remove classes that might cause z-index issues
                    const $container = $('#customer_id').next('.select2-container');
                    $container.removeClass('select2-container--open select2-container--focus');
                    $container.css('z-index', 'auto'); // Reset inline z-index if any
                    
                    // Blur active element
                    if (document.activeElement) {
                        document.activeElement.blur();
                    }

                    setTimeout(() => {
                        $('#customer_id').val(null).trigger('change');
                        const modalEl = document.getElementById('addCustomerModal');
                        if (modalEl) {
                            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                let modalInstance = bootstrap.Modal.getInstance(modalEl);
                                if (!modalInstance) {
                                    modalInstance = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
                                }
                                modalInstance.show();
                            } else if (typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal) {
                                 // Fallback for window.bootstrap
                                let modalInstance = window.bootstrap.Modal.getInstance(modalEl);
                                if (!modalInstance) {
                                    modalInstance = new window.bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
                                }
                                modalInstance.show();
                            } else {
                                console.error('Bootstrap 5 Modal is not loaded.');
                                alert('System Error: Bootstrap Modal library not found. Please refresh page.');
                            }
                        } else {
                             console.error('Modal element #addCustomerModal not found in DOM');
                        }
                    }, 150);
                    return 'Adding Customer...';
                }

                let name = customer.customer_name || $(customer.element).data('customer_name') || customer.text || customer.display_name;
                if(!name) return '';

                // Handle avatar from object or element data
                let avatar = customer.avatar || $(customer.element).data('avatar');
                let avatarHtml = '';
                
                if(avatar && avatar.trim() !== ''){
                    avatarHtml = `<img src="${basePath}/${avatar}" class="rounded-circle me-2" style="width:20px;height:20px;object-fit:cover;vertical-align:middle;">`;
                } else {
                     let letter = name.charAt(0).toUpperCase();
                     avatarHtml = `<span class="badge rounded-circle bg-primary text-white me-2 d-inline-flex align-items-center justify-content-center" style="width:20px;height:20px;font-size:10px;vertical-align:middle;">${letter}</span>`;
                }
                
                return `<span>${avatarHtml}${name}</span>`;
            },
            escapeMarkup: m => m
        }).on('select2:select', function (e) {
            var data = e.params.data;
            
            // Display Balance
            let bal = parseFloat(data.current_balance_due || 0);
            $('#cust_current_bal').val(bal);
            let balDisplay = $('#customer_balance_display');
            balDisplay.removeClass('d-none text-danger text-success');
            
            if(bal < 0) {
                balDisplay.addClass('text-success'); // Green for Credit/Advance
                balDisplay.html(`<i class="ti ti-wallet me-1"></i>Advance Available: ₹${Math.abs(bal).toFixed(2)}`);
            } else if (bal > 0) {
                balDisplay.addClass('text-danger'); // Red for Debt
                balDisplay.html(`Current Due: ₹${bal.toFixed(2)}`);
            } else {
                 // balDisplay.addClass('d-none'); 
                 balDisplay.html(`Total Due: ₹0.00`); 
            }

            fetchUnpaidInvoices(data.id);
        }).on('select2:clear', function() {
            $('#customer_balance_display').addClass('d-none').html('');
            $('#cust_current_bal').val(0);
            invoiceSelect.innerHTML = '<option value="">Select Invoice...</option>';
        });
    }

    // Payment Mode Validation
    $('select[name="payment_mode"]').on('change', function() {
        if($(this).val() === 'Advance Adjustment') {
            let bal = parseFloat($('#cust_current_bal').val() || 0);
            if(bal >= 0) {
                alert('This customer has no advance balance/credit to adjust.');
                $(this).val('Cash');
                return;
            }
            // Auto-limit amount logic handled in input or submit usually, but good to hint
            // Ensure Payment Type is Invoice
            $('#type_invoice').prop('checked', true).trigger('change');
            $('#type_advance').prop('disabled', true); // Can't make an advance adjustment on an advance
        } else {
            $('#type_advance').prop('disabled', false);
        }
    });

    // Amount Validation for Adjustment
    $('#amount_received').on('change keyup', function() {
        let mode = $('select[name="payment_mode"]').val();
        if(mode === 'Advance Adjustment') {
             let bal = parseFloat($('#cust_current_bal').val() || 0);
             let absBal = Math.abs(bal);
             let val = parseFloat($(this).val() || 0);
             if(val > absBal) {
                 alert('Amount cannot exceed available advance: ₹' + absBal);
                 $(this).val(absBal);
             }
        }
    });

    // Fetch Unpaid Invoices for Customer
    function fetchUnpaidInvoices(customerId) {
        invoiceSelect.innerHTML = '<option value="">Loading...</option>';
        
        fetch(basePath + '/controller/billing/get_unpaid_invoices.php?customer_id=' + customerId)
        .then(response => response.json())
        .then(data => {
            invoiceSelect.innerHTML = '<option value="">Select Invoice...</option>';
            if(data && data.length > 0) {
                // If invoices found, ensure "Invoice" type is selected
                if(typeAdvance.checked){
                    typeInvoice.checked = true;
                }
                toggleInvoiceSelect();
                
                data.forEach(inv => {
                    let opt = document.createElement('option');
                    opt.value = inv.invoice_id;
                    opt.text = `${inv.invoice_number} - Due: ₹${inv.balance_due} (Date: ${inv.invoice_date})`;
                    opt.dataset.balance = inv.balance_due;
                    invoiceSelect.appendChild(opt);
                });
            } else {
                let opt = document.createElement('option');
                opt.text = "No unpaid invoices found";
                opt.disabled = true;
                invoiceSelect.appendChild(opt);
                
                // Auto choose Advance
                typeAdvance.checked = true;
                toggleInvoiceSelect();
            }
        });
    }

    // Invoice Selection Handler
    invoiceSelect.addEventListener('change', function() {
        let selectedOption = this.options[this.selectedIndex];
        if(selectedOption && selectedOption.dataset.balance) {
            let bal = selectedOption.dataset.balance;
            document.getElementById('inv_balance').innerText = bal;
            invoiceDetails.classList.remove('d-none');
            // Auto-fill amount if empty
            if(!amountInput.value || amountInput.value == 0) {
                amountInput.value = bal;
            }
        } else {
            invoiceDetails.classList.add('d-none');
        }
    });

    // Modal Avatar Preview
    function previewModalAvatar(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('modal_avatar_preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Handle AJAX Customer Form Submission
    document.addEventListener('DOMContentLoaded', function() {
        $(document).on('submit', '#ajax_customer_form', function(e){
            e.preventDefault();
            const form = $(this);
            const btn = form.find('#save_customer_btn');
            btn.prop('disabled', true).text('Saving...');

            const formData = new FormData(this);
            
            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: formData,
                dataType: 'json',
                contentType: false,
                processData: false,
                success: function(response){
                    if(response.success){
                        // Hide Modal
                        const modalEl = document.getElementById('addCustomerModal');
                        if (typeof bootstrap !== 'undefined') {
                            const modalInstance = bootstrap.Modal.getInstance(modalEl);
                            if (modalInstance) modalInstance.hide();
                        } else {
                            // Fallback
                            $(modalEl).hide();
                            $('.modal-backdrop').remove();
                             document.body.classList.remove('modal-open');
                        }

                        form[0].reset();
                        document.getElementById('modal_avatar_preview').src = basePath + '/public/assets/images/users/default_user_image.png';

                        // Add new option to Select2 and select it
                        var newOption = new Option(response.customer_name, response.customer_id, true, true);
                        
                        // Attach extra data for valid rendering in template functions
                        $(newOption).data('avatar', response.avatar);
                        $(newOption).data('customer_name', response.customer_name);
                        $(newOption).data('customer_code', response.customer_code);
                        $(newOption).data('customers_type_name', response.customers_type_name);
                        $(newOption).data('email', response.email);
                        $(newOption).data('company_name', response.company_name);
                        $(newOption).data('company_name', response.company_name);
                        $(newOption).data('loyalty_point_balance', 0); 
                        $(newOption).data('current_balance_due', 0); 

                        $('#customer_id').append(newOption).trigger('change');
                        
                        // Refetch invoices (will be empty likely, but good to refresh context)
                        if(typeof fetchUnpaidInvoices === 'function'){
                             fetchUnpaidInvoices(response.customer_id);
                        }

                    } else {
                        alert('Error: ' + (response.message || response.error || 'Unknown error'));
                    }
                },
                error: function(xhr){
                    console.error(xhr.responseText);
                    alert('An error occurred while adding the customer.');
                },
                complete: function(){
                    btn.prop('disabled', false).text('Save Customer');
                }
            });
        });
    });

</script>
