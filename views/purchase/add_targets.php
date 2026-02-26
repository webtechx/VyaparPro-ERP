<?php
$title = 'Add Monthly Target';
$current_year = date('Y');
$years = range($current_year, $current_year + 5);
$months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

// Fetch Departments
$deptQuery = "SELECT department_id, department_name FROM department_listing";
$deptResult = $conn->query($deptQuery);
$departments = [];
if ($deptResult->num_rows > 0) {
    while ($row = $deptResult->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Fetch Roles
// Exclude Super Admin (1) and Employee (4) from Manager Share roles, but include Admin (2)
$roleQuery = "SELECT role_id, role_name FROM roles_listing WHERE is_active = 1 AND role_id NOT IN (1, 4)";
$roleResult = $conn->query($roleQuery);
$roles = [];
if ($roleResult->num_rows > 0) {
    while ($row = $roleResult->fetch_assoc()) {
        $roles[] = $row;
    }
}


// Check for Edit Mode
$target_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$showForm = ($target_id > 0) || ($action === 'create');
$targetData = [];
$deptTargets = [];
$existingManagerRoles = [];
$isEdit = false;

// Default Values
$selectedMonth = '';
$selectedYear = $current_year;
$valTotalTarget = '';
$valIncentive = '2.00';
$valManagerShare = '20.00';
$valTeamShare = '80.00';

if ($target_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM monthly_targets WHERE id = ?");
    $stmt->bind_param("i", $target_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $isEdit = true;
        $targetData = $result->fetch_assoc();
        if ($targetData['distributed'] == 1) {
            echo "<script>alert('This target has been distributed and cannot be edited.'); window.location.href='add_targets';</script>";
            exit;
        }

        $title = 'Edit Monthly Target';

        // Set Values
        $selectedMonth = $targetData['month'];
        $selectedYear = $targetData['year'];
        $valTotalTarget = $targetData['total_target'];
        $valIncentive = $targetData['incentive_percent'];
        $valManagerShare = $targetData['manager_share_percent'];
        $valTeamShare = $targetData['team_share_percent'];        
        
        // Decode manager roles
        if (!empty($targetData['manager_roles'])) {
            $decoded = json_decode($targetData['manager_roles'], true);
            if (is_array($decoded)) {
                $existingManagerRoles = $decoded; // Array of role IDs (strings or ints)
            }
        }
        
        // Fetch Department Targets
        $deptStmt = $conn->prepare("SELECT * FROM department_targets WHERE monthly_target_id = ?");
        $deptStmt->bind_param("i", $target_id);
        $deptStmt->execute();
        $deptResult = $deptStmt->get_result();
        while ($row = $deptResult->fetch_assoc()) {
            $deptTargets[$row['department_id']] = $row;
            // Key by department_id for easy lookup
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
            <?php if ($showForm): ?>
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0 text-white"><i class="ti ti-target me-2"></i><?= $isEdit ? 'Edit' : 'Create' ?> Monthly Target</h6>
                    </div>
                </div>
                
                <div class="card-body p-3">
                    <form action="<?= $basePath ?>/controller/purchase/save_target.php" method="POST">
                        <?php if($isEdit): ?>
                            <input type="hidden" name="target_id" value="<?= $target_id ?>">
                        <?php endif; ?>
                        
                        <!-- Global Settings Section -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">Target Period</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="ti ti-calendar"></i></span>
                                    <select name="month" id="target_month" class="form-select" required>
                                        <option value="">Select Period</option>
                                        <?php 
                                        $start = new DateTime('first day of this month');
                                        $end = clone $start;
                                        $end->modify('+12 months');
                                        
                                        $periodInterval = DateInterval::createFromDateString('1 month');
                                        $periodIterator = new DatePeriod($start, $periodInterval, $end);

                                        foreach ($periodIterator as $dt) {
                                            $m = $dt->format('F');
                                            $y = $dt->format('Y');
                                            echo "<option value='{$m}-{$y}' " . ($selectedMonth == $m && $selectedYear == $y ? 'selected' : '') . ">{$m} {$y}</option>";
                                        }
                                        ?>
                                    </select>
                                    <input type="hidden" name="month" id="hidden_month" value="<?= $selectedMonth ?>">
                                    <input type="hidden" name="year" id="hidden_year" value="<?= $selectedYear ?>">
                                </div>
                            </div>
                            
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const monthSelect = document.getElementById('target_month');
                                    // Initial set
                                    if(monthSelect.value) {
                                        const parts = monthSelect.value.split('-');
                                        document.getElementById('hidden_month').value = parts[0];
                                        document.getElementById('hidden_year').value = parts[1];
                                    }
                                    
                                    monthSelect.addEventListener('change', function() {
                                        if(this.value) {
                                            const parts = this.value.split('-');
                                            document.getElementById('hidden_month').value = parts[0];
                                            document.getElementById('hidden_year').value = parts[1];
                                        } else {
                                            document.getElementById('hidden_month').value = '';
                                            document.getElementById('hidden_year').value = '';
                                        }
                                    });
                                });
                            </script>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">Showroom Target (Total Target)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light fw-bold">₹</span>
                                    <input type="number" name="total_target" id="total_target" class="form-control fw-bold" step="0.01" placeholder="0.00" value="<?= $valTotalTarget ?>" required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted mb-1">Incentive Plan</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="incentive_percent" id="incentive_percent" class="form-control fw-bold text-primary" step="0.01" value="<?= $valIncentive ?>" required>
                                    <span class="input-group-text bg-primary text-white fw-bold">%</span>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted mb-1">Total Pool</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light fw-bold">₹</span>
                                    <div class="form-control fw-bold bg-light">
                                        <span id="total_incentive_amount">0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Distribution Section -->
                        <div class="row g-3">
                            <!-- Left Column: Manager Allocation -->
                            <div class="col-md-5">
                                <div class="border rounded p-3 h-100 bg-light-subtle">
                                    <div class="d-flex align-items-center mb-2 border-bottom pb-2">
                                        <i class="ti ti-user-check text-info me-2"></i>
                                        <h6 class="fw-bold mb-0 text-secondary">Manager Share</h6>
                                        <span class="ms-auto badge bg-light-info text-info">Pool: ₹ <span id="manager_share_amount">0.00</span></span>
                                    </div>
                                    
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label small text-muted mb-1">Percentage</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="manager_share_percent" id="manager_share_percent" class="form-control fw-bold" step="0.01" value="<?= $valManagerShare ?>" required>
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small text-muted mb-1">Eligible Roles</label>
                                            <select name="manager_roles[]" id="manager_roles" class="form-select select2 form-select-sm" multiple required data-placeholder="Select Roles">
                                                <?php foreach ($roles as $role): 
                                                    $selected = $isEdit ? (in_array($role['role_id'], $existingManagerRoles) ? 'selected' : '') : 'selected';
                                                ?>
                                                    <option value="<?= $role['role_id'] ?>" <?= $selected ?>><?= htmlspecialchars($role['role_name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Manager Share Table -->
                                    <div class="table-responsive border rounded bg-white mt-2" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-hover align-middle mb-0" id="manager_share_table">
                                            <thead class="bg-light position-sticky top-0">
                                                <tr>
                                                    <th class="ps-2 small text-muted text-uppercase" style="width: 55%;">Role</th>
                                                    <th class="small text-muted text-uppercase" style="width: 45%;">Share (₹)</th>
                                                </tr>
                                            </thead>
                                            <tbody class="border-top-0">
                                                <?php foreach ($roles as $role): 
                                                    // Placeholder: If you have stored values, retrieve them here. 
                                                    // currently using empty string as default.
                                                    $mVal = ''; 
                                                ?>
                                                    <tr data-role-id="<?= $role['role_id'] ?>" style="display:none;">
                                                        <td class="ps-2 small fw-medium">
                                                            <?= htmlspecialchars($role['role_name']) ?>
                                                        </td>
                                                        <td class="pe-2 py-1">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text border-end-0 bg-transparent text-muted py-0">₹</span>
                                                                <input type="number" name="manager_role_share[<?= $role['role_id'] ?>]" class="form-control form-control-sm border-start-0 manager-share-input fw-bold" step="0.01" placeholder="0.00" value="<?= $mVal ?>" disabled>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Team Allocation -->
                            <div class="col-md-7">
                                <div class="border rounded p-3 h-100 position-relative">
                                    <div class="d-flex align-items-center mb-2 border-bottom pb-2">
                                        <i class="ti ti-users text-success me-2"></i>
                                        <h6 class="fw-bold mb-0 text-secondary">Team Share</h6>
                                        <span class="ms-auto badge bg-light-success text-success">Pool: ₹ <span id="team_share_amount">0.00</span></span>
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">Percentage</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="team_share_percent" id="team_share_percent" class="form-control fw-bold" step="0.01" value="<?= $valTeamShare ?>" required>
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label small text-muted mb-1">Participating Teams</label>
                                            <select name="team_departments[]" id="team_departments" class="form-select select2 form-select-sm" multiple required data-placeholder="Select Depts">
                                                <?php foreach ($departments as $dept): 
                                                    $deptSelected = $isEdit ? (isset($deptTargets[$dept['department_id']]) ? 'selected' : '') : 'selected';
                                                ?>
                                                    <option value="<?= $dept['department_id'] ?>" <?= $deptSelected ?>><?= htmlspecialchars($dept['department_name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="table-responsive border rounded bg-white">
                                        <table class="table table-sm table-hover align-middle mb-0" id="dept_target_table">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-2 small text-muted text-uppercase" style="width: 40%;">Department</th>
                                                    <th class="small text-muted text-uppercase" style="width: 30%;">Target Amount (₹)</th>
                                                    <th class="small text-muted text-uppercase" style="width: 30%;">Incentive Share (₹)</th>
                                                </tr>
                                            </thead>
                                            <tbody class="border-top-0">
                                                <?php if (!empty($departments)): ?>
                                                    <?php foreach ($departments as $dept): 
                                                        $tVal = isset($deptTargets[$dept['department_id']]) ? $deptTargets[$dept['department_id']]['team_member_incentive'] : '';
                                                        $hVal = isset($deptTargets[$dept['department_id']]) ? $deptTargets[$dept['department_id']]['target_amount'] : '';
                                                    ?>
                                                        <tr data-dept-id="<?= $dept['department_id'] ?>">
                                                            <td class="ps-2 small fw-medium">
                                                                <input type="hidden" name="department_ids[]" value="<?= $dept['department_id'] ?>">
                                                                <?= htmlspecialchars($dept['department_name']) ?>
                                                            </td>
                                                            <td class="pe-2 py-1">
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text border-end-0 bg-transparent text-muted py-0">₹</span>
                                                                    <input type="number" name="dept_target[<?= $dept['department_id'] ?>]" class="form-control form-control-sm border-start-0 dept-target-input fw-bold text-primary" step="0.01" placeholder="0.00" value="<?= $hVal ?>">
                                                                </div>
                                                            </td>
                                                            <td class="pe-2 py-1">
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text border-end-0 bg-transparent text-muted py-0">₹</span>
                                                                    <input type="number" name="team_incentive[<?= $dept['department_id'] ?>]" class="form-control form-control-sm border-start-0 team-share-input fw-bold bg-light" step="0.01" placeholder="0.00" value="<?= $tVal ?>" readonly>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr><td colspan="2" class="text-center py-2 small text-muted">No Departments Found</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12 text-end">
                                <?php if($isEdit): ?>
                                    <a href="<?= $basePath ?>/add_targets" class="btn btn-sm btn-danger me-2"><i class="ti ti-x me-1"></i> Cancel</a>
                                    <button type="submit" name="save_target" class="btn btn-sm btn-warning px-3 shadow-sm text-white"><i class="ti ti-pencil me-1"></i> Update Target</button>
                                <?php else: ?>
                                    <a href="add_targets" class="btn btn-sm btn-danger me-2"><i class="ti ti-x me-1"></i> Cancel</a>
                                    <button type="reset" class="btn btn-sm btn-warning me-2"><i class="ti ti-refresh me-1"></i> Reset</button>
                                    <button type="submit" name="save_target" class="btn btn-sm btn-success px-3 shadow-sm"><i class="ti ti-device-floppy me-1"></i> Save Target</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$showForm): ?>
            <!-- List of Targets -->
            <div class="card ">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Targets</h5>
                    <form action="<?= $basePath ?>/controller/purchase/delete_all_insentive_targets.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete ALL targets? This cannot be undone.');">
                        <input type="hidden" name="action" value="delete_all_insentive_targets">
                        <button type="submit" class="btn btn-sm btn-danger shadow-sm"><i class="ti ti-trash me-1"></i>Clear Database For Testing </button>
                    </form>
                    <a href="add_targets?action=create" class="btn btn-sm btn-primary shadow-sm"><i class="ti ti-plus me-1"></i> Add New Target</a>

                </div>
                <div class="card-body">
                    <table data-tables="basic" class="table table-striped dt-responsive align-middle mb-0" style="width: 100%;">
                        <thead class="bg-light text-uppercase fs-xs">
                            <tr>
                                <th>Month / Year</th>
                                <th>Total Target</th>
                                <th>Incentive %</th>
                                <th>Total Pool</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th style="width: 1%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $targetSql = "SELECT * FROM monthly_targets ORDER BY created_at DESC";
                            $targetResult = $conn->query($targetSql);
                            if ($targetResult && $targetResult->num_rows > 0) {
                                while ($target = $targetResult->fetch_assoc()) {
                                    $isDistributed = isset($target['distributed']) && $target['distributed'] == '1';
                                    $targetDate = strtotime("1 " . $target['month'] . " " . $target['year']);
                                    
                                    // Strict Current Month Check
                                    $currentMonth = date('F');
                                    $currentYear = date('Y');
                                    $isCurrentPeriod = ($target['month'] === $currentMonth) && ($target['year'] == $currentYear);
                                    
                                    $canEdit = !$isDistributed;
                                    $canDelete = !$isDistributed;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($target['month'] . ' ' . $target['year']) ?></td>
                                        <td><?= number_format($target['total_target'], 2) ?></td>
                                        <td><?= number_format($target['incentive_percent'], 2) ?>%</td>
                                        <td>
                                            <?php 
                                            // Calculate Pool
                                            $pool = $target['total_target'] * ($target['incentive_percent'] / 100);
                                            echo '₹ ' . number_format($pool, 2);
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($target['distributed'] == '1'): ?>
                                                <span class="badge bg-light-success text-success">Distributed</span>
                                            <?php else: ?>
                                                <span class="badge bg-light-warning text-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d M Y H:i', strtotime($target['created_at'])) ?></td>
                                        <td class="text-nowrap">
                                            <a href="view_monthly_target?id=<?= $target['id'] ?>" class="btn btn-sm btn-info me-1 shadow-sm" title="View Details"><i class="ti ti-eye"></i></a>
                                            <?php if ($canEdit): ?>
                                                <a href="add_targets?id=<?= $target['id'] ?>" class="btn btn-sm btn-warning me-1 shadow-sm text-white" title="Edit"><i class="ti ti-pencil"></i></a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-light me-1 border" disabled title="Edit Disabled"><i class="ti ti-pencil text-muted"></i></button>
                                            <?php endif; ?>

                                            <?php if ($canDelete): ?>
                                                <a href="<?= $basePath ?>/controller/purchase/delete_target.php?id=<?= $target['id'] ?>" class="btn btn-sm btn-danger shadow-sm" onclick="return confirm('Are you sure you want to delete this target?');" title="Delete"><i class="ti ti-trash"></i></a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-light border" disabled title="Delete Disabled"><i class="ti ti-trash text-muted"></i></button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

<!-- View Modal or Logic can be added later -->
<script>


    // Calculation and Interaction Logic
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Select2
        $('.select2').select2({
            placeholder: "Select an option",
            allowClear: true
        });

        const totalTargetInput = document.getElementById('total_target');
        const incentivePercentInput = document.getElementById('incentive_percent');
        const managerSharePercentInput = document.getElementById('manager_share_percent');
        const teamSharePercentInput = document.getElementById('team_share_percent');
        
        const totalIncentiveDisplay = document.getElementById('total_incentive_amount');
        const managerShareDisplay = document.getElementById('manager_share_amount');
        const teamShareDisplay = document.getElementById('team_share_amount');

        // Manager handling
        const managerRoleSelect = $('#manager_roles');
        const managerTable = document.getElementById('manager_share_table');

        // Department handling
        const teamDeptSelect = $('#team_departments'); // Use jQuery for select2
        const deptTable = document.getElementById('dept_target_table');

        function calculateIncentives() {
            const totalTarget = parseFloat(totalTargetInput.value) || 0;
            const incentivePercent = parseFloat(incentivePercentInput.value) || 0;
            const managerSharePercent = parseFloat(managerSharePercentInput.value) || 0;
            const teamSharePercent = parseFloat(teamSharePercentInput.value) || 0;

            // correct total incentive calculation
            const totalIncentive = totalTarget * (incentivePercent / 100);
            totalIncentiveDisplay.innerText = totalIncentive.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const managerAmount = totalIncentive * (managerSharePercent / 100);
            managerShareDisplay.innerText = managerAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const teamAmount = totalIncentive * (teamSharePercent / 100);
            teamShareDisplay.innerText = teamAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Add event listeners
        totalTargetInput.addEventListener('input', function() {
             calculateIncentives();
             updateDepartmentTable();
             updateManagerTable();
         });
        incentivePercentInput.addEventListener('input', function() {
             calculateIncentives();
             updateDepartmentTable();
             updateManagerTable();
         });
        // Manager/Team share listeners below...

        // Initial calculation
        calculateIncentives();

        // Function to update department table rows based on selection
        function updateDepartmentTable(preserveExisting = false) {
            const selectedDepts = teamDeptSelect.val() || [];
            const rows = deptTable.querySelectorAll('tbody tr[data-dept-id]');
            
            // Count active departments
            let activeCount = 0;
            rows.forEach(row => {
                const deptId = row.getAttribute('data-dept-id');
                if (selectedDepts.includes(deptId)) {
                    activeCount++;
                }
            });

            // Calculate Total Showroom Target
            const totalTarget = parseFloat(totalTargetInput.value) || 0;
            const initialTargetShare = activeCount > 0 ? (totalTarget / activeCount) : 0;

            rows.forEach(row => {
                const deptId = row.getAttribute('data-dept-id');
                const targetInput = row.querySelector('.dept-target-input');
                const incentiveInput = row.querySelector('.team-share-input');
                
                if (selectedDepts.includes(deptId)) {
                    row.style.display = ''; 
                    targetInput.disabled = false;
                    incentiveInput.disabled = false;
                    
                    if(targetInput) {
                        if (preserveExisting && targetInput.value.trim() !== '') {
                            targetInput.dataset.manual = "false";
                        } else {
                            targetInput.value = initialTargetShare.toFixed(2);
                            targetInput.dataset.manual = "false";
                        }
                    }
                } else {
                    row.style.display = 'none'; 
                    targetInput.disabled = true; 
                    targetInput.value = ''; 
                    targetInput.dataset.manual = "false";
                    incentiveInput.disabled = true;
                    incentiveInput.value = '';
                }
            });
            updateHiddenIncentives();
        }

        // Generate Hidden Incentives (Forward Calculation) based on Target Amounts
        function updateHiddenIncentives() {
            const totalTarget = parseFloat(totalTargetInput.value) || 0;
            const teamShareDisplay = document.getElementById('team_share_amount');
            const totalTeamSharePool = parseFloat(teamShareDisplay.textContent.replace(/,/g, '')) || 0;

            const rows = deptTable.querySelectorAll('tbody tr[data-dept-id]');
            rows.forEach(row => {
                const targetInput = row.querySelector('.dept-target-input');
                const incentiveInput = row.querySelector('.team-share-input');
                
                if (targetInput && incentiveInput && !targetInput.disabled) {
                    const deptTarget = parseFloat(targetInput.value) || 0;
                    let share = 0;
                    if (totalTarget > 0) {
                        share = (deptTarget / totalTarget) * totalTeamSharePool;
                    }
                    incentiveInput.value = share.toFixed(2);
                }
            });
        }

        // Auto-adjust logic to balance TARGET inputs (Live)
        deptTable.addEventListener('input', function(e) {
            if (e.target.classList.contains('dept-target-input')) {
                const changedInput = e.target;
                
                // Mark this input as manually set by user
                changedInput.dataset.manual = "true";
                
                const totalTarget = parseFloat(totalTargetInput.value) || 0;

                // Get all active inputs
                const allInputs = Array.from(deptTable.querySelectorAll('.dept-target-input')).filter(input => !input.disabled);
                
                // Separate into Manual and Auto (Flexible) lists
                const manualInputs = allInputs.filter(input => input.dataset.manual === "true");
                const autoInputs = allInputs.filter(input => input.dataset.manual !== "true");
                
                // Check Sum of Manuals
                let currentManualSum = manualInputs.reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
                
                if (autoInputs.length > 0) {
                    // Distribute remaining to Auto
                    let remainingForAuto = totalTarget - currentManualSum;
                    if (remainingForAuto < 0) remainingForAuto = 0;
                    const share = remainingForAuto / autoInputs.length;
                    
                    autoInputs.forEach(input => {
                        input.value = share.toFixed(2);
                    });
                } else {
                    // Conflict: All are Manual. 
                    const victim = manualInputs.find(input => input !== changedInput);
                    
                    if (victim) {
                         victim.dataset.manual = "false"; // Convert to Auto
                         
                         // Recalculate Remainder
                         const newManualSum = currentManualSum - (parseFloat(victim.value) || 0);
                         let remainingTotal = totalTarget - newManualSum;
                         if (remainingTotal < 0) remainingTotal = 0;
                         
                         victim.value = remainingTotal.toFixed(2);
                    }
                }
                
                updateHiddenIncentives();
            }
        });
        
         // Manager and Team Share % Two-Way Binding
         managerSharePercentInput.addEventListener('input', function() {
             let val = parseFloat(this.value) || 0;
             if(val <= 100) {
                 teamSharePercentInput.value = (100 - val).toFixed(2);
             }
             calculateIncentives();
             updateDepartmentTable();
             updateManagerTable();
         });

         teamSharePercentInput.addEventListener('input', function() {
             let val = parseFloat(this.value) || 0;
             if(val <= 100) {
                 managerSharePercentInput.value = (100 - val).toFixed(2);
             }
             calculateIncentives();
             updateDepartmentTable();
             updateManagerTable();
         });

        // Handle Department/Manager Selection Changes
        teamDeptSelect.on('change', function() {
             updateDepartmentTable();
        });
        managerRoleSelect.on('change', function() {
             updateManagerTable();
        });
        
        // --- Manager Share Distribution Logic ---
        function updateManagerTable(preserveExisting = false) {
            const selectedRoles = managerRoleSelect.val() || [];
            
            const rows = managerTable.querySelectorAll('tbody tr[data-role-id]');
            
            // Count active roles
            let activeCount = 0;
            rows.forEach(row => {
                const roleId = row.getAttribute('data-role-id');
                if (selectedRoles.includes(roleId)) {
                    activeCount++;
                }
            });

            // Calculate Total Manager Pool
            const managerShareDisplay = document.getElementById('manager_share_amount');
            const totalManagerShare = parseFloat(managerShareDisplay.textContent.replace(/,/g, '')) || 0;
            
            const initialShare = activeCount > 0 ? (totalManagerShare / activeCount) : 0;

            rows.forEach(row => {
                const roleId = row.getAttribute('data-role-id');
                const inputs = row.querySelectorAll('input');
                const incentiveInput = row.querySelector('.manager-share-input');
                
                if (selectedRoles.includes(roleId)) {
                    row.style.display = ''; 
                    inputs.forEach(input => input.disabled = false);
                    
                    if(incentiveInput) {
                        if (preserveExisting && incentiveInput.value.trim() !== '') {
                            incentiveInput.dataset.manual = "false";
                        } else {
                            incentiveInput.value = initialShare.toFixed(2);
                            incentiveInput.dataset.manual = "false";
                        }
                    }
                } else {
                    row.style.display = 'none'; 
                    inputs.forEach(input => {
                        input.disabled = true; 
                        input.value = ''; 
                        input.dataset.manual = "false";
                    });
                }
            });
        }

        managerTable.addEventListener('input', function(e) {
            if (e.target.classList.contains('manager-share-input')) {
                const changedInput = e.target;
                changedInput.dataset.manual = "true";
                
                // Recalculate Total Manager Pool Source
                const totalTarget = parseFloat(totalTargetInput.value) || 0;
                const incentivePercent = parseFloat(incentivePercentInput.value) || 0;
                const managerSharePercent = parseFloat(managerSharePercentInput.value) || 0;
                const totalManagerShare = totalTarget * (incentivePercent / 100) * (managerSharePercent / 100);

                // Get Active Inputs
                const allInputs = Array.from(managerTable.querySelectorAll('.manager-share-input')).filter(input => !input.disabled);
                const manualInputs = allInputs.filter(input => input.dataset.manual === "true");
                const autoInputs = allInputs.filter(input => input.dataset.manual !== "true");
                
                let currentManualSum = manualInputs.reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
                
                if (autoInputs.length > 0) {
                    const remaining = totalManagerShare - currentManualSum;
                    const share = remaining / autoInputs.length;
                    autoInputs.forEach(input => {
                        input.value = share.toFixed(2);
                    });
                } else {
                    // Conflict resolution
                    const victim = manualInputs.find(input => input !== changedInput);
                    if (victim) {
                         victim.dataset.manual = "false"; 
                         const newManualSum = currentManualSum - (parseFloat(victim.value) || 0);
                         const remainingTotal = totalManagerShare - newManualSum;
                         victim.value = remainingTotal.toFixed(2);
                    }
                }
            }
        });
        
        // Check for Existing Target (Validation)
        const monthSelect = document.getElementById('target_month');
        const yearSelect = document.getElementById('target_year');
        const targetIdInput = document.querySelector('input[name="target_id"]'); 

        function checkTargetExists() {
            const month = monthSelect.value;
            const year = yearSelect.value;
            const excludeId = targetIdInput ? targetIdInput.value : 0;

            if (month && year) {
                const formData = new FormData();
                formData.append('month', month);
                formData.append('year', year);
                if(excludeId) formData.append('exclude_id', excludeId);

                fetch('<?= $basePath ?>/controller/purchase/check_target_exists.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        alert('A monthly target for ' + month + ' ' + year + ' already exists!');
                        monthSelect.value = ''; // Reset month
                    }
                })
                .catch(error => console.error('Error checking target:', error));
            }
        }

        if(monthSelect && yearSelect) {
            monthSelect.addEventListener('change', checkTargetExists);
            yearSelect.addEventListener('change', checkTargetExists);
        }

        // Initial Trigger
        updateDepartmentTable(true);
        updateManagerTable(true);
    });
</script>
