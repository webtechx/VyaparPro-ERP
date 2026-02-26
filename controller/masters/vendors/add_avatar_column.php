<?php
// Script to add avatar column to vendors_listing table
require_once '../../../config/conn.php';

// Check if column exists
$check = "SHOW COLUMNS FROM vendors_listing LIKE 'avatar'";
$result = $conn->query($check);

if($result && $result->num_rows > 0) {
    echo "Column 'avatar' already exists.";
} else {
    $sql = "ALTER TABLE vendors_listing ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER status";
    if($conn->query($sql)){
        echo "Success: Column 'avatar' added to vendors_listing table.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
}
?>
