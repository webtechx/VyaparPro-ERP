<?php
session_start();
include __DIR__ . '/../../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delete_all_insentive_targets') {

    $conn->begin_transaction();

    try {

        // Disable foreign key checks (important if tables linked)
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");

        // Truncate all incentive-related tables
        $conn->query("TRUNCATE TABLE incentive_ledger");
        $conn->query("TRUNCATE TABLE department_targets");
        $conn->query("TRUNCATE TABLE monthly_targets");

        // Reset employee incentive values
        $conn->query("UPDATE employees 
                      SET total_incentive_earned = 0, 
                          current_incentive_balance = 0");

        // Enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");

        $conn->commit();

         header("Location: ../../add_targets?success=Target deleted successfully.");

    } catch (Exception $e) {

        $conn->rollback();
        header("Location: ../../add_targets?error=Error deleting target: " . urlencode($e->getMessage()));
    }
}
?>
