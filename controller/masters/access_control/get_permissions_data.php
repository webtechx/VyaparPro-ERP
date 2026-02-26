<?php
// Define Modules Array Dynamically by parsing index.php
$modules = [];
$indexPath = __DIR__ . '/../../../index.php';

if (file_exists($indexPath)) {
    $content = file_get_contents($indexPath);
    // Regex to find routes: 'slug' => 'path' or "slug" => "path"
    if (preg_match_all("/['\"]([a-zA-Z0-9_-]+)['\"]\s*=>\s*['\"]([^'\"]+)['\"]/", $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $slug = $m[1];
            $path = $m[2];

            // --- FILTERING LOGIC ---
            // Exclude Auth Views (Login, Register, etc.)
            if (strpos($path, 'views/auth/') !== false) continue;
            // Exclude Controllers (Logout, etc.)
            if (strpos($path, 'controller/') !== false) continue;
            // Exclude root/empty slug if necessary (though regex expects 1+ chars)
            if ($slug === '') continue; 
            
            // Format Name: "purchase_orders" -> "Purchase Orders"
            $name = ucwords(str_replace(['_', '-'], ' ', $slug));

            $modules[$slug] = $name;
        }
    }
}

// Custom Virtual Modules (Permissions not tied to a specific URL route)
$modules['grn_backdate'] = 'GRN: Allow Backdated Entry';

// Fallback if parsing failed
if (empty($modules)) {
    $modules = [
        'dashboard' => 'Dashboard',
        'employees' => 'Employees'
    ];
}

// Sort alphabetically for easier finding
asort($modules);

// Fetch Employees for Dropdown (Instead of Roles)
$employees = [];
$eRes = $conn->query("SELECT e.employee_id, e.first_name, e.last_name, e.employee_code, rl.role_name FROM employees AS e LEFT JOIN roles_listing rl ON e.role_id = rl.role_id WHERE e.is_active = 1 ORDER BY e.first_name ASC");
if($eRes){
    while($eRow = $eRes->fetch_assoc()){
        $employees[] = $eRow;
    }
}

// Handle Selected Employee Data Fetching
$selected_employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;
$currentRedirect = 'dashboard';
$permMap = [];

if($selected_employee_id > 0){
    // Fetch Current Redirect from Employees Table
    $empQ = $conn->query("SELECT redirect_url FROM employees WHERE employee_id = $selected_employee_id");
    if($empQ && $empQ->num_rows > 0){
        $currentRedirect = $empQ->fetch_assoc()['redirect_url'] ?? 'dashboard';
    }

    // Fetch Current Permissions from employee_permissions
    $pRes = $conn->query("SELECT * FROM employee_permissions WHERE employee_id = $selected_employee_id");
    if($pRes){
        while($p = $pRes->fetch_assoc()){
            $permMap[$p['module_slug']] = $p;
        }
    }
}
?>
