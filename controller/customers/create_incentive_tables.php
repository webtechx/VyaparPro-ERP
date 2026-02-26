<?php
// create_incentive_tables.php
require_once '../../config/conn.php';

// 1. Alter employees_listing to add total_incentive_earned and current_incentive_balance
// Check if column exists first
$checkSql = "SHOW COLUMNS FROM employees_listing LIKE 'total_incentive_earned'";
$result = $conn->query($checkSql);

if ($result && $result->num_rows == 0) {
    // Add columns
    $sql = "ALTER TABLE employees_listing 
            ADD COLUMN total_incentive_earned DECIMAL(15,2) DEFAULT 0.00 AFTER bank_details_id,
            ADD COLUMN current_incentive_balance DECIMAL(15,2) DEFAULT 0.00 AFTER total_incentive_earned";
    
    if ($conn->query($sql)) {
        echo "Updated employees_listing table.<br>";
    } else {
        echo "Error updating employees_listing: " . $conn->error . "<br>";
    }
} else {
    echo "employees_listing already has incentive columns.<br>";
}

// 2. Create incentive_ledger table
$tableSql = "CREATE TABLE IF NOT EXISTS incentive_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    monthly_target_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    distribution_type ENUM('manager', 'team', 'manual') NOT NULL, 
    distribution_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_by INT,
    FOREIGN KEY (employee_id) REFERENCES employees_listing(id) ON DELETE CASCADE,
    FOREIGN KEY (monthly_target_id) REFERENCES monthly_targets(id) ON DELETE CASCADE
)";

if ($conn->query($tableSql)) {
    echo "Table incentive_ledger created/exists.<br>";
} else {
    echo "Error creating incentive_ledger: " . $conn->error . "<br>";
}

echo "Database updates completed.";
?>
