<?php
// views/customers/distribute_percentage.php
require_once __DIR__ . '/../../config/conn.php';
$title = "Distribute Percentage";

// Fetch Undistributed Targets
$sql = "SELECT * FROM monthly_targets WHERE distributed = '0' ORDER BY created_at ASC";
$result = $conn->query($sql);
$pendingTargets = [];
if ($result && $result->num_rows > 0) {
    $currentDate = strtotime(date("1 F Y"));
    $prevDate = strtotime("-1 month", $currentDate);

    while($row = $result->fetch_assoc()) {
        $targetDate = strtotime("1 " . $row['month'] . " " . $row['year']);
        // Show only Previous and Current Month
        if ($targetDate >= $prevDate && $targetDate <= $currentDate) {
            $pendingTargets[] = $row;
        }
    }
}

// Fetch Distribution History
$historySql = "SELECT il.*, 
               e.first_name, e.last_name, 
               mt.month, mt.year 
               FROM incentive_ledger il 
               JOIN employees e ON il.employee_id = e.employee_id 
               JOIN monthly_targets mt ON il.monthly_target_id = mt.id 
               ORDER BY il.distribution_date DESC LIMIT 50";
$historyResult = $conn->query($historySql);
?>

<div class="container-fluid">
    <div class="row">
        <!-- Distribution Form -->
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0 text-white"><i class="ti ti-coins me-2"></i>Distribute Incentives</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_GET['success']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_GET['error']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if(empty($pendingTargets)): ?>
                        <div class="text-center py-4">
                            <i class="ti ti-check-circle text-success fs-1 mb-3"></i>
                            <h5>All targets have been distributed!</h5>
                            <p class="text-muted">No pending monthly targets found.</p>
                        </div>
                    <?php else: ?>
                        <table data-tables="basic" class="table table-striped dt-responsive align-middle mb-0 w-100" style="width: 100%;">
                            <thead class="bg-light">
                                    <tr>
                                        <th>Period</th>
                                        <th>Total Target</th>
                                        <th>Pool Amount</th>
                                        <th>Manager Share</th>
                                        <th>Team Share</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($pendingTargets as $target): 
                                        $pool = $target['total_target'] * ($target['incentive_percent'] / 100);
                                        $mgrShare = $pool * ($target['manager_share_percent'] / 100);
                                        $teamShare = $pool * ($target['team_share_percent'] / 100);

                                        // Calculate Total Sales for the Target Month
                                        $monthNum = date("m", strtotime($target['month']));
                                        $year = $target['year'];
                                        $salesSql = "SELECT SUM(total_amount) as total_sales FROM sales_invoices 
                                                     WHERE MONTH(invoice_date) = '$monthNum' AND YEAR(invoice_date) = '$year' AND status != 'Cancelled'"; // Assuming 'Cancelled' status should be excluded
                                        $salesResult = $conn->query($salesSql);
                                        $totalSales = 0;
                                        if ($salesResult && $row = $salesResult->fetch_assoc()) {
                                            $totalSales = $row['total_sales'] ?? 0;
                                        }

                                        $isAchieved = $totalSales >= $target['total_target'];
                                    ?>
                                    <tr>
                                        <td class="fw-bold">
                                            <?= $target['month'] . ' ' . $target['year'] ?>
                                            <div class="small fw-normal text-muted mt-1">
                                                Sales: ₹<?= number_format($totalSales, 2) ?>
                                                <?php if($isAchieved): ?>
                                                    <span class="badge bg-success-subtle text-success ms-1">Achieved</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger ms-1">Short: ₹<?= number_format($target['total_target'] - $totalSales, 2) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?= number_format($target['total_target'], 2) ?></td>
                                        <td class="text-primary fw-bold">₹ <?= number_format($pool, 2) ?></td>
                                        <td><?= number_format($mgrShare, 2) ?> (<?= $target['manager_share_percent'] ?>%)</td>
                                        <td><?= number_format($teamShare, 2) ?> (<?= $target['team_share_percent'] ?>%)</td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-success shadow-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#distributeModal"
                                                    data-id="<?= $target['id'] ?>"
                                                    data-period="<?= $target['month'] . ' ' . $target['year'] ?>"
                                                    data-pool="<?= $pool ?>"
                                                    data-mgr="<?= $mgrShare ?>"
                                                    data-team="<?= $teamShare ?>"
                                                    <?= (!$isAchieved) ? 'disabled' : '' ?>
                                            >
                                                <i class="ti ti-check me-1"></i> Distribute
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Distribution History / Ledger -->
        <div class="col-12 mt-4">
            <div class="card">
                 <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Distribution Ledger (Recent History)</h5>
                     <!-- <button class="btn btn-sm btn-light-primary"><i class="ti ti-download me-1"></i> Export</button> -->
                </div>
                <div class="card-body">
                    <table data-tables="basic" class="table table-sm table-hover dt-responsive align-middle mb-0 w-100" id="ledgerTable" style="width: 100%;">
                        <thead class="bg-light fs-xs text-uppercase">
                                <tr>
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Target Month</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($historyResult && $historyResult->num_rows > 0): ?>
                                    <?php while($txn = $historyResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('d M Y H:i', strtotime($txn['distribution_date'])) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-2">
                                                    <span class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                        <?= strtoupper(substr($txn['first_name'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fs-sm"><?= htmlspecialchars($txn['first_name'] . ' ' . $txn['last_name']) ?></h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark"><?= $txn['month'] . ' ' . $txn['year'] ?></span></td>
                                        <td>
                                            <?php if($txn['distribution_type'] == 'manager'): ?>
                                                <span class="badge bg-soft-info text-info">Manager</span>
                                            <?php elseif($txn['distribution_type'] == 'team'): ?>
                                                <span class="badge bg-soft-success text-success">Team</span>
                                            <?php else: ?>
                                                <span class="badge bg-soft-warning text-warning">Manual</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold text-success">₹ <?= number_format($txn['amount'], 2) ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($txn['notes']) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Distribute Modal -->
<div class="modal fade" id="distributeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= $basePath ?>/controller/purchase/save_distribution.php" method="POST" id="distributeForm">
                <input type="hidden" name="target_id" id="modal_target_id">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white">Confirm Distribution: <span id="modal_period"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-soft-warning border-warning">
                        <i class="ti ti-alert-triangle me-2"></i>
                        <strong>Warning:</strong> This action is irreversible. Once distributed, the amounts will be credited to employees' ledgers and the target will be locked.
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded text-center">
                                <small class="text-muted d-block text-uppercase fw-bold">Total Pool</small>
                                <h4 class="mb-0 text-primary">₹ <span id="modal_pool">0.00</span></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded text-center">
                                <small class="text-muted d-block text-uppercase fw-bold">Manager Share</small>
                                <h5 class="mb-0">₹ <span id="modal_mgr">0.00</span></h5>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded text-center">
                                <small class="text-muted d-block text-uppercase fw-bold">Team Share</small>
                                <h5 class="mb-0">₹ <span id="modal_team">0.00</span></h5>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3 border-bottom pb-2">Employee Breakdown Preview</h6>
                    <div id="employee_preview_container" class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Calculating eligible employees and shares...</p>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="confirm_distribution" class="btn btn-success"><i class="ti ti-check me-1"></i> Confirm & Distribute</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const distributeModal = document.getElementById('distributeModal');
    
    distributeModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        
        // Extract data
        const id = button.getAttribute('data-id');
        const period = button.getAttribute('data-period');
        const pool = parseFloat(button.getAttribute('data-pool'));
        const mgr = parseFloat(button.getAttribute('data-mgr'));
        const team = parseFloat(button.getAttribute('data-team'));
        
        // Update Modal UI
        document.getElementById('modal_target_id').value = id;
        document.getElementById('modal_period').textContent = period;
        
        document.getElementById('modal_pool').textContent = pool.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('modal_mgr').textContent = mgr.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('modal_team').textContent = team.toLocaleString(undefined, {minimumFractionDigits: 2});

        // Fetch Preview
        fetchPreview(id);
    });

    function fetchPreview(targetId) {
        const container = document.getElementById('employee_preview_container');
        container.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
        
        const formData = new FormData();
        formData.append('target_id', targetId);
        formData.append('action', 'preview');

        const url = 'controller/purchase/save_distribution.php';
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
        })
        .catch(err => {
            container.innerHTML = '<p class="text-danger">Error loading preview.</p>';
            console.error(err);
        });
    }
});
</script>
