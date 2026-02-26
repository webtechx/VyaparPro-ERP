<?php
$title = 'Customers';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            
            <!-- Customer Form Card (Initially Hidden) -->
            <div class="card d-none" id="add_customer_form">
                <div class="card-header">
                    <h5 class="card-title" id="formTitle">Add New Customer</h5>
                </div>
                <div class="card-body">
                    <form id="customer_form" method="post" enctype="multipart/form-data" onsubmit="event.preventDefault(); saveCustomer();">
                        <input type="hidden" name="customer_id" id="customer_id">
                        <input type="hidden" name="action" id="form_action" value="add">
                        
                        <div class="row g-3">
                             <div class="col-md-1 text-center">
                                <div class="position-relative d-inline-block ">
                                    <img id="avatar_preview" src="<?= $basePath ?>/public/assets/images/users/default_user_image.png" 
                                         class="rounded-circle object-fit-cover shadow-sm border border-2 border-white bg-secondary-subtle" 
                                         style="width: 80px; height: 80px;"
                                         alt="Avatar Preview">
                                    <div id="avatar_initials" class="rounded-circle shadow-sm border border-2 border-white bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center d-none" 
                                         style="width: 80px; height: 80px; font-size: 24px;">
                                    </div>
                                    <label for="avatar_input" class="position-absolute bottom-0 end-0 bg-primary text-white p-1 rounded-circle cursor-pointer shadow-sm" 
                                           style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; transform: translate(10%, 10%);"
                                           title="Upload Avatar">
                                        <i class="ti ti-camera"></i>
                                    </label>
                                    <input type="file" name="avatar" id="avatar_input" class="d-none" accept="image/*" onchange="previewAvatar(this)">
                                </div>
                            </div>
                            
                            <div class="col-md-11">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Customer Type <span class="text-danger">*</span></label>
                                        <select name="customers_type_id" id="customers_type_id" class="form-select" required>
                                            <option value="">Select Customer Type</option>
                                            <?php
                                            $sql = "SELECT `customers_type_id`, `customers_type_name` FROM `customers_type_listing` WHERE organization_id = " . $_SESSION['organization_id'];
                                            $result = $conn->query($sql);
                                            if ($result && $result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    echo "<option value='" . $row['customers_type_id'] . "'>" . $row['customers_type_name'] . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                                        <input type="text" name="customer_name" id="customer_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Company Name</label>
                                        <input type="text" name="company_name" id="company_name" class="form-control">
                                    </div>
                                     <div class="col-md-4">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" id="email" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" id="phone" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabs -->
                        <div class="row mt-4">
                            <div class="col-xl-12">
                                <ul class="nav nav-tabs nav-justified nav-bordered nav-bordered-primary mb-3" role="tablist">
                                    <li class="nav-item">
                                        <a href="#basic-details" data-bs-toggle="tab" class="nav-link active">
                                            <i class="ti ti-user fs-lg me-1"></i> Basic Details
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#address-details" data-bs-toggle="tab" class="nav-link">
                                            <i class="ti ti-map-pin fs-lg me-1"></i> Address Details
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <!-- Basic Details Tab -->
                                    <div class="tab-pane show active" id="basic-details">
                                        <div class="row g-3">

                                            <div class="col-md-4">
                                                <label class="form-label">GST Number</label>
                                                <input type="text" name="gst_number" id="gst_number" class="form-control">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Date of Birth</label>
                                                <input type="date" name="date_of_birth" id="date_of_birth" class="form-control">
                                            </div>
                                             <div class="col-md-2">
                                                <label class="form-label">Anniversary Date</label>
                                                <input type="date" name="anniversary_date" id="anniversary_date" class="form-control">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Address Details Tab -->
                                    <div class="tab-pane" id="address-details">
                                        <div class="row g-3">
                                                                                    <!-- Billing Address -->
                                        <div class="col-md-6 border-end">
                                            <h6 class="fw-bold text-primary mb-3">Billing Address (Permanent)</h6>
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <label class="form-label">Address</label>
                                                    <textarea name="address" id="cust_billing_address" class="form-control" rows="2"></textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">City</label>
                                                    <input type="text" name="city" id="cust_billing_city" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Pincode</label>
                                                    <input type="text" name="pincode" id="cust_billing_pincode" class="form-control">
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label">State</label>
                                                    <select name="state" id="cust_billing_state" class="form-select" onchange="updateStateCode(this, 'cust_billing_state_code')">
                                                        <option value="">Select State</option>
                                                        <!-- Options populated via JS -->
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">State Code</label>
                                                    <input type="text" name="state_code" id="cust_billing_state_code" class="form-control bg-light" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Shipping Address -->
                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="fw-bold text-success mb-0">Shipping Address</h6>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="copy_address_cb" onchange="copyBillingToShipping()">
                                                    <label class="form-check-label small" for="copy_address_cb">Same as Billing</label>
                                                </div>
                                            </div>
                                            
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <label class="form-label">Address</label>
                                                    <textarea name="shipping_address" id="cust_shipping_address" class="form-control" rows="2"></textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">City</label>
                                                    <input type="text" name="shipping_city" id="cust_shipping_city" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Pincode</label>
                                                    <input type="text" name="shipping_pincode" id="cust_shipping_pincode" class="form-control">
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label">State</label>
                                                    <select name="shipping_state" id="cust_shipping_state" class="form-select" onchange="updateStateCode(this, 'cust_shipping_state_code')">
                                                        <option value="">Select State</option>
                                                        <!-- Options populated via JS -->
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">State Code</label>
                                                    <input type="text" name="shipping_state_code" id="cust_shipping_state_code" class="form-control bg-light" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="button" id="cancel_btn" class="btn btn-secondary me-2"> Cancel </button>
                            <button type="submit" id="save_customer_btn" class="btn btn-primary"> Save Customer </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- List Card -->
             <div class="card" id="customers_list">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Customers List</h5>
                    <div class="d-flex gap-2">
                        <a href="<?= $basePath ?>/controller/customers/export_customers_excel.php" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-info" onclick="printCustomersList()">
                            <i class="ti ti-printer me-1"></i> Print
                        </button>
                        <button type="button" id="add_customer_btn" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Add Customer
                        </button>
                    </div>
                </div>
                    <div class="card-body">
                        <table data-tables="basic" class="table table-striped dt-responsive align-middle mb-0" style="width: 100%;">
                            <thead class="thead-sm text-uppercase fs-xxs">
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Company</th>
                                    <th>Contact</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT c.*, t.customers_type_name,
                                        (SELECT COUNT(si.invoice_id) FROM sales_invoices si WHERE si.customer_id = c.customer_id) as invoice_count
                                        FROM customers_listing c 
                                        LEFT JOIN customers_type_listing t ON c.customers_type_id = t.customers_type_id
                                        WHERE c.organization_id = " . $_SESSION['organization_id'] . " ORDER BY c.customer_id DESC";
                                $result = $conn->query($sql);

                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($row['avatar'])): ?>
                                                        <img src="<?= $basePath . '/' . $row['avatar'] ?>" class="avatar avatar-sm rounded-circle me-2" alt="Avatar">
                                                    <?php else: ?>
                                                        <span class="avatar avatar-sm rounded-circle bg-primary-subtle text-primary me-2 fw-bold d-flex align-items-center justify-content-center">
                                                            <?= strtoupper(substr($row['customer_name'], 0, 1)) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold"> <?= ucwords(strtolower($row['customer_name'])) ?></span>
                                                        <small class="text-dark">
                                                            <i class="ti ti-hash me-1"></i><?= $row['customer_code'] ?? '-' ?> 
                                                            <span class="mx-1">|</span> 
                                                            <i class="ti ti-tag me-1"></i><?= strtoupper($row['customers_type_name'] ?? '-') ?>
                                                        </small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold"> <?= ucwords(strtolower($row['company_name'] ?? '-')) ?></span>
                                                </div>
                                            </td>
                                            <td>  
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold"><i class="ti ti-mail me-1"></i><?= strtolower($row['email'] ?? '-') ?></span>
                                                    <span class="fw-bold"><i class="ti ti-phone me-1"></i> <?= $row['phone'] ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-info" onclick='viewCustomer(<?= json_encode($row) ?>)' title="View">
                                                        <i class="ti ti-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-warning" onclick='editCustomer(<?= json_encode($row) ?>)' title="Edit">
                                                        <i class="ti ti-pencil"></i>
                                                    </button>
                                                    <?php if ($row['invoice_count'] > 0): ?>
                                                        <button type="button" class="btn btn-sm btn-danger disabled" title="Cannot delete: Customer has <?= $row['invoice_count'] ?> invoice(s)" disabled>
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteCustomer(<?= $row['customer_id'] ?>)" title="Delete">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Customer Modal -->
<div class="modal fade" id="viewCustomerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Customer Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="view_customer_content">
        <!-- Content loaded via JS -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php ob_start(); ?>
<script>
    const basePath = '<?= $basePath ?>';

    // GST State Codes
    const indianStates = {
        "01": "Jammu & Kashmir", "02": "Himachal Pradesh", "03": "Punjab", "04": "Chandigarh", "05": "Uttarakhand",
        "06": "Haryana", "07": "Delhi", "08": "Rajasthan", "09": "Uttar Pradesh", "10": "Bihar",
        "11": "Sikkim", "12": "Arunachal Pradesh", "13": "Nagaland", "14": "Manipur", "15": "Mizoram",
        "16": "Tripura", "17": "Meghalaya", "18": "Assam", "19": "West Bengal", "20": "Jharkhand",
        "21": "Odisha", "22": "Chhattisgarh", "23": "Madhya Pradesh", "24": "Gujarat", "25": "Daman & Diu",
        "26": "Dadra & Nagar Haveli", "27": "Maharashtra", "28": "Andhra Pradesh (Old)", "29": "Karnataka", "30": "Goa",
        "31": "Lakshadweep", "32": "Kerala", "33": "Tamil Nadu", "34": "Puducherry", "35": "Andaman & Nicobar Islands",
        "36": "Telangana", "37": "Andhra Pradesh (New)", "38": "Ladakh", "97": "Other Territory"
    };

    function populateStateDropdowns() {
        const options = Object.entries(indianStates).map(([code, name]) => `<option value="${name}" data-code="${code}">${name}</option>`).join('');
        
        const billingSelect = document.getElementById('cust_billing_state');
        const shippingSelect = document.getElementById('cust_shipping_state');
        
        // Save current selection if any
        const currentBilling = billingSelect ? billingSelect.value : '';
        const currentShipping = shippingSelect ? shippingSelect.value : '';

        if(billingSelect) billingSelect.innerHTML = '<option value="">Select State</option>' + options;
        if(shippingSelect) shippingSelect.innerHTML = '<option value="">Select State</option>' + options;
        
        // Restore selection if valid
        if(billingSelect && currentBilling) billingSelect.value = currentBilling;
        if(shippingSelect && currentShipping) shippingSelect.value = currentShipping;
    }

    function updateStateCode(select, targetId) {
        const option = select.options[select.selectedIndex];
        const code = option.getAttribute('data-code') || '';
        document.getElementById(targetId).value = code;
    }

    function copyBillingToShipping() {
        if (document.getElementById('copy_address_cb').checked) {
            document.getElementById('cust_shipping_address').value = document.getElementById('cust_billing_address').value;
            document.getElementById('cust_shipping_city').value = document.getElementById('cust_billing_city').value;
            document.getElementById('cust_shipping_pincode').value = document.getElementById('cust_billing_pincode').value;
            
            // State Handling
            const billStateVal = document.getElementById('cust_billing_state').value;
            const shippingState = document.getElementById('cust_shipping_state');
            shippingState.value = billStateVal;
            
            updateStateCode(shippingState, 'cust_shipping_state_code');
        } else {
            document.getElementById('cust_shipping_address').value = '';
            document.getElementById('cust_shipping_city').value = '';
            document.getElementById('cust_shipping_pincode').value = '';
            document.getElementById('cust_shipping_state').value = '';
            document.getElementById('cust_shipping_state_code').value = '';
        }
    }

    // Toggle logic
    document.getElementById('add_customer_btn').addEventListener('click', function() {
        openCustomerForm('add');
    });

    document.getElementById('cancel_btn').addEventListener('click', function() {
        document.getElementById('add_customer_form').classList.add('d-none');
        document.getElementById('customers_list').classList.remove('d-none');
    });

    function openCustomerForm(mode) {
        document.getElementById('customer_form').reset();
        document.getElementById('add_customer_form').classList.remove('d-none');
        document.getElementById('customers_list').classList.add('d-none');
        
        // Populate States
        populateStateDropdowns();
        
        // Reset Avatar
        var preview = document.getElementById('avatar_preview');
        var initials = document.getElementById('avatar_initials');
        preview.src = basePath + '/public/assets/images/users/default_user_image.png';
        preview.classList.remove('d-none');
        if (initials) initials.classList.add('d-none');

        document.getElementById('save_customer_btn').classList.remove('d-none');
        
        if (mode === 'add') {
             document.getElementById('customer_id').value = '';
             document.getElementById('form_action').value = 'add';
             document.getElementById('formTitle').innerText = 'Add New Customer';
             document.getElementById('save_customer_btn').innerText = 'Save Customer';
        }
    }

    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = document.getElementById('avatar_preview');
                var initials = document.getElementById('avatar_initials');
                preview.src = e.target.result;
                preview.classList.remove('d-none');
                if (initials) initials.classList.add('d-none');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function editCustomer(customer) {
        openCustomerForm('update');
        document.getElementById('formTitle').innerText = 'Edit Customer';
        document.getElementById('form_action').value = 'update';
        document.getElementById('save_customer_btn').innerText = 'Update Customer';
        
        populateCustomerForm(customer);
    }
    
    function viewCustomer(customer) {
        let html = `
            <div class="p-2">
                <!-- Professional Header -->
                <div class="d-flex align-items-center mb-4 pb-4 border-bottom">
                    ${customer.avatar ? `<img src="${basePath}/${customer.avatar}" class="rounded-circle me-4 shadow-sm" style="width:90px; height:90px; object-fit:cover;">` : '<div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-4 shadow-sm" style="width:90px; height:90px; font-size:2.5rem; font-weight:bold;">' + (customer.customer_name ? customer.customer_name.charAt(0).toUpperCase() : '?') + '</div>'}
                    
                    <div class="flex-grow-1">
                        <h3 class="mb-1 text-dark fw-bold">${customer.customer_name}</h3>
                        <div class="mb-2">
                             <span class="badge bg-dark px-3 py-2 fs-xs me-2">${customer.customers_type_name || '-'}</span>
                             ${customer.company_name ? `<span class="badge bg-secondary px-3 py-2 fs-xs me-2">${customer.company_name}</span>` : ''}
                             <span class="badge bg-light text-dark border px-3 py-2 fs-xs">${customer.customer_code || '-'}</span>
                        </div>
                        <div class="text-muted d-flex align-items-center flex-wrap gap-3">
                            <span><i class="ti ti-mail me-1"></i> ${customer.email || '-'}</span>
                            <span><i class="ti ti-phone me-1"></i> ${customer.phone || '-'}</span>
                            ${customer.gst_number ? `<span><i class="ti ti-receipt-2 me-1"></i> GST: ${customer.gst_number}</span>` : ''}
                        </div>
                    </div>
                </div>

                <!-- Details Section -->
                <div class="mb-4">
                    <h5 class="text-uppercase text-muted fs-xs fw-bolder ls-1 mb-3 border-bottom pb-2">
                        <i class="ti ti-map-pin me-2"></i>Address & Financials
                    </h5>
                    <div class="row g-4">
                         <div class="col-md-6">
                            <div class="p-3 border rounded bg-white h-100">
                                <div class="badge bg-light text-dark mb-2">Billing Address</div>
                                <p class="mb-0 fs-6 text-dark leading-relaxed">
                                    <strong>${customer.city || '-'}, ${customer.state || '-'} (${customer.state_code || '-'})</strong><br>
                                    ${customer.address || 'No street address provided'}<br>
                                    Pincode: <span class="fw-bold">${customer.pincode || '-'}</span>
                                </p>
                                ${customer.shipping_address ? `
                                <div class="badge bg-light text-success mt-3 mb-2">Shipping Address</div>
                                <p class="mb-0 fs-6 text-dark leading-relaxed">
                                    <strong>${customer.shipping_city || '-'}, ${customer.shipping_state || '-'} (${customer.shipping_state_code || '-'})</strong><br>
                                    ${customer.shipping_address}<br>
                                    Pincode: <span class="fw-bold">${customer.shipping_pincode || '-'}</span>
                                </p>
                                ` : ''}
                                <div class="mt-3 border-top pt-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted small">Date of Birth:</span>
                                        <span class="fw-bold small text-dark">${customer.date_of_birth ? new Date(customer.date_of_birth).toLocaleDateString('en-GB', {day: 'numeric', month: 'short', year: 'numeric'}) : '-'}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted small">Anniversary:</span>
                                        <span class="fw-bold small text-dark">${customer.anniversary_date ? new Date(customer.anniversary_date).toLocaleDateString('en-GB', {day: 'numeric', month: 'short', year: 'numeric'}) : '-'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        `;

        document.getElementById('view_customer_content').innerHTML = html;
        var myModal = new bootstrap.Modal(document.getElementById('viewCustomerModal'));
        myModal.show();
    }

    function populateCustomerForm(customer) {
         document.getElementById('customer_id').value = customer.customer_id;
         document.getElementById('customer_name').value = customer.customer_name || '';
         document.getElementById('company_name').value = customer.company_name || '';
         document.getElementById('email').value = customer.email || '';
         document.getElementById('phone').value = customer.phone || '';
         document.getElementById('gst_number').value = customer.gst_number || '';

         document.getElementById('date_of_birth').value = customer.date_of_birth || '';
         document.getElementById('anniversary_date').value = customer.anniversary_date || '';
         document.getElementById('customers_type_id').value = customer.customers_type_id || '';

         // New Billing Fields
         document.getElementById('cust_billing_address').value = customer.address || '';
         document.getElementById('cust_billing_city').value = customer.city || '';
         document.getElementById('cust_billing_state').value = customer.state || '';
         document.getElementById('cust_billing_state_code').value = customer.state_code || ''; // Populate state code
         document.getElementById('cust_billing_pincode').value = customer.pincode || '';

         // Manually trigger state change logic if needed, but value setting above handles selection if options exist
         // Note: If state options aren't loaded yet, this might fail unless we ensure populateStateDropdowns runs first.
         // openCustomerForm runs populateStateDropdowns, and editCustomer calls openCustomerForm FIRST. So options should be there.

         // New Shipping Fields
         document.getElementById('cust_shipping_address').value = customer.shipping_address || '';
         document.getElementById('cust_shipping_city').value = customer.shipping_city || '';
         document.getElementById('cust_shipping_state').value = customer.shipping_state || '';
         document.getElementById('cust_shipping_state_code').value = customer.shipping_state_code || '';
         document.getElementById('cust_shipping_pincode').value = customer.shipping_pincode || '';
         
         var preview = document.getElementById('avatar_preview');
         var initials = document.getElementById('avatar_initials');

         if (customer.avatar) {
            preview.src = basePath + '/' + customer.avatar;
            preview.classList.remove('d-none');
            if (initials) initials.classList.add('d-none');
         } else {
            preview.classList.add('d-none');
             if (initials) {
                initials.classList.remove('d-none');
                initials.innerText = (customer.customer_name || '?').charAt(0).toUpperCase();
            }
         }
    }
    
    function saveCustomer() {
         const form = document.getElementById('customer_form');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const btn = document.getElementById('save_customer_btn');
        btn.disabled = true;
        const originalText = btn.innerText;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        fetch(basePath + '/controller/customers/save_customer.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                 window.location.href = window.location.pathname + '?success=' + encodeURIComponent(data.message);
            } else {
                alert('Error: ' + data.message);
                btn.disabled = false;
                btn.innerText = originalText;
            }
        })
        .catch(error => {
            alert('An error occurred: ' + error);
             btn.disabled = false;
             btn.innerText = originalText;
        });
    }

    function deleteCustomer(id) {
         if(confirm('Are you sure you want to delete this customer?')) {
            fetch(basePath + '/controller/customers/delete_customer.php', {
                method: 'POST',
                 headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'customer_id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    }

    // Initialize dropdowns on load as well
    document.addEventListener('DOMContentLoaded', function() {
        populateStateDropdowns();
    });
</script>

<style>
@media print {
    body * { visibility: hidden !important; }
    #cust-print-area, #cust-print-area * { visibility: visible !important; }
    #cust-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<div id="cust-print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:4px;">
        <h2 style="margin:0; color:#5d282a; border-bottom:2px solid #d7b251; padding-bottom:6px;">CUSTOMERS LIST</h2>
    </div>
    <div style="margin:8px 0 12px 0; font-size:11px; color:#444; background:#fdf5e0; padding:6px 10px; border-left:3px solid #d7b251;">
        <strong>Report Scope:</strong> All Customers
    </div>
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead style="background:#5d282a; color:#fff;">
            <tr><th>Customer Name</th><th>Company</th><th>Contact</th></tr>
        </thead>
        <tbody id="cust-print-tbody"></tbody>
    </table>
    <p style="margin-top:8px; font-size:10px; color:#999; text-align:right;">Printed on: <span id="cust-print-date"></span></p>
</div>

<script>
function printCustomersList() {
    var rows = document.querySelectorAll('table[data-tables] tbody tr');
    var tbody = document.getElementById('cust-print-tbody');
    tbody.innerHTML = '';
    rows.forEach(function(tr) {
        var tds = tr.querySelectorAll('td');
        if(tds.length < 3) return;
        tbody.innerHTML += '<tr>' +
            '<td>' + tds[0].textContent.trim() + '</td>' +
            '<td>' + tds[1].textContent.trim() + '</td>' +
            '<td>' + tds[2].textContent.trim() + '</td>' +
            '</tr>';
    });
    document.getElementById('cust-print-date').textContent = new Date().toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('cust-print-area').style.display = 'block';
    window.print();
    document.getElementById('cust-print-area').style.display = 'none';
}
</script>
<?php 
$extra_scripts = ob_get_clean();
?>
