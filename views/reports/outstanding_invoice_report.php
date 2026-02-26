<?php
$title = "Outstanding Invoice Report";
require_once __DIR__ . '/../../config/auth_guard.php';
require_once __DIR__ . '/../../controller/reports/outstanding_invoice_report.php';
?>

<div class="container-fluid">
    <style>
        .customer-text-primary { color: #2a3547; }
        .select2-results__option--highlighted .customer-text-primary { color: white !important; }
        .select2-results__option--highlighted .text-muted { color: #e9ecef !important; }
        .customer-select2-dropdown .select2-results__options { max-height: 400px !important; }
        .select2-container--open .select2-dropdown { z-index: 9999999 !important; }
    </style>

    <div class="row">
        <div class="col-12">
            
            <!-- Filter Modal -->
            <div class="modal fade" id="filterModal" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Filter Report</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="" method="GET" id="filter_form">
                                <div class="row g-3">
                                    <!-- Date Range -->
                                    <div class="col-md-4">
                                        <label class="form-label">Due Date Range (Optional)</label>
                                        <div class="input-group">
                                            <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                                            <span class="input-group-text">to</span>
                                            <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
                                        </div>
                                    </div>

                                    <!-- Customer -->
                                    <div class="col-md-4">
                                        <label class="form-label">Customer</label>
                                        <select name="customer_id" id="customer_id" class="form-select">
                                            <?php if($customer_id > 0 && !empty($customer_name_prefill)): ?>
                                                <option value="<?= $customer_id ?>" data-avatar="<?= htmlspecialchars($customer_avatar_prefill) ?>" selected><?= htmlspecialchars($customer_name_prefill) ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <!-- Sales Person -->
                                    <div class="col-md-4">
                                        <label class="form-label">Sales Person</label>
                                        <select name="sales_employee_id" id="employee_id" class="form-select">
                                            <?php if($salesperson_id > 0 && !empty($salesperson_name_prefill)): ?>
                                                <option value="<?= $salesperson_id ?>" data-avatar="<?= htmlspecialchars($salesperson_avatar_prefill) ?>" selected><?= htmlspecialchars($salesperson_name_prefill) ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <!-- Search -->
                                    <div class="col-md-12">
                                        <label class="form-label">Search</label>
                                        <input type="text" name="q" class="form-control" placeholder="Invoice #, Customer Name..." value="<?= htmlspecialchars($search_query) ?>">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-warning" onclick="window.location.href=window.location.pathname"><i class="ti ti-refresh me-1"></i> Reset</button>
                            <button type="submit" form="filter_form" class="btn btn-primary"><i class="ti ti-filter me-1"></i> Apply Filter</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-danger-subtle text-danger border-danger mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Outstanding Amount</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($total_outstanding, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                         <div class="col-md-3">
                            <div class="card bg-warning-subtle text-warning border-warning mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Invoices Due</h6>
                                    <h3 class="card-title mb-0"><?= $count_invoices ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-primary-subtle text-primary border-primary mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Avg. Outstanding Age</h6>
                                    <h3 class="card-title mb-0">
                                        <?php 
                                            // Simple average age calculation if needed, or total bucket summary
                                            // For now, let's show breakdown
                                            echo $bucket_90_plus > 0 ? '> 90 Days High' : 'Manageable';
                                        ?>
                                    </h3>
                                    <small class="text-muted">High Risk: ₹<?= number_format($bucket_90_plus, 0) ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info-subtle text-info border-info mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Invoice Value</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($total_invoice_amt, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LIST -->
            <div class="card">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Outstanding Invoices</h5>
                    <div class="d-flex gap-2">
                        <?php $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>
                        <a href="<?= $basePath ?>/controller/reports/export_outstanding_invoice_excel.php<?= $qs ?>" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-info" onclick="printOutstandingReport()">
                            <i class="ti ti-printer me-1"></i> Print
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="ti ti-filter me-1"></i> Filter</button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 w-100 align-middle" id="outstanding_table">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Sales Person</th>
                                    <th class="text-center">Age (Days)</th>
                                    <th class="text-end">Inv Amount</th>
                                    <th class="text-end">Paid Amount</th>
                                    <th class="text-end">Balance Due</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($outstanding_invoices)): ?>
                                    <tr><td colspan="9" class="text-center py-4 text-muted">No outstanding invoices found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($outstanding_invoices as $row): 
                                        $custName = $row['company_name'] ?: $row['customer_name'];
                                        $age = intval($row['age_days']);
                                        $ageClass = 'bg-success-subtle text-success';
                                        if($age > 30) $ageClass = 'bg-info-subtle text-info';
                                        if($age > 60) $ageClass = 'bg-warning-subtle text-warning';
                                        if($age > 90) $ageClass = 'bg-danger-subtle text-danger';
                                    ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($row['invoice_date'])) ?></td>
                                        <td class="fw-bold"><a href="<?= $basePath ?>/view_invoice?id=<?= $row['invoice_id'] ?>"><?= $row['invoice_number'] ?></a></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= ucwords(strtolower($custName)) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($row['customer_code'] ?? '') ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= ucwords(strtolower($row['salesperson_name'] ?? '-')) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($row['employee_code'] ?? '') ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= $ageClass ?>"><?= $age ?> Days</span>
                                        </td>
                                        <td class="text-end">₹<?= number_format($row['total_amount'], 2) ?></td>
                                        <td class="text-end text-success">₹<?= number_format($row['total_amount'] - $row['balance_due'], 2) ?></td>
                                        <td class="text-end fw-bold text-danger">₹<?= number_format($row['balance_due'], 2) ?></td>
                                        <td class="text-center">
                                            <a href="<?= $basePath ?>/view_invoice?id=<?= $row['invoice_id'] ?>" class="btn btn-sm btn-light" title="View"><i class="ti ti-eye"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
            if (!$('#customer_id').hasClass('select2-hidden-accessible')) initCustomerSelect();
            if (!$('#employee_id').hasClass('select2-hidden-accessible')) initEmployeeSelect();
        });
    }

    $(document).ready(function() {
        initCustomerSelect();
        initEmployeeSelect();
    });

    // Initialize Customer Select2
    function initCustomerSelect() {
        const $customer = $('#customer_id');
        if ($customer.hasClass('select2-hidden-accessible')) $customer.select2('destroy');

        $customer.select2({
            placeholder: 'Search Customer...',
            width: '100%',
            dropdownCssClass: 'customer-select2-dropdown',
            allowClear: true,
            dropdownParent: $('.modal-content'),
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
                        customers_type_name: c.customers_type_name || ''
                    }));
                    return { results };
                }
            },
            templateResult: formatRepoResult,
            templateSelection: formatRepoSelection,
            escapeMarkup: m => m
        });
    }

    // Initialize Employee Select2
    function initEmployeeSelect() {
        const $employee = $('#employee_id');
        if ($employee.hasClass('select2-hidden-accessible')) $employee.select2('destroy');

        $employee.select2({
            placeholder: 'Search Sales Person...',
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

    function formatRepoResult(repo) {
        if (repo.loading) return repo.text;
        
        let name = repo.customer_name || repo.vendor_name || repo.text;
        let code = repo.customer_code || repo.vendor_code || '';
        let typeName = repo.customers_type_name || ''; 
        let letter = (name || '').charAt(0).toUpperCase();
        
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
             let cleanAvatar = repo.avatar.startsWith('/') ? repo.avatar.substring(1) : repo.avatar;
             if(!cleanAvatar.startsWith('http') && !cleanAvatar.startsWith(basePath.replace(/^\//,''))) cleanAvatar = basePath + '/' + cleanAvatar;
             else if(!cleanAvatar.startsWith('http')) cleanAvatar = basePath + '/' + cleanAvatar; 
             
             let src = cleanAvatar;
             src = basePath + '/' + (repo.avatar.startsWith('/') ? repo.avatar.substring(1) : repo.avatar);
             
            avatarHtml = `<img src="${src}" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">`;
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
        
        let avatar = repo.avatar || $(repo.element).data('avatar');
        let avatarHtml = '';
        if(avatar && avatar.trim() !== ''){
             let cleanAvatar = avatar.startsWith('/') ? avatar.substring(1) : avatar;
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
    #oi-print-area, #oi-print-area * { visibility: visible !important; }
    #oi-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<div id="oi-print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:4px;">
        <h2 style="margin:0; color:#5d282a; border-bottom:2px solid #d7b251; padding-bottom:6px;">OUTSTANDING INVOICE REPORT</h2>
    </div>
    <div id="oi-print-filters" style="margin:8px 0 12px 0; font-size:11px; color:#444; background:#fdf5e0; padding:6px 10px; border-left:3px solid #d7b251;"></div>
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead style="background:#5d282a; color:#fff;">
            <tr><th>Date</th><th>Invoice #</th><th>Customer</th><th>Sales Person</th><th>Age (Days)</th><th>Inv Amount</th><th>Paid Amt</th><th>Balance Due</th></tr>
        </thead>
        <tbody id="oi-print-tbody"></tbody>
    </table>
    <p style="margin-top:8px; font-size:10px; color:#999; text-align:right;">Printed on: <span id="oi-print-date"></span></p>
</div>

<?php
$activeFiltersDisplay = [];
if (!empty($start_date))           $activeFiltersDisplay[] = '<strong>Date From:</strong> ' . date('d M Y', strtotime($start_date));
if (!empty($end_date))             $activeFiltersDisplay[] = '<strong>Date To:</strong> ' . date('d M Y', strtotime($end_date));
if (!empty($customer_name_prefill))$activeFiltersDisplay[] = '<strong>Customer:</strong> ' . htmlspecialchars($customer_name_prefill);
if (!empty($salesperson_name_prefill))$activeFiltersDisplay[] = '<strong>Sales Person:</strong> ' . htmlspecialchars($salesperson_name_prefill);
if (!empty($search_query))         $activeFiltersDisplay[] = '<strong>Search:</strong> ' . htmlspecialchars($search_query);

$filterHtml = !empty($activeFiltersDisplay) ? implode('&nbsp;&nbsp;|&nbsp;&nbsp;', $activeFiltersDisplay) : 'All Records';
?>

<script>
function printOutstandingReport() {
    var rows = document.querySelectorAll('#outstanding_table tbody tr');
    if (!rows.length) rows = document.querySelectorAll('table.table tbody tr');
    var tbody = document.getElementById('oi-print-tbody');
    tbody.innerHTML = '';
    rows.forEach(function(tr) {
        var tds = tr.querySelectorAll('td');
        if(tds.length < 8) return;
        tbody.innerHTML += '<tr>' +
            '<td>' + tds[0].textContent.trim() + '</td>' +
            '<td>' + tds[1].textContent.trim() + '</td>' +
            '<td>' + tds[2].textContent.trim() + '</td>' +
            '<td>' + tds[3].textContent.trim() + '</td>' +
            '<td>' + tds[4].textContent.trim() + '</td>' +
            '<td>' + tds[5].textContent.trim() + '</td>' +
            '<td>' + tds[6].textContent.trim() + '</td>' +
            '<td>' + tds[7].textContent.trim() + '</td>' +
            '</tr>';
    });
    
    document.getElementById('oi-print-filters').innerHTML = '<?= addslashes($filterHtml) ?>';
    document.getElementById('oi-print-date').textContent = new Date().toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('oi-print-area').style.display = 'block';
    window.print();
    document.getElementById('oi-print-area').style.display = 'none';
}
</script>
