<?php
$title = 'Sales Report';
require_once __DIR__ . '/../../config/auth_guard.php';
require_once __DIR__ . '/../../controller/reports/sales_report.php';


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
            <div class="modal fade" id="filterModal" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Filter Report</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="" method="GET" id="filter_form">
                                <!-- Date Range -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Date Range</label>
                                        <div class="input-group">
                                            <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                                            <span class="input-group-text">to</span>
                                            <input type="date" name="end_date"   class="form-control" value="<?= $end_date ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Customer </label>
                                        <select name="customer_id" id="customer_id" class="form-select">
                                            <?php if($customer_id > 0 && !empty($customer_name_prefill)): ?>
                                                <option value="<?= $customer_id ?>" data-avatar="<?= htmlspecialchars($customer_avatar_prefill) ?>" selected><?= htmlspecialchars($customer_name_prefill) ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Sales Person</label>
                                        <select name="sales_employee_id" id="employee_id" class="form-select">
                                            <?php if($salesperson_id > 0 && !empty($salesperson_name_prefill)): ?>
                                                <option value="<?= $salesperson_id ?>" data-avatar="<?= htmlspecialchars($salesperson_avatar_prefill) ?>" selected><?= htmlspecialchars($salesperson_name_prefill) ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <!-- Department -->
                                    <div class="col-md-4">
                                         <label class="form-label">Department</label>
                                        <select name="department_id" id="department_id" class="form-select">
                                            <option value="">All Departments</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?= $dept['department_id'] ?>" <?= ($department_id == $dept['department_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($dept['department_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <!-- Status -->
                                    <div class="col-md-4"> 
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="">All Statuses</option>
                                            <option value="draft"     <?= $status == 'draft'     ? 'selected' : '' ?>>Draft</option>
                                            <option value="sent"      <?= $status == 'sent'      ? 'selected' : '' ?>>Sent</option>
                                            <option value="approved"  <?= $status == 'approved'  ? 'selected' : '' ?>>Approved</option>
                                            <option value="paid"      <?= $status == 'paid'      ? 'selected' : '' ?>>Paid</option>
                                            <option value="overdue"   <?= $status == 'overdue'   ? 'selected' : '' ?>>Overdue</option>
                                            <option value="refunded"  <?= $status == 'refunded'  ? 'selected' : '' ?>>Refunded</option>
                                            <option value="cancelled" <?= $status == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <!-- Search -->
                                    <div class="col-md-4">
                                        <label class="form-label">Search</label>
                                        <input type="text" name="q" class="form-control" placeholder="Invoice # / Customer" value="<?= htmlspecialchars($search_query) ?>">
                                    </div>

                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-warning " onclick="window.location.href=window.location.pathname"><i class="ti ti-refresh me-1"></i> Reset</button>
                            <button type="submit" form="filter_form" name="save_payment" class="btn btn-primary"><i class="ti ti-filter me-1"></i> Apply Filter</button>
                        </div>
                    </div>
                </div>
            </div>


             <div class="card" id="payment_list_card">
                

 

                <div class="card-body">
                    <div class="row">
                            <div class="col-md-4">
                                <div class="card bg-primary-subtle text-primary border-primary">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2">Total Sales Amount</h6>
                                        <h3 class="card-title mb-0">₹<?= number_format($total_sales, 2) ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-danger-subtle text-danger border-danger">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2">Total Balance Due</h6>
                                        <h3 class="card-title mb-0">₹<?= number_format($total_balance_due, 2) ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-info-subtle text-info border-info">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2">Total Invoices</h6>
                                        <h3 class="card-title mb-0"><?= number_format($count_invoices, 2) ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

 


            <!-- ── Summary Cards ── -->
                    
            <!-- PAYMENT LIST -->
            <div class="card" id="payment_list_card">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Sales Report</h5>
                    <div class="d-flex gap-2">
                        <?php $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>
                        <a href="<?= $basePath ?>/controller/reports/export_sales_report_excel.php<?= $qs ?>" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-info" onclick="printSalesReport()">
                            <i class="ti ti-printer me-1"></i> Print
                        </button>
                        <button title="Back" onclick="window.history.back()" class="btn btn-warning"><i class="ti ti-arrow-left me-1"></i>Back</button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="ti ti-filter me-1"></i>Filter</button>
                    </div>
                </div>

 

                <div class="card-body">
                    <table class="table table-hover table-striped mb-0 w-100">
                        <thead class="table-light">
                             <tr>
                                <th>Date</th>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Sales Person</th>
                                <th>Status</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Balance Due</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                                <?php if (!empty($invoices)): ?>
                                    <?php foreach ($invoices as $row):
                                        $statusClr = match($row['status']) {
                                            'draft'     => 'secondary',
                                            'sent'      => 'primary',
                                            'approved'  => 'info',
                                            'paid'      => 'success',
                                            'overdue'   => 'danger',
                                            'refunded'  => 'warning',
                                            'cancelled' => 'danger',
                                            default     => 'light'
                                        };
                                        $custDisplay = $row['company_name']
                                            ? htmlspecialchars(ucwords(strtolower($row['company_name']))) . '<br><small class="text-muted">' . htmlspecialchars(ucwords(strtolower($row['customer_name']))) . '</small>'
                                            : htmlspecialchars(ucwords(strtolower($row['customer_name'])));
                                    ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($row['invoice_date'])) ?></td>
                                        <td class="fw-bold">
                                            <a href="<?= $basePath ?>/view_invoice?id=<?= $row['invoice_id'] ?>"><?= htmlspecialchars($row['invoice_number']) ?></a>
                                        </td>
                                        <td><?= $custDisplay ?></td>
                                        <td><?= htmlspecialchars(ucwords(strtolower($row['salesperson_name']) ?? '-')) ?></td>
                                        <td><span class="badge bg-<?= $statusClr ?>"><?= ucfirst($row['status']) ?></span></td>
                                        <td class="text-end fw-bold">₹<?= number_format($row['total_amount'], 2) ?></td>
                                        <td class="text-end <?= floatval($row['balance_due']) > 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                                            ₹<?= number_format($row['balance_due'], 2) ?>
                                        </td>
                                        <td>
                                            <a href="<?= $basePath ?>/view_invoice?id=<?= $row['invoice_id'] ?>" class="btn btn-sm btn-light" title="View">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                            <a href="<?= $basePath ?>/controller/billing/download_invoice_pdf.php?id=<?= $row['invoice_id'] ?>" class="btn btn-sm btn-light" title="Download PDF" target="_blank">
                                                <i class="ti ti-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No invoices found matching criteria.</td>
                                    </tr>
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
            // Re-init only if not already initialized
            if (!$('#customer_id').hasClass('select2-hidden-accessible')) initCustomerSelect();
            if (!$('#employee_id').hasClass('select2-hidden-accessible')) initEmployeeSelect();
            if (!$('#department_id').hasClass('select2-hidden-accessible')) initDepartmentSelect();
        });
    }

    // Pre-fill Logic: Initializers run on document ready.
    $(document).ready(function() {
        initCustomerSelect();
        initEmployeeSelect();
        initDepartmentSelect();
    });

    function initDepartmentSelect() {
        $('#department_id').select2({
            placeholder: 'Select Department',
            width: '100%',
            dropdownParent: $('#filterModal')
        });
    }



    // Initialize Customer Select2
    function initCustomerSelect() {
        const $customer = $('#customer_id');

        if ($customer.hasClass('select2-hidden-accessible')) {
            $customer.select2('destroy');
        }

        $customer.select2({
            placeholder: 'Search Customer...',
            width: '100%',
            dropdownCssClass: 'customer-select2-dropdown',
            allowClear: true,
            allowClear: true,
            dropdownParent: $('.modal-content'), // Important for rendering in modal
            minimumInputLength: 0,
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
            $employee.select2('destroy');
        }
        $employee.select2({
            placeholder: 'Search Sales Person...', // Updated placeholder
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

<style>
@media print {
    body * { visibility: hidden !important; }
    #sr-print-area, #sr-print-area * { visibility: visible !important; }
    #sr-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<div id="sr-print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:4px;">
        <h2 style="margin:0; color:#5d282a; border-bottom:2px solid #d7b251; padding-bottom:6px;">SALES REPORT</h2>
    </div>
    <div id="sr-print-filters" style="margin:8px 0 12px 0; font-size:11px; color:#444; background:#fdf5e0; padding:6px 10px; border-left:3px solid #d7b251;"></div>
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead style="background:#5d282a; color:#fff;">
            <tr><th>Date</th><th>Invoice #</th><th>Customer</th><th>Sales Person</th><th>Status</th><th>Amount</th><th>Balance Due</th></tr>
        </thead>
        <tbody id="sr-print-tbody"></tbody>
    </table>
    <p style="margin-top:8px; font-size:10px; color:#999; text-align:right;">Printed on: <span id="sr-print-date"></span></p>
</div>

<?php
$activeFiltersDisplay = [];
if (!empty($start_date))              $activeFiltersDisplay[] = '<strong>Date From:</strong> ' . date('d M Y', strtotime($start_date));
if (!empty($end_date))                $activeFiltersDisplay[] = '<strong>Date To:</strong> ' . date('d M Y', strtotime($end_date));
if (!empty($customer_name_prefill))   $activeFiltersDisplay[] = '<strong>Customer:</strong> ' . htmlspecialchars($customer_name_prefill);
if (!empty($salesperson_name_prefill))$activeFiltersDisplay[] = '<strong>Sales Person:</strong> ' . htmlspecialchars($salesperson_name_prefill);
if ($department_id > 0 && !empty($departments)) {
    foreach ($departments as $dept) {
        if ($dept['department_id'] == $department_id) {
            $activeFiltersDisplay[] = '<strong>Department:</strong> ' . htmlspecialchars($dept['department_name']);
            break;
        }
    }
}
if (!empty($status))                  $activeFiltersDisplay[] = '<strong>Status:</strong> ' . ucfirst(htmlspecialchars($status));
if (!empty($search_query))            $activeFiltersDisplay[] = '<strong>Search:</strong> ' . htmlspecialchars($search_query);

$filterHtml = !empty($activeFiltersDisplay) ? implode('&nbsp;&nbsp;|&nbsp;&nbsp;', $activeFiltersDisplay) : 'All Records';
?>

<script>
function printSalesReport() {
    var rows = document.querySelectorAll('table.table tbody tr');
    var tbody = document.getElementById('sr-print-tbody');
    tbody.innerHTML = '';
    
    // Check if there are actual data rows or just a "No records found" row
    var hasData = false;
    rows.forEach(function(tr) {
        var tds = tr.querySelectorAll('td');
        if(tds.length >= 7) {
            hasData = true;
            
            // Reconstruct the customer data without hidden elements
            var customerCell = tds[2].cloneNode(true);
            var smallTags = customerCell.querySelectorAll('small');
            var customerText = '';
            
            if (smallTags.length > 0) {
                 // Try to format it like "Company Name (Customer Name)"
                 var mainText = customerCell.childNodes[0].textContent.trim();
                 var subText = smallTags[0].textContent.trim();
                 if (mainText) {
                     customerText = mainText;
                     if (subText) {
                        customerText += ' (' + subText + ')';
                     }
                 } else if (subText) {
                    customerText = subText;
                 }
            } else {
                 customerText = customerCell.textContent.trim();
            }

            tbody.innerHTML += '<tr>' +
                '<td>' + tds[0].textContent.trim() + '</td>' +
                '<td>' + tds[1].textContent.trim() + '</td>' +
                '<td>' + customerText + '</td>' +
                '<td>' + tds[3].textContent.trim() + '</td>' +
                '<td>' + tds[4].textContent.trim() + '</td>' +
                '<td>' + tds[5].textContent.trim() + '</td>' +
                '<td>' + tds[6].textContent.trim() + '</td>' +
                '</tr>';
        }
    });
    
    if (!hasData) {
         tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">No records found</td></tr>';
    }
    
    document.getElementById('sr-print-filters').innerHTML = '<?= addslashes($filterHtml) ?>';
    document.getElementById('sr-print-date').textContent = new Date().toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('sr-print-area').style.display = 'block';
    window.print();
    document.getElementById('sr-print-area').style.display = 'none';
}
</script>
