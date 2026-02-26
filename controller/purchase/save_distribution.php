<?php
// controller/customers/save_distribution.php
require_once __DIR__ . '/../../config/conn.php';


// Check Action
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'preview') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    // PREVIEW LOGIC
    $targetId = (int)$_POST['target_id'];
    
    // Fetch Target Details (including shares)
    $sql = "SELECT * FROM monthly_targets WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $targetId);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$target) {
        echo "<p class='text-danger'>Target not found.</p>";
        exit;
    }

    $monthNum = date("m", strtotime($target['month']));
    $yearVal = $target['year'];
    
    // Check Overall Showroom Sales vs Target
    $salesSql = "SELECT SUM(total_amount) as total_sales FROM sales_invoices 
                 WHERE MONTH(invoice_date) = '$monthNum' AND YEAR(invoice_date) = '$yearVal' AND status != 'Cancelled'";
    $salesResult = $conn->query($salesSql);
    $totalSales = 0;
    if ($salesResult && $sRow = $salesResult->fetch_assoc()) {
        $totalSales = $sRow['total_sales'] ?? 0;
    }
    
    $overallAchieved = $totalSales >= $target['total_target'];

    if (!$overallAchieved) {
        echo "<div class='alert alert-danger'><i class='ti ti-x me-2'></i><strong>Distribution Blocked:</strong> Overall Showroom Target of ₹" . number_format($target['total_target'], 2) . " was not met. Actual Sales: ₹" . number_format($totalSales, 2) . "</div>";
        exit;
    }
    
    $pool = $target['total_target'] * ($target['incentive_percent'] / 100);
    $mgrShare = $pool * ($target['manager_share_percent'] / 100);
    $teamSharePool = $pool * ($target['team_share_percent'] / 100);
    
    // Decode Manager Roles
    $mgrRoles = json_decode($target['manager_roles'], true);
    if (!is_array($mgrRoles)) {
        $mgrRoles = explode(',', $target['manager_roles']);
    }
    $roleIdsArray = array_filter(array_map('intval', $mgrRoles));
    $roleIds = implode(',', $roleIdsArray);
    
    $managers = [];
    if (!empty($roleIds)) {
        $mgrSql = "SELECT employee_id, first_name, last_name, role_id FROM employees WHERE role_id IN ($roleIds) AND is_active = 1 AND role_id != 4"; 
        $mgrResult = $conn->query($mgrSql);
        if ($mgrResult) {
            while ($row = $mgrResult->fetch_assoc()) {
                $managers[] = $row;
            }
        }
    }
    
    $numManagers = count($managers);
    $amtPerManager = $numManagers > 0 ? ($mgrShare / $numManagers) : 0;
    
    // Fetch Department Targets & Validate against Dept Sales
    $deptSql = "SELECT dt.*, d.department_name, 
                (SELECT COUNT(*) FROM employees WHERE department_id = dt.department_id AND is_active = 1 AND role_id = 4) as emp_count 
                FROM department_targets dt 
                JOIN department_listing d ON dt.department_id = d.department_id 
                WHERE dt.monthly_target_id = ?";
    $stmt = $conn->prepare($deptSql);
    $stmt->bind_param("i", $targetId);
    $stmt->execute();
    $deptResult = $stmt->get_result();
    
    $departments = [];
    $totalDistributedTeamShare = 0;

    // Check individual department sales target
    $deptSalesStmt = $conn->prepare("SELECT SUM(si.total_amount) as dept_sales FROM sales_invoices si JOIN employees e ON si.sales_employee_id = e.employee_id WHERE e.department_id = ? AND MONTH(si.invoice_date) = ? AND YEAR(si.invoice_date) = ? AND si.status != 'Cancelled'");

    while ($row = $deptResult->fetch_assoc()) {
        $dId = $row['department_id'];
        $deptSalesStmt->bind_param("iii", $dId, $monthNum, $yearVal);
        $deptSalesStmt->execute();
        $dsRes = $deptSalesStmt->get_result()->fetch_assoc();
        
        $row['actual_sales'] = $dsRes['dept_sales'] ?? 0;
        $row['is_achieved'] = $row['actual_sales'] >= $row['target_amount'];
        $departments[] = $row;
    }
    $deptSalesStmt->close();
    $stmt->close();
    
    // Generate Preview Layout
    echo '<div class="preview-results text-start">';
    
    // Managers
    if ($numManagers > 0) {
        echo '<div class="card mb-3 border border-info shadow-none overflow-auto">';
        echo '<div class="card-header bg-info-subtle border-info text-info fw-bold py-2"><i class="ti ti-user-check me-2"></i>Manager Distribution (Total: ₹' . number_format($mgrShare, 2) . ')</div>';
        echo '<div class="card-body p-0">';
        echo '<table class="table table-hover table-sm align-middle mb-0">';
        echo '<tbody>';
        foreach ($managers as $mgr) {
            echo '<tr>
                    <td class="ps-3 py-2"><div class="fw-bold text-dark fs-6">' . htmlspecialchars($mgr['first_name'] . ' ' . $mgr['last_name']) . '</div><div class="small fw-medium text-muted">Manager Role</div></td>
                    <td class="text-end pe-3 fw-bold text-success fs-6">+ ₹ ' . number_format($amtPerManager, 2) . '</td>
                  </tr>';
        }
        echo '</tbody></table></div></div>';
    } else {
        echo '<div class="alert alert-soft-secondary border-secondary text-muted"><i class="ti ti-info-circle me-1"></i> No eligible managers found.</div>';
    }
    
    // Departments
    if (!empty($departments)) {
        foreach ($departments as $dept) {
            $deptShare = $dept['team_member_incentive'];
            $empCount = $dept['emp_count'];
            $targetAmt = $dept['target_amount'];
            $actualAmt = $dept['actual_sales'];

            if ($dept['is_achieved']) {
                $amtPerEmp = $empCount > 0 ? ($deptShare / $empCount) : 0;
                $totalDistributedTeamShare += $deptShare;
                
                echo '<div class="card mb-3 border border-success shadow-none overflow-auto">';
                echo '<div class="card-header border-success bg-success-subtle text-success py-2 d-flex justify-content-between align-items-center">
                        <div><i class="ti ti-users me-2"></i><span class="fw-bold text-uppercase">' . htmlspecialchars($dept['department_name']) . '</span> &nbsp;<span class="badge bg-success shadow-sm">Target Achieved</span></div>
                        <div class="fw-bold text-success fs-6">Share: ₹' . number_format($deptShare, 2) . '</div>
                      </div>';
                echo '<div class="card-body p-2 bg-light border-bottom border-success border-opacity-25">';
                echo '<div class="d-flex justify-content-between small fw-bold text-muted px-2">
                        <span><i class="ti ti-target me-1"></i>Target: ₹' . number_format($targetAmt, 2) . '</span>
                        <span class="text-success"><i class="ti ti-trending-up me-1"></i>Sales: ₹' . number_format($actualAmt, 2) . '</span>
                        <span class="text-primary"><i class="ti ti-user me-1"></i>Eligible Staff: ' . $empCount . '</span>
                      </div>';
                echo '</div>';
                
                if ($empCount > 0) {
                    echo '<div class="card-body p-0">';
                    echo '<table class="table table-hover table-sm align-middle mb-0">';
                    echo '<tbody>';
                    $empSql = "SELECT employee_id, first_name, last_name FROM employees WHERE department_id = ? AND is_active = 1 AND role_id = 4";
                    $stmt = $conn->prepare($empSql);
                    $stmt->bind_param("i", $dept['department_id']);
                    $stmt->execute();
                    $emps = $stmt->get_result();
                    while ($emp = $emps->fetch_assoc()) {
                         echo '<tr>
                                <td class="ps-3 py-2"><div class="fw-bold text-dark">' . htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) . '</div></td>
                                <td class="text-end pe-3 fw-bold text-success">+ ₹ ' . number_format($amtPerEmp, 2) . '</td>
                              </tr>';
                    }
                    echo '</tbody></table></div>';
                    $stmt->close();
                } else {
                     echo '<div class="p-3 text-muted fst-italic text-center small bg-white">No active employees in this department.</div>';
                }
                echo '</div>'; // End Card
            } else {
                echo '<div class="card mb-3 border border-danger shadow-none opacity-75 overflow-auto">';
                echo '<div class="card-header border-danger bg-danger-subtle text-danger py-2 d-flex justify-content-between align-items-center">
                        <div><i class="ti ti-users-minus me-2"></i><span class="fw-bold text-uppercase">' . htmlspecialchars($dept['department_name']) . '</span> &nbsp;<span class="badge bg-danger shadow-sm">Target Failed</span></div>
                      </div>';
                echo '<div class="card-body p-2 bg-light">';
                echo '<div class="d-flex justify-content-between small fw-bold text-muted px-2">
                        <span><i class="ti ti-target me-1"></i>Target: ₹' . number_format($targetAmt, 2) . '</span>
                        <span class="text-danger"><i class="ti ti-trending-down me-1"></i>Sales: ₹' . number_format($actualAmt, 2) . '</span>
                        <span class="text-danger"><i class="ti ti-ban me-1"></i>Forfeited Share: ₹' . number_format($deptShare, 2) . '</span>
                      </div>';
                echo '</div></div>';
            }
        }
    }
    
    echo '</div>';
    exit;
}

// SAVE DISTRIBUTION LOGIC
if (isset($_POST['confirm_distribution'])) {
    $targetId = (int)$_POST['target_id'];
    
    if (!$targetId) {
        header("Location: ../../distribute_percentage?error=Invalid Target ID");
        exit;
    }
    
    $conn->begin_transaction();
    try {
        // Fetch Target Details
        $sql = "SELECT * FROM monthly_targets WHERE id = ? FOR UPDATE";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $targetId);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();
        
        if ($target['distributed'] == '1') {
            throw new Exception("Target already distributed.");
        }

        $monthNum = date("m", strtotime($target['month']));
        $yearVal = $target['year'];
        
        // Check Overall Showroom Sales vs Target
        $salesSql = "SELECT SUM(total_amount) as total_sales FROM sales_invoices 
                     WHERE MONTH(invoice_date) = '$monthNum' AND YEAR(invoice_date) = '$yearVal' AND status != 'Cancelled'";
        $salesResult = $conn->query($salesSql);
        $totalSales = 0;
        if ($salesResult && $sRow = $salesResult->fetch_assoc()) {
            $totalSales = $sRow['total_sales'] ?? 0;
        }
        
        $overallAchieved = $totalSales >= $target['total_target'];

        if (!$overallAchieved) {
            throw new Exception("Overall Showroom Target was not achieved. Distribution denied.");
        }
        
        $pool = $target['total_target'] * ($target['incentive_percent'] / 100);
        $mgrShare = $pool * ($target['manager_share_percent'] / 100);
        
        // --- 1. Distribute Manager Share ---
        // --- 1. Distribute Manager Share ---
        // Correctly Decode Manager Roles
        $mgrRoles = json_decode($target['manager_roles'], true);
        if (!is_array($mgrRoles)) {
            $mgrRoles = explode(',', $target['manager_roles']);
        }
        $roleIdsArray = array_filter(array_map('intval', $mgrRoles));
        $roleIds = implode(',', $roleIdsArray);
        
        $managers = [];
        if (!empty($roleIds)) {
            $mgrSql = "SELECT employee_id FROM employees WHERE role_id IN ($roleIds) AND is_active = 1 AND role_id != 4";
            $mgrResult = $conn->query($mgrSql);
            if ($mgrResult) {
                while ($row = $mgrResult->fetch_assoc()) {
                    $managers[] = $row['employee_id'];
                }
            }
        }
        
        $numManagers = count($managers);
        if ($numManagers > 0) {
            $amtPerManager = $mgrShare / $numManagers;
            foreach ($managers as $empId) {
                // Insert Ledger
                $insSql = "INSERT INTO incentive_ledger (employee_id, monthly_target_id, amount, distribution_type, notes) VALUES (?, ?, ?, 'manager', 'Manager Share for " . $target['month'] . " " . $target['year'] . "')";
                $stmt = $conn->prepare($insSql);
                $stmt->bind_param("iid", $empId, $targetId, $amtPerManager);
                $stmt->execute();
                
                // Update Employee Balance
                $updSql = "UPDATE employees SET total_incentive_earned = total_incentive_earned + ?, current_incentive_balance = current_incentive_balance + ? WHERE employee_id = ?";
                $stmt = $conn->prepare($updSql);
                $stmt->bind_param("ddi", $amtPerManager, $amtPerManager, $empId);
                $stmt->execute();
            }
        }
        
        // --- 2. Distribute Team Share (By Department if Target Achieved) ---
        $deptSql = "SELECT dt.department_id, dt.team_member_incentive, dt.target_amount, d.department_name  
                    FROM department_targets dt 
                    JOIN department_listing d ON dt.department_id = d.department_id
                    WHERE dt.monthly_target_id = ?";
        $stmt = $conn->prepare($deptSql);
        $stmt->bind_param("i", $targetId);
        $stmt->execute();
        $deptResult = $stmt->get_result();
        
        $deptSalesStmt = $conn->prepare("SELECT SUM(si.total_amount) as dept_sales FROM sales_invoices si JOIN employees e ON si.sales_employee_id = e.employee_id WHERE e.department_id = ? AND MONTH(si.invoice_date) = ? AND YEAR(si.invoice_date) = ? AND si.status != 'Cancelled'");

        while ($dept = $deptResult->fetch_assoc()) {
            $deptShare = $dept['team_member_incentive'];
            $deptId = $dept['department_id'];
            $targetAmt = $dept['target_amount'];
            
            // Check dept actual sales
            $deptSalesStmt->bind_param("iii", $deptId, $monthNum, $yearVal);
            $deptSalesStmt->execute();
            $dsRes = $deptSalesStmt->get_result()->fetch_assoc();
            $actualAmt = $dsRes['dept_sales'] ?? 0;
            
            if ($actualAmt >= $targetAmt) {
                // Get Employees in Dept
                $empSql = "SELECT employee_id FROM employees WHERE department_id = ? AND is_active = 1 AND role_id = 4";
                $estmt = $conn->prepare($empSql);
                $estmt->bind_param("i", $deptId);
                $estmt->execute();
                $empResult = $estmt->get_result();
                
                $deptEmployees = [];
                while ($e = $empResult->fetch_assoc()) {
                    $deptEmployees[] = $e['employee_id'];
                }
                
                $numEmps = count($deptEmployees);
                if ($numEmps > 0) {
                    $amtPerEmp = $deptShare / $numEmps;
                    foreach ($deptEmployees as $empId) {
                        // Insert Ledger
                        $insSql = "INSERT INTO incentive_ledger (employee_id, monthly_target_id, amount, distribution_type, notes) VALUES (?, ?, ?, 'team', 'Team Share (" . $dept['department_name'] . ") for " . $target['month'] . " " . $target['year'] . "')";
                        $lstmt = $conn->prepare($insSql);
                        $lstmt->bind_param("iid", $empId, $targetId, $amtPerEmp);
                        $lstmt->execute();
                        
                        // Update Balance
                        $updSql = "UPDATE employees SET total_incentive_earned = total_incentive_earned + ?, current_incentive_balance = current_incentive_balance + ? WHERE employee_id = ?";
                        $ustmt = $conn->prepare($updSql);
                        $ustmt->bind_param("ddi", $amtPerEmp, $amtPerEmp, $empId);
                        $ustmt->execute();
                    }
                }
                $estmt->close();
            }
        }
        $deptSalesStmt->close();
        
        // --- 3. Mark Target as Distributed ---
        $updTarget = "UPDATE monthly_targets SET distributed = '1' WHERE id = ?";
        $stmt = $conn->prepare($updTarget);
        $stmt->bind_param("i", $targetId);
        $stmt->execute();
        
        $conn->commit();
        header("Location: ../../distribute_percentage?success=Incentives distributed successfully!");
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../../distribute_percentage?error=Error: " . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: ../../distribute_percentage");
    exit;
}
?>
