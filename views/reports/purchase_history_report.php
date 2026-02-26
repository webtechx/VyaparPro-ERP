<?php
$title = "Purchase History Report";
require_once __DIR__ . '/../../config/auth_guard.php';
require_once __DIR__ . '/../../controller/reports/purchase_history_report.php';
?>

<div class="container-fluid">
    <style>
        .customer-text-primary { color: #2a3547; }
        
        /* Standard Highlight Theme */
        .select2-results__option--highlighted .customer-text-primary { color: white !important; }
        .select2-results__option--highlighted .text-muted { color: #e9ecef !important; }
        
        /* Custom Dropdown Styles */
        .customer-select2-dropdown .select2-results__options {
            max-height: 400px !important;
        }

        /* Fix Select2 Z-Index for Modal Overlap */
        .select2-container--open .select2-dropdown {
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

                                    <!-- Vendor -->
                                    <div class="col-md-4">
                                        <label class="form-label">Vendor</label>
                                        <select name="vendor_id" id="vendor_id" class="form-select">
                                            <?php if($vendor_id > 0 && !empty($vendor_name_prefill)): ?>
                                                <option value="<?= $vendor_id ?>" data-avatar="<?= htmlspecialchars($vendor_avatar_prefill) ?>" selected><?= htmlspecialchars($vendor_name_prefill) ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                     <!-- Status -->
                                     <div class="col-md-4">
                                         <label class="form-label">Status</label>
                                         <select name="status" id="filter_status" class="form-select">
                                             <option value="">All Statuses</option>
                                             <option value="draft" <?= $status == 'draft' ? 'selected' : '' ?>>Draft</option>
                                             <option value="sent" <?= $status == 'sent' ? 'selected' : '' ?>>Sent</option>
                                             <option value="approved" <?= $status == 'approved' ? 'selected' : '' ?>>Approved</option>
                                             <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                                             <option value="cancelled" <?= $status == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                             <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>Completed</option>
                                         </select>
                                    </div>

                                    <!-- Search -->
                                    <div class="col-md-12">
                                        <label class="form-label">Search</label>
                                        <input type="text" name="q" class="form-control" placeholder="PO Number / Vendor Name" value="<?= htmlspecialchars($search_query) ?>">
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
                            <div class="card bg-primary-subtle text-primary border-primary mb-0">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Purchase Amount</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($total_purchases, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info-subtle text-info border-info mb-0">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Orders</h6>
                                    <h3 class="card-title mb-0"><?= $count_orders ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                             <div class="card bg-success-subtle text-success border-success mb-0">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Avg. Order Value</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($count_orders > 0 ? $total_purchases / $count_orders : 0, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LIST -->
            <div class="card">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Purchase History Report</h5>
                    <div class="d-flex gap-2">
                        <?php $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>
                        <a href="<?= $basePath ?>/controller/reports/export_purchase_history_excel.php<?= $qs ?>" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-info" onclick="printPurchaseHistory()">
                            <i class="ti ti-printer me-1"></i> Print
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="ti ti-filter me-1"></i> Filter</button>
                    </div>
                </div>

                <div class="card-body">
                    <table data-tables="basic" class="table table-hover table-striped dt-responsive align-middle mb-0 w-100" id="purchase_history_table" style="width: 100%;">
                        <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>PO Number</th>
                                    <th>Vendor</th>
                                    <th>Status</th>
                                    <th class="text-end">Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($purchase_orders)): ?>
                                    <?php foreach ($purchase_orders as $row): 
                                        $venName = $row['company_name'] ? $row['company_name'] . ' <br><small class="text-muted">' . $row['display_name'] . '</small>' : $row['display_name'];
                                        $statusClr = match($row['status']) {
                                            'draft' => 'secondary',
                                            'sent' => 'primary',
                                            'pending' => 'warning',
                                            'partially_received' => 'info',
                                            'approved' => 'info',
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($row['order_date'])) ?></td>
                                        <td class="fw-bold">
                                            <a href="<?= $basePath ?>/view_purchase_order?id=<?= $row['purchase_order_id'] ?>"><?= $row['po_number'] ?></a>
                                        </td>
                                        <td><?= ucwords(strtolower($venName)) ?></td>
                                        <td><span class="badge bg-<?= $statusClr ?>"><?= ucwords(str_replace('_', ' ', $row['status'])) ?></span></td>
                                        <td class="text-end fw-bold">₹<?= number_format($row['total_amount'], 2) ?></td>
                                        <td>
                                            <a href="<?= $basePath ?>/view_purchase_order?id=<?= $row['purchase_order_id'] ?>" class="btn btn-sm btn-light" title="View"><i class="ti ti-eye"></i></a>
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

<script>
    const basePath = '<?= $basePath ?>';

    // Initialize Select2 when modal is shown
    const filterModal = document.getElementById('filterModal');
    if (filterModal) {
        filterModal.addEventListener('shown.bs.modal', function () {
            if (!$('#vendor_id').hasClass('select2-hidden-accessible')) initVendorSelect();
        });
    }

    // Pre-fill Logic: Initializers run on document ready (for pre-filled values)
    $(document).ready(function() {
        initVendorSelect();
    });

    // Initialize Vendor Select2
    function initVendorSelect() {
        const $vendor = $('#vendor_id');
        if ($vendor.hasClass('select2-hidden-accessible')) {
            $vendor.select2('destroy');
        }

        $vendor.select2({
            placeholder: 'Search Vendor...',
            width: '100%',
            dropdownCssClass: 'customer-select2-dropdown',
            allowClear: true,
            dropdownParent: $('.modal-content'),
            minimumInputLength: 0,
            ajax: {
                url: basePath + '/controller/payment/search_vendors_listing.php',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term || '' }; },
                processResults: function (data) {
                    if (!Array.isArray(data)) data = [];
                    let results = data.map(v => ({
                        id: String(v.id || v.vendor_id),
                        text: v.display_name || v.text,
                        vendor_name: v.display_name || v.text,
                        company_name: v.company_name || '',
                        email: v.email || '',
                        mobile: v.mobile || '',
                        vendor_code: v.vendor_code || '',
                        avatar: v.avatar || ''
                    }));
                    return { results };
                }
            },
            templateResult: formatVendorResult,
            templateSelection: formatVendorSelection,
            escapeMarkup: m => m
        });
    }

    function formatVendorResult(repo) {
        if (repo.loading) return repo.text;
        
        let name = repo.company_name || repo.vendor_name || repo.text;
        let code = repo.vendor_code || '';
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
                        ${code ? `<span class="small text-muted fw-normal">(${code})</span>` : ''}
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="d-flex flex-column small text-muted">
                            ${repo.mobile ? `<span class="mb-1 text-nowrap"><i class="ti ti-phone me-1"></i>${repo.mobile}</span>` : ''}
                            ${repo.email ? `<span class="text-break"><i class="ti ti-mail me-1"></i>${repo.email}</span>` : ''}
                        </div>
                    </div>
                </div>
            </div>`;
    }

    function formatVendorSelection(repo) {
        if (!repo.id) return repo.text;
        let name = repo.company_name || repo.vendor_name || repo.text;
        
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
    #ph-print-area, #ph-print-area * { visibility: visible !important; }
    #ph-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<div id="ph-print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:4px;">
        <h2 style="margin:0; color:#5d282a; border-bottom:2px solid #d7b251; padding-bottom:6px;">PURCHASE HISTORY REPORT</h2>
    </div>
    <div id="ph-print-filters" style="margin:8px 0 12px 0; font-size:11px; color:#444; background:#fdf5e0; padding:6px 10px; border-left:3px solid #d7b251;"></div>
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead style="background:#5d282a; color:#fff;">
            <tr>
                <th>Date</th>
                <th>PO Number</th>
                <th>Vendor</th>
                <th>Status</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody id="ph-print-tbody"></tbody>
    </table>
    <p style="margin-top:8px; font-size:10px; color:#999; text-align:right;">Printed on: <span id="ph-print-date"></span></p>
</div>

<?php
$activeFiltersDisplay = [];
if (!empty($start_date))           $activeFiltersDisplay[] = '<strong>Date From:</strong> ' . date('d M Y', strtotime($start_date));
if (!empty($end_date))             $activeFiltersDisplay[] = '<strong>Date To:</strong> ' . date('d M Y', strtotime($end_date));
if (!empty($vendor_name_prefill))  $activeFiltersDisplay[] = '<strong>Vendor:</strong> ' . htmlspecialchars($vendor_name_prefill);
if (!empty($status))               $activeFiltersDisplay[] = '<strong>Status:</strong> ' . ucwords(str_replace('_',' ', htmlspecialchars($status)));
if (!empty($search_query))         $activeFiltersDisplay[] = '<strong>Search:</strong> ' . htmlspecialchars($search_query);

$filterHtml = !empty($activeFiltersDisplay) ? implode('&nbsp;&nbsp;|&nbsp;&nbsp;', $activeFiltersDisplay) : 'All Records';
?>

<script>
function printPurchaseHistory() {
    var rows = document.querySelectorAll('#purchase_history_table tbody tr');
    if (!rows.length) rows = document.querySelectorAll('table.table tbody tr');
    var tbody = document.getElementById('ph-print-tbody');
    tbody.innerHTML = '';
    rows.forEach(function(tr) {
        var tds = tr.querySelectorAll('td');
        if(tds.length < 5) return;
        tbody.innerHTML += '<tr>' +
            '<td>' + tds[0].textContent.trim() + '</td>' +
            '<td>' + tds[1].textContent.trim() + '</td>' +
            '<td>' + tds[2].textContent.trim() + '</td>' +
            '<td>' + tds[3].textContent.trim() + '</td>' +
            '<td>' + tds[4].textContent.trim() + '</td>' +
            '</tr>';
    });
    
    document.getElementById('ph-print-filters').innerHTML = '<?= addslashes($filterHtml) ?>';
    document.getElementById('ph-print-date').textContent = new Date().toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('ph-print-area').style.display = 'block';
    window.print();
    document.getElementById('ph-print-area').style.display = 'none';
}
</script>
