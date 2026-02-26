<?php
$title = "Department wise Sales Report";
require_once __DIR__ . '/../../config/auth_guard.php';

// Prepare base filter vars if not already
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;

require_once __DIR__ . '/../../controller/reports/department_wise_sales_report.php';

// Fetch all departments for the filter dropdown
$departments = [];
$deptStmt = $conn->prepare("SELECT department_id, department_name FROM department_listing WHERE organization_id = ? ORDER BY department_name ASC");
$deptStmt->bind_param("i", $_SESSION['organization_id']);
$deptStmt->execute();
$deptResult = $deptStmt->get_result();
while ($row = $deptResult->fetch_assoc()) {
    $departments[] = $row;
}
$deptStmt->close();
?>

<div class="container-fluid">
    <style>
        .select2-container--open .select2-dropdown {
            z-index: 9999999 !important;
        }
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
                                        <label class="form-label">Date Range</label>
                                        <div class="input-group">
                                            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                                            <span class="input-group-text">to</span>
                                            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                         <label class="form-label">Department</label>
                                        <select name="department_id" id="department_id" class="form-select">
                                            <option value="">All Departments</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?= $dept['department_id'] ?>" <?= ($department_id == $dept['department_id']) ? 'selected' : '' ?>>
                                                    <?= ucwords(strtolower($dept['department_name'])) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
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

            <div class="card">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Department wise Sales</h5>
                    <div class="d-flex gap-2">
                        <?php $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>
                        <a href="<?= $basePath ?>/controller/reports/export_department_wise_sales_excel.php<?= $qs ?>" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-info" onclick="printDeptWiseSales()">
                            <i class="ti ti-printer me-1"></i> Print
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="ti ti-filter me-1"></i> Filter</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-primary-subtle text-primary border-primary">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Sales</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($total_sales_all, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-info-subtle text-info border-info">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Invoices</h6>
                                    <h3 class="card-title mb-0"><?= number_format($total_invoices_all, 0) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped w-100 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Department</th>
                                    <th class="text-center">Invoices</th>
                                    <th class="text-end">Total Sales</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reportData)): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-4">No sales data found for this period.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($reportData as $row): 
                                        $deptName = $row['department_name'] ? $row['department_name'] : 'Unassigned / Direct Sales';
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= ucwords(strtolower($deptName)) ?></div>
                                        </td>
                                        <td class="text-center fw-bold"><?= $row['invoice_count'] ?></td>
                                        <td class="text-end fw-bold text-success">₹<?= number_format($row['total_sales'], 2) ?></td>
                                        <td class="text-center">
                                            <?php if($row['department_id'] > 0): ?>
                                            <a href="<?= $basePath ?>/sales_report?department_id=<?= $row['department_id'] ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-sm btn-light" title="View Invoices"><i class="ti ti-list-details"></i> Details</a>
                                            <?php else: ?>
                                            <span class="text-muted small">-</span>
                                            <?php endif; ?>
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

    function initDepartmentSelect() {
        $('#department_id').select2({
            placeholder: 'Select Department',
            width: '100%',
            dropdownParent: $('#filterModal')
        });
    }

    const filterModal = document.getElementById('filterModal');
    if (filterModal) {
        filterModal.addEventListener('shown.bs.modal', function () {
            if (!$('#department_id').hasClass('select2-hidden-accessible')) {
                initDepartmentSelect();
            }
        });
    }

    $(document).ready(function() {
        initDepartmentSelect();
    });
</script>

<style>
@media print {
    body * { visibility: hidden !important; }
    #dws-print-area, #dws-print-area * { visibility: visible !important; }
    #dws-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<div id="dws-print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:4px;">
        <h2 style="margin:0; color:#5d282a; border-bottom:2px solid #d7b251; padding-bottom:6px;">DEPARTMENT WISE SALES REPORT</h2>
    </div>
    <div id="dws-print-filters" style="margin:8px 0 12px 0; font-size:11px; color:#444; background:#fdf5e0; padding:6px 10px; border-left:3px solid #d7b251;"></div>
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead style="background:#5d282a; color:#fff;">
            <tr><th>Department</th><th>Invoices</th><th>Total Sales</th></tr>
        </thead>
        <tbody id="dws-print-tbody"></tbody>
    </table>
    <p style="margin-top:8px; font-size:10px; color:#999; text-align:right;">Printed on: <span id="dws-print-date"></span></p>
</div>

<?php
$activeFiltersDisplay = [];
if (!empty($start_date))              $activeFiltersDisplay[] = '<strong>Date From:</strong> ' . date('d M Y', strtotime($start_date));
if (!empty($end_date))                $activeFiltersDisplay[] = '<strong>Date To:</strong> ' . date('d M Y', strtotime($end_date));
if ($department_id > 0 && !empty($departments)) {
    foreach ($departments as $dept) {
        if ($dept['department_id'] == $department_id) {
            $activeFiltersDisplay[] = '<strong>Department:</strong> ' . htmlspecialchars($dept['department_name']);
            break;
        }
    }
}
$filterHtml = !empty($activeFiltersDisplay) ? implode('&nbsp;&nbsp;|&nbsp;&nbsp;', $activeFiltersDisplay) : 'All Records';
?>

<script>
function printDeptWiseSales() {
    var rows = document.querySelectorAll('table.table tbody tr');
    var tbody = document.getElementById('dws-print-tbody');
    tbody.innerHTML = '';
    rows.forEach(function(tr) {
        var tds = tr.querySelectorAll('td');
        if(tds.length < 3) return;
        tbody.innerHTML += '<tr><td>' + tds[0].textContent.trim() + '</td><td>' + tds[1].textContent.trim() + '</td><td>' + tds[2].textContent.trim() + '</td></tr>';
    });
    
    document.getElementById('dws-print-filters').innerHTML = '<?= addslashes($filterHtml) ?>';
    document.getElementById('dws-print-date').textContent = new Date().toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('dws-print-area').style.display = 'block';
    window.print();
    document.getElementById('dws-print-area').style.display = 'none';
}
</script>
