<?php
session_start();
include __DIR__ . '/../../config/conn.php';

if (isset($_POST['save_target'])) {
    $month = $_POST['month'] ?? '';
    $year = (int)($_POST['year'] ?? date('Y'));
    $total_target = (float)($_POST['total_target'] ?? 0);
    $incentive_percent = (float)($_POST['incentive_percent'] ?? 0);
    
    $dept_ids = $_POST['department_ids'] ?? [];
    $dept_targets = $_POST['dept_target'] ?? [];

    $manager_share_percent = (float)($_POST['manager_share_percent'] ?? 20.00);
    $team_share_percent = (float)($_POST['team_share_percent'] ?? 80.00);
    
    $manager_roles = $_POST['manager_roles'] ?? [];
    $manager_roles_json = !empty($manager_roles) ? json_encode($manager_roles) : null;

    if (empty($month) || $total_target <= 0) {
        header("Location: ../../add_targets?error=Please fill all required fields correctly.");
        exit;
    }

    // Start Transaction
    $conn->begin_transaction();

    try {
        $target_id = isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0;

        if ($target_id > 0) {
             // Update existing target
             // Check for distributed status first
             $checkDistrib = $conn->query("SELECT distributed FROM monthly_targets WHERE id = $target_id");
             if ($checkDistrib && $checkDistrib->num_rows > 0) {
                 $dRow = $checkDistrib->fetch_assoc();
                 if ($dRow['distributed'] == 1) {
                     throw new Exception("Cannot update target: Incentive has already been distributed.");
                 }
             }

             $stmt = $conn->prepare("UPDATE monthly_targets SET month=?, year=?, total_target=?, incentive_percent=?, manager_share_percent=?, team_share_percent=?, manager_roles=? WHERE id=?");
             $stmt->bind_param("siddddsi", $month, $year, $total_target, $incentive_percent, $manager_share_percent, $team_share_percent, $manager_roles_json, $target_id);
             
             if (!$stmt->execute()) {
                 throw new Exception("Error updating monthly target: " . $stmt->error);
             }
             $monthly_target_id = $target_id;
             $stmt->close();
             
             // Delete existing department targets to re-insert fresh data
             $delStmt = $conn->prepare("DELETE FROM department_targets WHERE monthly_target_id = ?");
             $delStmt->bind_param("i", $monthly_target_id);
             $delStmt->execute();
             $delStmt->close();
             
        } else {
            // Insert into monthly_targets
            $stmt = $conn->prepare("INSERT INTO monthly_targets (month, year, total_target, incentive_percent, manager_share_percent, team_share_percent, manager_roles) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sidddds", $month, $year, $total_target, $incentive_percent, $manager_share_percent, $team_share_percent, $manager_roles_json);
            
            if (!$stmt->execute()) {
                throw new Exception("Error saving monthly target: " . $stmt->error);
            }
            $monthly_target_id = $stmt->insert_id;
            $stmt->close();
        }

        // Insert into department_targets
        // Using manager_incentive and team_member_incentive as 0 since they are handled globally/dynamically now.
        // Update: Restoring team_member_incentive storage as per latest request.
        $deptStmt = $conn->prepare("INSERT INTO department_targets (monthly_target_id, department_id, target_amount, team_member_incentive) VALUES (?, ?, ?, ?)");
        
        $dept_targets = $_POST['dept_target'] ?? []; 
        $team_incentives = $_POST['team_incentive'] ?? [];
        
        // Loop through valid submitted department IDs
        foreach ($dept_ids as $dept_id) {
            // Use dept_id as key because view inputs are named field[id]
            $amount = isset($dept_targets[$dept_id]) ? (float)$dept_targets[$dept_id] : 0;
            $team_inc = isset($team_incentives[$dept_id]) ? (float)$team_incentives[$dept_id] : 0;
            
            if ($amount > 0 || $team_inc > 0) {
                $deptStmt->bind_param("iidd", $monthly_target_id, $dept_id, $amount, $team_inc);
                if (!$deptStmt->execute()) {
                    throw new Exception("Error saving department target for ID $dept_id: " . $deptStmt->error);
                }
            }
        }
        $deptStmt->close();

        $conn->commit();
        header("Location: ../../add_targets?success=Target added successfully.");

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../../add_targets?error=" . urlencode($e->getMessage()));
    }

} else {
    header("Location: ../../add_targets");
}
?>
