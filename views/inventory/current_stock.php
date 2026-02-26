<?php
$title = 'Current Stock';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Current Stock Inventory</h5>
                    <div class="d-flex gap-2">
                        <?php
                        // Build query string for export
                        $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
                        ?>
                        <a href="<?= $basePath ?>/controller/inventory/export_current_stock_excel.php<?= $qs ?>" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-info" onclick="printCurrentStock()">
                            <i class="ti ti-printer me-1"></i> Print
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table data-tables="basic" class="table table-striped dt-responsive align-middle mb-0" style="width: 100%;" id="current-stock-table">
                            <thead class="table-light text-uppercase fs-xxs">
                                <tr>
                                    <th>Brand</th>
                                    <th>SKU</th>
                                    <th>Item Name</th>
                                    <th class="text-center">Unit</th>
                                    <th class="text-end">Cost Value</th>
                                    <th class="text-center">Stock QTY</th>
                                    <th class="text-end">Total Value</th>
                                    <th class="text-end">Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT i.item_name, i.brand, i.stock_keeping_unit, i.current_stock, i.update_at, u.unit_name,
                                        (SELECT AVG(poi.rate) FROM purchase_order_items poi WHERE poi.item_id = i.item_id) as avg_rate
                                        FROM items_listing i 
                                        LEFT JOIN units_listing u ON i.unit_id = u.unit_id 
                                        WHERE 1=1";
                                
                                $sql .= " ORDER BY i.item_name ASC";
                                
                                $result = $conn->query($sql);
                                
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $stock = floatval($row['current_stock']);
                                        $avg_rate = floatval($row['avg_rate']);
                                        $total_value = $stock * $avg_rate;
                                        $badgeClass = $stock > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars(ucwords(strtolower($row['brand'])) ?: '-') ?></td>
                                            <td><code class="text-dark"><?= htmlspecialchars(strtoupper($row['stock_keeping_unit']) ?: '-') ?></code></td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars(ucwords(strtolower($row['item_name']))) ?></div>
                                            </td>
                                            <td class="text-center text-dark small"><?= htmlspecialchars(strtoupper($row['unit_name'])) ?></td>
                                            <td class="text-end text-dark"><?= number_format($avg_rate, 2) ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $badgeClass ?> fs-6 border min-w-50">
                                                    <?= $stock + 0 ?>
                                                </span>
                                            </td>
                                            <td class="text-end fw-bold text-dark">₹<?= number_format($total_value, 2) ?></td>
                                            <td class="text-end small text-dark"><?= $row['update_at'] ? date('d M Y', strtotime($row['update_at'])) : '-' ?></td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="8" class="text-center py-4 text-muted">No stock records found.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden !important; }
    #stock-print-area, #stock-print-area * { visibility: visible !important; }
    #stock-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<div id="stock-print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:16px;">
        <h3 style="margin:0;">Current Stock Inventory</h3>
        <p style="margin:4px 0; font-size:12px; color:#666;">Printed on: <span id="stock-print-date"></span></p>
    </div>
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead style="background:#5d282a; color:#fff;">
            <tr>
                <th>Brand</th>
                <th>SKU</th>
                <th>Item Name</th>
                <th>Unit</th>
                <th>Cost Value</th>
                <th>Stock QTY</th>
                <th>Total Value</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody id="stock-print-tbody"></tbody>
    </table>
</div>

<script>
function printCurrentStock() {
    var rows = document.querySelectorAll('#current-stock-table tbody tr');
    var tbody = document.getElementById('stock-print-tbody');
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
    document.getElementById('stock-print-date').textContent = new Date().toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('stock-print-area').style.display = 'block';
    window.print();
    document.getElementById('stock-print-area').style.display = 'none';
}
</script>
