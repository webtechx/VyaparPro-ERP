<?php
$title = "Products Sales & Purchase Price Comparison";
require_once __DIR__ . '/../../controller/reports/product_price_comparison_report.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            
            <!-- Filter Modal -->
            <div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Filter Report</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="" method="GET" id="filter_form">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Search Item</label>
                                        <select name="search_item_id" id="search_item_id" class="form-select"></select>
                                    </div>
                                    <div class="col-md-4">
                                         <label class="form-label">Search SKU</label>
                                         <input type="text" name="sku" class="form-control" placeholder="Enter SKU..." value="<?= htmlspecialchars($sku_filter ?? '') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Filter by Brand</label>
                                        <select name="brand" class="form-select">
                                            <option value="">All Brands</option>
                                            <?php foreach ($brands as $brand): ?>
                                                <option value="<?= htmlspecialchars($brand) ?>" <?= $brand_filter === $brand ? 'selected' : '' ?>><?= htmlspecialchars($brand) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <a href="<?= $basePath ?>/product_price_comparison_report" class="btn btn-warning"><i class="ti ti-refresh me-1"></i> Reset</a>
                            <button type="submit" form="filter_form" class="btn btn-primary"><i class="ti ti-filter me-1"></i> Apply Filter</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Products Sales & Purchase Price Comparison</h5>
                    <div class="d-print-none">
                        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="ti ti-filter me-1"></i> Filter</button>
                        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="ti ti-printer me-1"></i> Print</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-bordered align-middle mb-0" id="report_table">
                            <thead class="table-light align-middle text-center">
                                <tr>
                                    <th class="text-start" style="width: 25%;">Item Name</th>
                                    <th>Brand</th>
                                    <th>SKU</th>
                                    <th class="bg-primary-subtle text-primary fw-bold">Current SP</th>
                                    <th class="bg-warning-subtle text-warning-emphasis fw-bold">Last Purchase Price</th>
                                    <th>Avg Purchase Price</th>
                                    <th>Margin (Current SP - Last PP)</th>
                                    <th>Comparison Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data)): ?>
                                    <tr><td colspan="8" class="text-center py-4 text-muted">No items found matching your criteria.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($data as $row): 
                                        $sp = (float)$row['current_selling_price'];
                                        $last_pp = (float)($row['last_purchase_price'] ?? 0);
                                        $avg_pp = (float)($row['avg_purchase_price'] ?? 0);
                                        
                                        $margin = ($last_pp > 0) ? ($sp - $last_pp) : ($sp - $avg_pp); // Fallback to Avg if Last not available? 
                                        // Usually explicit Last is preferred. If Last is 0, margin is N/A logically or compared to 0 cost.
                                        
                                        // Percentage Margin
                                        $margin_pct = ($sp > 0) ? ($margin / $sp) * 100 : 0;
                                        
                                        $statusClass = 'text-muted';
                                        $statusText = '-';
                                        
                                        if ($last_pp > 0) {
                                            if ($sp < $last_pp) {
                                                $statusClass = 'badge bg-danger-subtle text-danger';
                                                $statusText = 'Loss Making'; // Selling below cost!
                                            } elseif ($margin_pct < 10) {
                                                $statusClass = 'badge bg-warning-subtle text-warning';
                                                $statusText = 'Low Margin (<10%)';
                                            } else {
                                                $statusClass = 'badge bg-success-subtle text-success';
                                                $statusText = 'Healthy margin';
                                            }
                                        } else {
                                            $statusText = 'No Purchase History';
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($row['item_name']) ?></div>
                                            <small class="text-muted d-block text-truncate" style="max-width: 200px;"><?= htmlspecialchars($row['brand'] ?? '') ?></small>
                                        </td>
                                        <td class="text-center"><?= htmlspecialchars($row['brand'] ?? '-') ?></td>
                                        <td class="text-center"><small><?= htmlspecialchars($row['stock_keeping_unit'] ?? '-') ?></small></td>
                                        
                                        <td class="text-end fw-bold text-primary">₹<?= number_format($sp, 2) ?></td>
                                        
                                        <td class="text-end fw-bold text-warning-emphasis">
                                            <?php if($last_pp > 0): ?>
                                                ₹<?= number_format($last_pp, 2) ?>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td class="text-end">
                                            <?php if($avg_pp > 0): ?>
                                                ₹<?= number_format($avg_pp, 2) ?>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td class="text-end">
                                            <?php if($last_pp > 0): ?>
                                                <div class="<?= $margin < 0 ? 'text-danger fw-bold' : 'text-success fw-bold' ?>">
                                                    ₹<?= number_format($margin, 2) ?>
                                                </div>
                                                <small class="text-muted sub-text"><?= number_format($margin_pct, 1) ?>%</small>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td class="text-center">
                                            <span class="<?= $statusClass ?>"><?= $statusText ?></span>
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

    // Initialize Select2
    window.addEventListener('load', function() {
        if (typeof $ !== 'undefined') {
            const filterModal = document.getElementById('filterModal');
            if (filterModal) {
                filterModal.addEventListener('shown.bs.modal', function () {
                   initItemSelect();
                });
            }
        }
    });

    function initItemSelect() {
        const $item = $('#search_item_id');
        if ($item.hasClass('select2-hidden-accessible')) return; 

        $item.select2({
            placeholder: 'Search Item...',
            width: '100%',
            dropdownParent: $('#filterModal .modal-content'), // Use .modal-content to ensure it's inside
            allowClear: true,
            ajax: {
                url: basePath + '/controller/billing/search_items_listing.php',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term || '' };
                },
                processResults: function(data) {
                    return {
                        results: $.map(data, function(item) {
                            return {
                                id: item.id || item.item_id,
                                text: item.item_name || item.text
                            }
                        })
                    };
                }
            }
        });

        // Pre-select logic
        <?php 
        $pre_id = isset($_GET['search_item_id']) ? intval($_GET['search_item_id']) : 0;
        if($pre_id > 0) {
            $preName = '';
            // Ideally should fetch from DB if not in $data, but let's try finding in $data first
            foreach($data as $d) {
                if($d['item_id'] == $pre_id) {
                    $preName = $d['item_name'];
                    break;
                }
            }
            if(!empty($preName)) {
                echo "var option = new Option('".addslashes($preName)."', '".$pre_id."', true, true);";
                echo "$item.append(option).trigger('change');";
            }
        }
        ?>
    }
</script>
