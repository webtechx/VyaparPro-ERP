<?php
$title = "Employee Performance Report";
require_once __DIR__ . '/../../config/auth_guard.php';
require_once __DIR__ . '/../../controller/reports/employee_performance_report.php';

// Prepare data for summary cards
$total_revenue = 0;
$total_invoices_count = 0;
$total_incentive_paid = 0;
$best_performer_name = "N/A";
$best_performer_amount = 0;

foreach ($reportData as $row) {
    $total_revenue += $row['sales_total'];
    $total_invoices_count += $row['sales_count'];
    $total_incentive_paid += $row['incentive_total'];

    if ($row['sales_total'] > $best_performer_amount) {
        $best_performer_amount = $row['sales_total'];
        $best_performer_name = $row['employee']['first_name'] . ' ' . $row['employee']['last_name'];
    }
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

    <div class="row">
        <div class="col-12">
            
            <!-- FILTER MODAL -->
            <div class="modal fade" id="filterModal" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Filter Report</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="" method="GET" id="filter_form">
                                <div class="row g-3">
                                    <!-- Date Range -->
                                    <div class="col-md-4">
                                        <label class="form-label">Date Range</label>
                                        <div class="input-group">
                                            <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                                            <span class="input-group-text">to</span>
                                            <input type="date" name="end_date"   class="form-control" value="<?= $end_date ?>">
                                        </div>
                                    </div>

                                    <!-- Employee -->
                                    <div class="col-md-4">
                                        <label class="form-label">Employee</label>
                                        <select name="employee_id" id="employee_id" class="form-select select2">
                                            <option value="">All Employees</option>
                                            <?php 
                                            // Make sure to reset pointer
                                            if($allEmpRes->num_rows > 0) $allEmpRes->data_seek(0);
                                            while($ae = $allEmpRes->fetch_assoc()):
                                                $aeName = $ae['first_name'] . ' ' . $ae['last_name'];
                                                $aeAvatar = $ae['employee_image'] ? "uploads/" . $ae['organizations_code'] . "/employees/avatars/" . $ae['employee_image'] : '';
                                                $selected = ($employee_id == $ae['employee_id']) ? 'selected' : '';
                                            ?>
                                                <option value="<?= $ae['employee_id'] ?>" data-avatar="<?= htmlspecialchars($aeAvatar) ?>" <?= $selected ?>><?= htmlspecialchars($aeName) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <!-- Role -->
                                    <div class="col-md-4">
                                        <label class="form-label">Role</label>
                                        <select name="role_id" class="form-select select2">
                                            <option value="">All Roles</option>
                                            <?php
                                            // Reset pointer for roles
                                            $rolesQ->data_seek(0);
                                            while($r = $rolesQ->fetch_assoc()):
                                            ?>
                                                <option value="<?= $r['role_id'] ?>" <?= $role_id == $r['role_id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['role_name']) ?></option>
                                            <?php endwhile; ?>
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
                        <div class="col-md-3">
                            <div class="card bg-primary-subtle text-primary border-primary mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Team Sales</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($total_revenue, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success-subtle text-success border-success mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Top Performer</h6>
                                    <h3 class="card-title mb-0 text-truncate" title="<?= $best_performer_name ?>"><?= $best_performer_name ?></h3>
                                    <small>₹<?= number_format($best_performer_amount, 2) ?></small>
                                </div>
                            </div>
                        </div>
                         <div class="col-md-3">
                             <div class="card bg-info-subtle text-info border-info mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Invoices Generated</h6>
                                    <h3 class="card-title mb-0"><?= $total_invoices_count ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                             <div class="card bg-warning-subtle text-warning border-warning mb-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Incentives Paid</h6>
                                    <h3 class="card-title mb-0">₹<?= number_format($total_incentive_paid, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LIST -->
            <div class="card">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Performance Data</h5>
                     <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="ti ti-filter me-1"></i> Filter</button>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 w-100 align-middle" id="performance_table">
                            <thead class="table-light">
                                <tr>
                                    <th>Rank</th>
                                    <th>Employee</th>
                                    <th>Role</th>
                                    <th class="text-center">Invoices</th>
                                    <th class="text-end">Sales Amount</th>
                                    <th class="text-end">Contribution</th>
                                    <th class="text-end">Incentive Earned</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($reportData)): ?>
                                    <?php 
                                    $rank = 1;
                                    foreach ($reportData as $row): 
                                        $emp = $row['employee'];
                                        $name = $emp['first_name'] . ' ' . $emp['last_name'];
                                        $avatar = $emp['employee_image'];
                                        $orgCode = $emp['organizations_code'];
                                        $initial = strtoupper(substr($name, 0, 1));
                                        
                                        $avatarHtml = '';
                                        if(!empty($avatar)){
                                            $src = "$basePath/uploads/$orgCode/employees/avatars/$avatar";
                                            $avatarHtml = "<img src='$src' class='rounded-circle me-2' style='width:32px;height:32px;object-fit:cover;'>";
                                        } else {
                                            $avatarHtml = "<div class='rounded-circle bg-light text-primary d-inline-flex align-items-center justify-content-center fw-bold me-2' style='width:32px;height:32px;'>$initial</div>";
                                        }
                                        
                                        $contribution = $total_revenue > 0 ? ($row['sales_total'] / $total_revenue) * 100 : 0;
                                        
                                        // Rank Badge
                                        $rankBadge = '<span class="badge bg-light text-dark order-badge">#'.$rank.'</span>';
                                        if($rank == 1) $rankBadge = '<span class="badge bg-warning text-dark"><i class="ti ti-trophy me-1"></i>#1</span>';
                                        if($rank == 2) $rankBadge = '<span class="badge bg-secondary text-white">#2</span>';
                                        if($rank == 3) $rankBadge = '<span class="badge bg-brown text-white" style="background-color: #cd7f32;">#3</span>'; // Bronze-ish
                                    ?>
                                    <tr>
                                        <td><?= $rankBadge ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?= $avatarHtml ?>
                                                <div>
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($name) ?></div>
                                                    <div class="small text-muted"><?= htmlspecialchars($emp['employee_code']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($emp['role_name'] ?? 'N/A') ?></td>
                                        <td class="text-center fw-bold"><?= $row['sales_count'] ?></td>
                                        <td class="text-end fw-bold text-success">₹<?= number_format($row['sales_total'], 2) ?></td>
                                        <td class="text-end">
                                            <div class="d-flex align-items-center justify-content-end gap-2">
                                                <span class="small fw-bold"><?= number_format($contribution, 1) ?>%</span>
                                                <div class="progress" style="width: 50px; height: 4px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $contribution ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end fw-bold text-warning">₹<?= number_format($row['incentive_total'], 2) ?></td>
                                        <td>
                                            <a href="<?= $basePath ?>/sales_report?sales_employee_id=<?= $emp['employee_id'] ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-sm btn-light" title="View Sales"><i class="ti ti-chart-bar"></i> Details</a>
                                        </td>
                                    </tr>
                                    <?php 
                                    $rank++;
                                    endforeach; 
                                    ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No performance data found matching criteria.</td>
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
    const basePath = '<?= $basePath ?>';

    // Initialize Select2 when modal is shown
    const filterModal = document.getElementById('filterModal');
    if (filterModal) {
        filterModal.addEventListener('shown.bs.modal', function () {
            // Destroy if already exists to prevent duplication
            if ($('#employee_id').hasClass("select2-hidden-accessible")) {
                 $('#employee_id').select2('destroy');
            }
            if ($('select[name="role_id"]').hasClass("select2-hidden-accessible")) {
                 $('select[name="role_id"]').select2('destroy');
            }
            
            // Re-init
            $('select[name="role_id"]').select2({ dropdownParent: $('.modal-content'), width: '100%' });
            initEmployeeSelect();
        });
    }

    $(document).ready(function() {
        initEmployeeSelect();
    });

    // Initialize Employee Select2
    function initEmployeeSelect() {
        const $employee = $('#employee_id');
        if ($employee.hasClass('select2-hidden-accessible')) {
            $employee.select2('destroy');
        }
        $employee.select2({
            placeholder: 'Search Sales Person...',
            width: '100%',
            dropdownCssClass: 'customer-select2-dropdown',
            allowClear: true,
            dropdownParent: $('.modal-content'),
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
            },
            templateResult: formatEmployeeResult,
            templateSelection: formatRepoSelection,
            escapeMarkup: m => m
        });
    }

    function formatEmployeeResult(repo) {
        if (repo.loading) return repo.text;
        
        let name = repo.employee_name || repo.text;
        let letter = (name || '').charAt(0).toUpperCase();

        let avatarHtml = '';
        if(repo.avatar && repo.avatar.trim() !== ''){
             let cleanAvatar = repo.avatar.startsWith('/') ? repo.avatar.substring(1) : repo.avatar;
            avatarHtml = `<img src="${basePath}/${cleanAvatar}" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">`;
        } else {
             avatarHtml = `<div class="customer-avatar rounded-circle bg-light text-dark d-flex align-items-center justify-content-center fw-bold" style="width:32px;height:32px;min-width:32px;">${letter}</div>`;
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
