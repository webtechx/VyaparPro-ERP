<?php
include __DIR__ . '/../../../config/auth_guard.php';

if (isset($_GET['id'])) {
    $role_id = (int) $_GET['id'];
    $organization_id = $_SESSION['organization_id'];

    // Check if role is used by any employee
    $check = $conn->prepare("SELECT COUNT(employee_id) as cnt FROM employees WHERE role_id = ? AND organization_id = ?");
    $check->bind_param("ii", $role_id, $organization_id);
    $check->execute();
    $cnt_result = $check->get_result();
    $used = $cnt_result->fetch_assoc();

    if ($used['cnt'] > 0) {
        header("Location: ../../../roles?error=Cannot delete role because it is assigned to " . $used['cnt'] . " employees.");
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM roles_listing WHERE role_id = ? AND organization_id = ?");
    $stmt->bind_param("ii", $role_id, $organization_id);

    if ($stmt->execute()) {
        header("Location: ../../../roles?success=Role deleted successfully");
    } else {
        header("Location: ../../../roles?error=Failed to delete role");
    }
    exit;
} else {
    header("Location: ../../../roles?error=Invalid request");
    exit;
}
?>
