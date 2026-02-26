<?php
$title = "Sales by Sales Person Report";
require_once __DIR__ . '/../../config/auth_guard.php';

// Prepare base filter vars if not already
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$salesperson_id = isset($_GET['sales_employee_id']) ? intval($_GET['sales_employee_id']) : 0;

require_once __DIR__ . '/../../controller/reports/sales_by_salesperson_report.php';

// Prefill logic moved to controller
?>

<div class="container-fluid">
    <style>
        .customer-text-primary { color: #2a3547; }
        .select2-results__option--highlighted .customer-text-primary { color: white !important; }
        .select2-results__option--highlighted .text-muted { color: #e9ecef !important; }
        .customer-select2-dropdown .select2-results__options { max-height: 400px !important; }
        .select2-container--open .select2-dropdown { z-index: 9999999 !important; }
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
                                            <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                                            <span class="input-group-text">to</span>
                                            <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                         <label class="form-label">Sales Person</label>
                                        <select name="sales_employee_id" id="employee_id" class="form-select">
                                            <?php if($salesperson_id > 0 && !empty($salesperson_name_prefill)): ?>
                                                <option value="<?= $salesperson_id ?>" data-avatar="<?= htmlspecialchars($salesperson_avatar_prefill) ?>" selected><?= htmlspecialchars($salesperson_name_prefill) ?></option>
                                            <?php endif; ?>
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
                    <h5 class="card-title mb-0">Sales by Sales Person</h5>
                    <div class="d-flex gap-2">
                        <?php $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>
                        <a href="<?= $basePath ?>/controller/reports/export_sales_by_salesperson_excel.php<?= $qs ?>" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-info" onclick="printSalesBySalesperson()">
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
                                    <th>Sales Person</th>
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
                                        $empName = $row['first_name'] ? ($row['first_name'] . ' ' . $row['last_name']) : 'Unassigned / Direct Sales';
                                        $empCode = $row['employee_code'] ?? '';
                                        $avatar = $row['employee_image'];
                                        $orgCode = $row['organizations_code'];
                                        
                                        $avatarHtml = '';
                                        if(!empty($avatar) && !empty($orgCode)){
                                             $src = "$basePath/uploads/$orgCode/employees/avatars/$avatar";
                                             $avatarHtml = "<img src='$src' class='rounded-circle me-2' style='width:32px;height:32px;object-fit:cover;'>";
                                        } else {
                                             $initial = strtoupper(substr($empName, 0, 1));
                                             $avatarHtml = "<div class='rounded-circle bg-light text-primary d-inline-flex align-items-center justify-content-center fw-bold me-2' style='width:32px;height:32px;'>$initial</div>";
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?= $avatarHtml ?>
                                                <div>
                                                    <div class="fw-semibold text-dark"><?= ucwords(strtolower($empName)) ?></div>
                                                    <?php if($empCode): ?><div class="small text-muted"><?= htmlspecialchars($empCode) ?></div><?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center fw-bold"><?= $row['invoice_count'] ?></td>
                                        <td class="text-end fw-bold text-success">₹<?= number_format($row['total_sales'], 2) ?></td>
                                        <td class="text-center">
                                            <?php if($row['sales_employee_id'] > 0): ?>
                                            <a href="<?= $basePath ?>/sales_report?sales_employee_id=<?= $row['sales_employee_id'] ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-sm btn-light" title="View Invoices"><i class="ti ti-list-details"></i> Details</a>
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

    // Initialize Select2 when modal is shown
    const filterModal = document.getElementById('filterModal');
    if (filterModal) {
        filterModal.addEventListener('shown.bs.modal', function () {
            if (!$('#employee_id').hasClass('select2-hidden-accessible')) initEmployeeSelect();
        });
    }

    $(document).ready(function() {
        initEmployeeSelect();
    });

    function initEmployeeSelect() {
        const $emp = $('#employee_id');
        if ($emp.hasClass('select2-hidden-accessible')) {
            $emp.select2('destroy');
        }

        $emp.select2({
            placeholder: 'Search Sales Person...',
            width: '100%',
            dropdownCssClass: 'customer-select2-dropdown',
            allowClear: true,
            dropdownParent: $('.modal-content'),
            templateResult: formatEmployeeResult,
            templateSelection: formatRepoSelection,
            escapeMarkup: m => m,
            minimumInputLength: 0,
            ajax: {
                url: basePath + '/controller/billing/search_employees.php',
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term || '' }),
                processResults: data => {
                     return {
                        results: (Array.isArray(data) ? data : []).map(e => ({
                            id: e.id,
                            text: e.text,
                            employee_name: e.text,
                            code: e.employee_code,
                            email: e.email,
                            phone: e.phone,
                            designation: e.designation,
                            avatar: e.avatar
                        }))
                    };
                }
            }
        });
    }

    function formatEmployeeResult(repo) {
        if (repo.loading) return repo.text;
        
        let name = repo.employee_name || repo.text;
        
        // Simple avatar logic matching Sales Report style
        let avatarHtml = '';
        if(repo.avatar && repo.avatar.trim() !== ''){
             let cleanAvatar = repo.avatar.startsWith('/') ? repo.avatar.substring(1) : repo.avatar;
             // Ensure base path handled correctly if stored path is relative
             if(!cleanAvatar.startsWith('http') && !cleanAvatar.startsWith(basePath.replace(/^\//,''))) cleanAvatar = basePath + '/' + cleanAvatar;
             else if(!cleanAvatar.startsWith('http')) cleanAvatar = basePath + '/' + cleanAvatar; // safe fallback
             
             let src = cleanAvatar; // basePath is already in cleanAvatar logic above mostly or we just trust cleanAvatar
             // Actually let's use the robust logic from employee_performance
             src = basePath + '/' + (repo.avatar.startsWith('/') ? repo.avatar.substring(1) : repo.avatar);
             
             avatarHtml = `<img src="${src}" class="rounded-circle me-2" style="width:20px;height:20px;object-fit:cover;vertical-align:middle;">`;
        } else {
             let letter = (name || '').charAt(0).toUpperCase();
             avatarHtml = `<span class="badge rounded-circle bg-primary text-white me-2 d-inline-flex align-items-center justify-content-center" style="width:20px;height:20px;font-size:10px;vertical-align:middle;">${letter}</span>`;
        }
        
        return `
            <div class="d-flex align-items-center gap-2 py-1">
                ${avatarHtml}
                <div class="flex-grow-1">
                    <div class="customer-text-primary fw-semibold lh-sm mb-1">
                        ${name} 
                        ${repo.code ? `<span class="small text-muted fw-normal">(${repo.code})</span>` : ''}
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="d-flex flex-column small text-muted">
                            ${repo.phone ? `<span class="mb-1 text-nowrap"><i class="ti ti-phone me-1"></i>${repo.phone}</span>` : ''}
                            ${repo.email ? `<span class="text-break"><i class="ti ti-mail me-1"></i>${repo.email}</span>` : ''}
                        </div>
                        ${repo.designation ? `<div class="small text-muted"><i class="ti ti-briefcase me-1"></i>${repo.designation}</div>` : ''}
                    </div>
                </div>
            </div>`;
    }

    function formatRepoSelection(repo) {
        if (!repo.id) return repo.text;
        let name = repo.customer_name || repo.vendor_name || repo.employee_name || repo.text;
        
        // Handle avatar
        let avatar = repo.avatar || $(repo.element).data('avatar');
        let avatarHtml = '';
        if(avatar && avatar.trim() !== ''){
             let cleanAvatar = avatar.startsWith('/') ? avatar.substring(1) : avatar;
            avatarHtml = `<img src="${basePath}/${cleanAvatar}" class="rounded-circle me-2" style="width:20px;height:20px;object-fit:cover;vertical-align:middle;">`;
        } else {
             let letter = (name || '').charAt(0).toUpperCase();
             avatarHtml = `<span class="badge rounded-circle bg-primary text-white me-2 d-inline-flex align-items-center justify-content-center" style="width:20px;height:20px;font-size:10px;vertical-align:middle;">${letter}</span>`;
        }
        
        return `<span>${avatarHtml}${name}</span>`;
    }
</script>

<style>
@media print {
    body * { visibility: hidden !important; }
    #sbs-print-area, #sbs-print-area * { visibility: visible !important; }
    #sbs-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<div id="sbs-print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:4px;">
        <h2 style="margin:0; color:#5d282a; border-bottom:2px solid #d7b251; padding-bottom:6px;">SALES BY SALESPERSON REPORT</h2>
    </div>
    <div id="sbs-print-filters" style="margin:8px 0 12px 0; font-size:11px; color:#444; background:#fdf5e0; padding:6px 10px; border-left:3px solid #d7b251;"></div>
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead style="background:#5d282a; color:#fff;">
            <tr><th>Sales Person</th><th>Code</th><th>Invoices</th><th>Total Sales</th></tr>
        </thead>
        <tbody id="sbs-print-tbody"></tbody>
    </table>
    <p style="margin-top:8px; font-size:10px; color:#999; text-align:right;">Printed on: <span id="sbs-print-date"></span></p>
</div>

<?php
$activeFiltersDisplay = [];
if (!empty($start_date))           $activeFiltersDisplay[] = '<strong>Date From:</strong> ' . date('d M Y', strtotime($start_date));
if (!empty($end_date))             $activeFiltersDisplay[] = '<strong>Date To:</strong> ' . date('d M Y', strtotime($end_date));
if (!empty($salesperson_name_prefill))$activeFiltersDisplay[] = '<strong>Sales Person:</strong> ' . htmlspecialchars($salesperson_name_prefill);

$filterHtml = !empty($activeFiltersDisplay) ? implode('&nbsp;&nbsp;|&nbsp;&nbsp;', $activeFiltersDisplay) : 'All Records';
?>

<script>
function printSalesBySalesperson() {
    var rows = document.querySelectorAll('table.table tbody tr');
    var tbody = document.getElementById('sbs-print-tbody');
    tbody.innerHTML = '';
    rows.forEach(function(tr) {
        var tds = tr.querySelectorAll('td');
        if(tds.length < 3) return;
        
        // Extract plain text names without the avatar element text block causing mess
        let empInfo = tds[0].querySelector('.fw-semibold');
        let nameText = empInfo ? empInfo.textContent.trim() : tds[0].textContent.trim();
        let codeText = tds[0].querySelector('.small') ? tds[0].querySelector('.small').textContent.trim() : '';

        tbody.innerHTML += '<tr>' +
            '<td>' + nameText + '</td>' +
            '<td>' + codeText + '</td>' +
            '<td>' + tds[1].textContent.trim() + '</td>' +
            '<td>' + tds[2].textContent.trim() + '</td>' +
            '</tr>';
    });
    
    document.getElementById('sbs-print-filters').innerHTML = '<?= addslashes($filterHtml) ?>';
    document.getElementById('sbs-print-date').textContent = new Date().toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('sbs-print-area').style.display = 'block';
    window.print();
    document.getElementById('sbs-print-area').style.display = 'none';
}
</script>
