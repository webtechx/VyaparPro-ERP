<?php
$title = "Commission Report";
require_once __DIR__ . '/../../config/auth_guard.php';

// Prepare base filter vars if not already
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$customers_type_id = isset($_GET['customers_type_id']) ? intval($_GET['customers_type_id']) : 0;
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

require_once __DIR__ . '/../../controller/reports/commission_report.php';

// Prefill logic moved to controller
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
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Filter Report</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="" method="GET" id="filter_form">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Date Range</label>
                                        <div class="input-group">
                                            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                                            <span class="input-group-text">to</span>
                                            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="form-label">Entity Type</label>
                                        <select name="customers_type_id" id="customers_type_id" class="form-select">
                                            <option value="0">All Commission Types</option>
                                            <?php foreach($typesList as $type): ?>
                                                <option value="<?= $type['customers_type_id'] ?>" <?= $customers_type_id == $type['customers_type_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($type['customers_type_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Specific Entity (Optional)</label>
                                        <select name="customer_id" id="customer_id" class="form-select">
                                            <?php if($customer_id > 0 && !empty($customer_name_prefill)): ?>
                                                <option value="<?= $customer_id ?>" data-avatar="<?= htmlspecialchars($customer_avatar_prefill ?? '') ?>" selected><?= htmlspecialchars($customer_name_prefill) ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <a href="<?= $basePath ?>/commission_report" class="btn btn-warning"><i class="ti ti-refresh me-1"></i> Reset</a>
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
                            <div class="card bg-success-subtle text-success border-success mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Commissions Earned</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($totalEarnedAll, 2) ?></h3>
                                    <small class="d-block mt-2">For selected date range</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info-subtle text-info border-info mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Commissions Paid</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($totalPaidAll, 2) ?></h3>
                                    <small class="d-block mt-2">For selected date range</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-danger-subtle text-danger border-danger mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Current Open Balances</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($totalBalanceAll, 2) ?></h3>
                                    <small class="d-block mt-2">Global Live Total</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LIST -->
            <div class="card">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">Architect, Interior, Carpenter Commission Report</h5>
                        <p class="text-muted small mb-0 mt-1"><i class="ti ti-calendar me-1"></i> Period: <?= date('d M Y', strtotime($start_date)) ?> to <?= date('d M Y', strtotime($end_date)) ?></p>
                    </div>
                    <div class="d-flex gap-2">
                        <?php $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>
                        <a href="<?= $basePath ?>/controller/reports/export_commission_report_excel.php<?= $qs ?>" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-info" onclick="printCommissionReport()">
                            <i class="ti ti-printer me-1"></i> Print
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="ti ti-filter me-1"></i> Filter</button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-striped mb-0 w-100 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Entity</th>
                                    <th>Type</th>
                                    <th class="text-center">Earnings Invoices</th>
                                    <th class="text-end">Earned (Period)</th>
                                    <th class="text-end">Paid (Period)</th>
                                    <th class="text-end bg-danger-subtle">Current Wallet Balance</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reportData)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4">No commission activity found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($reportData as $row): 
                                        $custName = $row['company_name'] ? $row['company_name'] . ' <small class="text-muted">(' . $row['customer_name'] . ')</small>' : $row['customer_name'];
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= ucwords(strtolower($custName)) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($row['customer_code'] ?? '') ?></div>
                                        </td>
                                        <td>
                                            <?php 
                                            // Badges dynamically designed
                                            $bClass = match($row['customers_type_name']) {
                                                'Architecture' => 'bg-primary',
                                                'Interior' => 'bg-info',
                                                'Carpenter' => 'bg-warning text-dark',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $bClass ?>"><?= htmlspecialchars($row['customers_type_name']) ?></span>
                                        </td>
                                        <td class="text-center fw-bold"><?= $row['invoice_count'] ?></td>
                                        <td class="text-end fw-bold text-success">
                                            <?= $row['total_earned'] > 0 ? '₹' . number_format($row['total_earned'], 2) : '-' ?>
                                        </td>
                                        <td class="text-end fw-bold text-info">
                                            <?= $row['total_paid'] > 0 ? '₹' . number_format($row['total_paid'], 2) : '-' ?>
                                        </td>
                                        <td class="text-end fw-bold text-danger bg-danger-subtle bg-opacity-10">
                                            <?= $row['global_balance'] > 0 ? '₹' . number_format($row['global_balance'], 2) : '-' ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= $basePath ?>/customers_wallet?customer_id=<?= $row['customer_id'] ?>" class="btn btn-sm btn-light" title="View Commission Ledger"><i class="ti ti-wallet"></i> Ledger</a>
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
            placeholder: 'Filter by Entity (Optional)...',
            width: '100%',
            dropdownCssClass: 'customer-select2-dropdown',
            allowClear: true,
            dropdownParent: $('#filterModal'),
            minimumInputLength: 0,
            ajax: {
                url: basePath + '/controller/billing/search_customers_listing.php',
                dataType: 'json',
                delay: 250,
                data: function (params) { 
                    const typeId = $('#customers_type_id').val();
                    return { q: params.term || '' }; 
                },
                processResults: function (data) {
                    if (!Array.isArray(data)) data = [];
                    // Fallback to manually filter local JSON
                    let selectedType = parseInt($('#customers_type_id').val(), 10);
                    
                    let filtered = data;
                    if (selectedType > 0) {
                         filtered = filtered.filter(c => parseInt(c.customers_type_id, 10) === selectedType);
                    }

                    let results = filtered.map(c => ({
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

    // Refresh Select2 logic when Entity Type changes
    $('#customers_type_id').on('change', function() {
        $('#customer_id').val(null).trigger('change');
    });

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
    #comm-print-area, #comm-print-area * { visibility: visible !important; }
    #comm-print-area { position: absolute; left: 0; top: 0; width: 100%; }
    .d-print-none { display: none !important; }
}
</style>

<div id="comm-print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:4px;">
        <h2 style="margin:0; color:#5d282a; border-bottom:2px solid #d7b251; padding-bottom:6px;">COMMISSION REPORT</h2>
    </div>
    <div id="comm-print-filters" style="margin:8px 0 12px 0; font-size:11px; color:#444; background:#fdf5e0; padding:6px 10px; border-left:3px solid #d7b251;"></div>
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead style="background:#5d282a; color:#fff;">
            <tr><th>Entity</th><th>Type</th><th>Invoices</th><th>Earned (Period)</th><th>Paid (Period)</th><th>Wallet Balance</th></tr>
        </thead>
        <tbody id="comm-print-tbody"></tbody>
    </table>
    <p style="margin-top:8px; font-size:10px; color:#999; text-align:right;">Printed on: <span id="comm-print-date"></span></p>
</div>

<?php
$activeFiltersDisplay = [];
if (!empty($start_date))           $activeFiltersDisplay[] = '<strong>Date From:</strong> ' . date('d M Y', strtotime($start_date));
if (!empty($end_date))             $activeFiltersDisplay[] = '<strong>Date To:</strong> ' . date('d M Y', strtotime($end_date));
if ($customers_type_id > 0 && !empty($typesList)) {
    foreach ($typesList as $type) {
        if ($type['customers_type_id'] == $customers_type_id) {
            $activeFiltersDisplay[] = '<strong>Type:</strong> ' . htmlspecialchars($type['customers_type_name']);
            break;
        }
    }
}
if (!empty($customer_name_prefill))$activeFiltersDisplay[] = '<strong>Entity:</strong> ' . htmlspecialchars($customer_name_prefill);

$filterHtml = !empty($activeFiltersDisplay) ? implode('&nbsp;&nbsp;|&nbsp;&nbsp;', $activeFiltersDisplay) : 'All Records';
?>

<script>
function printCommissionReport() {
    var rows = document.querySelectorAll('table.table tbody tr');
    var tbody = document.getElementById('comm-print-tbody');
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
    
    document.getElementById('comm-print-filters').innerHTML = '<?= addslashes($filterHtml) ?>';
    document.getElementById('comm-print-date').textContent = new Date().toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('comm-print-area').style.display = 'block';
    window.print();
    document.getElementById('comm-print-area').style.display = 'none';
}
</script>
