<?php
include __DIR__ . '/../../config/conn.php';

if(isset($_POST['sign_in']))
{
    $employee_code = trim($_POST['employee_code']);
    $password = $_POST['password'];

    // Prepare statement to fetch employee details by Employee Code
    $sql = "SELECT e.*, o.organizations_code, o.organization_short_code, o.organization_name FROM employees e LEFT JOIN organizations o ON e.organization_id = o.organization_id WHERE e.employee_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $employee_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1)
    {
        $user = $result->fetch_assoc();
        
        // Verify Password
        if(password_verify($password, $user['password']))
        {
            // Check if Account is Active
            if($user['is_active'] == 1) {
                // Start Session if not started
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                // Set Session Variables
                $_SESSION['user_id'] = $user['employee_id']; 
                $_SESSION['organization_id'] = $user['organization_id'];
                $_SESSION['organization_code'] = $user['organizations_code'];
                $_SESSION['organization_short_code'] = $user['organization_short_code'];
                $_SESSION['organization_name'] = $user['organization_name'];
                $_SESSION['role_id'] = $user['role_id']; // Kept for reference
                $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['avatar'] = $user['employee_image'];

                // Load Permissions from employee_permissions
                $_SESSION['permissions'] = [];
                $permStmt = $conn->prepare("SELECT module_slug, can_view, can_add, can_edit, can_delete FROM employee_permissions WHERE employee_id = ?");
                $permStmt->bind_param("i", $user['employee_id']);
                $permStmt->execute();
                $permResult = $permStmt->get_result();
                while($perm = $permResult->fetch_assoc()){
                    $_SESSION['permissions'][$perm['module_slug']] = [
                        'view' => $perm['can_view'],
                        'add' => $perm['can_add'],
                        'edit' => $perm['can_edit'],
                        'delete' => $perm['can_delete']
                    ];
                }

                // Redirect based on Role
                $redirect = $user['redirect_url'] ?? 'dashboard';
                header("Location: ../../" . $redirect);
                exit();
            } else {
                header("Location: ../../?error=Account is inactive. Please contact Admin.");
                exit();
            }
        }
        else
        {
            header("Location: ../../?error=Invalid Password");
            exit();
        }
    }
    else
    {
        header("Location: ../../?error=Employee Code not found");
        exit();
    }
}
else {
    header("Location: ../../");
    exit();
}
?>