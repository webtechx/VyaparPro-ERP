<?php
$title = 'Tax Invoice';

// Debug: Check if this is being loaded through layout
if (!defined('LAYOUT_LOADED')) {
    // If not loaded through layout, redirect to proper route
    header('Location: ' . (isset($basePath) ? $basePath : '') . '/tax_invoice');
    exit;
}
?>

<div class="container-fluid">
    <style>
        .select2-results__option--highlighted .customer-text-primary { color: white !important; }
        .select2-results__option--highlighted .customer-text-secondary { color: #e9ecef !important; }
        .select2-results__option--highlighted .status-badge { background-color: rgba(255,255,255,0.2) !important; color: white !important; }
        .select2-results__option--highlighted .text-dark { color: white !important; }
        .select2-results__option--highlighted .text-muted { color: #e9ecef !important; }
        .select2-results__option--highlighted .badge { background-color: rgba(255,255,255,0.2) !important; color: white !important; }
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

        /* Force Reference Customer Highlight */
        #select2-reference_customer_id_select-results .select2-results__option--highlighted {
            background-color: #5d87ff !important;
            color: white !important;
        }
        /* Ensure text classes inside adhere to white on highlight */
        #select2-reference_customer_id_select-results .select2-results__option--highlighted .customer-text-primary,
        #select2-reference_customer_id_select-results .select2-results__option--highlighted .text-muted,
        #select2-reference_customer_id_select-results .select2-results__option--highlighted .text-dark {
            color: white !important;
        }


    </style>

    <div class="row">
        <div class="col-xl-12">
            
            <?php
            // Fetch Organization Short Code
            $org_short_code = isset($_SESSION['organization_short_code']) ? strtoupper($_SESSION['organization_short_code']) : 'ORG';

            // Fetch Next Invoice Number
            // NOTE: Only works if sales_invoices table exists
            $next_inv_number = 'INV-' . $org_short_code . '-0001';
            try {
                $inv_sql = "SELECT invoice_number FROM sales_invoices WHERE organization_id = {$_SESSION['organization_id']} ORDER BY invoice_id DESC LIMIT 1";
                $inv_res = $conn->query($inv_sql);
                if ($inv_res && $inv_res->num_rows > 0) {
                    $last_inv = $inv_res->fetch_assoc()['invoice_number'];
                    
                    // Try matching INV-CODE-XXXX
                    if (preg_match('/INV-' . preg_quote($org_short_code) . '-(\d+)/', $last_inv, $matches)) {
                        $next_num = intval($matches[1]) + 1;
                        $next_inv_number = 'INV-' . $org_short_code . '-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
                    } 
                    // Fallback for older format INV-XXXX
                    else if (preg_match('/INV-(\d+)/', $last_inv, $matches)) {
                        $next_num = intval($matches[1]) + 1;
                        $next_inv_number = 'INV-' . $org_short_code . '-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
                    }
                }
            } catch (Exception $e) {
                // Table might not exist yet
            }
            ?>

            <!-- Create Invoice Form -->
            <div class="card d-none" id="add_invoice_form">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title" id="form_title">New Tax Invoice</h5>
                </div>

                <div class="card-body">
                    <form id="invoice_form" action="<?= $basePath ?>/controller/billing/save_invoice.php" method="post" novalidate>
                        
                        <!-- Fetch Proforma Section (Unique to Tax Invoice) -->
                        <div class="row mb-4">
                            <div class="col-md-5">
                                <label class="form-label">Link Proforma (Optional)</label>
                                <div class="input-group has-validation">
                                    <input type="text" id="proforma_fetch_no" class="form-control" placeholder="Enter Proforma # (e.g. PRO-ORG-0001)">
                                    <button class="btn btn-outline-primary" type="button" onclick="fetchProforma()">Fetch Data</button>
                                    <div id="proforma_fetch_error" class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Header Details -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-5">
                                <label class="form-label">Customer <span class="text-danger">*</span></label>
                                <select name="customer_id" id="customer_id" class="form-select"></select>
                                <input type="hidden" name="proforma_invoice_id" id="proforma_invoice_id" value="">
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-2">
                                <label class="form-label">Invoice # <span class="text-danger">*</span></label>
                                <input type="text" name="invoice_number" class="form-control" required value="<?= $next_inv_number ?>" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Reference</label>
                                <input type="text" name="reference_no" id="reference_no" class="form-control">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="invoice_date" id="invoice_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Payment Terms</label>
                                <select name="payment_terms" id="payment_terms" class="form-select">
                                    <option value="Due on Receipt">Due on Receipt</option>
                                    <option value="Net 15">Net 15</option>
                                    <option value="Net 30">Net 30</option>
                                    <option value="Immediate">Immediate</option>
                                    <option value="Advance">Advance</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Sales Person <span class="text-danger">*</span></label>
                                <select name="sales_employee_id" id="sales_employee_id" class="form-select"></select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ref. Customer</label>
                                <select name="reference_customer_id" id="reference_customer_id_select" class="form-select"></select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Delivery Mode</label>
                                <input type="text" name="delivery_mode" id="delivery_mode" class="form-control" placeholder="Delivery Mode">
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-sm align-middle" id="items_table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 35%">Item</th>
                                        <th style="width: 9%; text-align:center;">HSN</th>
                                        <th style="width: 7%; text-align:center;">Unit</th>
                                        <th style="width: 7%; text-align:center;">Qty</th>
                                        <th style="width: 9%; text-align:center;">Discount</th>
                                        <th style="width: 6%; text-align:center;">GST %</th>
                                        <th style="width: 9%; text-align:right;">Rate</th>
                                        <th style="width: 9%; text-align:right;">Taxable</th>
                                        <th style="width: 11%; text-align:right;">Total</th>
                                        <th style="width: 5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="items_body">
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-sm btn-soft-primary" onclick="addItemRow()">+ Add Item</button>
                            
                        </div>

                        <!-- Footer Totals -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="3" placeholder="Notes..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Terms & Conditions</label>
                                    <textarea name="terms_conditions" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="fw-semibold">Gross Amount</span>
                                            <span class="fw-bold text-primary" id="sub_total_display">₹0.00</span>
                                            <input type="hidden" name="sub_total" id="sub_total">
                                        </div>
                                        <!-- Taxable -->
                                         <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-semibold">Taxable Value</span>
                                            <span class="fw-bold text-primary" id="taxable_value_display">₹0.00</span>
                                        </div>
                                        <!-- Discount -->
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-semibold">Discount</span>
                                            <span class="fw-bold text-primary" id="discount_display">₹0.00</span>
                                            <input type="hidden" name="discount_value" id="discount_value">
                                            <input type="hidden" name="discount_type" value="amount">
                                        </div>

                                        <!-- Summary Fields -->
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-semibold">Total Tax</span>
                                            <span class="fw-bold text-dark" id="total_tax_display">₹0.00</span>
                                            <input type="hidden" name="total_tax" id="total_tax">
                                        </div>
                                        <input type="hidden" name="cgst_amount" id="cgst_amount">
                                        <input type="hidden" name="sgst_amount" id="sgst_amount">
                                        <input type="hidden" name="igst_amount" id="igst_amount">

                                        <!-- GST Type Selection -->
                                        <!-- GST Type Selection (Hidden as per request) -->
                                        <div class="row mb-2 align-items-center d-none">
                                            <div class="col-4">
                                                <label class="form-label small mb-0 fw-semibold">GST Type</label>
                                            </div>
                                            <div class="col-8">
                                                 <select name="gst_type" id="gst_type" class="form-select form-select-sm" onchange="calculateTotal()">
                                                    <option value="CGST_SGST">CGST/SGST (Intra-State)</option>
                                                    <option value="IGST">IGST (Inter-State)</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Tax Breakdown -->
                                        <div id="tax_breakdown_section">
                                            <div id="cgst_sgst_section">
                                                 <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-semibold">C.G.S.T</span>
                                                    <span class="fw-bold text-primary" id="cgst_display">₹0.00</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="fw-semibold">S.G.S.T</span>
                                                    <span class="fw-bold text-primary" id="sgst_display">₹0.00</span>
                                                </div>
                                            </div>
                                            <div id="igst_section" class="d-none">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="fw-semibold">I.G.S.T</span>
                                                    <span class="fw-bold text-primary" id="igst_display">₹0.00</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Adjustment -->
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-semibold">Round Off</span>
                                            <div class="input-group input-group-sm" style="width: 120px;">
                                                <input type="number" name="adjustment" id="adjustment" class="form-control text-end" value="0" step="0.01" oninput="calculateTotal()">
                                            </div>
                                        </div>

                                        <!-- Redemption -->
                                        <div class="row mb-2 align-items-center" id="redemption_section" style="display:none;">
                                            <div class="col-12">
                                                <div class="form-check text-end">
                                                    <label class="form-check-label small" for="redeem_points_check">
                                                        Redeem Points? (Avail: ₹<span id="available_points_display" class="fw-bold">0</span>)
                                                    </label>
                                                    <input class="form-check-input float-none ms-2" type="checkbox" id="redeem_points_check" onchange="toggleRedemption()">
                                                </div>
                                            </div>
                                            <div class="col-12 mt-1 d-none" id="redeem_input_group">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                     <span class="fw-semibold text-success">Redemption</span>
                                                     <div class="d-flex align-items-center justify-content-end">
                                                         <input type="text" inputmode="numeric" name="redeemed_points" id="redeemed_points" 
                                                             class="form-control-plaintext form-control-sm text-end p-0 text-success fw-bold shadow-none" 
                                                             value="0" style="width: 80px;" 
                                                             onfocus="this.value = this.value.replace(/[^0-9.]/g, '')" 
                                                             onblur="this.value = '₹' + this.value.replace(/[^0-9.]/g, '')">
                                                         
                                                         <input type="hidden" name="redemption_amount" id="redemption_amount" value="0">
                                                          <input type="hidden" id="max_loyalty_points" value="0">
                                                     </div>
                                                </div>
                                            </div>
                                        </div>

                                        <hr>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong class="fs-5">Net Amount</strong>
                                            <strong class="fs-5" id="total_amount_display" class="text-primary">₹0.00</strong>
                                            <input type="hidden" name="total_amount" id="total_amount">
                                        </div>
                                        
                                        <!-- Loyalty Slabs (Hidden) -->
                                        <div class="mt-3" id="loyalty_slab_container">
                                              <!-- ... (Slab rendering handled by JS) ... -->
                                              <input type="hidden" name="loyalty_slab_id" id="loyalty_slab_id" value="0">
                                              <div id="active_slab_display" class="alert alert-light border-dashed d-none p-2 mb-0">
                                                  <div class="d-flex justify-content-between align-items-center">
                                                      <div>
                                                          <div class="fw-bold text-success" id="slab_name">Slab</div>
                                                          <div class="small text-muted" id="slab_range"></div>
                                                      </div>
                                                      <div class="text-end">
                                                          <!-- Rate Display -->
                                                          <div class="text-muted small">Rate: ₹<span id="slab_rate">0</span> / ₹100</div>
                                                          <!-- Calculated Earned Points -->
                                                          <div class="fw-bold text-primary fs-5">+ ₹<span id="slab_earn_amount">0</span></div>
                                                          <div class="small text-muted" style="font-size: 10px;">Val: <span id="slab_validity">0</span> days</div>
                                                      </div>
                                                  </div>
                                              </div>
                                              <div id="no_slab_display" class="d-none"></div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="mt-4 text-end">
                            <button type="button" id="cancel_btn" class="btn btn-secondary me-2">Cancel</button>
                            <button type="button" name="save_invoice" value="sent" class="btn btn-primary" id="save_invoice_btn">Create Invoice</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- List View -->
            <div class="card" id="invoice_list_card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Tax Invoices</h5>
                    <div>
                        <button type="button" id="add_invoice_btn" class="btn btn-primary btn-sm">Create New Invoice</button>
                    </div>
                </div>
                <div class="card-body">
                    <table data-tables="basic" id="invoice_table" class="table table-hover table-striped mb-0 w-100 dt-responsive" style="width: 100%;">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Check if table exists first
                            $checkTable = $conn->query("SHOW TABLES LIKE 'sales_invoices'");
                            if($checkTable && $checkTable->num_rows > 0) {
                                // Updated Query to fetch Credit Note ID if exists and calculate total from items
                                $sql = "SELECT inv.*, c.customer_name, c.company_name, c.avatar, cn.credit_note_id as cn_id,
                                        (SELECT COALESCE(SUM(total_amount), 0) 
                                         FROM sales_invoice_items sii 
                                         WHERE sii.invoice_id = inv.invoice_id) + 
                                        COALESCE(inv.adjustment, 0) as calculated_total
                                        FROM sales_invoices inv 
                                        LEFT JOIN customers_listing c ON inv.customer_id = c.customer_id 
                                        LEFT JOIN credit_notes cn ON inv.invoice_id = cn.invoice_id AND cn.status != 'cancelled'
                                        WHERE 1=1 ORDER BY inv.invoice_id DESC";

                                $result = $conn->query($sql);

                                if($result && $result->num_rows > 0){
                                    while($row = $result->fetch_assoc()){
                                        $statusClr = match($row['status']) {
                                            'draft' => 'secondary',
                                            'sent' => 'primary',
                                            'paid' => 'success',
                                            'cancelled' => 'danger',
                                            'overdue' => 'warning',
                                            'refunded' => 'info',
                                            'approved' => 'warning',
                                            default => 'light'
                                        };
                                        ?>
                                        <tr>
                                            <td><?= date('d M Y', strtotime($row['invoice_date'])) ?></td>
                                            <td><a href="#" class="fw-bold text-dark"><?= $row['invoice_number'] ?></a></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if(!empty($row['avatar'])): ?>
                                                        <img src="<?= $basePath ?>/<?= $row['avatar'] ?>" class="rounded-circle me-2" width="32" height="32" style="object-fit:cover;">
                                                    <?php else: ?>
                                                        <div class="rounded-circle bg-light text-primary d-flex align-items-center justify-content-center me-2 fw-bold" style="width:32px; height:32px;">
                                                            <?= strtoupper(substr($row['company_name'] ?: $row['customer_name'], 0, 1)) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <?= $row['company_name'] ? ucwords(strtolower($row['company_name'])) . ' <br><small class="text-muted">' . ucwords(strtolower($row['customer_name'])) . '</small>' : ucwords(strtolower($row['customer_name'])) ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-<?= $statusClr ?>"><?= ucfirst($row['status']) ?></span></td>
                                            <td><?= number_format($row['calculated_total'], 2) ?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">Action</button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="<?= $basePath ?>/view_invoice?id=<?= $row['invoice_id'] ?>"><i class="ti ti-eye me-2"></i> View</a></li>
                                                        <li><a class="dropdown-item" href="<?= $basePath ?>/controller/billing/download_invoice_pdf.php?id=<?= $row['invoice_id'] ?>"><i class="ti ti-download me-2"></i> Download PDF</a></li>
                                                        <?php if(!empty($row['cn_id'])): ?>
                                                            <li><a class="dropdown-item text-primary" href="<?= $basePath ?>/credit_note_view?id=<?= $row['cn_id'] ?>"><i class="ti ti-file-check me-2"></i> View Credit Note</a></li>
                                                            <li><a class="dropdown-item" href="<?= $basePath ?>/controller/billing/download_credit_note_pdf.php?id=<?= $row['cn_id'] ?>"><i class="ti ti-download me-2"></i> Credit Note PDF</a></li>
                                                        <?php else: ?>
                                                            <li><a class="dropdown-item" href="<?= $basePath ?>/credit_note?id=<?= $row['invoice_id'] ?>"><i class="ti ti-plus me-2"></i> Credit Note</a></li>
                                                        <?php endif; ?>
                                                        
                                                        <?php if($row['status'] == 'draft'): ?>
                                                            <li><a class="dropdown-item" href="#"><i class="ti ti-edit me-2"></i> Edit</a></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
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
                                        <div class="col-md-4">
                                            <label class="form-label">City</label>
                                            <input type="text" name="city" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">State</label>
                                            <input type="text" name="state" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Pincode</label>
                                            <input type="text" name="pincode" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal"> Cancel </button>
                        <button type="submit" id="save_customer_btn" class="btn btn-primary"> Save Customer </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ITEMS DATA -->
<?php
// Pre-fetch items
// Fetch items for the dropdown
$itemsData = [];
try {
    $iSql = "SELECT i.item_id, i.item_name, i.selling_price, i.stock_keeping_unit, i.current_stock, u.unit_name, h.hsn_code, h.gst_rate 
             FROM items_listing i 
             LEFT JOIN units_listing u ON i.unit_id = u.unit_id
             LEFT JOIN hsn_listing h ON i.hsn_id = h.hsn_id"; 
    $iRes = $conn->query($iSql);
    if($iRes){
        while($item = $iRes->fetch_assoc()){
            $itemsData[] = $item;
        }
    }
} catch (Exception $e) {
    // Tables might not exist yet
    $itemsData = [];
}

// Fetch Loyalty Slabs
$slabsData = [];
try {
    // Check if table exists
    $checkSlab = $conn->query("SHOW TABLES LIKE 'loyalty_point_slabs'");
    if($checkSlab && $checkSlab->num_rows > 0) {
        $today = date('Y-m-d');
        $sSql = "SELECT * FROM loyalty_point_slabs 
                 WHERE organization_id = {$_SESSION['organization_id']} 
                 AND '$today' BETWEEN applicable_from_date AND applicable_to_date
                 ORDER BY from_sale_amount ASC";
        $sRes = $conn->query($sSql);
        if($sRes){
            while($row = $sRes->fetch_assoc()){
                $slabsData[] = $row;
            }
        }
    }
} catch (Exception $e) {
    // Slabs might not exist
}
?>

<?php ob_start(); ?>
<script>
console.log('Tax Invoice Script Loading...');

/* GLOBAL DATA */
const itemsList = <?= json_encode($itemsData) ?>;
const basePath = '<?= $basePath ?>';
const loyaltySlabs = <?= json_encode($slabsData) ?>; // Populated from DB
<?php 
    $currEmpId = 0; $currEmpName = "";
    // Ensure $currentUser is available (from access_guard)
    if(isset($currentUser) && is_array($currentUser)){
        $currEmpId = intval($currentUser['employee_id']);
        $currEmpName = trim(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? ''));
    }
?>
const CURRENT_USER_ID = <?= $currEmpId ?>;
const CURRENT_USER_NAME = "<?= addslashes($currEmpName) ?>";

/* UI TOGGLE */
function toggleView(showForm){
    if(showForm){
        $('#add_invoice_form').removeClass('d-none');
        $('#invoice_list_card').addClass('d-none');
    } else {
        $('#add_invoice_form').addClass('d-none');
        $('#invoice_list_card').removeClass('d-none');
    }
}


function resetForm() {
    $('#invoice_form')[0].reset();
    $('#proforma_invoice_id').val('');
    $('#items_body').empty();
    $('#customer_id').empty().trigger('change');
    $('#sales_employee_id').val('').trigger('change');
    $('#reference_customer_id_select').val('').trigger('change');
    document.querySelector('input[name="invoice_date"]').value = '<?= date('Y-m-d') ?>';
    document.getElementById('adjustment').value = '0';
    document.getElementById('redeemed_points').value = '0';
    calculateTotal();
}

// Wait for DOM and jQuery to be ready
$(document).ready(function() {
    console.log('Tax Invoice: jQuery ready, initializing...');
    
    const addBtn = document.getElementById('add_invoice_btn');
    console.log('Add button found:', addBtn);
    
    if(addBtn) {
        addBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('=== Create New Invoice clicked ===');
            
            // Check if form exists
            const form = document.getElementById('add_invoice_form');
            const listCard = document.getElementById('invoice_list_card');
            console.log('Form element:', form);
            console.log('List card element:', listCard);
            
            resetForm();
            isFromProforma = false; // Reset flag for manual invoice creation
            $('#form_title').text('New Tax Invoice');
            
            const saveBtn = document.getElementById('save_invoice_btn');
            if (saveBtn) {
                saveBtn.textContent = 'Create Invoice';
            }
            
            // Add one empty row
            console.log('Adding item row...');
            addItemRow();
            
            console.log('Toggling view...');
            toggleView(true);
            
            // Initialize Select2 components
            setTimeout(() => { 
                console.log('Initializing Select2 components...');
                initCustomerSelect2(); 
                initReferenceCustomerSelect2();
                initSalesPersonSelect2();
            }, 100);
        });
        console.log('Event listener attached to Create New Invoice button');
    } else {
        console.error('ERROR: add_invoice_btn not found in DOM!');
    }
    
    const cancelBtn = document.getElementById('cancel_btn');
    if(cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            console.log('Cancel clicked');
            toggleView(false);
            resetForm();
        });
        console.log('Event listener attached to Cancel button');
    }
});


/* AVATAR ERROR HELPER */
function handleAvatarError(imgEl, initial, w, h) {
    imgEl.onerror = null;
    var div = document.createElement('div');
    div.className = "rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center fw-bold me-2";
    div.style.width = w;
    div.style.height = h;
    div.style.fontSize = "10px"; 
    if(parseInt(h) > 25) div.style.fontSize = "14px"; 
    div.style.verticalAlign = "middle";
    div.style.objectFit = "cover";
    div.innerText = initial;
    if(imgEl.parentNode) imgEl.parentNode.replaceChild(div, imgEl);
}


 

/* SALES PERSON SELECT2 */
// Match with proforma_invoice.php style
function initSalesPersonSelect2() {
    let $sel = $('#sales_employee_id');

    if ($sel.hasClass("select2-hidden-accessible")) {
        return; // Already initialized, prevent duplicates
    }

    $sel.select2({
        placeholder: "Search Sales Person...",
        width: "100%",
        allowClear: true,
        dropdownParent: $('body'), 
        ajax: {
            url: basePath + "/controller/billing/search_employees.php",
            type: "GET",
            dataType: "json",
            delay: 200,
            data: function (params) {
                return { q: params.term || "" };
            },
            processResults: function (data) {
                return { results: data };
            }
        },

        templateResult: function (emp) {
            if (emp.loading) return emp.text;
            if (!emp.id) return emp.text;

            // Fallback for manually added options
            let data = emp;
            if (emp.element) {
                const $el = $(emp.element);
                data = {
                    ...emp,
                    text: emp.text || $el.text(),
                    avatar: emp.avatar || $el.data('avatar'),
                    employee_code: emp.employee_code || $el.data('code'), // Note: Tax Invoice JS uses data-code
                    designation: emp.designation || $el.data('designation'),
                    email: emp.email || $el.data('email'),
                    phone: emp.phone || $el.data('phone')
                };
            }

            let fallbackHtml = `<div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="width:32px;height:32px;">${(data.text || '?').charAt(0)}</div>`;
            
            let invalid = !data.avatar || data.avatar === 'null' || data.avatar === 'undefined' || data.avatar.trim() === '';
            
            let img = invalid
                ? fallbackHtml
                : `<img src="${basePath}/${data.avatar}" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;" onerror="handleAvatarError(this, '${(data.text||'?').charAt(0)}', '32px', '32px')">`;

            let designation = data.designation ? ` - ${data.designation}` : '';

            // Rich Card Design
            return $(`
                <div class="d-flex align-items-center gap-2 py-1">
                    ${img}
                    <div class="flex-grow-1">
                        <div class="fw-semibold text-dark lh-sm">
                            ${data.text} <span class="small text-muted fw-normal">(${data.employee_code || ''}${designation})</span>
                        </div>
                        <div class="d-flex gap-3 small text-muted mt-1">
                            ${data.email ? `<span><i class="ti ti-mail me-1"></i>${data.email}</span>` : ''}
                            ${data.phone ? `<span><i class="ti ti-phone me-1"></i>${data.phone}</span>` : ''}
                        </div>
                    </div>
                </div>
            `);
        },

        templateSelection: function (emp) {
            if (!emp.id) return emp.text || "Select Sales Person";
            
            // Fallback for manually added options (Edit Mode / Fetch)
            let data = emp;
            if (emp.element) {
                const $el = $(emp.element);
                data = {
                    ...emp,
                    text: emp.text || $el.text(),
                    avatar: emp.avatar || $el.data('avatar'),
                    employee_code: emp.employee_code || $el.data('code')
                };
            }

            let fallbackHtml = `<div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center fw-bold me-2" style="width:20px;height:20px;font-size:10px;vertical-align:middle;">${(data.text||'?').charAt(0)}</div>`;
            
            let invalid = !data.avatar || data.avatar === 'null' || data.avatar === 'undefined' || data.avatar.trim() === '';
            
            let img = invalid
                ? fallbackHtml
                : `<img src="${basePath}/${data.avatar}" class="rounded-circle me-2" style="width:20px;height:20px;object-fit:cover;vertical-align:middle;" onerror="handleAvatarError(this, '${(data.text||'?').charAt(0)}', '20px', '20px')">`;

            return $(`<span class="d-flex align-items-center">${img}${data.text}</span>`);
        }
    });
}

/* CUSTOMER SELECT2 */
function initCustomerSelect2() {
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
        dropdownParent: $('#add_invoice_form'),
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
                    text: c.customer_name, // Default text for selection
                    display_name: c.display_name,
                    customer_name: c.customer_name || c.text, // fallback
                    company_name: c.company_name || '',
                    email: c.email || '',
                    avatar: c.avatar || '',
                    customer_code: c.customer_code || '',
                    customers_type_name: c.customers_type_name || '',
                    loyalty_point_balance: c.loyalty_point_balance || 0,
                    state_code: c.state_code || ''
                }));

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
            if (customer.isNew) {
                return `<div class="text-primary fw-bold p-2 border-top"><i class="ti ti-plus me-1"></i> New Customer</div>`;
            }

            let letter = (customer.customer_name || customer.display_name || customer.text || '').charAt(0).toUpperCase();

            let avatarHtml = '';
            if(customer.avatar && customer.avatar.trim() !== ''){
                avatarHtml = `<img src="${basePath}/${customer.avatar}" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">`;
            } else {
                avatarHtml = `<div class="customer-avatar rounded-circle bg-light text-dark d-flex align-items-center justify-content-center fw-bold"
                         style="width:32px;height:32px;min-width:32px;">
                        ${letter}
                    </div>`;
            }
            
            // Loyalty Badge Logic
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
            
            return $(`<span class="d-flex align-items-center">${avatarHtml}${name}</span>`);
        },
        escapeMarkup: m => m
    })
    .on('change', function(e) {
        // Unified Data Retrieval (Works for both AJAX and Manual/Proforma Fetch)
        const $select = $(this);
        const $option = $select.find(':selected');
        
        let data = {};
        
        // 1. Get Select2 Data (Primary for AJAX)
        const s2Data = $select.select2('data');
        if (s2Data && s2Data.length > 0) {
            data = { ...s2Data[0] };
        }
        
        // 2. Merge with Element Data (Primary for Manual/Proforma)
        if ($option.length > 0) {
            const elemData = $option.data();
            // Merge element data (prioritizing element data for manually set attributes)
            data = { ...data, ...elemData };
        }

        // Store state_code explicitly on the option for calculateTotal() if present in data
        if(data.state_code && $option.length > 0) {
           $option.data('state_code', data.state_code);
        }
        
        // Show/Update Loyalty Redemption Section
        const points = parseFloat(data.loyalty_point_balance) || 0;
        const redemptionSection = document.getElementById('redemption_section');
        const pointsDisplay = document.getElementById('available_points_display');
        
        if(redemptionSection && pointsDisplay) {
            if(points > 0) {
                redemptionSection.style.display = 'flex'; // Or 'block' based on layout, flex implies from class
                // actually existing class is "row mb-2 align-items-center" which is flex.
                // But inline style says display:none. So setting style.display = '' might revert to CSS class which is row (flex).
                // Let's set it to '' (empty) so class takes over.
                redemptionSection.style.display = ''; 
                pointsDisplay.innerText = points;
                
                // Store max points for validation
                const maxInput = document.getElementById('max_loyalty_points');
                if(maxInput) maxInput.value = points;
                
            } else {
                redemptionSection.style.display = 'none';
                pointsDisplay.innerText = '0';
                // Uncheck if checked
                const chk = document.getElementById('redeem_points_check');
                if(chk && chk.checked) {
                    chk.checked = false;
                    toggleRedemption(); // Trigger hide of input
                }
            }
        }
        
        calculateTotal();
    });
    
    // Add validation to prevent same customer selection
    validateCustomerSelection();
}
function initReferenceCustomerSelect2() {
    const $customer = $('#reference_customer_id_select');

    if ($customer.hasClass('select2-hidden-accessible')) {
        $customer.select2('destroy');
        $customer.empty();
    }

    $customer.select2({
        placeholder: 'Search Reference Customer...',
        width: '100%',
        dropdownCssClass: 'customer-select2-dropdown',
        dropdownParent: $('#add_invoice_form'),
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: basePath + '/controller/billing/search_customers_listing.php',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term || '' }; },
            processResults: function (data) {
                if (!Array.isArray(data)) data = [];
                // Identical mapping to main customer select, but excluding 'New Customer'
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
                    loyalty_point_balance: c.loyalty_point_balance || 0
                }));
                return { results };
            }
        },
        templateResult: function (customer) {
            if (customer.loading) return customer.text;

            let letter = (customer.customer_name || customer.display_name || customer.text || '').charAt(0).toUpperCase();

            let avatarHtml = '';
            if(customer.avatar && customer.avatar.trim() !== ''){
                avatarHtml = `<img src="${basePath}/${customer.avatar}" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">`;
            } else {
                avatarHtml = `<div class="customer-avatar rounded-circle bg-white text-primary d-flex align-items-center justify-content-center fw-bold"
                         style="width:32px;height:32px;min-width:32px;">
                        ${letter}
                    </div>`;
            }
            
            // Loyalty Badge Logic
            let points = parseFloat(customer.loyalty_point_balance || 0);
            let pointsBadge = '';
            if(points > 0){
                pointsBadge = `<span class="badge bg-success-subtle text-success border border-success ms-auto"><i class="ti ti-gift me-1"></i>₹${points} </span>`;
            }

            return `
                <div class="d-flex align-items-center gap-2 py-1" style="width: 100%; overflow: hidden;">
                    <div style="flex-shrink: 0;">
                        ${avatarHtml}
                    </div>
                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="customer-text-primary fw-semibold lh-sm" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                ${customer.customer_name || customer.display_name} 
                                ${customer.customers_type_name ? `<span class="small text-muted fw-normal">(${customer.customer_code} - ${customer.customers_type_name})</span>` : ''}
                            </div>
                         
                        </div>
                        <div class="customer-text-primary small text-muted d-flex align-items-center gap-3 mt-1" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            ${customer.email ? `<span><i class="ti ti-mail me-1"></i>${customer.email}</span>` : ''}
                            ${customer.company_name ? `<span><i class="ti ti-building me-1"></i>${customer.company_name}</span>` : ''}
                        </div>
                    </div>
                </div>`;
        },
        templateSelection: function (customer) {
            if (!customer.id) return customer.text;
            
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
            
            return $(`<span class="d-flex align-items-center">${avatarHtml}${name}</span>`);
        },
        escapeMarkup: m => m
    });
    
    // Add validation: Reference Customer cannot be same as main Customer
    $customer.on('select2:select', function(e) {
        const refCustomerId = $('#reference_customer_id_select').val();
        const mainCustomerId = $('#customer_id').val();
        
        if (refCustomerId && mainCustomerId && refCustomerId === mainCustomerId) {
            alert('Reference Customer cannot be the same as the main Customer. Please select a different reference customer.');
            $('#reference_customer_id_select').val(null).trigger('change');
        }
    });
}

// Global flag to track if customer came from proforma
let isFromProforma = false;

// Add validation for main Customer selection too
function validateCustomerSelection() {
    $('#customer_id').on('select2:select', function(e) {
        const refCustomerId = $('#reference_customer_id_select').val();
        const mainCustomerId = $('#customer_id').val();
        
        // Always enable Reference Customer when a customer is selected
        $('#reference_customer_id_select').prop('disabled', false);
        // Remove disabled class from Select2 container
        $('#reference_customer_id_select').next('.select2-container').removeClass('select2-container--disabled');
        $('span[aria-labelledby="select2-reference_customer_id_select-container"]').attr('aria-disabled', 'false');
        
        if (refCustomerId && mainCustomerId && refCustomerId === mainCustomerId) {
            alert('Customer and Reference Customer cannot be the same. Clearing Reference Customer.');
            $('#reference_customer_id_select').val(null).trigger('change');
        }
    });
    
    // Disable Reference Customer when main Customer is cleared
    $('#customer_id').on('select2:clear select2:unselect', function(e) {
        $('#reference_customer_id_select').prop('disabled', true);
        $('#reference_customer_id_select').val(null).trigger('change');
        // Add disabled class to Select2 container
        $('#reference_customer_id_select').next('.select2-container').addClass('select2-container--disabled');
        $('span[aria-labelledby="select2-reference_customer_id_select-container"]').attr('aria-disabled', 'true');
        isFromProforma = false; // Reset flag when customer is cleared
    });
}

/* -------------------------------------------------------------------------- */
/*                               ITEM LOGIC                                   */
/* -------------------------------------------------------------------------- */

function addItemRow(data = null) {
    const tbody = document.getElementById('items_body');
    const index = tbody.children.length;
    const row = document.createElement('tr');
    
    // Find item to get default tax rate
    let defaultTaxRate = 0;
    if(data && data.item_id){
         const existingItem = itemsList.find(i => i.item_id == data.item_id);
         if(existingItem) defaultTaxRate = existingItem.gst_rate || 0;
    }

    let options = '<option value="">Select Item</option>';
    itemsList.forEach(item => {
        const rate = item.selling_price;
        const selected = (data && data.item_id == item.item_id) ? 'selected' : '';
        options += `<option value="${item.item_id}" 
                            ${selected}
                            data-rate="${rate}" 
                            data-unit="${item.unit_name||''}" 
                            data-sku="${item.stock_keeping_unit||''}"
                            data-gst="${item.gst_rate||0}"
                            data-stock="${item.current_stock||0}"
                            data-hsn="${item.hsn_code||''}">${item.item_name}</option>`;
    });

    const qty = data ? data.quantity : 1;
    const rate = data ? data.rate : 0;
    const disc = data ? data.discount : 0;
    const discType = data ? data.discount_type : 'amount';
    const unit = data ? data.unit_name : '';
    const hsn = data ? data.hsn_code : '';
    const taxRate = data && data.tax_rate !== undefined ? data.tax_rate : defaultTaxRate;

    row.innerHTML = `
        <td><select name="items[${index}][item_id]" class="form-select form-select-sm item-select" required onchange="onItemSelect(this)">${options}</select></td>
        <td><input type="text" class="form-control-plaintext form-control-sm hsn-display text-center" value="${hsn}" readonly></td>
        <td><input type="text" class="form-control-plaintext form-control-sm unit-display text-center" value="${unit}" readonly></td>
        <td><input type="number" name="items[${index}][quantity]" class="form-control form-control-sm qty-input text-center" value="${qty}" min="1" oninput="calculateRow(this)"></td>
        
        <!-- Discount -->
         <td>
             <div class="input-group input-group-sm">
                 <input type="number" name="items[${index}][discount]" class="form-control discount-input text-center px-1" value="${disc}" step="0.01" oninput="calculateRow(this)">
                 <select name="items[${index}][discount_type]" class="form-select text-center p-0 discount-type-select shadow-none" onchange="calculateRow(this)" style="max-width: 40px; background-image: none !important; padding-right: 0 !important; cursor: pointer;">
                    <option value="amount" ${discType=='amount'?'selected':''}>₹</option>
                    <option value="percentage" ${discType=='percentage'?'selected':''}>%</option>
                 </select>
            </div>
        </td>
        
        <!-- GST Rate -->
        <td>
             <input type="text" name="items[${index}][tax_rate]" class="form-control-plaintext form-control-sm tax-rate-input text-center" value="${taxRate}" readonly>
        </td>
        
        <!-- Rate (Allocated to Rate Col) -->
        <td><input type="number" name="items[${index}][rate]" class="form-control form-control-sm rate-input text-end" value="${rate}" step="0.01" oninput="calculateRow(this)"></td>

        <td>
            <input type="text" readonly class="form-control-plaintext form-control-sm taxable-display text-end" value="0.00">
            <input type="hidden" name="items[${index}][amount]" class="taxable-input" value="0">
        </td>
        <td>
             <input type="text" readonly class="form-control-plaintext form-control-sm total-display text-end fw-bold" value="0.00">
             <input type="hidden" name="items[${index}][total_amount]" class="amount-input" value="0">
             <input type="hidden" class="row-tax-amount" value="0">
        </td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-ghost-danger p-1" onclick="removeRow(this)"><i class="ti ti-trash"></i></button></td>
    `;
    tbody.appendChild(row);
    
    // Init Select2 for this row
    $(row.querySelector('.item-select')).select2({
        width: '100%',
        dropdownParent: $('#add_invoice_form'),
        templateResult: function(item) {
            if (!item.id) return item.text;
            const element = $(item.element);
            const sku = element.data('sku');
            const stock = element.data('stock');
            const unit_name = element.data('unit');
            const rate = parseFloat(element.data('rate') || 0).toFixed(2);
            const gst = element.data('gst') || 0;
            
            const stockVal = parseFloat(stock);
            const stockClass = stockVal > 0 ? 'text-success' : 'text-danger';
            
            return $(`
                <div class="d-flex justify-content-between align-items-center py-1">
                    <div class="text-dark">
                        <div class="fw-bold">${item.text}</div>
                        <small>SKU: ${sku || '--'} | Rate: ₹${rate} | GST: ${gst}%</small>
                    </div>
                    <div class="text-end text-dark">
                        <span class="badge text-dark mb-1">Stock in Hand</span><br>
                        <small class="${stockClass} fw-bold">${stock} ${unit_name || ''}</small>
                    </div>
                </div>
            `);
        },
        templateSelection: function(item) { return item.text; }
    });
    
    // Always calculate row to populate derived fields (taxable, tax amt, total)
    calculateRow(row.querySelector('.qty-input'));
}

function onItemSelect(select){
    const option = select.options[select.selectedIndex];
    const rate = option.getAttribute('data-rate');
    const unit = option.getAttribute('data-unit');
    const hsn = option.getAttribute('data-hsn');
    const gst = option.getAttribute('data-gst');
    const stock = parseFloat(option.getAttribute('data-stock')) || 0;
    const row = select.closest('tr');

    if(rate){
        row.querySelector('.rate-input').value = rate;
        row.querySelector('.unit-display').value = unit || '-';
        row.querySelector('.hsn-display').value = hsn || '-';
        row.querySelector('.tax-rate-input').value = gst || 0;
        
        // Set Max Stock on Qty Input
        const qtyInput = row.querySelector('.qty-input');
        qtyInput.setAttribute('data-max-stock', stock);
        qtyInput.setAttribute('max', stock); // Optional HTML5 validation
        
        // Initial Validation
        if(stock <= 0) {
             alert('Warning: This item is out of stock!');
             qtyInput.value = 0;
        } else {
             qtyInput.value = 1; // Default to 1 if available
        }

        calculateRow(select);
    }
}

function calculateRow(input){
    const row = input.closest('tr');
    if(!row) return;

    let qtyInput = row.querySelector('.qty-input');
    let qty = parseFloat(qtyInput.value) || 0;
    
    // Stock Validation
    const maxStock = parseFloat(qtyInput.getAttribute('data-max-stock'));
    // Check if maxStock is a valid number (it might be NaN if item not selected yet, though onItemSelect handles it)
    if (!isNaN(maxStock)) {
        if (maxStock <= 0) {
            if (qty > 0) {
                alert("Item is out of stock!");
                 qty = 0;
                 qtyInput.value = 0;
            }
        } else if (qty > maxStock) {
            alert("Quantity cannot exceed available stock (" + maxStock + ")");
            qty = maxStock;
            qtyInput.value = maxStock;
        }
    }

    const rate = parseFloat(row.querySelector('.rate-input').value) || 0;
    const discountInput = parseFloat(row.querySelector('.discount-input').value) || 0;
    const discountType = row.querySelector('.discount-type-select').value;
    const taxRate = parseFloat(row.querySelector('.tax-rate-input').value) || 0;
    
    let baseAmount = qty * rate;
    let discount = 0;

    if(discountType === 'percentage'){
        discount = baseAmount * (discountInput / 100);
    } else {
        discount = discountInput;
    }
    
    let taxable = baseAmount - discount;
    if(taxable < 0) taxable = 0;
    
    // Tax Calculation
    let taxAmt = taxable * (taxRate / 100);
    let total = taxable + taxAmt;

    row.querySelector('.taxable-display').value = taxable.toFixed(2);
    row.querySelector('.taxable-input').value = taxable.toFixed(2);
    row.querySelector('.row-tax-amount').value = taxAmt.toFixed(2);
    
    // Total Amount Display
    row.querySelector('.total-display').value = '₹' + total.toFixed(2);
    row.querySelector('.amount-input').value = total.toFixed(2);
    
    calculateTotal();
}

function removeRow(btn){
    btn.closest('tr').remove();
    calculateTotal();
}

function calculateTotal(){
    let grossAmount = 0;  // Sum of (Qty * Rate)
    let totalDiscount = 0; // Sum of discounts
    let subTotal = 0;      // Sum of Taxable Values (Base - Discount)
    let totalTax = 0;      // Sum of Tax Amounts

    // 1. Accumulate values from rows
    document.querySelectorAll('#items_body tr').forEach((row) => {
        const qty = parseFloat(row.querySelector('.qty-input')?.value) || 0;
        const rate = parseFloat(row.querySelector('.rate-input')?.value) || 0;
        const discountInput = parseFloat(row.querySelector('.discount-input')?.value) || 0;
        const discountType = row.querySelector('.discount-type-select')?.value || 'amount';
        
        // Base
        let baseAmount = qty * rate;
        
        // Discount
        let discount = 0;
        if(discountType === 'percentage'){
            discount = baseAmount * (discountInput / 100);
        } else {
            discount = discountInput;
        }
        
        // From hidden inputs (which are computed in calculateRow)
        const taxableVal = parseFloat(row.querySelector('.taxable-input')?.value) || 0;
        // Fix: Use the input with class 'row-tax-amount' inside the current row
        const taxVal = parseFloat(row.querySelector('.row-tax-amount')?.value) || 0;
        
        grossAmount += baseAmount;
        totalDiscount += discount;
        subTotal += taxableVal;
        totalTax += taxVal;
    });

    // 2. Update UI Displays
    
    // Gross Amount (Sub Total in UI Label)
    if(document.getElementById('sub_total_display')) {
        document.getElementById('sub_total_display').innerText = '₹' + grossAmount.toFixed(2);
    }
    if(document.getElementById('sub_total')) {
        document.getElementById('sub_total').value = grossAmount.toFixed(2);
    }

    // Discounts
    if(document.getElementById('discount_display')) {
        document.getElementById('discount_display').innerText = '₹' + totalDiscount.toFixed(2);
    }
    if(document.getElementById('discount_amt_display')) {
        document.getElementById('discount_amt_display').innerText = '₹' + totalDiscount.toFixed(2);
    }
    if(document.getElementById('discount_value')) {
        document.getElementById('discount_value').value = totalDiscount.toFixed(2);
    }

    // Taxable Value
    if(document.getElementById('taxable_value_display')) {
        document.getElementById('taxable_value_display').innerText = '₹' + subTotal.toFixed(2);
    }
    if(document.getElementById('sub_total_taxable_display')) {
        document.getElementById('sub_total_taxable_display').innerText = '₹' + subTotal.toFixed(2);
    }
    if(document.getElementById('sub_total_taxable')) {
        document.getElementById('sub_total_taxable').value = subTotal.toFixed(2);
    }

    // Taxes
    if(document.getElementById('total_tax_display')) {
        document.getElementById('total_tax_display').innerText = '₹' + totalTax.toFixed(2);
    }
    if(document.getElementById('total_tax')) {
        document.getElementById('total_tax').value = totalTax.toFixed(2);
    }

    // 3. Automatic GST Type Detection
    const customerSelect = document.getElementById('customer_id');
    let customerStateCode = '';
    
    // Try to get from Select2 data
    if ($('#customer_id').hasClass("select2-hidden-accessible")) {
        // First check selected option data
        const selectedOption = $('#customer_id').find(':selected');
        if(selectedOption.length > 0) {
            customerStateCode = selectedOption.data('state_code');
        }
        // Fallback to Select2 data object
        if(!customerStateCode) {
            const data = $('#customer_id').select2('data')[0];
            if(data && data.state_code) customerStateCode = data.state_code;
        }
    }
    
    // Organization State Code (injected from PHP session)
    const orgStateCode = '<?= $_SESSION["organization_state_code"] ?? "" ?>';
    
    let gstType = 'CGST_SGST'; // Default
    const isInterstate = customerStateCode && orgStateCode && String(customerStateCode).toUpperCase() !== String(orgStateCode).toUpperCase();
    
    if(isInterstate) {
        gstType = 'IGST';
    }
    
    // Update Dropdown
    const gstSelect = document.getElementById('gst_type');
    if(gstSelect && gstSelect.value !== gstType) {
        gstSelect.value = gstType;
        // Optionally trigger change if other listeners exist, though we are handling logic here
    }

    // Calculate Split
    let cgstAmount = 0;
    let sgstAmount = 0;
    let igstAmount = 0;

    if(gstType === 'IGST') {
        igstAmount = totalTax;
        
        // Show IGST, Hide CGST/SGST
        if(document.getElementById('igst_section')) document.getElementById('igst_section').classList.remove('d-none');
        if(document.getElementById('cgst_sgst_section')) document.getElementById('cgst_sgst_section').classList.add('d-none');
        
        if(document.getElementById('igst_display')) document.getElementById('igst_display').innerText = '₹' + igstAmount.toFixed(2);
    } else {
        cgstAmount = totalTax / 2;
        sgstAmount = totalTax / 2;
        
        // Show CGST/SGST, Hide IGST
        if(document.getElementById('cgst_sgst_section')) document.getElementById('cgst_sgst_section').classList.remove('d-none');
        if(document.getElementById('igst_section')) document.getElementById('igst_section').classList.add('d-none');
        
        if(document.getElementById('cgst_display')) document.getElementById('cgst_display').innerText = '₹' + cgstAmount.toFixed(2);
        if(document.getElementById('sgst_display')) document.getElementById('sgst_display').innerText = '₹' + sgstAmount.toFixed(2);
    }

    // Update Hidden Tax Fields
    if(document.getElementById('cgst_amount')) document.getElementById('cgst_amount').value = cgstAmount.toFixed(2);
    if(document.getElementById('sgst_amount')) document.getElementById('sgst_amount').value = sgstAmount.toFixed(2);
    if(document.getElementById('igst_amount')) document.getElementById('igst_amount').value = igstAmount.toFixed(2);


    // 4. Grand Total Calculation
    // Read manual adjustment input instead of auto-calculating
    let manualAdjustment = parseFloat(document.getElementById('adjustment')?.value) || 0;
    
    // Redemption
    let redemptionAmount = 0;
    if(document.getElementById('redeem_points_check') && document.getElementById('redeem_points_check').checked){
         redemptionAmount = parseFloat(document.getElementById('redemption_amount')?.value) || 0;
    }
    
    const rawTotal = subTotal + totalTax - redemptionAmount;
    const finalTotal = rawTotal + manualAdjustment;
    
    // Prevent negative total - set minimum to 0
    const displayTotal = Math.max(0, finalTotal);
    
    if(document.getElementById('total_amount_display')) document.getElementById('total_amount_display').innerText = '₹' + displayTotal.toFixed(2);
    if(document.getElementById('total_amount')) document.getElementById('total_amount').value = displayTotal.toFixed(2);
    
    // Loyalty Slab Update (Based on Taxable Value)
    if(typeof updateLoyaltySlabDisplay === 'function') {
        updateLoyaltySlabDisplay(subTotal);
    }
}

// Main handling for "Create Invoice" button click
document.getElementById('save_invoice_btn').addEventListener('click', function(e) {
    e.preventDefault(); // Just in case
    console.log('Save button clicked - starting manual submission process...');

    // 1. Manual Validation
    const customerId = document.getElementById('customer_id').value;
    const salesId = document.getElementById('sales_employee_id').value;
    
    if(!customerId) {
        alert('Please select a Customer');
        return;
    }
    if(!salesId) {
        alert('Please select a Sales Person');
        return;
    }

    const itemCount = document.querySelectorAll('#items_body tr').length;
    console.log('Items in form:', itemCount);
    
    if (itemCount === 0) {
        alert('Please add at least one item to the invoice');
        return;
    }
    
    // 2. Recalculate totals to ensure hidden inputs are fresh
    calculateTotal();
    
    console.log('Hidden Inputs Ready:', {
        cgst_amount: document.getElementById('cgst_amount').value,
        sgst_amount: document.getElementById('sgst_amount').value,
        igst_amount: document.getElementById('igst_amount').value,
        total_tax: document.getElementById('total_tax').value,
        sub_total: document.getElementById('sub_total').value,
        total_amount: document.getElementById('total_amount').value
    });
    
    // 3. Prepare for submission
    const form = document.getElementById('invoice_form');

    // Create a hidden input to simulate the submit button (required by backend check)
    // Check if it already exists first
    let hiddenInput = form.querySelector('input[name="save_invoice"]');
    if (!hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'save_invoice';
        hiddenInput.value = 'sent';
        form.appendChild(hiddenInput);
    }
    
    // 4. Show Cube Loader
    CubeLoader.show('Submitting... Do not click Back Button', 5000); // Set minimum time to 5s to cover server processing
    
    console.log('Submitting form manually now...');
    form.submit();
});

function updateLoyaltySlabDisplay(taxableValue) {
    const display = document.getElementById('active_slab_display');
    const noDisplay = document.getElementById('no_slab_display');
    const input = document.getElementById('loyalty_slab_id');
    
    // Default State
    let matchedSlab = null;
    
    // Find matching slab based on Taxable Value
    for(let slab of loyaltySlabs) {
        let from = parseFloat(slab.from_sale_amount);
        
        if(taxableValue >= from) {
            matchedSlab = slab;
        }
    }
    
    if(matchedSlab) {
        input.value = matchedSlab.slab_id;
        
        // Update UI
        document.getElementById('slab_name').innerText = matchedSlab.slab_no;
        document.getElementById('slab_range').innerText = `Range: ₹${parseFloat(matchedSlab.from_sale_amount).toLocaleString()} - ₹${parseFloat(matchedSlab.to_sale_amount).toLocaleString()}`;
        document.getElementById('slab_rate').innerText = matchedSlab.points_per_100_rupees;
        
        // Calculate Earned Points based on Taxable Value (not Net Amount)
        // Points are calculated on Taxable Value / 100 * Rate
        let redeemedVal = 0;
        const redeemInput = document.getElementById('redeemed_points');
        if(redeemInput) {
            redeemedVal = parseFloat(redeemInput.value.replace(/[^0-9.]/g, '')) || 0;
        }
        
        let eligible = taxableValue - redeemedVal;
        if(eligible < 0) eligible = 0;
        
        let earned = Math.floor(eligible / 100) * parseFloat(matchedSlab.points_per_100_rupees);
        document.getElementById('slab_earn_amount').innerText = earned;

        document.getElementById('slab_validity').innerText = matchedSlab.valid_for_days + ' Days';
        
        display.classList.remove('d-none');
        noDisplay.classList.add('d-none');
    } else {
        input.value = 0;
        display.classList.add('d-none');
        noDisplay.classList.remove('d-none');
    }
}

function fetchProforma() {
    let proformaNo = document.getElementById('proforma_fetch_no').value.trim();
    let errorDiv = document.getElementById('proforma_fetch_error');
    let input = document.getElementById('proforma_fetch_no');
    
    // Reset Error State
    input.classList.remove('is-invalid');
    if(errorDiv) errorDiv.innerText = '';

    if (!proformaNo) {
        input.classList.add('is-invalid');
        if(errorDiv) errorDiv.innerText = "Please enter a Proforma Invoice Number";
        return;
    }

    // Button UI
    let btn = document.querySelector('button[onclick="fetchProforma()"]');
    let originalText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Fetching...';
    btn.disabled = true;
    
    // Show Cube Loader
    CubeLoader.show('Fetching Proforma Data...');

    $.ajax({
        url: basePath + '/controller/billing/get_proforma_by_number.php',
        method: 'GET',
        data: { proforma_number: proformaNo },
        dataType: 'json',
        success: function(res) {
            // Hide Cube Loader with callback
            CubeLoader.hide(function() {
                btn.innerHTML = originalText;
                btn.disabled = false;

                if (!res.success) {
                    input.classList.add('is-invalid');
                    if(errorDiv) errorDiv.innerText = res.error || "Proforma not found.";
                    return;
                }

                let inv = res.invoice;

            /* -------------------------
                1. HEADER FIELDS
            -------------------------*/
            $('#proforma_invoice_id').val(inv.proforma_invoice_id);
            $('#reference_no').val(inv.reference_no || '');
            $('#payment_terms').val(inv.payment_terms);
            $('textarea[name="notes"]').val(inv.notes);
            $('textarea[name="terms_conditions"]').val(inv.terms_conditions);

            /* -------------------------
                2. DELIVERY MODE
            -------------------------*/
            $('#delivery_mode').val(inv.delivery_mode_raw ?? inv.delivery_mode ?? '');

            /* -------------------------
                3. CUSTOMER
            -------------------------*/
            isFromProforma = true;
            $('#customer_id').select2('destroy');
            $('#customer_id').empty();
            
            let cust = new Option(inv.customer_name, inv.customer_id, true, true);
            $(cust).data('avatar', inv.avatar);
            $(cust).data('customer_name', inv.customer_name);
            $(cust).data('customer_code', inv.customer_code);
            $(cust).data('customers_type_name', inv.customers_type_name);
            $(cust).data('email', inv.email);
            $(cust).data('company_name', inv.company_name);
            $(cust).data('loyalty_point_balance', inv.loyalty_point_balance);
            $(cust).data('state_code', inv.state_code);

            $('#customer_id').append(cust);
            initCustomerSelect2();
            $('#customer_id').trigger('change');
            
            if (!$('#reference_customer_id_select').hasClass('select2-hidden-accessible')) {
                initReferenceCustomerSelect2();
            }
            
            setTimeout(function() {
                $('#reference_customer_id_select').prop('disabled', false);
                $('#reference_customer_id_select').val(null).trigger('change');
                $('#reference_customer_id_select').next('.select2-container').removeClass('select2-container--disabled');
                $('span[aria-labelledby="select2-reference_customer_id_select-container"]').attr('aria-disabled', 'false');
            }, 100);

            /* -------------------------
                4. SALES PERSON
            -------------------------*/
            let sId = inv.sales_emp_id_raw ?? inv.sales_employee_id;
            if (sId) {
                let salesName = (inv.sales_first_name || "") + " " + (inv.sales_last_name || "");
                if (!salesName.trim()) salesName = "Employee #" + sId;

                let emp = new Option(salesName.trim(), sId, true, true);
                $(emp).attr('data-avatar', inv.sales_avatar);
                $(emp).attr('data-email', inv.sales_email);
                $(emp).attr('data-phone', inv.sales_phone);
                $(emp).attr('data-designation', inv.sales_designation);
                $(emp).attr('data-code', inv.sales_employee_code);

                $('#sales_employee_id').select2('destroy');
                $('#sales_employee_id').empty().append(emp);
                initSalesPersonSelect2();
                $('#sales_employee_id').val(sId).trigger('change');
            } else {
                $('#sales_employee_id').val(null).trigger('change');
            }

            /* -------------------------
                5. ITEMS
            -------------------------*/
            $('#items_body').empty();
            res.items.forEach(item => addItemRow(item));

            /* -------------------------
                6. TOTAL CALCULATIONS
            -------------------------*/
            $('#discount_value').val(inv.discount_value);
            $('#discount_type').val(inv.discount_type);
            $('#gst_type').val(inv.gst_type);
            $('#gst_rate').val(inv.gst_rate);
            $('#adjustment').val(inv.adjustment);


            calculateTotal();
            }); // Close CubeLoader callback
        },
        error: function(xhr) {
            // Hide Cube Loader with callback
            CubeLoader.hide(function() {
                btn.innerHTML = originalText;
                btn.disabled = false;

                console.error(xhr.responseText);
                input.classList.add('is-invalid');
                if(errorDiv) errorDiv.innerText = "System Error! Failed to fetch proforma.";
            });
        }
    });
}

function toggleRedemption(){
    const chk = document.getElementById('redeem_points_check');
    const group = document.getElementById('redeem_input_group');
    const input = document.getElementById('redeemed_points');
    const maxInput = document.getElementById('max_loyalty_points');
    
    if(!chk || !group || !input) return;

    if(chk.checked) {
        group.classList.remove('d-none');
        // Auto-fill max available
        const max = parseFloat(maxInput ? maxInput.value : 0) || 0;
        input.value = '₹' + max;
        input.removeAttribute('readonly'); 
        
        // Attach listener if not exists
        input.oninput = calculateRedemptionValue;
        
        calculateRedemptionValue();
    } else {
        group.classList.add('d-none');
        input.value = 0; // Value reset
        calculateRedemptionValue(); // Reset total
    }
}

function calculateRedemptionValue() {
    const input = document.getElementById('redeemed_points');
    const maxInput = document.getElementById('max_loyalty_points');
    const amountInput = document.getElementById('redemption_amount');
    const display = document.getElementById('redemption_amount_display');
    
    // Strip symbol to get number
    let raw = input.value.replace(/[^0-9.]/g, '');
    let points = parseFloat(raw) || 0;
    
    // Get current net amount display value (exact amount shown)
    const netAmountDisplay = parseFloat(document.getElementById('total_amount_display')?.innerText.replace(/[^0-9.]/g, '')) || 0;
    
    // Use exact net amount as maximum redemption
    const maxRedemption = netAmountDisplay;
    
    // Validate Cap - limit to invoice total
    if(points > maxRedemption) {
        points = maxRedemption;
        // Maintain format state based on focus
        if(document.activeElement === input) {
            input.value = points;
        } else {
            input.value = '₹' + points;
        }
    }

    if(points < 0) {
        points = 0;
        if(document.activeElement === input) {
            input.value = 0;
        } else {
            input.value = '₹0';
        }
    }
    
    // Conversion: 1 Point = 1 Rupee
    const amount = points * 1; 
    
    if(amountInput) amountInput.value = amount;
    if(display) display.innerText = '₹' + amount.toFixed(2);
    
    calculateTotal();
}

function previewModalAvatar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.getElementById('modal_avatar_preview');
            preview.src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

</script>
<?php $extra_scripts = ob_get_clean(); ?>

<?php
// Typically this file is included, but if not:
?>
