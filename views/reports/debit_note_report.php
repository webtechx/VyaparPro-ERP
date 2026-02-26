<?php
$title = "Debit Note Report";
require_once __DIR__ . '/../../config/auth_guard.php';
require_once __DIR__ . '/../../controller/reports/debit_note_report.php';
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

                                    <!-- Search -->
                                    <div class="col-md-4">
                                        <label class="form-label">Search</label>
                                        <input type="text" name="q" class="form-control" placeholder="DN Number / PO #" value="<?= htmlspecialchars($search_query) ?>">
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
                            <div class="card bg-danger-subtle text-danger border-danger mb-0">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Debit Amount</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($total_debit_amount, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info-subtle text-info border-info mb-0">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Debit Notes</h6>
                                    <h3 class="card-title mb-0"><?= $count_notes ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                             <div class="card bg-warning-subtle text-warning border-warning mb-0">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Avg. Note Value</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($count_notes > 0 ? $total_debit_amount / $count_notes : 0, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LIST -->
            <div class="card">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Debit Note Report</h5>
                    <div class="d-flex gap-2">
                        <?php $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>
                        <a href="<?= $basePath ?>/controller/reports/export_debit_note_report_excel.php<?= $qs ?>" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-info" onclick="printDebitNoteReport()">
                            <i class="ti ti-printer me-1"></i> Print
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="ti ti-filter me-1"></i> Filter</button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 w-100" id="debit_note_report_table">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>DN Number</th>
                                    <th>PO #</th>
                                    <th>Vendor</th>
                                    <th>Remarks</th>
                                    <th class="text-end">Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($debit_notes)): ?>
                                    <?php foreach ($debit_notes as $row): 
                                        $venName = $row['company_name'] ? $row['company_name'] . ' <br><small class="text-muted">' . $row['vendor_name'] . '</small>' : $row['vendor_name'];
                                    ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($row['debit_note_date'])) ?></td>
                                        <td class="fw-bold"><?= $row['debit_note_number'] ?></td>
                                        <td>
                                            <a href="<?= $basePath ?>/view_po?id=<?= intval($row['po_id']??0) ?>" class="text-dark"><?= $row['po_number'] ?: '-' ?></a>
                                        </td>
                                        <td><?= $venName ?></td>
                                        <td><?= htmlspecialchars($row['remarks']) ?></td>
                                        <td class="text-end fw-bold">₹<?= number_format($row['total_amount'], 2) ?></td>
                                        <td>
                                            <a href="<?= $basePath ?>/debit_note_view?id=<?= $row['id'] ?? $row['debit_note_id'] ?>" class="btn btn-sm btn-light" title="View"><i class="ti ti-eye"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No debit notes found matching criteria.</td>
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

<!-- ═══════════════════════════════════════════ -->
<!-- PRINT AREA                                  -->
<!-- ═══════════════════════════════════════════ -->
<style>
@media print {
    body * { visibility: hidden !important; }
    #dnr-print-area, #dnr-print-area * { visibility: visible !important; }
    #dnr-print-area { position: absolute; left: 0; top: 0; width: 100%; padding: 20px; }
}
</style>

<div id="dnr-print-area" style="display:none;">
    <!-- Title -->
    <div style="text-align:center; margin-bottom:8px;">
        <h2 style="margin:0; color:#5d282a; border-bottom:2px solid #d7b251; padding-bottom:6px; font-size:18px;">DEBIT NOTE REPORT</h2>
        <p style="margin:4px 0 0; font-size:11px; color:#555;">
            Period: <?= date('d M Y', strtotime($start_date)) ?> to <?= date('d M Y', strtotime($end_date)) ?>
        </p>
    </div>

    <!-- Active Filters -->
    <div id="dnr-print-filters" style="margin:6px 0 10px; font-size:10px; color:#333; background:#fff8f0; padding:5px 10px; border-left:4px solid #5D282A; display:none;"></div>

    <!-- Summary Cards -->
    <div style="display:flex; gap:10px; margin-bottom:12px;">
        <div style="flex:1; border:1px solid #f5c6cb; border-radius:4px; padding:8px; text-align:center; background:#fff5f5;">
            <div style="font-size:9px; color:#721c24; margin-bottom:2px;">Total Debit Amount</div>
            <div style="font-size:15px; font-weight:bold; color:#dc3545;">₹<?= number_format($total_debit_amount, 2) ?></div>
        </div>
        <div style="flex:1; border:1px solid #bee5eb; border-radius:4px; padding:8px; text-align:center; background:#f0faff;">
            <div style="font-size:9px; color:#0c5460; margin-bottom:2px;">Total Debit Notes</div>
            <div style="font-size:15px; font-weight:bold; color:#17a2b8;"><?= $count_notes ?></div>
        </div>
        <div style="flex:1; border:1px solid #ffc107; border-radius:4px; padding:8px; text-align:center; background:#fffdf0;">
            <div style="font-size:9px; color:#856404; margin-bottom:2px;">Avg. Note Value</div>
            <div style="font-size:15px; font-weight:bold; color:#fd7e14;">₹<?= number_format($count_notes > 0 ? $total_debit_amount / $count_notes : 0, 2) ?></div>
        </div>
    </div>

    <!-- Table -->
    <table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:10px;">
        <thead>
            <tr style="background:#5d282a; color:#fff;">
                <th style="padding:6px; text-align:left;">Date</th>
                <th style="padding:6px; text-align:left;">DN Number</th>
                <th style="padding:6px; text-align:left;">PO #</th>
                <th style="padding:6px; text-align:left;">Vendor</th>
                <th style="padding:6px; text-align:left;">Remarks</th>
                <th style="padding:6px; text-align:right;">Amount (₹)</th>
            </tr>
        </thead>
        <tbody id="dnr-print-tbody"></tbody>
        <tfoot>
            <tr style="background:#e8e8ff; font-weight:bold;">
                <td colspan="5" style="padding:5px;">TOTAL</td>
                <td style="text-align:right; padding:5px;">₹<?= number_format($total_debit_amount, 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <p style="margin-top:10px; font-size:9px; color:#999; text-align:right;">
        Printed on: <span id="dnr-print-date"></span>
    </p>
</div>

<?php
$activeFiltersDisplay = [];
if (!empty($start_date))           $activeFiltersDisplay[] = '<strong>Date From:</strong> ' . date('d M Y', strtotime($start_date));
if (!empty($end_date))             $activeFiltersDisplay[] = '<strong>Date To:</strong> ' . date('d M Y', strtotime($end_date));
if (!empty($vendor_name_prefill))  $activeFiltersDisplay[] = '<strong>Vendor:</strong> ' . htmlspecialchars($vendor_name_prefill);
if (!empty($search_query))         $activeFiltersDisplay[] = '<strong>Search:</strong> ' . htmlspecialchars($search_query);

$filterHtml = !empty($activeFiltersDisplay) ? implode('&nbsp;&nbsp;|&nbsp;&nbsp;', $activeFiltersDisplay) : 'All Records';
?>

<script>
function printDebitNoteReport() {
    // Build tbody from live table
    var rows  = document.querySelectorAll('#debit_note_report_table tbody tr');
    var tbody = document.getElementById('dnr-print-tbody');
    tbody.innerHTML = '';

    rows.forEach(function(tr, i) {
        var tds = tr.querySelectorAll('td');
        if (tds.length < 6) return;
        var bg = (i % 2 === 0) ? '#ffffff' : '#f7f7ff';
        tbody.innerHTML +=
            '<tr style="background:' + bg + ';">' +
            '<td style="padding:5px;">'                  + tds[0].textContent.trim() + '</td>' +
            '<td style="padding:5px; font-weight:bold;">' + tds[1].textContent.trim() + '</td>' +
            '<td style="padding:5px;">'                  + tds[2].textContent.trim() + '</td>' +
            '<td style="padding:5px;">'                  + tds[3].textContent.trim() + '</td>' +
            '<td style="padding:5px;">'                  + tds[4].textContent.trim() + '</td>' +
            '<td style="text-align:right; padding:5px; font-weight:bold;">' + tds[5].textContent.trim() + '</td>' +
            '</tr>';
    });

    document.getElementById('dnr-print-filters').innerHTML = '<?= addslashes($filterHtml) ?>';
    document.getElementById('dnr-print-filters').style.display = 'block';

    // Timestamp
    document.getElementById('dnr-print-date').textContent =
        new Date().toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });

    // Show → Print → Hide
    var area = document.getElementById('dnr-print-area');
    area.style.display = 'block';
    window.print();
    area.style.display = 'none';
}
</script>
