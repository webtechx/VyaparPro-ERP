<?php
$title = "Product wise Purchase Report";
require_once __DIR__ . '/../../config/auth_guard.php';

// Prepare base filter vars if not already
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

require_once __DIR__ . '/../../controller/reports/product_wise_purchase_report.php';

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
    </style>

    <div class="row mb-3">
        <div class="col-12">
 
            <!-- Filter Modal -->
            <div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Filter Report</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="" method="GET" id="filter_form">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Date Range</label>
                                        <div class="input-group">
                                            <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                                            <span class="input-group-text">to</span>
                                            <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Item</label>
                                        <select name="item_id" id="item_id" class="form-select">
                                            <?php if($item_id > 0 && !empty($item_name_prefill)): ?>
                                                <option value="<?= $item_id ?>" selected><?= htmlspecialchars($item_name_prefill) ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <a href="<?= $basePath ?>/product_wise_purchase_report" class="btn btn-warning"><i class="ti ti-refresh me-1"></i> Reset</a>
                            <button type="submit" form="filter_form" class="btn btn-primary"><i class="ti ti-filter me-1"></i> Apply</button>
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
                                     <h6 class="card-subtitle mb-2">Total Purchase Value</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($total_purchase_all, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info-subtle text-info border-info mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Quantity Purchased</h6>
                                    <h3 class="card-title mb-0"><?= number_format($total_qty_purchased, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning-subtle text-warning border-warning mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Received GRN Qty</h6>
                                    <h3 class="card-title mb-0"><?= number_format($total_qty_received, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Purchases by Product</h5>
                    <div class="d-flex gap-2">
                        <?php $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>
                        <a href="<?= $basePath ?>/controller/reports/export_product_wise_purchase_excel.php<?= $qs ?>" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-info" onclick="printProductPurchase()">
                            <i class="ti ti-printer me-1"></i> Print
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="ti ti-filter me-1"></i> Filter</button>
                    </div>
                 </div>
                <div class="card-body">
                     
                    <table data-tables="basic" class="table table-hover table-bordered table-striped dt-responsive align-middle mb-0 w-100" id="product_purchase_table" style="width: 100%;">
                        <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">HSN/SAC</th>
                                    <th class="text-end">Avg. Rate</th>
                                    <th class="text-end">Qty Purchased</th>
                                    <th class="text-end">Received GRN Qty</th>
                                    <th class="text-end">Total Purchase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($reportData)): ?>
                                    <?php foreach ($reportData as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars(ucwords(strtolower($row['item_name']))) ?></div>
                                        </td>
                                        <td class="text-center"><?= htmlspecialchars($row['hsn_code'] ?? '-') ?></td>
                                        <td class="text-end">₹<?= number_format($row['avg_rate'], 2) ?></td>
                                        <td class="text-end fw-bold"><?= $row['total_qty'] ?></td>
                                        <td class="text-end fw-bold text-info"><?= $row['total_received'] ?></td>
                                        <td class="text-end fw-bold text-success">₹<?= number_format($row['total_purchase'], 2) ?></td>
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
            if (!$('#item_id').hasClass('select2-hidden-accessible')) initItemSelect();
        });
    }

    $(document).ready(function() {
        initItemSelect();
    });

    function initItemSelect() {
        $('#item_id').select2({
            placeholder: 'Search Item...',
            width: '100%',
            dropdownParent: $('#filterModal'),
            allowClear: true,
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
                            id: item.id || item.item_id,
                            text: item.text || item.item_name // e.g. "Item Name (Code)"
                        }))
                    };
                }
            }
        });
    }
</script>

<style>
@media print {
    body * { visibility: hidden !important; }
    #pp-print-area, #pp-print-area * { visibility: visible !important; }
    #pp-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<div id="pp-print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:4px;">
        <h2 style="margin:0; color:#5d282a; border-bottom:2px solid #d7b251; padding-bottom:6px;">PRODUCT WISE PURCHASE REPORT</h2>
    </div>
    <div id="pp-print-filters" style="margin:8px 0 12px 0; font-size:11px; color:#444; background:#fdf5e0; padding:6px 10px; border-left:3px solid #d7b251;"></div>
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead style="background:#5d282a; color:#fff;">
            <tr>
                <th>Item Name</th>
                <th>HSN/SAC</th>
                <th>Avg. Rate</th>
                <th>Qty Purchased</th>
                <th>Received GRN Qty</th>
                <th>Total Purchase</th>
            </tr>
        </thead>
        <tbody id="pp-print-tbody"></tbody>
    </table>
    <p style="margin-top:8px; font-size:10px; color:#999; text-align:right;">Printed on: <span id="pp-print-date"></span></p>
</div>

<?php
$activeFiltersDisplay = [];
if (!empty($start_date))           $activeFiltersDisplay[] = '<strong>Date From:</strong> ' . date('d M Y', strtotime($start_date));
if (!empty($end_date))             $activeFiltersDisplay[] = '<strong>Date To:</strong> ' . date('d M Y', strtotime($end_date));
if (!empty($item_name_prefill))    $activeFiltersDisplay[] = '<strong>Item:</strong> ' . htmlspecialchars($item_name_prefill);

$filterHtml = !empty($activeFiltersDisplay) ? implode('&nbsp;&nbsp;|&nbsp;&nbsp;', $activeFiltersDisplay) : 'All Records';
?>

<script>
function printProductPurchase() {
    var rows = document.querySelectorAll('#product_purchase_table tbody tr');
    if (!rows.length) rows = document.querySelectorAll('table.table tbody tr');
    var tbody = document.getElementById('pp-print-tbody');
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
    
    document.getElementById('pp-print-filters').innerHTML = '<?= addslashes($filterHtml) ?>';
    document.getElementById('pp-print-date').textContent = new Date().toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('pp-print-area').style.display = 'block';
    window.print();
    document.getElementById('pp-print-area').style.display = 'none';
}
</script>
