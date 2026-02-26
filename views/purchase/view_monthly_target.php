<?php
$title = 'View Monthly Target';
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
$roleQuery = "SELECT role_id, role_name FROM roles_listing WHERE is_active = 1 AND role_id NOT IN (1, 4)";
$roleResult = $conn->query($roleQuery);
$roles = [];
if ($roleResult->num_rows > 0) {
    while ($row = $roleResult->fetch_assoc()) {
        $roles[] = $row;
    }
}

// Check for ID
$target_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($target_id == 0) {
    header("Location: add_targets");
    exit;
}

$targetData = [];
$deptTargets = [];
$existingManagerRoles = [];

// Default Values
$selectedMonth = '';
$selectedYear = $current_year;
$valTotalTarget = '';
$valIncentive = '2.00';
$valManagerShare = '20.00';
$valTeamShare = '80.00';

$stmt = $conn->prepare("SELECT * FROM monthly_targets WHERE id = ?");
$stmt->bind_param("i", $target_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $targetData = $result->fetch_assoc();
    
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
            $existingManagerRoles = $decoded; 
        }
    }
    
    // Fetch Department Targets
    $deptStmt = $conn->prepare("SELECT * FROM department_targets WHERE monthly_target_id = ?");
    $deptStmt->bind_param("i", $target_id);
    $deptStmt->execute();
    $deptResult = $deptStmt->get_result();
    while ($row = $deptResult->fetch_assoc()) {
        $deptTargets[$row['department_id']] = $row;
    }
} else {
    // ID not found
    header("Location: add_targets?error=Target Not Found");
    exit;
}
?>

<div class="container-fluid">
    <div class="row">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0 text-white"><i class="ti ti-eye me-2"></i>View Monthly Target</h6>
                    </div>
                </div>
                
                <div class="card-body p-3">
                    <!-- Read Only Mode -->
                        <!-- Global Settings Section -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">Target Period</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="ti ti-calendar"></i></span>
                                    <input type="text" class="form-control fw-medium bg-light" value="<?= htmlspecialchars($selectedMonth . ' ' . $selectedYear) ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">Showroom Target (Total Target)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light fw-bold bg-light">₹</span>
                                    <input type="number" id="total_target" class="form-control fw-bold bg-light" value="<?= $valTotalTarget ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted mb-1">Incentive Plan</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" id="incentive_percent" class="form-control fw-bold text-primary bg-light" value="<?= $valIncentive ?>" readonly>
                                    <span class="input-group-text bg-primary text-white fw-bold">%</span>
                                </div>
                            </div>
                            
 

                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">Total Pool</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light fw-bold bg-light">₹</span>
                                    <input type="text" id="total_incentive_amount" class="form-control fw-bold bg-light" value="0.00" readonly>
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
                                                <input type="number" id="manager_share_percent" class="form-control fw-bold" value="<?= $valManagerShare ?>" disabled>
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small text-muted mb-1">Eligible Roles</label>
                                            <select class="form-select select2 form-select-sm" multiple disabled>
                                                <?php foreach ($roles as $role): 
                                                    $selected = in_array($role['role_id'], $existingManagerRoles) ? 'selected' : '';
                                                ?>
                                                    <option value="<?= $role['role_id'] ?>" <?= $selected ?>><?= htmlspecialchars($role['role_name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
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
                                                <input type="number" id="team_share_percent" class="form-control fw-bold" value="<?= $valTeamShare ?>" disabled>
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label small text-muted mb-1">Participating Teams</label>
                                            <select id="team_departments" class="form-select select2 form-select-sm" multiple disabled>
                                                <?php foreach ($departments as $dept): 
                                                    $deptSelected = isset($deptTargets[$dept['department_id']]) ? 'selected' : '';
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
                                                                <?= htmlspecialchars($dept['department_name']) ?>
                                                            </td>
                                                            <td class="pe-2 py-1">
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text border-end-0 bg-transparent text-muted py-0">₹</span>
                                                                    <input type="number" class="form-control form-control-sm border-start-0 fw-bold text-primary" value="<?= $hVal ?>" disabled>
                                                                </div>
                                                            </td>
                                                            <td class="pe-2 py-1">
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text border-end-0 bg-transparent text-muted py-0">₹</span>
                                                                    <input type="number" class="form-control form-control-sm border-start-0 fw-bold bg-light" value="<?= $tVal ?>" disabled>
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
                                <a href="<?= $basePath ?>/add_targets" class="btn btn-sm btn-danger me-2"><i class="ti ti-arrow-left me-1"></i> Back to List</a>
                                <a href="<?= $basePath ?>/add_targets?id=<?= $target_id ?>" class="btn btn-sm btn-warning shadow-sm text-white"><i class="ti ti-pencil me-1"></i> Edit Target</a>
                            </div>
                        </div>
                </div>
            </div>
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

        // Department handling
        const teamDeptSelect = $('#team_departments'); // Use jQuery for select2
        const deptTable = document.getElementById('dept_target_table');

        function calculateIncentives() {
            const totalTarget = parseFloat(totalTargetInput.value) || 0;
            const incentivePercent = parseFloat(incentivePercentInput.value) || 0;
            const managerSharePercent = parseFloat(managerSharePercentInput.value) || 0;
            const teamSharePercent = parseFloat(teamSharePercentInput.value) || 0;

            const totalIncentive = totalTarget * (incentivePercent / 100);
            totalIncentiveDisplay.value = totalIncentive.toFixed(2);

            const managerAmount = totalIncentive * (managerSharePercent / 100);
            managerShareDisplay.innerText = managerAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const teamAmount = totalIncentive * (teamSharePercent / 100);
            teamShareDisplay.innerText = teamAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Initial calculation
        calculateIncentives();

        // Function to update department table rows based on selection
        function updateDepartmentTable() {
            const selectedDepts = teamDeptSelect.val() || [];
            
            const rows = deptTable.querySelectorAll('tbody tr[data-dept-id]');
            
            rows.forEach(row => {
                const deptId = row.getAttribute('data-dept-id');
                if (selectedDepts.includes(deptId)) {
                    row.style.display = ''; 
                } else {
                    row.style.display = 'none'; 
                }
            });
        }
        
        // Initial Trigger
        updateDepartmentTable();
    });
</script>
