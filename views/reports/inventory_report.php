<?php
$title = "Inventory Report";
require_once __DIR__ . '/../../config/auth_guard.php';
require_once __DIR__ . '/../../controller/reports/inventory_report.php';
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
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Filter Inventory</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="" method="GET" id="filter_form">
                                <div class="row g-3">
                                    <!-- Search -->
                                    <div class="col-md-4">
                                        <label class="form-label">Search Item</label>
                                        <input type="text" name="q" class="form-control" placeholder="Search by Name or SKU" value="<?= htmlspecialchars($search_query) ?>">
                                    </div>

                                    <!-- Brand -->
                                    <div class="col-md-4">
                                        <label class="form-label">Brand</label>
                                        <select name="brand" id="filter_brand" class="form-select">
                                            <option value="">All Brands</option>
                                            <?php foreach($brands as $b): ?>
                                                <option value="<?= htmlspecialchars($b) ?>" <?= $brand_filter == $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Stock Status -->
                                    <div class="col-md-4">
                                         <label class="form-label">Stock Status</label>
                                         <select name="status" id="filter_status" class="form-select">
                                             <option value="">All Statuses</option>
                                             <option value="in_stock" <?= $status_filter == 'in_stock' ? 'selected' : '' ?>>In Stock (>0)</option>
                                             <option value="low_stock" <?= $status_filter == 'low_stock' ? 'selected' : '' ?>>Low Stock (<10)</option>
                                             <option value="out_of_stock" <?= $status_filter == 'out_of_stock' ? 'selected' : '' ?>>Out of Stock (0)</option>
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
                            <div class="card bg-primary-subtle text-primary border-primary mb-0">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Items</h6>
                                    <h3 class="card-title mb-0"><?= number_format($total_items) ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success-subtle text-success border-success mb-0">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Stock Qty</h6>
                                    <h3 class="card-title mb-0"><?= number_format($total_stock_qty, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning-subtle text-warning border-warning mb-0">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Stock Value (Purchase)</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($total_inventory_value, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- INVENTORY LIST -->
            <div class="card">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Inventory Report</h5>
                    <div class="d-flex gap-2">
                        <?php $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>
                        <a href="<?= $basePath ?>/controller/reports/export_inventory_report.php<?= $qs ?>" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-info" onclick="printInventoryReport()">
                            <i class="ti ti-printer me-1"></i> Print
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="ti ti-filter me-1"></i> Filter</button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 w-100" id="inventory_report_table">
                            <thead class="table-light">
                                <tr>
                                    <th>Item Name</th>
                                    <th>SKU</th>
                                    <th>Brand</th>
                                    <th class="text-center">Unit</th>
                                    <th class="text-end">Create Date</th>
                                    <th class="text-end">MRP</th>
                                    <th class="text-end">Purchase Price</th>
                                    <th class="text-center">Stock Qty</th>
                                    <th class="text-end">Total Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($inventory_items)): ?>
                                    <?php foreach ($inventory_items as $row): 
                                         $stock = (float)$row['current_stock'];
                                         $value = (float)$row['total_value'];
                                         
                                         // Stock styling
                                         $stockClass = 'bg-success-subtle text-success';
                                         if($stock <= 0) $stockClass = 'bg-danger-subtle text-danger';
                                         elseif($stock < 10) $stockClass = 'bg-warning-subtle text-warning';
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars(ucwords(strtolower($row['item_name']))) ?></div>
                                        </td>
                                        <td><code class="text-dark"><?= htmlspecialchars(strtoupper($row['stock_keeping_unit'] ?: '-')) ?></code></td>
                                        <td><?= htmlspecialchars(ucwords(strtolower($row['brand'] ?: '-'))) ?></td>
                                        <td class="text-center small"><?= htmlspecialchars(strtoupper($row['unit_name'] ?: '-')) ?></td>
                                        <td class="text-end text-muted"><?= $row['create_at'] ? date('d M Y', strtotime($row['create_at'])) : '-' ?></td>
                                        <td class="text-end text-muted">₹<?= number_format($row['mrp'], 2) ?></td>
                                        <td class="text-end">₹<?= number_format($row['purchase_price'], 2) ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $stockClass ?> fs-5">
                                                <?= $stock + 0 ?>
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold">₹<?= number_format($value, 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">No items found matching your criteria.</td>
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
    // Initialize Select2 when modal is shown
    const filterModal = document.getElementById('filterModal');
    if (filterModal) {
        filterModal.addEventListener('shown.bs.modal', function () {
            if (!$('#filter_brand').hasClass('select2-hidden-accessible')) {
                $('#filter_brand').select2({
                    placeholder: "Select Brand",
                    allowClear: true,
                    dropdownParent: $('#filterModal'),
                    width: '100%'
                });
            }
        });
    }

    // Initialize on load as well if needed, though mostly modal driven now
    $(document).ready(function() {
         // Optional: Init branding select2 immediately if you want it ready, 
         // but inside modal it's better to init on show or check visibility
    });

</script>

<style>
@media print {
    body * { visibility: hidden !important; }
    #inv-report-print-area, #inv-report-print-area * { visibility: visible !important; }
    #inv-report-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<div id="inv-report-print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:4px;">
        <h2 style="margin:0; color:#5d282a; border-bottom:2px solid #d7b251; padding-bottom:6px;">INVENTORY REPORT</h2>
    </div>
    <div id="inv-report-print-filters" style="margin:8px 0 12px 0; font-size:11px; color:#444; background:#fdf5e0; padding:6px 10px; border-left:3px solid #d7b251;"></div>
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead style="background:#5d282a; color:#fff;">
            <tr>
                <th>Item Name</th>
                <th>SKU</th>
                <th>Brand</th>
                <th>Unit</th>
                <th>Create Date</th>
                <th>MRP</th>
                <th>Purchase Price</th>
                <th>Stock Qty</th>
                <th>Total Value</th>
            </tr>
        </thead>
        <tbody id="inv-report-print-tbody"></tbody>
    </table>
    <p style="margin-top:8px; font-size:10px; color:#999; text-align:right;">Printed on: <span id="inv-report-print-date"></span></p>
</div>

<?php
$activeFiltersDisplay = [];
if (!empty($search_query))       $activeFiltersDisplay[] = '<strong>Search:</strong> ' . htmlspecialchars($search_query);
if (!empty($brand_filter))       $activeFiltersDisplay[] = '<strong>Brand:</strong> ' . htmlspecialchars($brand_filter);
$status_label = match($status_filter) {
    'in_stock' => 'In Stock (>0)',
    'low_stock' => 'Low Stock (<10)',
    'out_of_stock' => 'Out of Stock (0)',
    default => 'All Statuses'
};
if (!empty($status_filter))      $activeFiltersDisplay[] = '<strong>Status:</strong> ' . htmlspecialchars($status_label);

$filterHtml = !empty($activeFiltersDisplay) ? implode('&nbsp;&nbsp;|&nbsp;&nbsp;', $activeFiltersDisplay) : 'All Records';
?>

<script>
function printInventoryReport() {
    var rows = document.querySelectorAll('#inventory_report_table tbody tr');
    var tbody = document.getElementById('inv-report-print-tbody');
    tbody.innerHTML = '';
    rows.forEach(function(tr) {
        var tds = tr.querySelectorAll('td');
        if(tds.length < 9) return;
        tbody.innerHTML += '<tr>' +
            '<td>' + tds[0].textContent.trim() + '</td>' +
            '<td>' + tds[1].textContent.trim() + '</td>' +
            '<td>' + tds[2].textContent.trim() + '</td>' +
            '<td>' + tds[3].textContent.trim() + '</td>' +
            '<td>' + tds[4].textContent.trim() + '</td>' +
            '<td>' + tds[5].textContent.trim() + '</td>' +
            '<td>' + tds[6].textContent.trim() + '</td>' +
            '<td>' + tds[7].textContent.trim() + '</td>' +
            '<td>' + tds[8].textContent.trim() + '</td>' +
            '</tr>';
    });
    
    document.getElementById('inv-report-print-filters').innerHTML = '<?= addslashes($filterHtml) ?>';
    document.getElementById('inv-report-print-date').textContent = new Date().toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('inv-report-print-area').style.display = 'block';
    window.print();
    document.getElementById('inv-report-print-area').style.display = 'none';
}
</script>
