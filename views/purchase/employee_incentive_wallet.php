<?php
// views/customers/employee_incentive_wallet.php
require_once __DIR__ . '/../../config/conn.php';
$title = "Employee Incentive Wallet";

// Filter Logic
// Filter Logic
$emp_filter = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
$role_filter = isset($_GET['role_id']) ? (int)$_GET['role_id'] : 0;
$month_filter = isset($_GET['month']) ? $_GET['month'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';

// 1. Employee Wallets Summary
$walletSql = "SELECT e.employee_id, e.first_name, e.last_name, e.employee_code, e.current_incentive_balance, e.total_incentive_earned, e.employee_image, 
              r.role_name, o.organizations_code 
              FROM employees e
              LEFT JOIN roles_listing r ON e.role_id = r.role_id
              LEFT JOIN organizations o ON e.organization_id = o.organization_id
              WHERE e.is_active = 1";

if ($emp_filter > 0) {
    $walletSql .= " AND e.employee_id = $emp_filter";
}
if ($role_filter > 0) {
    $walletSql .= " AND e.role_id = $role_filter";
}
$walletResult = $conn->query($walletSql);

// 2. Ledger / History
$ledgerSql = "SELECT il.*, 
              e.first_name, e.last_name, e.employee_code, e.employee_image, 
              r.role_name, o.organizations_code, 
              mt.month, mt.year 
              FROM incentive_ledger il 
              JOIN employees e ON il.employee_id = e.employee_id 
              LEFT JOIN roles_listing r ON e.role_id = r.role_id 
              LEFT JOIN organizations o ON e.organization_id = o.organization_id
              LEFT JOIN monthly_targets mt ON il.monthly_target_id = mt.id 
              WHERE 1=1";

if ($emp_filter > 0) {
    $ledgerSql .= " AND il.employee_id = $emp_filter";
}
// Date filtering on ledger is tricky if distribution_date vs target date.
// Assuming viewing ledger entries created in a period? Or for a target period?
// Let's filter by Target Month/Year if provided, else distribution date?
// Usually Wallet History is chronological.
if ($month_filter && $year_filter) {
    $ledgerSql .= " AND mt.month = '$month_filter' AND mt.year = '$year_filter'";
}

$ledgerSql .= " ORDER BY il.distribution_date DESC LIMIT 100";
$ledgerResult = $conn->query($ledgerSql);

// Fetch Employees for Filter
$empListSql = "SELECT e.employee_id, e.first_name, e.last_name, e.employee_code, e.primary_email, e.employee_image, 
               r.role_name, d.department_name, ds.designation_name, o.organizations_code
               FROM employees e
               LEFT JOIN roles_listing r ON e.role_id = r.role_id
               LEFT JOIN department_listing d ON e.department_id = d.department_id
               LEFT JOIN designation_listing ds ON e.designation_id = ds.designation_id
               LEFT JOIN organizations o ON e.organization_id = o.organization_id
               WHERE e.is_active = 1 
               ORDER BY e.first_name ASC";
$empListResult = $conn->query($empListSql);
// Fetch Roles for Filter
$roleListResult = $conn->query("SELECT role_id, role_name FROM roles_listing WHERE is_active = 1 AND role_name != 'SUPER ADMIN' ORDER BY role_name ASC");
?>

<style>
    /* Select2 Highlighting Overrides to match Proforma style */
    .select2-results__option--highlighted .text-dark { color: white !important; }
    .select2-results__option--highlighted .text-muted { color: #e9ecef !important; }
    .select2-results__option--highlighted .fw-semibold { color: white !important; }
    .select2-results__option--highlighted .small { color: #e9ecef !important; }
    
    .select2-container {
        z-index: 100 !important; 
    }
    .select2-container .select2-dropdown {
        z-index: 1060; 
    }
    
    /* Ensure native select is hidden when Select2 is active */
    .select2-hidden-accessible {
        border: 0 !important;
        clip: rect(0 0 0 0) !important;
        height: 1px !important;
        margin: -1px !important;
        overflow: hidden !important;
        padding: 0 !important;
        position: absolute !important;
        width: 1px !important;
    }
</style>
<div class="container-fluid">
    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="role_id" class="form-select select2">
                        <option value="">All Roles</option>
                        <?php while($role = $roleListResult->fetch_assoc()): ?>
                            <option value="<?= $role['role_id'] ?>" <?= $role_filter == $role['role_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['role_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="employee_id" class="form-select select2">
                        <option value="">All Employees</option>
                        <?php while($emp = $empListResult->fetch_assoc()): 
                        
                        ?>
                            <option value="<?= $emp['employee_id'] ?>" 
                                    <?= $emp_filter == $emp['employee_id'] ? 'selected' : '' ?>>
                             <?= htmlspecialchars(ucwords(strtolower($emp['first_name'] . ' ' . $emp['last_name']))) ?>
                                (<?= htmlspecialchars($emp['employee_code']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="month" class="form-select">
                        <option value="">All Months</option>
                        <?php
                        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                        foreach ($months as $m) {
                            $selected = ($month_filter == $m) ? 'selected' : '';
                            echo "<option value='$m' $selected>$m</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="year" class="form-select">
                        <option value="">All Years</option>
                        <?php
                        $currentYear = date('Y');
                        for ($y = $currentYear; $y >= 2024; $y--) {
                            $selected = ($year_filter == $y) ? 'selected' : '';
                            echo "<option value='$y' $selected>$y</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-filter"></i> Filter</button>
                    <a href="<?= $basePath ?>/employee_incentive_wallet" class="btn btn-light ms-1">Reset</a>
                    
                    <?php
                    // Build query string for export
                    $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
                    ?>
                    <a href="<?= $basePath ?>/controller/purchase/export_incentive_wallet_excel.php<?= $qs ?>" class="btn btn-success ms-1">
                        <i class="ti ti-download"></i> Export XL
                    </a>
                    <button type="button" class="btn btn-info ms-1" onclick="printWalletPage()">
                        <i class="ti ti-printer"></i> Print
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Left: Employee Cards / Wallet Balances -->
        <div class="col-md-4">
             <div class="card" style="max-height: 80vh; overflow-y: auto;">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Employee Wallets</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if ($walletResult && $walletResult->num_rows > 0): ?>
                            <?php while ($wallet = $walletResult->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="avatar-sm me-2">
                                            <?php if (!empty($wallet['employee_image'])): ?>
                                                <img src="<?= $basePath ?>/uploads/<?= $wallet['organizations_code'] ?>/employees/avatars/<?= $wallet['employee_image'] ?>" alt="" class="avatar-title rounded-circle obj-cover">
                                            <?php else: ?>
                                                <span class="avatar-title rounded-circle bg-light text-dark fw-bold fs-5 d-flex align-items-center justify-content-center">
                                                    <?= strtoupper(substr($wallet['first_name'], 0, 1)) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-truncate"><?= htmlspecialchars(ucwords(strtolower($wallet['first_name'] . ' ' . $wallet['last_name']))) ?></h6>
                                            <small class="text-muted d-block"><b><?= htmlspecialchars($wallet['role_name'] ?? 'N/A') ?></b> - <?= htmlspecialchars($wallet['employee_code']) ?>  </small>
                                        </div>
                                        <div class="text-end">
                                            <button class="btn btn-sm btn-outline-success payout-btn" 
                                                    data-id="<?= $wallet['employee_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($wallet['first_name'] . ' ' . $wallet['last_name']) ?>"
                                                    data-balance="<?= $wallet['current_incentive_balance'] ?>">
                                                Pay Out
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row g-0 text-center bg-light rounded p-2">
                                        <div class="col-6 border-end">
                                            <small class="text-muted d-block fs-xs">Current Balance</small>
                                            <span class="fw-bold text-success fs-6">₹ <?= number_format($wallet['current_incentive_balance'], 2) ?></span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block fs-xs">Total Earned</small>
                                            <span class="fw-bold text-dark fs-6">₹ <?= number_format($wallet['total_incentive_earned'], 2) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted">No employees found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Ledger / Transaction History -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Transaction Ledger</h5>
                    <span class="badge bg-light text-dark">Recent 100 Transactions</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light fs-xs text-uppercase">
                                <tr>
                                    <th style="width: 120px;">Date</th>
                                    <th>Employee</th>
                                    <th>Ref / Period</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th class="text-center">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($ledgerResult && $ledgerResult->num_rows > 0): ?>
                                    <?php while ($txn = $ledgerResult->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-medium"><?= date('d M Y', strtotime($txn['distribution_date'])) ?></span>
                                                    <small class="text-muted"><?= date('H:i A', strtotime($txn['distribution_date'])) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-2">
                                                        <?php if (!empty($txn['employee_image'])): ?>
                                                            <img src="<?= $basePath ?>/uploads/<?= $txn['organizations_code'] ?>/employees/avatars/<?= $txn['employee_image'] ?>" alt="" class="avatar-title rounded-circle obj-cover">
                                                        <?php else: ?>
                                                            <span class="avatar-title rounded-circle bg-light text-dark fw-bold fs-6 d-flex align-items-center justify-content-center">
                                                                <?= htmlspecialchars(ucwords(strtolower($txn['first_name'] . ' ' . $txn['last_name']))) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 fs-sm"><?= htmlspecialchars(ucwords(strtolower($txn['first_name'] . ' ' . $txn['last_name']))) ?></h6>
                                                        <small class="text-muted d-block" style="font-size: 11px;">
                                                            <?= htmlspecialchars($txn['role_name'] ?? 'N/A') ?> 
                                                            <span class="mx-1">•</span> 
                                                            <?= htmlspecialchars($txn['employee_code']) ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if($txn['month'] && $txn['year']): ?>
                                                    <span class="badge bg-light text-dark border"><?= $txn['month'] . ' ' . $txn['year'] ?></span>
                                                <?php elseif($txn['distribution_type'] == 'payout'): ?>
                                                    <span class="badge bg-light-danger text-danger border border-danger">Wallet Withdrawal</span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $typeClass = 'bg-soft-primary text-primary';
                                                $typeLabel = ucfirst($txn['distribution_type']);
                                                
                                                switch($txn['distribution_type']) {
                                                    case 'manager': 
                                                        $typeClass = 'bg-soft-info text-info'; 
                                                        break;
                                                    case 'team': 
                                                        $typeClass = 'bg-soft-success text-success'; 
                                                        break;
                                                    case 'payout': 
                                                        $typeClass = 'bg-soft-danger text-danger'; 
                                                        $typeLabel = 'PAYOUT';
                                                        break; 
                                                    case 'manual': 
                                                        $typeClass = 'bg-soft-warning text-warning'; 
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?= $typeClass ?> text-uppercase"><?= $typeLabel ?></span>
                                            </td>
                                            <td class="fw-bold <?= $txn['amount'] < 0 ? 'text-danger' : 'text-success' ?>">
                                                <?= $txn['amount'] < 0 ? '-' : '+' ?> ₹ <?= number_format(abs($txn['amount']), 2) ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <?php if(!empty(trim($txn['notes']))): ?>
                                                    <span class="text-primary fw-bold" style="cursor: help; font-size: 14px;" data-bs-toggle="tooltip" data-bs-placement="left" title="<?= htmlspecialchars($txn['notes']) ?>">&#9432;</span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">No transactions found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payout Modal -->
<div class="modal fade" id="payoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= $basePath ?>/controller/purchase/process_payout.php" method="POST">
                <input type="hidden" name="employee_id" id="payout_employee_id">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title text-white"><i class="ti ti-cash me-2"></i>Process Payout</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="avatar-lg mx-auto mb-2">
                            <span class="avatar-title rounded-circle bg-soft-success text-success fs-1">
                                <i class="ti ti-wallet"></i>
                            </span>
                        </div>
                        <h5 class="mb-1" id="payout_emp_name">Employee Name</h5>
                        <p class="text-muted">Current Balance: <span class="fw-bold text-success" id="payout_balance_display">₹ 0.00</span></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payout Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" name="amount" id="payout_amount" class="form-control form-control-lg" step="0.01" min="1" required>
                        </div>
                        <div class="form-text">Currently available: <span id="max_payout_hint">0.00</span></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Method/Ref</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Bank Transfer Ref: REF123456" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="confirm_payout" class="btn btn-success">Confirm Payout</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    if ($.fn.select2) {
        
        // DESTROY existing instances to prevent conflicts with global auto-inits
        $('select[name="role_id"], select[name="employee_id"]').each(function() {
            if ($(this).hasClass("select2-hidden-accessible")) {
                $(this).select2('destroy');
            }
        });

        // Standard Select2 for Roles
        $('select[name="role_id"]').select2({
            placeholder: "Select Role",
            allowClear: true,
            width: '100%'
        });

        // Simplified formatter showing Name and Code
        function formatEmployeeSimplified(emp) {
            if (!emp.id) return emp.text;

            let data = emp;
            if (emp.element) {
                const $el = $(emp.element);
                data = {
                    text: emp.text || $el.text(),
                    employee_code: $el.data('employee_code') || ''
                };
            }

            return $(`
                <div class="d-flex align-items-center py-1">
                    <div class="fw-semibold text-dark lh-sm">
                        ${data.text} <span class="small text-muted fw-normal">${data.employee_code ? `(${data.employee_code})` : ''}</span>
                    </div>
                </div>
            `);
        };

        $('select[name="employee_id"]').select2({
            placeholder: "Select Employee",
            allowClear: true,
            width: '100%',
            templateResult: formatEmployeeSimplified,
            templateSelection: function(emp) {
                 if (!emp.id) return emp.text;
                 const code = $(emp.element).data('employee_code');
                 return emp.text + (code ? ' (' + code + ')' : '');
            },
            escapeMarkup: function(m) { return m; }
        });
    }

    const payoutModal = document.getElementById('payoutModal');
    const payoutBtns = document.querySelectorAll('.payout-btn');
    
    payoutBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const balance = parseFloat(this.getAttribute('data-balance'));
            
            document.getElementById('payout_employee_id').value = id;
            document.getElementById('payout_emp_name').textContent = name;
            
            const balStr = balance.toLocaleString(undefined, {minimumFractionDigits: 2});
            document.getElementById('payout_balance_display').textContent = '₹ ' + balStr;
            document.getElementById('max_payout_hint').textContent = balStr;
            
            const amtInput = document.getElementById('payout_amount');
            amtInput.max = balance;
            amtInput.value = ''; // Reset value
            
            var modal = new bootstrap.Modal(payoutModal);
            modal.show();
        });
    });
});
</script>

<style>
@media print {
    body * { visibility: hidden !important; }
    #wallet-print-area, #wallet-print-area * { visibility: visible !important; }
    #wallet-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<div id="wallet-print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:16px;">
        <h3 style="margin:0;">Employee Incentive Wallet</h3>
        <p style="margin:4px 0; font-size:12px; color:#666;">Printed on: <span id="wallet-print-date"></span></p>
    </div>
    <h5 style="margin-bottom:8px;">Transaction Ledger</h5>
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead style="background:#5d282a; color:#fff;">
            <tr>
                <th>Date</th>
                <th>Employee</th>
                <th>Ref / Period</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody id="wallet-print-tbody"></tbody>
    </table>
</div>

<script>
function printWalletPage() {
    var rows = document.querySelectorAll('.col-md-8 table tbody tr');
    var tbody = document.getElementById('wallet-print-tbody');
    tbody.innerHTML = '';
    rows.forEach(function(tr) {
        var tds = tr.querySelectorAll('td');
        if(tds.length < 6) return;
        var date   = (tds[0].querySelector('.fw-medium') ? tds[0].querySelector('.fw-medium').textContent : '').trim();
        var emp    = (tds[1].querySelector('h6') ? tds[1].querySelector('h6').textContent : '').trim();
        var ref    = (tds[2].querySelector('.badge') ? tds[2].querySelector('.badge').textContent : tds[2].textContent).trim();
        var type   = (tds[3].querySelector('.badge') ? tds[3].querySelector('.badge').textContent : '').trim();
        var amount = tds[4].textContent.trim();
        var notes  = (tds[5].querySelector('[title]') ? tds[5].querySelector('[title]').getAttribute('title') : '-');
        
        tbody.innerHTML += '<tr>' +
            '<td>' + date + '</td>' +
            '<td>' + emp + '</td>' +
            '<td>' + ref + '</td>' +
            '<td>' + type + '</td>' +
            '<td>' + amount + '</td>' +
            '<td>' + notes + '</td>' +
            '</tr>';
    });
    document.getElementById('wallet-print-date').textContent = new Date().toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('wallet-print-area').style.display = 'block';
    window.print();
    document.getElementById('wallet-print-area').style.display = 'none';
}
</script>
