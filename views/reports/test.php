<?php
$title = 'Deafult Report';
require_once __DIR__ . '/../../config/auth_guard.php';



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
            

            <!-- FILTER MODAL -->
            <div class="modal fade" id="filterModal" aria-hidden="true" >
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Filter Report</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="<?= $basePath ?>/controller/payment/save_payment_received.php" method="POST" id="filter_form">
                                <!-- Date Range -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-12">
                                        <label class="form-label">Date Range</label>
                                        <div class="input-group">
                                            <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                                            <span class="input-group-text">to</span>
                                            <input type="date" name="end_date"   class="form-control" value="<?= $end_date ?>">
                                        </div>
                                    </div>
                                </div>
            
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Customer <span class="text-danger">*</span></label>
                                        <select name="customer_id" id="customer_id" class="form-select" required></select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Vendor</label>
                                        <select name="vendor_id" id="vendor_id" class="form-select"></select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Employee</label>
                                        <select name="employee_id" id="employee_id" class="form-select"></select>
                                    </div>
                                    <!-- Status -->
                                    <div class="col-md-4"> <!-- Adjusted col size for better grid in modal -->
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="">All Statuses</option>
                                            <option value="draft"     <?= $status == 'draft'     ? 'selected' : '' ?>>Draft</option>
                                            <option value="pending"   <?= $status == 'pending'   ? 'selected' : '' ?>>Pending</option>
                                            <option value="paid"      <?= $status == 'paid'      ? 'selected' : '' ?>>Paid</option>
                                            <option value="partial"   <?= $status == 'partial'   ? 'selected' : '' ?>>Partial</option>
                                            <option value="cancelled" <?= $status == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" form="filter_form" name="save_payment" class="btn btn-primary"><i class="ti ti-check me-1"></i> Apply Filter</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAYMENT LIST -->
            <div class="card" id="payment_list_card">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Deafult Report</h5>
               
                     <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="ti ti-filter me-1"></i>Filter</button>
                </div>

 

                <div class="card-body">
                    <table class="table table-hover table-striped mb-0 w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Customer</th>
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
                                    ORDER BY p.payment_date DESC, p.payment_id DESC";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $_SESSION['organization_id']);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if($result->num_rows > 0):
                                while($row = $result->fetch_assoc()):

                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= $row['customer_name'] ?></div>
                                        <small class="text-muted"><?= $row['company_name'] ?></small>
                                    </td>
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
                            <?php endwhile; else: ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">No recorded yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>





<script>
    const basePath = '<?= $basePath ?>';

    // Initialize Select2 when modal is shown
    const filterModal = document.getElementById('filterModal');
    if (filterModal) {
        filterModal.addEventListener('shown.bs.modal', function () {
            initCustomerSelect();
            initVendorSelect();
            initEmployeeSelect();
        });
    }



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
            dropdownParent: $('.modal-content'), // Important for rendering in modal
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
                        phone: c.phone || '',
                        avatar: c.avatar || '',
                        customer_code: c.customer_code || '',
                        customers_type_name: c.customers_type_name || '',
                        current_balance_due: c.current_balance_due || 0
                    }));
                    


                    return { results };
                }
            },
            templateResult: formatRepoResult, // Use the new shared formatter
            templateSelection: formatRepoSelection, // Use the new shared selector formatter
            escapeMarkup: m => m
        });
    }



    // Initialize Vendor Select2
    function initVendorSelect() {
        const $vendor = $('#vendor_id');
        if ($vendor.hasClass('select2-hidden-accessible')) {
            $vendor.select2('destroy').empty();
        }
        $vendor.select2({
            placeholder: 'Search Vendor...',
            width: '100%',
            dropdownCssClass: 'customer-select2-dropdown', // Reusing same class for styling
            allowClear: true,
            dropdownParent: $('.modal-content'),
            minimumInputLength: 0,
            ajax: {
                url: basePath + '/controller/payment/search_vendors_listing.php', // Using discovered path
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term || '' }),
                processResults: data => {
                    return {
                        results: (Array.isArray(data) ? data : []).map(v => ({
                            id: v.vendor_id || v.id,
                            text: v.display_name || v.text,
                            vendor_name: v.display_name,
                            vendor_code: v.vendor_code,
                            company_name: v.company_name,
                            email: v.email,
                            phone: v.mobile, // Mapping mobile to phone for template consistency
                            avatar: v.avatar
                        }))
                    };
                }
            },
            templateResult: formatRepoResult, // Reuse generic formatter if possible, or define specific
            templateSelection: formatRepoSelection,
            escapeMarkup: m => m
        });
    }

    // Initialize Employee Select2
    function initEmployeeSelect() {
        const $employee = $('#employee_id');
        if ($employee.hasClass('select2-hidden-accessible')) {
            $employee.select2('destroy').empty();
        }
        $employee.select2({
            placeholder: 'Search Employee...',
            width: '100%',
            dropdownCssClass: 'customer-select2-dropdown',
            allowClear: true,
            dropdownParent: $('.modal-content'),
            minimumInputLength: 0,
            ajax: {
                url: basePath + '/controller/billing/search_employees.php',
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term || '' }),
                processResults: data => {
                    return {
                        results: (Array.isArray(data) ? data : []).map(e => ({
                            id: e.id,
                            text: e.text,
                            employee_name: e.text,
                            code: e.employee_code,
                            email: e.email,
                            phone: e.phone,
                            designation: e.designation,
                            avatar: e.avatar
                        }))
                    };
                }
            },
            templateResult: formatEmployeeResult,
            templateSelection: formatRepoSelection,
            escapeMarkup: m => m
        });
    }

    // Generic Formatter for Customer/Vendor (since they share similar structure)
    // We'll use the logic currently inside initCustomerSelect for consistency
    // But since initCustomerSelect has it inline, let's extract it or copy it.
    // For now, I'll copy the logic to keep them independent but consistent.

    function formatRepoResult(repo) {
        if (repo.loading) return repo.text;
        
        let name = repo.customer_name || repo.vendor_name || repo.text;
        let code = repo.customer_code || repo.vendor_code || '';
        let typeName = repo.customers_type_name || ''; 
        let letter = (name || '').charAt(0).toUpperCase();
        
        // Avatar
        let avatarHtml = '';
        if(repo.avatar && repo.avatar.trim() !== ''){
            avatarHtml = `<img src="${basePath}/${repo.avatar}" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">`;
        } else {
            avatarHtml = `<div class="customer-avatar rounded-circle bg-light text-dark d-flex align-items-center justify-content-center fw-bold" style="width:32px;height:32px;min-width:32px;">${letter}</div>`;
        }

        return `
            <div class="d-flex align-items-center gap-2 py-1">
                ${avatarHtml}
                <div class="flex-grow-1">
                    <div class="customer-text-primary fw-semibold lh-sm mb-1">
                        ${name} 
                        ${code ? `<span class="small text-muted fw-normal">(${code}${typeName ? ' - ' + typeName : ''})</span>` : ''}
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="d-flex flex-column small text-muted">
                            ${repo.phone ? `<span class="mb-1 text-nowrap"><i class="ti ti-phone me-1"></i>${repo.phone}</span>` : ''}
                            ${repo.email ? `<span class="text-break"><i class="ti ti-mail me-1"></i>${repo.email}</span>` : ''}
                        </div>
                        ${repo.company_name ? `<div class="small text-muted"><i class="ti ti-building me-1"></i>${repo.company_name}</div>` : ''}
                    </div>
                </div>
            </div>`;
    }

    function formatEmployeeResult(repo) {
        if (repo.loading) return repo.text;
        
        let name = repo.employee_name || repo.text;
        let letter = (name || '').charAt(0).toUpperCase();

        let avatarHtml = '';
        if(repo.avatar && repo.avatar.trim() !== ''){
            // Handle potential relative/absolute path issues for employee avatars if needed
             // Employee search returns partial path like "uploads/..."
             // Helper to insure slash consistency
             let cleanAvatar = repo.avatar.startsWith('/') ? repo.avatar.substring(1) : repo.avatar;
            avatarHtml = `<img src="${basePath}/${cleanAvatar}" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">`;
        } else {
             avatarHtml = `<div class="customer-avatar rounded-circle bg-light text-dark d-flex align-items-center justify-content-center fw-bold" style="width:32px;height:32px;min-width:32px;">${letter}</div>`;
        }

        return `
            <div class="d-flex align-items-center gap-2 py-1">
                ${avatarHtml}
                <div class="flex-grow-1">
                    <div class="customer-text-primary fw-semibold lh-sm mb-1">
                        ${name} 
                        ${repo.code ? `<span class="small text-muted fw-normal">(${repo.code})</span>` : ''}
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="d-flex flex-column small text-muted">
                            ${repo.phone ? `<span class="mb-1 text-nowrap"><i class="ti ti-phone me-1"></i>${repo.phone}</span>` : ''}
                            ${repo.email ? `<span class="text-break"><i class="ti ti-mail me-1"></i>${repo.email}</span>` : ''}
                        </div>
                        ${repo.designation ? `<div class="small text-muted"><i class="ti ti-briefcase me-1"></i>${repo.designation}</div>` : ''}
                    </div>
                </div>
            </div>`;
    }

    function formatRepoSelection(repo) {
        if (!repo.id) return repo.text;
        let name = repo.customer_name || repo.vendor_name || repo.employee_name || repo.text;
        
        // Handle avatar
        let avatar = repo.avatar || $(repo.element).data('avatar');
        let avatarHtml = '';
        if(avatar && avatar.trim() !== ''){
             let cleanAvatar = avatar.startsWith('/') ? avatar.substring(1) : avatar;
              // If it's a full URL or starts with basepath logic elsewhere, might need adjustment. 
              // Assuming consistent basePath usage:
            avatarHtml = `<img src="${basePath}/${cleanAvatar}" class="rounded-circle me-2" style="width:20px;height:20px;object-fit:cover;vertical-align:middle;">`;
        } else {
             let letter = (name || '').charAt(0).toUpperCase();
             avatarHtml = `<span class="badge rounded-circle bg-primary text-white me-2 d-inline-flex align-items-center justify-content-center" style="width:20px;height:20px;font-size:10px;vertical-align:middle;">${letter}</span>`;
        }
        
        return `<span>${avatarHtml}${name}</span>`;
    }

</script>
