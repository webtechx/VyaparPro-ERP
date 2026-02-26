<?php
$title = "User wise daily Collection Report";
require_once __DIR__ . '/../../config/auth_guard.php';

// Prepare base filter vars if not already
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

require_once __DIR__ . '/../../controller/reports/user_wise_collection_report.php';

// Prefill Sales Person Name
$salesperson_name_prefill = '';
$salesperson_avatar_prefill = '';
if ($employee_id > 0) {
    $spStmt = $conn->prepare("SELECT CONCAT(e.first_name, ' ', e.last_name) as name, e.employee_code, e.employee_image, o.organizations_code 
                              FROM employees e 
                              JOIN organizations o ON e.organization_id = o.organization_id 
                              WHERE e.employee_id = ?");
    $spStmt->bind_param("i", $employee_id);
    $spStmt->execute();
    $spStmt->bind_result($spName, $spCode, $spImage, $orgCode);
    if ($spStmt->fetch()) {
        $salesperson_name_prefill = "$spName ($spCode)";
        if (!empty($spImage)) {
            $salesperson_avatar_prefill = "uploads/" . $orgCode . "/employees/avatars/" . $spImage;
        }
    }
    $spStmt->close();
}
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
                                            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                                            <span class="input-group-text">to</span>
                                            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                         <label class="form-label">User</label>
                                        <select name="employee_id" id="employee_id" class="form-select">
                                            <?php if($employee_id > 0 && !empty($salesperson_name_prefill)): ?>
                                                <option value="<?= $employee_id ?>" data-avatar="<?= htmlspecialchars($salesperson_avatar_prefill) ?>" selected><?= htmlspecialchars($salesperson_name_prefill) ?></option>
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
                    <h5 class="card-title mb-0">User wise daily Collection Report</h5>
                    <div class="d-flex gap-2">
                        <?php $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>
                        <a href="<?= $basePath ?>/controller/reports/export_user_wise_collection_excel.php<?= $qs ?>" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-info" onclick="printCollectionReport()">
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
                                    <h6 class="card-subtitle mb-2">Total Collection</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($total_collection_all, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-info-subtle text-info border-info">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Transactions</h6>
                                    <h3 class="card-title mb-0"><?= number_format($total_transactions_all, 0) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped w-100 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>User</th>
                                    <th class="text-center">Transactions</th>
                                    <th class="text-end">Total Collection</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reportData)): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-4">No collection data found for this period.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($reportData as $row): 
                                        $empName = $row['first_name'] ? ($row['first_name'] . ' ' . $row['last_name']) : 'User ID: ' . $row['user_id'];
                                        $empCode = $row['employee_code'] ?? '';
                                        $avatar = $row['employee_image'] ?? '';
                                        $orgCode = $row['organizations_code'] ?? '';
                                        
                                        $avatarHtml = '';
                                        if(!empty($avatar) && !empty($orgCode)){
                                             $src = "$basePath/uploads/$orgCode/employees/avatars/$avatar";
                                             $avatarHtml = "<img src='$src' class='rounded-circle me-2' style='width:32px;height:32px;object-fit:cover;'>";
                                        } else {
                                             $initial = strtoupper(substr($empName, 0, 1));
                                             if ($initial) {
                                                $avatarHtml = "<div class='rounded-circle bg-light text-primary d-inline-flex align-items-center justify-content-center fw-bold me-2' style='width:32px;height:32px;'>$initial</div>";
                                             } else {
                                                $avatarHtml = "<div class='rounded-circle bg-light text-primary d-inline-flex align-items-center justify-content-center fw-bold me-2' style='width:32px;height:32px;'><i class='ti ti-user'></i></div>";
                                             }
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
                                        <td class="text-center fw-bold"><?= $row['collection_count'] ?></td>
                                        <td class="text-end fw-bold text-success">₹<?= number_format($row['total_collection'], 2) ?></td>
                                        <td class="text-center">
                                            <?php if($row['user_id'] > 0): ?>
                                            <a href="<?= $basePath ?>/user_collection_details?user_id=<?= $row['user_id'] ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-sm btn-light" title="View Details"><i class="ti ti-list-details"></i> Details</a>
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
            placeholder: 'Search User...',
            width: '100%',
            dropdownCssClass: 'customer-select2-dropdown',
            allowClear: true,
            dropdownParent: $('#filterModal'),
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
        
        let avatarHtml = '';
        if(repo.avatar && repo.avatar.trim() !== ''){
             let cleanAvatar = repo.avatar.startsWith('/') ? repo.avatar.substring(1) : repo.avatar;
             if(!cleanAvatar.startsWith('http') && !cleanAvatar.startsWith(basePath.replace(/^\//,''))) cleanAvatar = basePath + '/' + cleanAvatar;
             else if(!cleanAvatar.startsWith('http')) cleanAvatar = basePath + '/' + cleanAvatar; 
             
             let src = basePath + '/' + (repo.avatar.startsWith('/') ? repo.avatar.substring(1) : repo.avatar);
             
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
                </div>
            </div>`;
    }

    function formatRepoSelection(repo) {
        if (!repo.id) return repo.text;
        let name = repo.customer_name || repo.vendor_name || repo.employee_name || repo.text;
        
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
    #uwc-print-area, #uwc-print-area * { visibility: visible !important; }
    #uwc-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<div id="uwc-print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:4px;">
        <h2 style="margin:0; color:#5d282a; border-bottom:2px solid #d7b251; padding-bottom:6px;">USER WISE DAILY COLLECTION REPORT</h2>
    </div>
    <div id="uwc-print-filters" style="margin:8px 0 12px 0; font-size:11px; color:#444; background:#fdf5e0; padding:6px 10px; border-left:3px solid #d7b251;"></div>
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead style="background:#5d282a; color:#fff;">
            <tr><th>User</th><th>Transactions</th><th>Total Collection</th></tr>
        </thead>
        <tbody id="uwc-print-tbody"></tbody>
    </table>
    <p style="margin-top:8px; font-size:10px; color:#999; text-align:right;">Printed on: <span id="uwc-print-date"></span></p>
</div>

<?php
$activeFiltersDisplay = [];
if (!empty($start_date))           $activeFiltersDisplay[] = '<strong>Date From:</strong> ' . date('d M Y', strtotime($start_date));
if (!empty($end_date))             $activeFiltersDisplay[] = '<strong>Date To:</strong> ' . date('d M Y', strtotime($end_date));
if (!empty($salesperson_name_prefill))$activeFiltersDisplay[] = '<strong>Employee :</strong> ' . htmlspecialchars($salesperson_name_prefill);

$filterHtml = !empty($activeFiltersDisplay) ? implode('&nbsp;&nbsp;|&nbsp;&nbsp;', $activeFiltersDisplay) : 'All Records';
?>

<script>
function printCollectionReport() {
    var rows = document.querySelectorAll('table.table tbody tr');
    var tbody = document.getElementById('uwc-print-tbody');
    tbody.innerHTML = '';
    rows.forEach(function(tr) {
        var tds = tr.querySelectorAll('td');
        if(tds.length < 3) return;
        
        let empInfo = tds[0].querySelector('.fw-semibold');
        let nameText = empInfo ? empInfo.textContent.trim() : tds[0].textContent.trim();
        let codeText = tds[0].querySelector('.small') ? tds[0].querySelector('.small').textContent.trim() : '';

        // include codeText if present
        let displayName = codeText ? nameText + ' (' + codeText + ')' : nameText;

        tbody.innerHTML += '<tr><td>' + displayName + '</td><td>' + tds[1].textContent.trim() + '</td><td>' + tds[2].textContent.trim() + '</td></tr>';
    });
    
    document.getElementById('uwc-print-filters').innerHTML = '<?= addslashes($filterHtml) ?>';
    document.getElementById('uwc-print-date').textContent = new Date().toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('uwc-print-area').style.display = 'block';
    window.print();
    document.getElementById('uwc-print-area').style.display = 'none';
}
</script>
