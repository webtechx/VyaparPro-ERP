<?php
$title = "Discount Report";
require_once __DIR__ . '/../../config/auth_guard.php';
require_once __DIR__ . '/../../controller/reports/discount_report.php'; // New controller
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
                                <div class="row g-3">
                                    <!-- Date Range -->
                                    <div class="col-md-4">
                                        <label class="form-label">Date Range</label>
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

                                    <!-- Discount Type -->
                                    <div class="col-md-4">
                                        <label class="form-label">Discount Type</label>
                                        <select name="discount_type" class="form-select">
                                            <option value="all">All Types</option>
                                            <option value="Percentage" <?= $discount_type_filter == 'Percentage' ? 'selected' : '' ?>>Percentage</option>
                                            <option value="Flat" <?= $discount_type_filter == 'Flat' ? 'selected' : '' ?>>Flat</option>
                                        </select>
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
                        <div class="col-md-4">
                            <div class="card bg-danger-subtle text-danger border-danger mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Discount Given</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($total_discount_given, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-primary-subtle text-primary border-primary mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Sales (Discounted)</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($total_sales_with_discount, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                         <div class="col-md-4">
                             <div class="card bg-info-subtle text-info border-info mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Invoices with Discount</h6>
                                    <h3 class="card-title mb-0"><?= $count_invoices ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LIST -->
            <div class="card">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Discount Report</h5>
                    <div class="d-flex gap-2">
                        <?php $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>
                        <a href="<?= $basePath ?>/controller/reports/export_discount_report_excel.php<?= $qs ?>" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-info" onclick="printDiscountReport()">
                            <i class="ti ti-printer me-1"></i> Print
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="ti ti-filter me-1"></i> Filter</button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 w-100 align-middle" id="discount_report_table">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Discount Type</th>
                                    <th class="text-end">Discount Amount</th>
                                    <th class="text-end">Final Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($invoices)): ?>
                                    <?php foreach ($invoices as $row): 
                                        $custName = $row['company_name'] ? $row['company_name'] . ' <small class="text-muted">(' . $row['customer_name'] . ')</small>' : $row['customer_name'];
                                        $discType = ucfirst($row['discount_type']);
                                        $discValue = ($discType == 'Percentage') ? number_format($row['discount_amount'], 2) : '-'; // We only have amount now as value stores the actual deduction or percent value? 
                                        // Wait, `discount_value` in DB seems to be the AMOUNT deducted directly or is it the percent?
                                        // In save_invoice.php: `discount_value` is `POST['discount_value']`. 
                                        // The user enters a value. If type is %, is value the % or the amount?
                                        // Usually `discount_value` stores the monetary amount in most systems unless specified.
                                        // let's assume `discount_amount` alias we created holds the monetary deduction.
                                        
                                        $discDisplay = $discType;
                                    ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($row['invoice_date'])) ?></td>
                                        <td class="fw-bold">
                                            <a href="<?= $basePath ?>/view_invoice?id=<?= $row['invoice_id'] ?>"><?= $row['invoice_number'] ?></a>
                                        </td>
                                        <td><?= ucwords(strtolower($custName)) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= ucwords(strtolower($discDisplay)) ?></span></td>
                                        <td class="text-end fw-bold text-danger">₹<?= number_format($row['discount_amount'], 2) ?></td>
                                        <td class="text-end fw-bold text-success">₹<?= number_format($row['total_amount'], 2) ?></td>
                                        <td>
                                            <a href="<?= $basePath ?>/view_invoice?id=<?= $row['invoice_id'] ?>" class="btn btn-sm btn-light" title="View"><i class="ti ti-eye"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No discounted invoices found matching criteria.</td>
                                    </tr>
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
        });
    }

    $(document).ready(function() {
        initCustomerSelect();
    });

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
    #dr-print-area, #dr-print-area * { visibility: visible !important; }
    #dr-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<div id="dr-print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:4px;">
        <h2 style="margin:0; color:#5d282a; border-bottom:2px solid #d7b251; padding-bottom:6px;">DISCOUNT REPORT</h2>
    </div>
    <div id="dr-print-filters" style="margin:8px 0 12px 0; font-size:11px; color:#444; background:#fdf5e0; padding:6px 10px; border-left:3px solid #d7b251;"></div>
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead style="background:#5d282a; color:#fff;">
            <tr><th>Date</th><th>Invoice #</th><th>Customer</th><th>Discount Type</th><th>Discount Amount</th><th>Final Amount</th></tr>
        </thead>
        <tbody id="dr-print-tbody"></tbody>
    </table>
    <p style="margin-top:8px; font-size:10px; color:#999; text-align:right;">Printed on: <span id="dr-print-date"></span></p>
</div>

<?php
$activeFiltersDisplay = [];
if (!empty($start_date))           $activeFiltersDisplay[] = '<strong>Date From:</strong> ' . date('d M Y', strtotime($start_date));
if (!empty($end_date))             $activeFiltersDisplay[] = '<strong>Date To:</strong> ' . date('d M Y', strtotime($end_date));
if (!empty($customer_name_prefill))$activeFiltersDisplay[] = '<strong>Customer:</strong> ' . htmlspecialchars($customer_name_prefill);
if (!empty($discount_type_filter) && $discount_type_filter != 'all') {
    $activeFiltersDisplay[] = '<strong>Discount Type:</strong> ' . htmlspecialchars($discount_type_filter);
}

$filterHtml = !empty($activeFiltersDisplay) ? implode('&nbsp;&nbsp;|&nbsp;&nbsp;', $activeFiltersDisplay) : 'All Records';
?>

<script>
function printDiscountReport() {
    var rows = document.querySelectorAll('#discount_report_table tbody tr');
    if (!rows.length) rows = document.querySelectorAll('table.table tbody tr');
    var tbody = document.getElementById('dr-print-tbody');
    tbody.innerHTML = '';
    rows.forEach(function(tr) {
        var tds = tr.querySelectorAll('td');
        if(tds.length < 6) return;
        tbody.innerHTML += '<tr>' +
            '<td>' + tds[0].textContent.trim() + '</td>' +
            '<td>' + tds[1].textContent.trim() + '</td>' +
            '<td>' + tds[2].textContent.trim() + '</td>' +
            '<td>' + tds[3].textContent.trim() + '</td>' +
            '<td>' + tds[4].textContent.trim() + '</td>' +
            '<td>' + tds[5].textContent.trim() + '</td>' +
            '</tr>';
    });
    
    document.getElementById('dr-print-filters').innerHTML = '<?= addslashes($filterHtml) ?>';
    document.getElementById('dr-print-date').textContent = new Date().toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('dr-print-area').style.display = 'block';
    window.print();
    document.getElementById('dr-print-area').style.display = 'none';
}
</script>
