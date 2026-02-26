<?php
$title = "Customer Loyalty Point Report";
require_once __DIR__ . '/../../config/auth_guard.php';

// Prepare base filter vars if not already
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$customers_type_id = isset($_GET['customers_type_id']) ? intval($_GET['customers_type_id']) : 0;
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

require_once __DIR__ . '/../../controller/reports/loyalty_point_report.php';

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
                                        <label class="form-label">Customer Type</label>
                                        <select name="customers_type_id" id="customers_type_id" class="form-select">
                                            <option value="0">All Customers</option>
                                            <?php foreach($typesList as $type): ?>
                                                <option value="<?= $type['customers_type_id'] ?>" <?= $customers_type_id == $type['customers_type_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($type['customers_type_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Specific Customer (Optional)</label>
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
                            <a href="<?= $basePath ?>/loyalty_point_report" class="btn btn-warning"><i class="ti ti-refresh me-1"></i> Reset</a>
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
                            <div class="card bg-success-subtle text-success border-success mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Points Earned</h6>
                                    <h3 class="card-title mb-0"><?= number_format($totalEarnedAll, 2) ?> pt</h3>
                                    <small class="d-block mt-2">For selected date range</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger-subtle text-danger border-danger mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Redeemed</h6>
                                    <h3 class="card-title mb-0"><?= number_format($totalRedeemedAll, 2) ?> pt</h3>
                                    <small class="d-block mt-2">For selected date range</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning-subtle text-warning border-warning mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Expired</h6>
                                    <h3 class="card-title mb-0"><?= number_format($totalExpiredAll, 2) ?> pt</h3>
                                    <small class="d-block mt-2">For selected date range</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info-subtle text-info border-info mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Global Current Wallet</h6>
                                    <h3 class="card-title mb-0"><?= number_format($totalBalanceAll, 2) ?> pt</h3>
                                    <small class="d-block mt-2">Sum of Live Active Points</small>
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
                        <h5 class="card-title mb-0">Customer Loyalty Point Report</h5>
                        <p class="text-muted small mb-0 mt-1"><i class="ti ti-calendar me-1"></i> Period: <?= date('d M Y', strtotime($start_date)) ?> to <?= date('d M Y', strtotime($end_date)) ?></p>
                    </div>
                    <div class="d-flex gap-2">
                        <?php $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>
                        <a href="<?= $basePath ?>/controller/reports/export_loyalty_point_report_excel.php<?= $qs ?>" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-info" onclick="printLoyaltyReport()" title="Print Report"><i class="ti ti-printer me-1"></i> Print</button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="ti ti-filter me-1"></i> Filter</button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-striped mb-0 w-100 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Customer</th>
                                    <th>Type</th>
                                    <th class="text-center">Earnings Invoices</th>
                                    <th class="text-end text-success">Earned</th>
                                    <th class="text-end text-danger">Redeemed</th>
                                    <th class="text-end text-warning">Expired</th>
                                    <th class="text-end bg-info-subtle text-info fw-bold">Current Point Balance</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reportData)): ?>
                                    <tr><td colspan="8" class="text-center text-muted py-4">No loyalty point activity found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($reportData as $row): 
                                        $custName = $row['company_name'] ? $row['company_name'] . ' <small class="text-muted">(' . $row['customer_name'] . ')</small>' : $row['customer_name'];
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= $custName ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($row['customer_code'] ?? '') ?></div>
                                        </td>
                                        <td>
                                            <?php 
                                            // Badges dynamically designed
                                            $bClass = match($row['customers_type_name']) {
                                                'Architecture' => 'bg-primary',
                                                'Interior' => 'bg-info',
                                                'Carpenter' => 'bg-warning text-dark',
                                                'Retail' => 'bg-success',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $bClass ?>"><?= htmlspecialchars($row['customers_type_name'] ?? 'N/A') ?></span>
                                        </td>
                                        <td class="text-center fw-bold"><?= $row['invoice_count'] ?></td>
                                        <td class="text-end fw-bold text-success">
                                            <?= $row['total_earned'] > 0 ? '+ ' . number_format($row['total_earned'], 2) : '-' ?>
                                        </td>
                                        <td class="text-end fw-bold text-danger">
                                            <?= $row['total_redeemed'] > 0 ? '- ' . number_format($row['total_redeemed'], 2) : '-' ?>
                                        </td>
                                        <td class="text-end fw-bold text-warning">
                                            <?= $row['total_expired'] > 0 ? number_format($row['total_expired'], 2) : '-' ?>
                                        </td>
                                        <td class="text-end fw-bold text-info bg-info-subtle bg-opacity-10">
                                            <?= number_format($row['global_balance'], 2) ?> pt
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= $basePath ?>/customers_wallet?customer_id=<?= $row['customer_id'] ?>" class="btn btn-sm btn-light" title="View Wallet Details"><i class="ti ti-wallet"></i> Wallet</a>
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
            placeholder: 'Filter by Customer (Optional)...',
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
                    return { q: params.term || '' }; 
                },
                processResults: function (data) {
                    if (!Array.isArray(data)) data = [];
                    // Fallback to manually filter local JSON
                    let selectedType = parseInt($('#customers_type_id').val(), 10);
                    
                    let filtered = data;
                    if (selectedType > 0) {
                         filtered = data.filter(c => parseInt(c.customers_type_id, 10) === selectedType);
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

    // Refresh Select2 logic when Customer Type changes
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

<!-- ═══════════════════════════════════════════ -->
<!-- PRINT AREA                                  -->
<!-- ═══════════════════════════════════════════ -->
<style>
@media print {
    body * { visibility: hidden !important; }
    #lpr-print-area, #lpr-print-area * { visibility: visible !important; }
    #lpr-print-area { position: absolute; left: 0; top: 0; width: 100%; padding: 20px; }
}
</style>

<div id="lpr-print-area" style="display:none;">
    <!-- Report Title -->
    <div style="text-align:center; margin-bottom:6px;">
        <h2 style="margin:0; color:#5d282a; text-decoration:underline; font-size:20px;">CUSTOMER LOYALTY POINT REPORT</h2>
        <p style="margin:4px 0 0; font-size:11px; color:#555;">
            Period: <?= date('d M Y', strtotime($start_date)) ?> to <?= date('d M Y', strtotime($end_date)) ?>
        </p>
    </div>

    <!-- Active Filters Box -->
    <div id="lpr-print-filters" style="margin:8px 0 12px 0; font-size:11px; color:#333; background:#fdf5e0; padding:6px 12px; border-left:4px solid #5D282A; display:none;"></div>

    <!-- Summary Row -->
    <div style="display:flex; gap:12px; margin-bottom:12px;">
        <div style="flex:1; border:1px solid #ccc; border-radius:4px; padding:8px; text-align:center;">
            <div style="font-size:10px; color:#555; margin-bottom:2px;">Total Earned</div>
            <div style="font-size:14px; font-weight:bold; color:#198754;">+<?= number_format($totalEarnedAll, 2) ?> pts</div>
        </div>
        <div style="flex:1; border:1px solid #ccc; border-radius:4px; padding:8px; text-align:center;">
            <div style="font-size:10px; color:#555; margin-bottom:2px;">Total Redeemed</div>
            <div style="font-size:14px; font-weight:bold; color:#dc3545;"><?= number_format($totalRedeemedAll, 2) ?> pts</div>
        </div>
        <div style="flex:1; border:1px solid #ccc; border-radius:4px; padding:8px; text-align:center;">
            <div style="font-size:10px; color:#555; margin-bottom:2px;">Total Expired</div>
            <div style="font-size:14px; font-weight:bold; color:#fd7e14;"><?= number_format($totalExpiredAll, 2) ?> pts</div>
        </div>
        <div style="flex:1; border:1px solid #ccc; border-radius:4px; padding:8px; text-align:center;">
            <div style="font-size:10px; color:#555; margin-bottom:2px;">Global Wallet</div>
            <div style="font-size:14px; font-weight:bold; color:#0d6efd;"><?= number_format($totalBalanceAll, 2) ?> pts</div>
        </div>
    </div>

    <!-- Data Table -->
    <table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:10px;">
        <thead>
            <tr style="background:#5d282a; color:#fff;">
                <th style="text-align:left; padding:6px;">Customer</th>
                <th style="text-align:left;">Company</th>
                <th style="text-align:left;">Code</th>
                <th style="text-align:left;">Type</th>
                <th style="text-align:center;">Invoices</th>
                <th style="text-align:right;">Earned (pts)</th>
                <th style="text-align:right;">Redeemed (pts)</th>
                <th style="text-align:right;">Expired (pts)</th>
                <th style="text-align:right;">Wallet Balance</th>
            </tr>
        </thead>
        <tbody id="lpr-print-tbody"></tbody>
        <tfoot>
            <tr style="background:#e8e8ff; font-weight:bold; font-size:10px;">
                <td colspan="5" style="padding:5px;">TOTALS</td>
                <td style="text-align:right; padding:5px;"><?= number_format($totalEarnedAll, 2) ?></td>
                <td style="text-align:right; padding:5px;"><?= number_format($totalRedeemedAll, 2) ?></td>
                <td style="text-align:right; padding:5px;"><?= number_format($totalExpiredAll, 2) ?></td>
                <td style="text-align:right; padding:5px;"><?= number_format($totalBalanceAll, 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Footer -->
    <p style="margin-top:10px; font-size:9px; color:#999; text-align:right;">
        Printed on: <span id="lpr-print-date"></span>
    </p>
</div>

<?php
$activeFiltersDisplay = [];
if (!empty($start_date))           $activeFiltersDisplay[] = '<strong>Date From:</strong> ' . date('d M Y', strtotime($start_date));
if (!empty($end_date))             $activeFiltersDisplay[] = '<strong>Date To:</strong> ' . date('d M Y', strtotime($end_date));

if ($customers_type_id > 0) {
    $typeName = 'Unknown Type';
    foreach($typesList as $type) {
        if ($type['customers_type_id'] == $customers_type_id) {
            $typeName = $type['customers_type_name'];
            break;
        }
    }
    $activeFiltersDisplay[] = '<strong>Customer Type:</strong> ' . htmlspecialchars($typeName);
}

if (!empty($customer_name_prefill))$activeFiltersDisplay[] = '<strong>Customer:</strong> ' . htmlspecialchars($customer_name_prefill);

$filterHtml = !empty($activeFiltersDisplay) ? implode('&nbsp;&nbsp;|&nbsp;&nbsp;', $activeFiltersDisplay) : 'All Records';
?>

<script>
function printLoyaltyReport() {
    // ── Build tbody rows from the live table
    var rows   = document.querySelectorAll('table.table tbody tr');
    var tbody  = document.getElementById('lpr-print-tbody');
    tbody.innerHTML = '';

    rows.forEach(function(tr) {
        var tds = tr.querySelectorAll('td');
        if (tds.length < 7) return;
        var bg = (tbody.children.length % 2 === 0) ? '#ffffff' : '#f7f7ff';
        tbody.innerHTML +=
            '<tr style="background:' + bg + ';">' +
            '<td style="padding:5px;">'                               + (tds[0].querySelector('.fw-semibold') ? tds[0].querySelector('.fw-semibold').textContent.trim() : tds[0].textContent.trim()) + '</td>' +
            '<td style="padding:5px;">'                               + (tds[0].querySelector('small') ? tds[0].querySelector('small').textContent.trim() : '') + '</td>' +
            '<td style="padding:5px;">'                               + (tds[0].querySelector('.small.text-muted') ? tds[0].querySelector('.small.text-muted').textContent.trim() : '') + '</td>' +
            '<td style="padding:5px;">'                               + tds[1].textContent.trim() + '</td>' +
            '<td style="text-align:center; padding:5px;">'            + tds[2].textContent.trim() + '</td>' +
            '<td style="text-align:right; padding:5px; color:#198754;">' + tds[3].textContent.trim() + '</td>' +
            '<td style="text-align:right; padding:5px; color:#dc3545;">' + tds[4].textContent.trim() + '</td>' +
            '<td style="text-align:right; padding:5px; color:#fd7e14;">' + tds[5].textContent.trim() + '</td>' +
            '<td style="text-align:right; padding:5px; font-weight:bold; color:#0d6efd;">' + tds[6].textContent.trim() + '</td>' +
            '</tr>';
    });

    // ── Filter info
    var filterDiv = document.getElementById('lpr-print-filters');
    var htmlContent = '<?= addslashes($filterHtml) ?>';
    if (htmlContent && htmlContent !== 'All Records') {
        filterDiv.innerHTML = '<strong>Active Filters:</strong> ' + htmlContent;
        filterDiv.style.display = 'block';
    } else {
        filterDiv.style.display = 'none';
    }

    // ── Timestamp
    document.getElementById('lpr-print-date').textContent =
        new Date().toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });

    // ── Show, print, hide
    var area = document.getElementById('lpr-print-area');
    area.style.display = 'block';
    window.print();
    area.style.display = 'none';
}
</script>
