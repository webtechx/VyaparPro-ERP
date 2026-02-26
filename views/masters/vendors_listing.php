<?php
$title = 'Vendors';
?>

<!-- ============================================================== -->
<!-- Start Main Content -->
<!-- ============================================================== -->

<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            
            <!-- Add/Edit Vendor Form -->
            <div class="card d-none" id="add_vendor_form">
                <div class="card-header">
                    <h5 class="card-title">Add Vendor</h5>
                </div>

                <div class="card-body">
                    <form id="vendor_form" action="<?= $basePath ?>/controller/masters/vendors/add_vendor.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="vendor_id" id="edit_vendor_id">
                        
                        <!-- Primary Information -->
                        <div class="row  mb-4">
                             <div class="col-md-1 text-center ">
                                <div class="position-relative d-inline-block ">
                                    <img id="edit_avatar_preview" src="<?= $basePath ?>/public/assets/images/users/default_user_image.png" 
                                         class="rounded-circle object-fit-cover shadow-sm border border-2 border-white bg-secondary-subtle" 
                                         style="width: 60px; height: 60px;"
                                         alt="Avatar Preview">
                                    <label for="edit_avatar_input" class="position-absolute bottom-0 end-0 bg-primary text-white p-1 rounded-circle cursor-pointer shadow-sm" 
                                           style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; transform: translate(10%, 10%);"
                                           title="Upload Avatar">
                                        <i class="ti ti-camera"></i>
                                    </label>
                                    <input type="file" name="avatar" id="edit_avatar_input" class="d-none" accept="image/*" onchange="previewAvatar(this)">
                                </div>
                            </div>
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
                            <div class="col-md-3">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Display Name <span class="text-danger">*</span></label>
                                <input type="text" name="display_name" class="form-control" required>
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-2 mt-3">
                                <label class="form-label">Work Phone</label>
                                <input type="text" name="work_phone" class="form-control">
                            </div>
                            <div class="col-md-2 mt-3">
                                <label class="form-label">Mobile</label>
                                <input type="text" name="mobile" class="form-control">
                            </div>
                            <div class="col-md-2 mt-3">
                                <label class="form-label">Vendor Type</label>
                                <select name="vendor_type" class="form-select">
                                    <option value="Goods Supplier">Goods Supplier</option>
                                    <option value="Service Provider">Service Provider</option>
                                    <option value="Transporter">Transporter</option>
                                    <option value="Utility Provider">Utility Provider</option>
                                    <option value="Contractor">Contractor</option>
                                </select>
                            </div>
                            <div class="col-md-2 mt-3">
                                <label class="form-label">Account Type</label>
                                <select name="vendor_account_type" class="form-select">
                                    <option value="Sundry Creditors">Sundry Creditors</option>
                                    <option value="Advance to Vendor">Advance to Vendor</option>
                                </select>
                            </div>
                            <div class="col-lg-1 mt-3">
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
                                                <label class="form-label">GST No</label>
                                                <input type="text" name="gst_no" class="form-control">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Opening Balance</label>
                                                <input type="number" step="0.01" name="opening_balance" class="form-control">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Opening Balance Type</label>
                                                <select name="opening_balance_type" class="form-select">
                                                    <option value="DR">Debit</option>
                                                    <option value="CR">Credit</option>
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

            <!-- ============================================================== -->
            <!-- Vendors List -->
            <!-- ============================================================== -->

            <div class="card" id="vendors_list_card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Vendors List</h5>
                    <div class="d-flex gap-2">
                        <a href="<?= $basePath ?>/controller/masters/vendors/export_vendors_excel.php" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-info" onclick="printVendorsList()">
                            <i class="ti ti-printer me-1"></i> Print
                        </button>
                        <button type="button" id="add_vendor_btn" class="btn btn-primary" <?= can_access('vendors', 'add') ? '' : 'disabled title="Access Denied"' ?>>Add Vendor</button>
                    </div>
                </div>

                <div class="card-body">
                    <table data-tables="basic" class="table table-striped dt-responsive align-middle mb-0" id="vendors_table" style="width: 100%;">
                        <thead class="thead-sm text-uppercase fs-xxs">
                            <tr>
                                
                                <th>Display Name</th>
                                <th>Company Name</th>
                                <th>Contact</th>
                                <th>Type</th>
                                <th>Balance Due</th>
                                <th>Status</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT v.*, (SELECT COUNT(po.purchase_orders_id) FROM purchase_orders po WHERE po.vendor_id = v.vendor_id) as po_count 
                                    FROM vendors_listing v 
                                    WHERE v.organization_id = " . $_SESSION['organization_id'] . " 
                                    ORDER BY v.display_name ASC";
                            $result = $conn->query($sql);

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if(!empty($row['avatar'])): ?>
                                                    <img src="<?= $basePath ?>/<?= $row['avatar'] ?>" class="rounded-circle me-2" width="32" height="32" style="object-fit:cover;">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-light text-primary d-flex align-items-center justify-content-center me-2 fw-bold" style="width:32px; height:32px;">
                                                        <?= strtoupper(substr($row['display_name'], 0, 1)) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold"> <?= htmlspecialchars(ucwords(strtolower($row['display_name']))) ?></span>
                                                    <small class="text-dark"><?= htmlspecialchars(strtoupper($row['vendor_code'] ?? '-')) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars(ucwords(strtolower($row['company_name']))) ?></td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold"><i class="ti ti-mail me-1"></i><?= htmlspecialchars(strtolower($row['email'])) ?></span>
                                                <small class="text-dark"><i class="ti ti-phone me-1"></i><?= htmlspecialchars($row['mobile']) ?></small>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars(ucwords(strtolower($row['vendor_type']))) ?></td>
                                        <td class="<?= $row['current_balance_due'] > 0 ? 'text-danger' : 'text-success' ?>">
                                            ₹<?= number_format($row['current_balance_due'], 2) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $row['status'] == 'active' ? 'success' : 'danger' ?>">
                                                <?= ucfirst(strtolower(htmlspecialchars($row['status']))) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-sm btn-warning" 
                                                        data-id="<?= $row['vendor_id'] ?>" 
                                                        onclick="viewVendor(this)">
                                                    <i class="ti ti-eye"></i>
                                                </button>
                                                
                                                <button class="btn btn-sm btn-info" 
                                                        data-id="<?= $row['vendor_id'] ?>" 
                                                        data-json='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>'
                                                        <?= can_access('vendors', 'edit') ? 'onclick="editVendor(this)"' : 'disabled title="Access Denied"' ?>>
                                                    <i class="ti ti-edit"></i>
                                                </button>
                                                
                                                <?php 
                                                $hasDependencies = ($row['po_count'] > 0);
                                                $canDelete = can_access('vendors', 'delete');
                                                
                                                if ($hasDependencies) {
                                                    $delLink = 'javascript:void(0);';
                                                    $delClass = 'disabled';
                                                    $delAttr = 'title="Cannot delete: Vendor has associated Purchase Orders"';
                                                } elseif (!$canDelete) {
                                                    $delLink = 'javascript:void(0);';
                                                    $delClass = 'disabled';
                                                    $delAttr = 'title="Access Denied"';
                                                } else {
                                                    $delLink = $basePath . '/controller/masters/vendors/delete_vendor.php?id=' . $row['vendor_id'];
                                                    $delClass = '';
                                                    $delAttr = 'onclick="return confirm(\'Are you sure you want to delete this vendor?\');" title="Delete Vendor"';
                                                }
                                                ?>
                                                <a href="<?= $delLink ?>" class="btn btn-sm btn-danger <?= $delClass ?>" <?= $delAttr ?>>
                                                    <i class="ti ti-trash"></i>
                                                </a>
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

<!-- View Vendor Modal -->
<div class="modal fade" id="view_vendor_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vendor Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="view_vendor_body">
                <!-- Content injected via JS -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle Views
    document.getElementById('cancel_btn').addEventListener('click', function() {
        resetForm();
        toggleView(false);
    });

    document.getElementById('add_vendor_btn').addEventListener('click', function() {
        resetForm();
        toggleView(true);
    });

    function toggleView(showForm) {
        const formCard = document.getElementById('add_vendor_form');
        const listCard = document.getElementById('vendors_list_card');
        if (showForm) {
            formCard.classList.remove('d-none');
            listCard.classList.add('d-none');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            formCard.classList.add('d-none');
            listCard.classList.remove('d-none');
        }
    }

    function viewVendor(btn) {
        const id = btn.getAttribute('data-id');
        const modalEl = document.getElementById('view_vendor_modal');
        const modalBody = document.getElementById('view_vendor_body');
        
        // Show Spinner
        modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div></div>';
        
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        fetch(`<?= $basePath ?>/controller/masters/vendors/get_vendor_details.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if(data.error) {
                    modalBody.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                    return;
                }
                
                const v = data.vendor;
                
                // Helper for optional values
                const val = (v) => v || '-';

                let html = `
                    <div class="d-flex align-items-center mb-4">
                         ${v.avatar ? 
                            `<img src="<?= $basePath ?>/${v.avatar}" class="rounded-circle me-3 shadow-sm" style="width:80px; height:80px; object-fit:cover;">` : 
                            `<div class="rounded-circle bg-light text-primary d-flex align-items-center justify-content-center me-3 fw-bold shadow-sm" style="width:80px; height:80px; font-size:2rem;">
                                ${v.display_name ? v.display_name.charAt(0).toUpperCase() : '?'}
                            </div>`
                        }
                        <div>
                            <h4 class="mb-1">${val(v.display_name)}</h4>
                            <div class="badge bg-primary-subtle text-primary mb-1">${val(v.vendor_code)}</div>
                            <p class="mb-0 text-muted">${val(v.company_name)}</p>
                        </div>
                    </div>

                    <!-- Main Info -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <small class="text-muted d-block">First Name</small>
                            <strong>${val(v.salutation)} ${val(v.first_name)} ${val(v.last_name)}</strong>
                        </div>
                        
                        <div class="col-md-3">
                            <small class="text-muted d-block">Company Name</small>
                            <strong>${val(v.company_name)}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Display Name</small>
                            <strong>${val(v.display_name)}</strong>
                        </div>
                      
                        <div class="col-md-3">
                            <small class="text-muted d-block">Work Phone</small>
                            <strong>${val(v.work_phone)}</strong>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted d-block">Mobile</small>
                            <strong>${val(v.mobile)}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Email</small>
                            <strong>${val(v.email)}</strong>
                        </div>
                         <div class="col-md-3">
                            <small class="text-muted d-block">Vendor Type</small>
                            <strong>${val(v.vendor_type)}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Account Type</small>
                            <strong>${val(v.vendor_account_type)}</strong>
                        </div>
                    </div>
                    
                    <hr class="my-3">

                    <!-- Other Details -->
                    <h6 class="fw-bold text-primary mb-3">Other Details</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <small class="text-muted d-block">Currency</small>
                            <strong>${val(v.currency)}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Payment Terms</small>
                            <strong>${val(v.payment_terms)}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Status</small>
                            <span class="badge bg-${v.status == 'active' ? 'success' : 'danger'}">${v.status.charAt(0).toUpperCase() + v.status.slice(1).toLowerCase()}</span>
                        </div>
                         <div class="col-md-3">
                            <small class="text-muted d-block">PAN No</small>
                            <strong>${val(v.pan)}</strong>
                        </div>
                         <div class="col-md-3">
                            <small class="text-muted d-block">GST No</small>
                            <strong>${val(v.gst_no)}</strong>
                        </div>
                         <div class="col-md-3">
                            <small class="text-muted d-block">Opening Balance</small>
                            <strong>${val(v.opening_balance)} (${val(v.opening_balance_type)})</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Current Balance Due</small>
                            <strong class="${v.current_balance_due > 0 ? 'text-danger' : 'text-success'}">₹${v.current_balance_due}</strong>
                        </div>

                    </div>

                    <hr class="my-3">

                    <!-- Addresses -->
                    <h6 class="fw-bold text-primary mb-3">Addresses</h6>
                    <div class="row g-3">
                        <div class="col-md-6 border-end">
                            <h6 class="text-secondary  text-uppercase fw-bold">Billing Address</h6>
                            ${data.addresses.billing ? `
                                <div class="mb-1"><strong>${val(data.addresses.billing.attention)}</strong></div>
                                <div class="text-muted  mb-2">
                                    ${data.addresses.billing.address_line1}, ${val(data.addresses.billing.address_line2)}<br>
                                    ${data.addresses.billing.city}, ${data.addresses.billing.state} - ${data.addresses.billing.pin_code}<br>
                                    ${data.addresses.billing.country}
                                </div>
                                <div class="">Phone: ${val(data.addresses.billing.phone)} | Fax: ${val(data.addresses.billing.fax)}</div>
                            ` : '<p class="text-muted ">No billing address provided.</p>'}
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-secondary  text-uppercase fw-bold">Shipping Address</h6>
                             ${data.addresses.shipping ? `
                                <div class="mb-1"><strong>${val(data.addresses.shipping.attention)}</strong></div>
                                <div class="text-muted  mb-2">
                                    ${data.addresses.shipping.address_line1}, ${val(data.addresses.shipping.address_line2)}<br>
                                    ${data.addresses.shipping.city}, ${data.addresses.shipping.state} - ${data.addresses.shipping.pin_code}<br>
                                    ${data.addresses.shipping.country}
                                </div>
                                <div class="">Phone: ${val(data.addresses.shipping.phone)} | Fax: ${val(data.addresses.shipping.fax)}</div>
                            ` : '<p class="text-muted ">No shipping address provided.</p>'}
                        </div>
                    </div>

                    <hr class="my-3">

                    <!-- Contact Persons -->
                    <h6 class="fw-bold text-primary mb-3">Contact Persons</h6>
                `;

                if(data.contacts && data.contacts.length > 0) {
                     html += '<div class="table-responsive"><table class="table table-sm table-bordered mb-0">';
                     html += '<thead class="table-light"><tr><th>Name</th><th>Role</th><th>Email</th><th>Mobile</th></tr></thead><tbody>';
                     data.contacts.forEach(c => {
                         html += `<tr>
                            <td>${c.salutation} ${c.first_name} ${c.last_name}</td>
                            <td>${val(c.role)}</td>
                            <td>${val(c.email)}</td>
                            <td>${val(c.mobile)}</td>
                         </tr>`;
                     });
                     html += '</tbody></table></div>';
                } else {
                    html += '<p class="text-muted small">No contact persons.</p>';
                }

                html += `<hr class="my-3"> <h6 class="fw-bold text-primary mb-3">Bank Details</h6>`;
                
                // Bank Details
                if(data.bank_account) {
                    html += `
                        <div class="row g-3">
                            <div class="col-md-3">
                                <small class="text-muted d-block">Account Holder</small>
                                <strong>${val(data.bank_account.account_holder_name)}</strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Bank Name</small>
                                <strong>${val(data.bank_account.bank_name)}</strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Account Number</small>
                                <strong>${val(data.bank_account.account_number)}</strong>
                            </div>
                             <div class="col-md-3">
                                <small class="text-muted d-block">IFSC Code</small>
                                <strong>${val(data.bank_account.ifsc_code)}</strong>
                            </div>
                        </div>
                    `;
                } else {
                     html += '<p class="text-muted small">No bank details added.</p>';
                }

                html += `<hr class="my-3">`;

                // Custom Fields & Reporting Tags & Remarks
                html += `

                             <h6 class="fw-bold text-primary mb-2">Remarks</h6>
                             <div class="p-3 bg-light rounded">${val(data.remarks).replace(/\n/g, '<br>')}</div>
                        </div>
                    </div>
                `;

                modalBody.innerHTML = html;
            })
            .catch(err => {
                console.error(err);
                modalBody.innerHTML = `<div class="alert alert-danger">Failed to load details.</div>`;
            });
    }

    function editVendor(btn) {
        // Use basic data to switch view immediately
        const id = btn.getAttribute('data-id');
        toggleView(true);
        document.querySelector('.card-title').innerText = 'Edit Vendor';
        
        // Fetch full details
        fetch(`<?= $basePath ?>/controller/masters/vendors/get_vendor_details.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if(data.error) {
                    alert(data.error);
                    return;
                }
                const v = data.vendor;
                // Main Fields
                document.getElementById('edit_vendor_id').value = v.vendor_id;
                document.querySelector('select[name="salutation"]').value = v.salutation;
                document.querySelector('input[name="first_name"]').value = v.first_name;
                document.querySelector('input[name="last_name"]').value = v.last_name;
                document.querySelector('input[name="company_name"]').value = v.company_name;
                document.querySelector('input[name="display_name"]').value = v.display_name;
                document.querySelector('input[name="email"]').value = v.email;
                document.querySelector('input[name="work_phone"]').value = v.work_phone;
                document.querySelector('input[name="mobile"]').value = v.mobile;
                document.querySelector('select[name="vendor_type"]').value = v.vendor_type;
                document.querySelector('select[name="vendor_account_type"]').value = v.vendor_account_type;

                // Other Details
                document.querySelector('input[name="currency"]').value = v.currency;
                document.querySelector('select[name="payment_terms"]').value = v.payment_terms;
                document.getElementById('switchSuccess').checked = (v.status.toLowerCase() === 'active');
                document.querySelector('input[name="pan"]').value = v.pan;
                document.querySelector('input[name="gst_no"]').value = v.gst_no;
                document.querySelector('input[name="opening_balance"]').value = v.opening_balance;
                let obt = v.opening_balance_type;
                if(obt === 'Debit') obt = 'DR';
                if(obt === 'Credit') obt = 'CR';
                document.querySelector('select[name="opening_balance_type"]').value = obt;

                // Avatar
                if(v.avatar){
                    document.getElementById('edit_avatar_preview').src = '<?= $basePath ?>/' + v.avatar;
                } else {
                    document.getElementById('edit_avatar_preview').src = '<?= $basePath ?>/public/assets/images/users/default_user_image.png';
                }

                // Addresses
                if(data.addresses.billing) {
                    const b = data.addresses.billing;
                    document.querySelector('input[name="billing_attention"]').value = b.attention;
                    document.querySelector('input[name="billing_country"]').value = b.country;
                    document.querySelector('input[name="billing_address_line1"]').value = b.address_line1;
                    document.querySelector('input[name="billing_address_line2"]').value = b.address_line2;
                    document.querySelector('input[name="billing_city"]').value = b.city;
                    document.querySelector('input[name="billing_state"]').value = b.state;
                    document.querySelector('input[name="billing_pin_code"]').value = b.pin_code;
                    document.querySelector('input[name="billing_phone"]').value = b.phone;
                    document.querySelector('input[name="billing_fax"]').value = b.fax;
                }
                if(data.addresses.shipping) {
                    const s = data.addresses.shipping;
                    document.querySelector('input[name="shipping_attention"]').value = s.attention;
                    document.querySelector('input[name="shipping_country"]').value = s.country;
                    document.querySelector('input[name="shipping_address_line1"]').value = s.address_line1;
                    document.querySelector('input[name="shipping_address_line2"]').value = s.address_line2;
                    document.querySelector('input[name="shipping_city"]').value = s.city;
                    document.querySelector('input[name="shipping_state"]').value = s.state;
                    document.querySelector('input[name="shipping_pin_code"]').value = s.pin_code;
                    document.querySelector('input[name="shipping_phone"]').value = s.phone;
                    document.querySelector('input[name="shipping_fax"]').value = s.fax;
                }

                // Bank
                if(data.bank_account) {
                    document.querySelector('input[name="bank_account_holder_name"]').value = data.bank_account.account_holder_name;
                    document.querySelector('input[name="bank_name"]').value = data.bank_account.bank_name;
                    document.querySelector('input[name="bank_account_number"]').value = data.bank_account.account_number;
                    document.querySelector('input[name="bank_ifsc_code"]').value = data.bank_account.ifsc_code;
                }

                // Remarks
                document.querySelector('textarea[name="remarks"]').value = data.remarks || '';

                // Contacts (Dynamic)
                const cWrap = document.getElementById('contacts_wrapper');
                cWrap.innerHTML = ''; // Clear
                if(data.contacts && data.contacts.length > 0) {
                    data.contacts.forEach(c => {
                        addContactRow(c);
                    });
                } else {
                    addContactRow(); // Add empty
                }



            })
            .catch(err => console.error(err));

        const form = document.getElementById('vendor_form');
        form.action = '<?= $basePath ?>/controller/masters/vendors/update_vendor.php';
        document.getElementById('submit_btn').classList.add('d-none');
        document.getElementById('update_btn').classList.remove('d-none');
    }

    function resetForm() {
        document.getElementById('vendor_form').reset();
        document.getElementById('vendor_form').action = '<?= $basePath ?>/controller/masters/vendors/add_vendor.php';
        document.getElementById('edit_vendor_id').value = '';
        document.querySelector('.card-title').innerText = 'Add Vendor';
        document.getElementById('submit_btn').classList.remove('d-none');
        document.getElementById('update_btn').classList.add('d-none');
        
        // Reset dynamic rows to ONE empty row
        document.getElementById('contacts_wrapper').innerHTML = '';
        addContactRow();

        document.getElementById('switchSuccess').checked = true;
        
        document.getElementById('edit_avatar_preview').src = '<?= $basePath ?>/public/assets/images/users/default_user_image.png';
    }

    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('edit_avatar_preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Helper to add rows with optional data
    function addContactRow(data = {}) {
        const wrapper = document.getElementById('contacts_wrapper');
        const div = document.createElement('div');
        div.className = 'row g-2 mb-2 contact-row';
        const sal = data.salutation || 'Mr.';
        div.innerHTML = `
            <div class="col-md-2">
                 <select name="contact_salutation[]" class="form-select">
                    <option value="Mr." ${sal === 'Mr.' ? 'selected' : ''}>Mr.</option>
                    <option value="Mrs." ${sal === 'Mrs.' ? 'selected' : ''}>Mrs.</option>
                    <option value="Ms." ${sal === 'Ms.' ? 'selected' : ''}>Ms.</option>
                    <option value="Dr." ${sal === 'Dr.' ? 'selected' : ''}>Dr.</option>
                </select>
            </div>
            <div class="col-md-2"><input type="text" name="contact_first_name[]" class="form-control" placeholder="First Name" value="${data.first_name || ''}"></div>
            <div class="col-md-2"><input type="text" name="contact_last_name[]" class="form-control" placeholder="Last Name" value="${data.last_name || ''}"></div>
            <div class="col-md-2"><input type="email" name="contact_email[]" class="form-control" placeholder="Email" value="${data.email || ''}"></div>
            <div class="col-md-2"><input type="text" name="contact_mobile[]" class="form-control" placeholder="Mobile" value="${data.mobile || ''}"></div>
            <div class="col-md-2"><input type="text" name="contact_role[]" class="form-control" placeholder="Role" value="${data.role || ''}"></div>
        `;
        wrapper.appendChild(div);
    }
    


    function copyBillingToShipping() {
        document.querySelector('input[name="shipping_attention"]').value = document.querySelector('input[name="billing_attention"]').value;
        document.querySelector('input[name="shipping_country"]').value = document.querySelector('input[name="billing_country"]').value;
        document.querySelector('input[name="shipping_address_line1"]').value = document.querySelector('input[name="billing_address_line1"]').value;
        document.querySelector('input[name="shipping_address_line2"]').value = document.querySelector('input[name="billing_address_line2"]').value;
        document.querySelector('input[name="shipping_city"]').value = document.querySelector('input[name="billing_city"]').value;
        document.querySelector('input[name="shipping_state"]').value = document.querySelector('input[name="billing_state"]').value;
        document.querySelector('input[name="shipping_pin_code"]').value = document.querySelector('input[name="billing_pin_code"]').value;
        document.querySelector('input[name="shipping_phone"]').value = document.querySelector('input[name="billing_phone"]').value;
        document.querySelector('input[name="shipping_fax"]').value = document.querySelector('input[name="billing_fax"]').value;
    }
</script>

<style>
@media print {
    body * { visibility: hidden !important; }
    #vendors-print-area, #vendors-print-area * { visibility: visible !important; }
    #vendors-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<div id="vendors-print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:4px;">
        <h2 style="margin:0; color:#4F46E5; border-bottom:2px solid #4F46E5; padding-bottom:6px;">VENDORS LIST</h2>
    </div>
    <div style="margin:8px 0 12px 0; font-size:11px; color:#444; background:#f0f0ff; padding:6px 10px; border-left:3px solid #4F46E5;">
        <strong>Report Scope:</strong> All Vendors
    </div>
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead style="background:#4F46E5; color:#fff;">
            <tr>
                <th>Vendor Code</th>
                <th>Display Name</th>
                <th>Company Name</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Type</th>
                <th>Balance Due</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody id="vendors-print-tbody"></tbody>
    </table>
    <p style="margin-top:8px; font-size:10px; color:#999; text-align:right;">Printed on: <span id="vendors-print-date"></span></p>
</div>

<script>
function printVendorsList() {
    var rows = document.querySelectorAll('#vendors_table tbody tr');
    var tbody = document.getElementById('vendors-print-tbody');
    tbody.innerHTML = '';
    rows.forEach(function(tr) {
        var tds = tr.querySelectorAll('td');
        if(tds.length < 6) return;
        var code    = (tds[0].querySelector('small') ? tds[0].querySelector('small').textContent : '').trim();
        var name    = (tds[0].querySelector('.fw-bold') ? tds[0].querySelector('.fw-bold').textContent : '').trim();
        var company = tds[1].textContent.trim();
        var email   = (tds[2].querySelector('.fw-bold') ? tds[2].querySelector('.fw-bold').textContent : '').trim();
        var mobile  = (tds[2].querySelector('.text-dark') ? tds[2].querySelector('.text-dark').textContent : '').trim();
        var type    = tds[3].textContent.trim();
        var balance = tds[4].textContent.trim();
        var status  = (tds[5].querySelector('.badge') ? tds[5].querySelector('.badge').textContent : '').trim();
        tbody.innerHTML += '<tr>' +
            '<td>' + code + '</td>' +
            '<td>' + name + '</td>' +
            '<td>' + company + '</td>' +
            '<td>' + email + '</td>' +
            '<td>' + mobile + '</td>' +
            '<td>' + type + '</td>' +
            '<td>' + balance + '</td>' +
            '<td>' + status + '</td>' +
            '</tr>';
    });
    document.getElementById('vendors-print-date').textContent = new Date().toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('vendors-print-area').style.display = 'block';
    window.print();
    document.getElementById('vendors-print-area').style.display = 'none';
}
</script>
