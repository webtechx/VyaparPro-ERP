<?php
$title = "User Collection Details";
require_once __DIR__ . '/../../config/auth_guard.php';

// Prepare base filter vars if not already
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

require_once __DIR__ . '/../../controller/reports/user_collection_details.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            
            <!-- FILTER MODAL -->
            <div class="modal fade" id="filterModal" aria-hidden="true" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Filter Report</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="" method="GET" id="filter_form">
                                <input type="hidden" name="user_id" value="<?= $user_id ?>">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Date Range</label>
                                        <div class="input-group">
                                            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                                            <span class="input-group-text">to</span>
                                            <input type="date" name="end_date"   class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Search</label>
                                        <input type="text" name="q" class="form-control" placeholder="Payment # / Reference / Customer" value="<?= htmlspecialchars($search_query) ?>">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-warning " onclick="window.location.href='?user_id=<?= $user_id ?>'"><i class="ti ti-refresh me-1"></i> Reset</button>
                            <button type="submit" form="filter_form" class="btn btn-primary"><i class="ti ti-filter me-1"></i> Apply Filter</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h6 class="text-muted mb-1">User</h6>
                            <h4 class="mb-0"><?= htmlspecialchars($user_name) ?></h4>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-primary-subtle text-primary border-primary mb-0 mt-3 mt-md-0">
                                <div class="card-body py-3">
                                    <h6 class="card-subtitle mb-1">Total Collection</h6>
                                    <h4 class="card-title mb-0">₹<?= number_format($total_amount, 2) ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info-subtle text-info border-info mb-0 mt-3 mt-md-0">
                                <div class="card-body py-3">
                                    <h6 class="card-subtitle mb-1">Total Transactions</h6>
                                    <h4 class="card-title mb-0"><?= number_format($count_transactions, 0) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAYMENT LIST -->
            <div class="card">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Collection Details</h5>
                    <div>
                        <button title="Back" onclick="window.location.href='<?= $basePath ?>/user_wise_collection_report?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>'" class="btn btn-warning"><i class="ti ti-arrow-left me-1"></i>Back</button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="ti ti-filter me-1"></i>Filter</button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 w-100">
                            <thead class="table-light">
                                 <tr>
                                    <th>Date</th>
                                    <th>Created At</th>
                                    <th>Payment #</th>
                                    <th>Customer</th>
                                    <th>Mode</th>
                                    <th>Reference #</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                    <?php if (!empty($payments)): ?>
                                        <?php foreach ($payments as $row):
                                            $custDisplay = $row['company_name']
                                                ? htmlspecialchars($row['company_name']) . '<br><small class="text-muted">' . htmlspecialchars($row['customer_name']) . '</small>'
                                                : htmlspecialchars($row['customer_name']);
                                        ?>
                                        <tr>
                                            <td><?= date('d M Y', strtotime($row['payment_date'])) ?></td>
                                            <td><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
                                            <td class="fw-bold">
                                                <a href="<?= $basePath ?>/views/payment/view_payment.php?id=<?= $row['payment_id'] ?>"><?= htmlspecialchars($row['payment_number']) ?></a>
                                            </td>
                                            <td><?= $custDisplay ?></td>
                                            <td><span class="badge bg-secondary"><?= ucfirst($row['payment_mode']) ?></span></td>
                                            <td><?= htmlspecialchars($row['reference_no'] ?? '-') ?></td>
                                            <td class="text-end fw-bold text-success">₹<?= number_format($row['amount'], 2) ?></td>
                                            <td class="text-center">
                                                <a href="<?= $basePath ?>/views/payment/view_payment.php?id=<?= $row['payment_id'] ?>" class="btn btn-sm btn-light" title="View"><i class="ti ti-eye"></i></a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">No collections found matching criteria.</td>
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
