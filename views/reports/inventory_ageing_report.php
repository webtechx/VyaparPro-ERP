<?php
$title = "Inventory Ageing Summary Report";
require_once __DIR__ . '/../../config/auth_guard.php';

// Prepare base filter vars if not already
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
$sku = isset($_GET['sku']) ? trim($_GET['sku']) : '';

require_once __DIR__ . '/../../controller/reports/inventory_ageing_report.php';

// Prefill Item Name if filtered
$item_name_prefill = '';
if ($item_id > 0) {
    // Simple fetch
    $iStmt = $conn->prepare("SELECT item_name FROM items_listing WHERE item_id = ?");
    $iStmt->bind_param("i", $item_id);
    $iStmt->execute();
    $iStmt->bind_result($iName);
    if ($iStmt->fetch()) {
        $item_name_prefill = $iName;
    }
    $iStmt->close();
}
?>

<div class="container-fluid">
    <style>
        .select2-container .select2-selection--single { height: 38px; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
        .select2-dropdown { z-index: 9999999 !important; }
        
        .customer-text-primary { color: #2a3547; }
        .select2-results__option--highlighted .customer-text-primary { color: white !important; }
        .select2-results__option--highlighted .text-muted { color: #e9ecef !important; }
        .customer-select2-dropdown .select2-results__options { max-height: 400px !important; }
    </style>

    <div class="row mb-3">
        <div class="col-12">
            
             <!-- Filter Modal -->
            <div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Filter Report</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="" method="GET" id="filter_form">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Item</label>
                                        <select name="item_id" id="item_id" class="form-select">
                                            <?php if($item_id > 0 && !empty($item_name_prefill)): ?>
                                                <option value="<?= $item_id ?>" selected><?= htmlspecialchars($item_name_prefill) ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">SKU Code</label>
                                        <input type="text" name="sku" class="form-control" placeholder="Enter SKU Code" value="<?= htmlspecialchars($sku) ?>">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-warning" onclick="window.location.href=window.location.pathname"><i class="ti ti-refresh me-1"></i> Reset</button>
                            <button type="submit" form="filter_form" class="btn btn-primary"><i class="ti ti-filter me-1"></i> Apply</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Summary Cards -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-success-subtle text-success border-success h-100">
                                <div class="card-body p-3">
                                    <h6 class="card-subtitle mb-1">0 - 30 Days (Value)</h6>
                                    <h4 class="card-title mb-0">₹<?= number_format($total_0_30, 2) ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info-subtle text-info border-info h-100">
                                <div class="card-body p-3">
                                    <h6 class="card-subtitle mb-1">31 - 60 Days (Value)</h6>
                                    <h4 class="card-title mb-0">₹<?= number_format($total_31_60, 2) ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning-subtle text-warning border-warning h-100">
                                <div class="card-body p-3">
                                    <h6 class="card-subtitle mb-1">61 - 90 Days (Value)</h6>
                                    <h4 class="card-title mb-0">₹<?= number_format($total_61_90, 2) ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger-subtle text-danger border-danger h-100">
                                <div class="card-body p-3">
                                    <h6 class="card-subtitle mb-1">> 90 Days (Value)</h6>
                                    <h4 class="card-title mb-0">₹<?= number_format($total_90_plus, 2) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Inventory Ageing Summary</h5>
                    <div class="d-flex gap-2">
                        <?php $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>
                        <a href="<?= $basePath ?>/controller/reports/export_inventory_ageing_report.php<?= $qs ?>" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-info" onclick="printAgeingReport()">
                            <i class="ti ti-printer me-1"></i> Print
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="ti ti-filter me-1"></i> Filter</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped w-100 align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Item Name</th>
                                    <th>SKU</th>
                                    <th class="text-end">Current Stock</th>
                                    <th class="text-end">Total Value</th>
                                    <th class="text-end">0-30 Days</th>
                                    <th class="text-end">31-60 Days</th>
                                    <th class="text-end">61-90 Days</th>
                                    <th class="text-end">> 90 Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($inventoryData)): ?>
                                    <tr><td colspan="8" class="text-center text-muted py-4">No inventory found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($inventoryData as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark text-wrap" style="max-width: 250px;"><?= htmlspecialchars(ucwords(strtolower($row['item_name']))) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars(ucwords(strtolower($row['brand']))) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars(strtoupper($row['sku'] ?? '-')) ?></td>
                                        <td class="text-end fw-bold"><?= $row['current_stock'] ?></td>
                                        <td class="text-end">₹<?= number_format($row['total_value'], 2) ?></td>
                                        
                                        <td class="text-end text-success">₹<?= number_format($row['age_0_30'] ?? 0, 2) ?></td>
                                        <td class="text-end text-info">₹<?= number_format($row['age_31_60'] ?? 0, 2) ?></td>
                                        <td class="text-end text-warning">₹<?= number_format($row['age_61_90'] ?? 0, 2) ?></td>
                                        <td class="text-end text-danger fw-bold">₹<?= number_format($row['age_90_plus'] ?? 0, 2) ?></td>
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
            if (!$('#item_id').hasClass('select2-hidden-accessible')) initItemSelect();
        });
    }

    $(document).ready(function() {
        initItemSelect();
    });

    function initItemSelect() {
        const $item = $('#item_id');
        if ($item.hasClass('select2-hidden-accessible')) {
            $item.select2('destroy');
        }
        
        $item.select2({
            placeholder: 'Search Item...',
            width: '100%',
            dropdownParent: $('.modal-content'),
            allowClear: true,
            dropdownCssClass: 'customer-select2-dropdown',
            minimumInputLength: 0,
            ajax: {
                url: basePath + '/controller/billing/search_items_listing.php', 
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term || '' }; },
                processResults: function (data) {
                    if (!Array.isArray(data)) data = [];
                    return {
                        results: data.map(item => ({
                            id: item.id,
                            text: item.text,
                            stock: item.stock || item.current_stock, 
                            price: item.price || item.selling_price
                        }))
                    };
                }
            },
            templateResult: formatItemResult,
            templateSelection: formatItemSelection,
            escapeMarkup: m => m
        });
    }

    function formatItemResult(repo) {
        if (repo.loading) return repo.text;
        
        return `
            <div class="d-flex justify-content-between align-items-center py-1">
                <div class="customer-text-primary fw-semibold">${repo.text}</div>
                ${repo.stock ? `<div class="small text-muted">Stock: ${repo.stock}</div>` : ''}
            </div>`;
    }

    function formatItemSelection(repo) {
        return repo.text;
    }
</script>

<style>
@media print {
    body * { visibility: hidden !important; }
    #ageing-print-area, #ageing-print-area * { visibility: visible !important; }
    #ageing-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<div id="ageing-print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:16px;">
        <h3 style="margin:0;">Inventory Ageing Summary Report</h3>
        <p style="margin:4px 0; font-size:12px; color:#666;">Printed on: <span id="ageing-print-date"></span></p>
    </div>
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead style="background:#5d282a; color:#fff;">
            <tr>
                <th>Item Name</th>
                <th>SKU</th>
                <th>Current Stock</th>
                <th>Total Value</th>
                <th>0-30 Days</th>
                <th>31-60 Days</th>
                <th>61-90 Days</th>
                <th>&gt; 90 Days</th>
            </tr>
        </thead>
        <tbody id="ageing-print-tbody"></tbody>
    </table>
</div>

<script>
function printAgeingReport() {
    var rows = document.querySelectorAll('.card table tbody tr');
    var tbody = document.getElementById('ageing-print-tbody');
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
    document.getElementById('ageing-print-date').textContent = new Date().toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('ageing-print-area').style.display = 'block';
    window.print();
    document.getElementById('ageing-print-area').style.display = 'none';
}
</script>
