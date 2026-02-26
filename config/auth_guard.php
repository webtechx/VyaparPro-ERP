<?php
include __DIR__ . '/conn.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    // Assume $basePath is available if running through router, otherwise default to relative root
    $redirect = (isset($basePath) ? $basePath : '') . '/';
    header('Location: ' . $redirect);
    exit();
}
// Fetch current user details
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $currentUserId = $_SESSION['user_id'];
    // Changed: Query employees table. Alias employee_id as user_id for compatibility.
    // Ensure connection is clean
    while($conn->more_results()) $conn->next_result();
    
    $stmt = $conn->prepare("SELECT u.*, u.employee_id as user_id, r.role_name FROM employees u LEFT JOIN roles_listing r ON u.role_id = r.role_id WHERE u.employee_id = ?");
    $stmt->bind_param("i", $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $currentUser = $result->fetch_assoc();
        
        // Security Check: If user is inactive/banned, force logout
        if ($currentUser['is_active'] != 1) {
            session_destroy();
            $redirect = (isset($basePath) ? $basePath : '') . '/?error=Account deactivated';
            header('Location: ' . $redirect);
            exit();
        }
    } else {
        // User ID in session but not in DB (deleted?)
        session_destroy();
        $redirect = (isset($basePath) ? $basePath : '') . '/';
        exit();
    }
    if(isset($result) && is_object($result)) $result->free(); 
    $stmt->close();

    // Fetch Org State Code if missing
    if (!isset($_SESSION['organization_state_code']) && isset($_SESSION['organization_id'])) {
         while($conn->more_results()) $conn->next_result();
         $osSql = "SELECT state_code FROM organizations WHERE organization_id = ?";
         $osStmt = $conn->prepare($osSql);
         $osStmt->bind_param("i", $_SESSION['organization_id']);
         $osStmt->execute();
         $osRes = $osStmt->get_result();
         if($osRes && $osRes->num_rows > 0){
             $_SESSION['organization_state_code'] = $osRes->fetch_assoc()['state_code'];
         }
         $osStmt->close();
    }

    // --- REFRESH PERMISSIONS ON EVERY REQUEST ---
    // This ensures that if an Admin changes permissions, the user sees it immediately without logout.
    $_SESSION['permissions'] = [];
    
    while($conn->more_results()) $conn->next_result();
    
    $permStmt = $conn->prepare("SELECT module_slug, can_view, can_add, can_edit, can_delete FROM employee_permissions WHERE employee_id = ?");
    $permStmt->bind_param("i", $currentUserId);
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
    if(isset($permResult) && is_object($permResult)) $permResult->free();
    $permStmt->close();
    // --------------------------------------------
}

if (!function_exists('can_access')) {
    function can_access($module, $action = 'view') {
        global $currentUser;
        // Admin Bypass (By Role Name)
        if (isset($currentUser['role_name']) && strcasecmp($currentUser['role_name'], 'SUPER ADMIN') === 0) return true;

        if (!isset($_SESSION['permissions'])) return false;
        
        // If module not defined in permissions, deny or allow? 
        // Deny by default usually. But for start, maybe allow if array empty? 
        // Let's being strict: Deny if not found.
        if (!isset($_SESSION['permissions'][$module])) return false;

        return $_SESSION['permissions'][$module][$action] == 1;
    }
}

