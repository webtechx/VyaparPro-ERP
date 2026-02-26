<?php
require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../../config/auth_guard.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $currentUserId = $currentUser['employee_id'] ?? 0;
    
    // Quick empty checks
    $current_pass = isset($_POST['current_password']) ? trim($_POST['current_password']) : '';
    $new_pass = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $confirm_pass = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        header("Location: $basePath/account_settings?error=" . urlencode("All fields are required."));
        exit;
    }

    if ($new_pass !== $confirm_pass) {
        header("Location: $basePath/account_settings?error=" . urlencode("New passwords do not match."));
        exit;
    }

    if (strlen($new_pass) < 6) {
        header("Location: $basePath/account_settings?error=" . urlencode("Password must be at least 6 characters."));
        exit;
    }

    // Verify current user exists
    $stmt = $conn->prepare("SELECT password FROM employees WHERE employee_id = ?");
    $stmt->bind_param("i", $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        
        // Verify old pass against old hash
        if (password_verify($current_pass, $row['password'])) {
            // Success, hash the new pass
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            
            // Assuming `password_view` is also actively stored (not highly recommended but following schema)
            $stmtUpdate = $conn->prepare("UPDATE employees SET password = ?, password_view = ? WHERE employee_id = ?");
            $stmtUpdate->bind_param("ssi", $new_hash, $new_pass, $currentUserId);
            
            if ($stmtUpdate->execute()) {
                header("Location: $basePath/account_settings?success=" . urlencode("Password updated successfully."));
            } else {
                header("Location: $basePath/account_settings?error=" . urlencode("Failed to update password. DB Error."));
            }
            $stmtUpdate->close();
            
        } else {
            header("Location: $basePath/account_settings?error=" . urlencode("Incorrect current password."));
        }
    } else {
        header("Location: $basePath/account_settings?error=" . urlencode("Account not found."));
    }
    
    $stmt->close();
    exit;

} else {
    // If someone tries to visit GET
    header("Location: $basePath/account_settings");
    exit;
}
