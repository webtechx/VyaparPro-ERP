<?php
require_once '../../../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['employee_id'])) {
    $employee_id = intval($_POST['employee_id']);
    $redirect_url = $_POST['redirect_url'];
    $perms = $_POST['perms'] ?? [];

    $conn->begin_transaction();
    try {
        // 1. Update Redirect URL in employees table
        $stmt = $conn->prepare("UPDATE employees SET redirect_url = ? WHERE employee_id = ?");
        $stmt->bind_param("si", $redirect_url, $employee_id);
        $stmt->execute();

        // 2. Clear Existing Permissions for this employee
        $conn->query("DELETE FROM employee_permissions WHERE employee_id = $employee_id");

        // 3. Insert New Permissions into employee_permissions
        $sql = "INSERT INTO employee_permissions (employee_id, module_slug, can_view, can_add, can_edit, can_delete) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        foreach ($perms as $slug => $actions) {
            $view = isset($actions['view']) ? 1 : 0;
            $add = isset($actions['add']) ? 1 : 0;
            $edit = isset($actions['edit']) ? 1 : 0;
            $del = isset($actions['delete']) ? 1 : 0;

            if ($view || $add || $edit || $del) {
                $stmt->bind_param("isiiii", $employee_id, $slug, $view, $add, $edit, $del);
                $stmt->execute();
            }
        }

        // --- Permission Update Live Refresh ---
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // If the modified employee is the currently logged-in user, update their session immediately
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $employee_id) {
            // Re-build permissions array for session
            $_SESSION['permissions'] = [];
            foreach ($perms as $slug => $actions) {
                $_SESSION['permissions'][$slug] = [
                    'view' => isset($actions['view']) ? 1 : 0,
                    'add' => isset($actions['add']) ? 1 : 0,
                    'edit' => isset($actions['edit']) ? 1 : 0,
                    'delete' => isset($actions['delete']) ? 1 : 0
                ];
            }
            // Note: Redirect URL typically applies on next login, but if stored in session, update it too.
            // SigninController doesn't seem to store redirect_url in session, just uses it for the header() call.
        }
        // --------------------------------------

        $conn->commit();
        header("Location: ../../../access_control?employee_id=$employee_id&success=Permissions updated successfully");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../../../access_control?employee_id=$employee_id&error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: ../../../access_control");
    exit;
}
?>
