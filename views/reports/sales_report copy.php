<?php
$title = 'Sales Report';
require_once __DIR__ . '/../../controller/reports/sales_report.php';
?>

<style>
    /* ── Customer Select2 Styles (identical to payment_received) ── */
    .customer-text-primary  { color: #2a3547; }

    .select2-results__option--highlighted .customer-text-primary  { color: white !important; }
    .select2-results__option--highlighted .customer-text-secondary { color: #e9ecef !important; }
    .select2-results__option--highlighted .status-badge            { background-color: rgba(255,255,255,0.2) !important; color: white !important; }
    .select2-results__option--highlighted .text-dark               { color: white !important; }
    .select2-results__option--highlighted .text-muted              { color: #e9ecef !important; }
    .select2-results__option--highlighted .badge                   { background-color: rgba(255,255,255,0.2) !important; color: white !important; border-color: white !important; }
    .select2-results__option--highlighted .text-success            { color: white !important; }
    .select2-results__option--highlighted .text-danger             { color: white !important; }
    .select2-results__option--highlighted .customer-avatar         { background-color: white !important; color: #5d87ff !important; }

    .customer-select2-dropdown .select2-results__options { max-height: 400px !important; }
    .customer-select2-dropdown .select2-results__options li:last-child {
        position: sticky; bottom: 0;
        background-color: #fff; z-index: 51;
        box-shadow: 0 -2px 5px rgba(0,0,0,0.05);
    }

    .select2-container--open .select2-dropdown { z-index: 9999999 !important; }
    .select2-dropdown                           { z-index: 9999999 !important; }
</style>

<!-- Hidden input to pass basePath to the deferred script -->
<input type="hidden" id="sr_base_path" value="<?= $basePath ?>">

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h4 class="card-title mb-0">Sales Report</h4>
                    </div>

                    <!-- ── Filters ── -->
                    <form method="GET" action="<?= $basePath ?>/sales_report" id="sales_report_filter_form" class="row g-3 mb-4 align-items-end">

                        <!-- Date Range -->
                        <div class="col-md-3">
                            <label class="form-label">Date Range</label>
                            <div class="input-group">
                                <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                                <span class="input-group-text">to</span>
                                <input type="date" name="end_date"   class="form-control" value="<?= $end_date ?>">
                            </div>
                        </div>

                         
                        <div class="col-md-3">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" id="filter_customer_id" class="form-select select2" style="width:100%;">
                                <option value="">All Customers</option>
                               <?php
                               $stmt = $conn->prepare("SELECT 
                                            cl.customer_id AS id, 
                                            cl.customer_name, 
                                            cl.customer_code, 
                                            ctl.customers_type_name, 
                                            cl.company_name, 
                                            cl.phone
                                        FROM customers_listing cl
                                        LEFT JOIN customers_type_listing ctl 
                                            ON cl.customers_type_id = ctl.customers_type_id
                                        WHERE cl.organization_id = ?
                                        ORDER BY cl.customer_id DESC");

                                $stmt->bind_param("i", $_SESSION['organization_id']);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="'.$row['id'].'">'
                                        . htmlspecialchars($row['company_name'])
                                        . ' ('.$row['customer_name'].' - '.$row['customer_code'].' - '.$row['customers_type_name'].' - Mob No.'.$row['phone'].')'
                                        . '</option>';
                                }
                              ?>
                            </select>
                        </div>

                        <!-- Sales Person -->
                        <div class="col-md-3">
                            <label class="form-label">Sales Person</label>
                            <select name="sales_employee_id" class="form-select select2" style="width:100%;">
                                <option value="">All Sales Persons</option>
                                <?php
                                $empStmt = $conn->prepare("SELECT 
                                            employee_id, 
                                            first_name, 
                                            last_name, 
                                            employee_code,
                                            primary_phone
                                        FROM employees 
                                        WHERE organization_id = ?
                                        ORDER BY first_name ASC");
                                $empStmt->bind_param("i", $_SESSION['organization_id']);
                                $empStmt->execute();
                                $empResult = $empStmt->get_result();

                                while ($emp = $empResult->fetch_assoc()) {
                                    $selected = ($salesperson_id == $emp['employee_id']) ? 'selected' : '';
                                    echo '<option value="'.$emp['employee_id'].'" '.$selected.'>'
                                        . htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'])
                                        . ' ('.$emp['employee_code'] . ($emp['primary_phone'] ? ' - ' . $emp['primary_phone'] : '') . ')'
                                        . '</option>';
                                }
                                $empStmt->close();
                                ?>
                            </select>
                        </div>



                        

                        <!-- Status -->
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="draft"     <?= $status == 'draft'     ? 'selected' : '' ?>>Draft</option>
                                <option value="pending"   <?= $status == 'pending'   ? 'selected' : '' ?>>Pending</option>
                                <option value="paid"      <?= $status == 'paid'      ? 'selected' : '' ?>>Paid</option>
                                <option value="partial"   <?= $status == 'partial'   ? 'selected' : '' ?>>Partial</option>
                                <option value="cancelled" <?= $status == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>

                        <!-- Search -->
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="q" class="form-control" placeholder="Invoice # / Customer" value="<?= htmlspecialchars($search_query) ?>">
                        </div>

                        <!-- Buttons -->
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1"><i class="ti ti-filter"></i> Go</button>
                            <?php if (!empty($search_query) || $customer_id > 0 || !empty($status) || $start_date != date('Y-m-01') || $end_date != date('Y-m-d')): ?>
                                <a href="<?= $basePath ?>/sales_report" class="btn btn-outline-danger" title="Clear Filters"><i class="ti ti-x"></i></a>
                            <?php endif; ?>
                        </div>

                    </form>

                    <!-- ── Summary Cards ── -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-primary-subtle text-primary border-primary">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Sales Amount</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($total_sales, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-danger-subtle text-danger border-danger">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Balance Due</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($total_balance_due, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info-subtle text-info border-info">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Invoices</h6>
                                    <h3 class="card-title mb-0"><?= $count_invoices ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Table ── -->
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-bordered align-middle" id="sales_report_table">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Sales Person</th>
                                    <th>Status</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Balance Due</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($invoices)): ?>
                                    <?php foreach ($invoices as $row):
                                        $statusClr = match($row['status']) {
                                            'draft'     => 'secondary',
                                            'pending'   => 'warning',
                                            'paid'      => 'success',
                                            'partial'   => 'info',
                                            'cancelled' => 'danger',
                                            default     => 'light'
                                        };
                                        $custDisplay = $row['company_name']
                                            ? htmlspecialchars($row['company_name']) . '<br><small class="text-muted">' . htmlspecialchars($row['customer_name']) . '</small>'
                                            : htmlspecialchars($row['customer_name']);
                                    ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($row['invoice_date'])) ?></td>
                                        <td class="fw-bold">
                                            <a href="<?= $basePath ?>/view_invoice?id=<?= $row['invoice_id'] ?>"><?= htmlspecialchars($row['invoice_number']) ?></a>
                                        </td>
                                        <td><?= $custDisplay ?></td>
                                        <td><?= htmlspecialchars($row['salesperson_name'] ?? '-') ?></td>
                                        <td><span class="badge bg-<?= $statusClr ?>"><?= ucfirst($row['status']) ?></span></td>
                                        <td class="text-end fw-bold">₹<?= number_format($row['total_amount'], 2) ?></td>
                                        <td class="text-end <?= floatval($row['balance_due']) > 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                                            ₹<?= number_format($row['balance_due'], 2) ?>
                                        </td>
                                        <td>
                                            <a href="<?= $basePath ?>/view_invoice?id=<?= $row['invoice_id'] ?>" class="btn btn-sm btn-light" title="View">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                            <a href="<?= $basePath ?>/print_invoice_view?id=<?= $row['invoice_id'] ?>" class="btn btn-sm btn-light" title="Print" target="_blank">
                                                <i class="ti ti-printer"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No invoices found matching criteria.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div><!-- /card-body -->
            </div><!-- /card -->
        </div>
    </div>
</div>
 