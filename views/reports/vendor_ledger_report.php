<?php
$title = "Vendor Ledger Report";
require_once __DIR__ . '/../../config/auth_guard.php';
require_once __DIR__ . '/../../controller/reports/vendor_ledger_report.php';
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
            
            <!-- Filter Modal -->
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
                                            <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                                            <span class="input-group-text">to</span>
                                            <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Vendor</label>
                                        <select name="vendor_id" id="vendor_id" class="form-select">
                                            <?php if($vendor_id > 0 && !empty($vendor_name_prefill)): ?>
                                                <option value="<?= $vendor_id ?>" selected><?= htmlspecialchars($vendor_name_prefill) ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Search Transaction</label>
                                        <input type="text" name="q" class="form-control" placeholder="Search Ref No..." value="<?= htmlspecialchars($search_query) ?>">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <a href="<?= $basePath ?>/vendor_ledger_report" class="btn btn-warning"><i class="ti ti-refresh me-1"></i> Reset</a>
                            <button type="submit" form="filter_form" class="btn btn-primary"><i class="ti ti-filter me-1"></i> Apply Filter</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ledger Detailed View -->
            <div class="card mb-3">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                    <div>
                        <?php if($vendor_id > 0): ?>
                            <h5 class="card-title mb-1 fw-bold text-dark">Vendor Ledger: <?= htmlspecialchars($vendor_details['vendor_name']) ?></h5>
                            <small class="text-muted">
                                <i class="ti ti-calendar me-1"></i> <?= date('d M Y', strtotime($start_date)) ?> to <?= date('d M Y', strtotime($end_date)) ?>
                            </small>
                        <?php else: ?>
                            <h5 class="card-title mb-1 fw-bold text-dark">Vendor Ledger Report</h5>
                            <small class="text-muted">
                                <i class="ti ti-calendar me-1"></i> <?= date('d M Y', strtotime($start_date)) ?> to <?= date('d M Y', strtotime($end_date)) ?>
                                <span class="mx-2">|</span> All Vendors
                            </small>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <?php $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>
                        <a href="<?= $basePath ?>/controller/reports/export_vendor_ledger_excel.php<?= $qs ?>" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-info" onclick="printVendorLedger()">
                            <i class="ti ti-printer me-1"></i> Print
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="ti ti-filter me-1"></i> Filter
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <table class="table table-hover table-bordered table-striped dt-responsive align-middle mb-0 w-100" id="vendor_ledger_table" style="width: 100%;">
                            <thead class="table-light">
                                <tr>
                                    <th><?= ($vendor_id == 0) ? 'Vendor / Date' : 'Date' ?></th>
                                    <th>Ref No</th>
                                    <th>Type</th>
                                    <th>Particulars</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                    <th class="text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $running_balance = 0; 
                                if($vendor_id > 0) {
                                    $running_balance = $opening_balance;
                                }
                                
                                if(empty($transactions) && $vendor_id > 0 && $running_balance == 0): ?>
                                    <tr><td colspan="8" class="text-center py-4 text-muted">No transactions found for the selected period.</td></tr>
                                <?php elseif(empty($transactions) && $vendor_id == 0): ?>
                                     <tr><td colspan="8" class="text-center py-4 text-muted">No transactions found for the selected period.</td></tr>
                                <?php else: ?>
                                    
                                    <?php if($vendor_id > 0): ?>
                                    <!-- Opening Balance Row -->
                                    <tr class="bg-light fw-bold text-muted">
                                        <td colspan="4" class="text-end">Opening Balance</td>
                                        <td class="text-end fw-semibold text-danger"></td>
                                        <td class="text-end fw-semibold text-success"></td>
                                        <td class="text-end">
                                            ₹<?= number_format(abs($opening_balance), 2) ?> 
                                            <small><?= $opening_balance >= 0 ? 'Dr' : 'Cr' ?></small>
                                        </td>
                                    </tr>
                                    <?php endif; ?>

                                    <?php foreach($transactions as $row): 
                                        $debit = floatval($row['debit_amount']);
                                        $credit = floatval($row['credit_amount']);
                                        
                                        // Update: User requested Purchase Order as Debit, Payment as Credit.
                                        // View Perspective: Statement View (Debit = Invoice/Charges, Credit = Payments)
                                        // Balance (Payable) = Accrued Invoices (Debit) - Payments Made (Credit)
                                        
                                        $running_balance = $running_balance + $debit - $credit;
                                        
                                        $typeClass = match($row['type_code']) {
                                            'GRN' => 'info',
                                            'PAY' => 'success',
                                            'DN'  => 'warning',
                                            default => 'secondary'
                                        };
                                    ?>
                                        <td>
                                            <?php if($vendor_id == 0): ?>
                                                <div class="fw-bold text-primary mb-1"><?= ucwords(strtolower($row['vendor_name'] ?? 'N/A')) ?></div>
                                            <?php endif; ?>
                                            <div class="text-muted small"><?= date('d M Y', strtotime($row['trans_date'])) ?></div>
                                        </td>
                                        <td>
                                            <span class="font-monospace text-dark"><?= htmlspecialchars($row['ref_no']) ?></span>
                                        </td>
                                        <td><span class="badge bg-<?= $typeClass ?>-subtle text-<?= $typeClass ?> border border-<?= $typeClass ?>-subtle"><?= $row['type_label'] ?></span></td>
                                        <td>
                                            <?php if($row['type_code'] == 'GRN'): ?>
                                                <div class="fw-semibold">Receive GRN</div>
                                                <?php if(!empty($row['external_ref'])): ?><small class="text-muted d-block"><i class="ti ti-shopping-cart me-1"></i>PO: <?= htmlspecialchars($row['external_ref']) ?></small><?php endif; ?>
                                                <?php if(!empty($row['notes'])): ?><small class="text-muted d-block fst-italic"><?= htmlspecialchars(substr($row['notes'], 0, 50)) ?></small><?php endif; ?>
                                            <?php elseif($row['type_code'] == 'PAY'): ?>
                                                <div class="fw-semibold">Payment Made</div>
                                                <?php if(!empty($row['payment_mode'])): ?><small class="text-muted d-block">Mode: <?= htmlspecialchars($row['payment_mode']) ?></small><?php endif; ?>
                                                <?php if(!empty($row['external_ref'])): ?><small class="text-muted d-block">Ref: <?= htmlspecialchars($row['external_ref']) ?></small><?php endif; ?>
                                                <?php if(!empty($row['notes'])): ?><small class="text-muted d-block fst-italic"><?= htmlspecialchars(substr($row['notes'], 0, 50)) ?></small><?php endif; ?>
                                            <?php elseif($row['type_code'] == 'DN'): ?>
                                                <div class="fw-semibold">Debit Note Adjustment</div>
                                                <?php if(!empty($row['notes'])): ?><small class="text-muted d-block fst-italic"><?= htmlspecialchars(substr($row['notes'], 0, 50)) ?></small><?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-semibold text-danger">
                                            <?= $debit > 0 ? '₹'.number_format($debit, 2) : '-' ?>
                                        </td>
                                        <td class="text-end fw-semibold text-success">
                                            <?= $credit > 0 ? '₹'.number_format($credit, 2) : '-' ?>
                                        </td>
                                        <td class="text-end fw-bold">
                                            ₹<?= number_format(abs($running_balance), 2) ?> <small><?= $running_balance >= 0 ? 'Dr' : 'Cr' ?></small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Closing Balance -->
                                    <tr class="table-active fw-bold border-top border-2">
                                        <td colspan="4" class="text-end">Closing Balance</td>
                                        <td class="text-end text-danger">₹<?= number_format($total_debit, 2) ?></td>
                                        <td class="text-end text-success">₹<?= number_format($total_credit, 2) ?></td>
                                        <td class="text-end">₹<?= number_format(abs($running_balance), 2) ?> <small><?= $running_balance >= 0 ? 'Dr' : 'Cr' ?></small></td>
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
            initVendorSelect();
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
    #vl-print-area, #vl-print-area * { visibility: visible !important; }
    #vl-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<div id="vl-print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:4px;">
        <h2 style="margin:0; color:#5d282a; border-bottom:2px solid #4F46E5; padding-bottom:6px;">VENDOR LEDGER REPORT</h2>
    </div>
    <div id="vl-print-filters" style="margin:8px 0 12px 0; font-size:11px; color:#444; background:#fdf5e0; padding:6px 10px; border-left:3px solid #d7b251;"></div>
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead style="background:#4F46E5; color:#fff;">
            <tr>
                <th><?= ($vendor_id == 0) ? 'Vendor / Date' : 'Date' ?></th>
                <th>Ref No</th>
                <th>Type</th>
                <th>Particulars</th>
                <th>Debit</th>
                <th>Credit</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody id="vl-print-tbody"></tbody>
    </table>
    <p style="margin-top:8px; font-size:10px; color:#999; text-align:right;">Printed on: <span id="vl-print-date"></span></p>
</div>

<?php
$activeFiltersDisplay = [];
if (!empty($start_date))           $activeFiltersDisplay[] = '<strong>Date From:</strong> ' . date('d M Y', strtotime($start_date));
if (!empty($end_date))             $activeFiltersDisplay[] = '<strong>Date To:</strong> ' . date('d M Y', strtotime($end_date));
if (!empty($vendor_name_prefill))  $activeFiltersDisplay[] = '<strong>Vendor:</strong> ' . htmlspecialchars($vendor_name_prefill);

$filterHtml = !empty($activeFiltersDisplay) ? implode('&nbsp;&nbsp;|&nbsp;&nbsp;', $activeFiltersDisplay) : 'All Records';
?>

<script>
function printVendorLedger() {
    var rows = document.querySelectorAll('#vendor_ledger_table tbody tr');
    var tbody = document.getElementById('vl-print-tbody');
    tbody.innerHTML = '';
    rows.forEach(function(tr) {
        var tds = tr.querySelectorAll('td');
        if(tds.length < 7) {
             // Let's account for colspan rows (Opening balance, No records, Closing balance)
             if(tds.length === 1 && tds[0].colSpan) {
                  tbody.innerHTML += '<tr><td colspan="7" style="text-align:center;">' + tds[0].textContent.trim() + '</td></tr>';
             } else if(tds.length === 4) { // Closing balance row format
                  tbody.innerHTML += '<tr>' +
                  '<td colspan="4" style="text-align:right;"><b>' + tds[0].textContent.trim() + '</b></td>' +
                  '<td><b>' + tds[1].textContent.trim() + '</b></td>' +
                  '<td><b>' + tds[2].textContent.trim() + '</b></td>' +
                  '<td><b>' + tds[3].textContent.trim() + '</b></td>' +
                  '</tr>';
             } else if(tds.length === 5) { // Opening balance row format
                  tbody.innerHTML += '<tr>' +
                  '<td colspan="4" style="text-align:right;"><b>' + tds[0].textContent.trim() + '</b></td>' +
                  '<td><b>' + (tds[1] ? tds[1].textContent.trim() : '') + '</b></td>' +
                  '<td><b>' + (tds[2] ? tds[2].textContent.trim() : '') + '</b></td>' +
                  '<td><b>' + (tds[3] ? tds[3].textContent.trim() : '') + '</b></td>' +
                  '</tr>';
             }
             return;
        }
        tbody.innerHTML += '<tr>' +
            '<td>' + tds[0].textContent.trim() + '</td>' +
            '<td>' + tds[1].textContent.trim() + '</td>' +
            '<td>' + tds[2].textContent.trim() + '</td>' +
            '<td>' + tds[3].textContent.trim() + '</td>' +
            '<td>' + tds[4].textContent.trim() + '</td>' +
            '<td>' + tds[5].textContent.trim() + '</td>' +
            '<td>' + tds[6].textContent.trim() + '</td>' +
            '</tr>';
    });
    
    document.getElementById('vl-print-filters').innerHTML = '<?= addslashes($filterHtml) ?>';
    document.getElementById('vl-print-date').textContent = new Date().toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('vl-print-area').style.display = 'block';
    window.print();
    document.getElementById('vl-print-area').style.display = 'none';
}
</script>
