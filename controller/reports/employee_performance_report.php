<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// --- Filters ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');
$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;
$role_id    = isset($_GET['role_id'])    ? intval($_GET['role_id'])    : 0;
$status     = isset($_GET['status'])     ? trim($_GET['status'])       : ''; // Invoice Status filter

// --- 1. Fetch Sales Data ---
// Only consider non-cancelled invoices for performance
$salesSql = "SELECT 
                si.sales_employee_id,
                COUNT(si.invoice_id) as invoice_count,
                SUM(si.total_amount) as total_sales
             FROM sales_invoices si
             WHERE si.organization_id = ? AND si.sales_employee_id > 0";

$salesParams = [$organization_id];
$salesTypes = "i";

// Date Filter for Sales
if (!empty($start_date) && !empty($end_date)) {
    $salesSql .= " AND si.invoice_date BETWEEN ? AND ?";
    $salesParams[] = $start_date;
    $salesParams[] = $end_date;
    $salesTypes .= "ss";
}

// Status Filter (Default to 'approved'/'paid' if strict, but report might want 'all valid')
// Let's exclude cancelled by default if no status selected, or filter specific
if (!empty($status)) {
    $salesSql .= " AND si.status = ?";
    $salesParams[] = $status;
    $salesTypes .= "s";
} else {
    $salesSql .= " AND si.status != 'cancelled'";
}

$salesSql .= " GROUP BY si.sales_employee_id";

$stmt = $conn->prepare($salesSql);
$stmt->bind_param($salesTypes, ...$salesParams);
$stmt->execute();
$salesRes = $stmt->get_result();

$salesData = []; // emp_id => ['count'=>, 'total'=>]
while($row = $salesRes->fetch_assoc()){
    $salesData[$row['sales_employee_id']] = [
        'count' => $row['invoice_count'],
        'total' => $row['total_sales']
    ];
}
$stmt->close();

// --- 2. Fetch Incentive Data ---
// Sum of incentives earned in the period
$incSql = "SELECT 
               il.employee_id,
               SUM(il.amount) as total_incentive
           FROM incentive_ledger il
           WHERE il.amount > 0"; // Only positive (earned), ignore withdrawals/deductions

// Date Filter for Incentives (distribution_date)
if (!empty($start_date) && !empty($end_date)) {
    // Adjust logic: end_date 23:59:59
    $incSql .= " AND il.distribution_date BETWEEN ? AND ?";
    // We need to pass full timestamp for date range validity if ledger has time
    // But simple compare works if format matches. Let's assume params are Y-m-d
    // Append time for accuracy
    $incSql .= " "; // logic handle in bind
}

// We need separate params for this query
// Actually, let's just run it carefully.
$incParams = []; 
$incTypes = "";

if (!empty($start_date) && !empty($end_date)) {
    $start_ts = $start_date . " 00:00:00";
    $end_ts = $end_date . " 23:59:59";
    $incParams[] = $start_ts;
    $incParams[] = $end_ts;
    $incTypes .= "ss";
}

$incSql .= " GROUP BY il.employee_id";

$incStmt = $conn->prepare($incSql);
if(!empty($incTypes)){
    $incStmt->bind_param($incTypes, ...$incParams);
}
$incStmt->execute();
$incRes = $incStmt->get_result();

$incentiveData = [];
while($row = $incRes->fetch_assoc()){
    $incentiveData[$row['employee_id']] = $row['total_incentive'];
}
$incStmt->close();


// --- 3. Fetch Employees & Merge ---
// We want employees who satisfy the header filters (Employee ID, Role)
// AND (Have Sales OR Have Incentives OR are Active Sales Staff)
// Simpler: Fetch ALL active employees matching filters, join data.

$empSql = "SELECT 
            e.employee_id,
            e.first_name,
            e.last_name,
            e.employee_code,
            e.employee_image,
            e.primary_email as email,
            r.role_name,
            d.department_name,
            o.organizations_code
           FROM employees e
           LEFT JOIN roles_listing r ON e.role_id = r.role_id
           LEFT JOIN department_listing d ON e.department_id = d.department_id
           JOIN organizations o ON e.organization_id = o.organization_id
           WHERE e.organization_id = ? AND e.is_active = 1";

$empParams = [$organization_id];
$empTypes = "i";

if ($employee_id > 0) {
    $empSql .= " AND e.employee_id = ?";
    $empParams[] = $employee_id;
    $empTypes .= "i";
}

if ($role_id > 0) {
    $empSql .= " AND e.role_id = ?";
    $empParams[] = $role_id;
    $empTypes .= "i";
}

$empStmt = $conn->prepare($empSql);
$empStmt->bind_param($empTypes, ...$empParams);
$empStmt->execute();
$empRes = $empStmt->get_result();

$reportData = [];
$total_sales_all = 0;
$total_incentive_all = 0;
$top_performer = null;
$max_sales = -1;

while($emp = $empRes->fetch_assoc()){
    $eid = $emp['employee_id'];
    
    $sales = $salesData[$eid] ?? ['count' => 0, 'total' => 0];
    $incentive = $incentiveData[$eid] ?? 0;
    
    // Only include if they have sales, incentives, or are selected specifically
    // If "All Employees" selected, maybe only show those with activity? 
    // Usually reports show zeros too if they are sales staff.
    // Let's show everyone who matches the filter for now.
    
    $row = [
        'employee' => $emp,
        'sales_count' => $sales['count'],
        'sales_total' => $sales['total'],
        'incentive_total' => $incentive
    ];
    
    $reportData[] = $row;
    
    $total_sales_all += $sales['total'];
    $total_incentive_all += $incentive;
    
    if($sales['total'] > $max_sales){
        $max_sales = $sales['total'];
        $top_performer = $emp;
    }
}
$empStmt->close();

// Sort by Sales Total Descending
usort($reportData, function($a, $b) {
    return $b['sales_total'] <=> $a['sales_total'];
});


// --- Prefill Data for View ---
$prefill_emp_name = '';
$prefill_emp_avatar = '';

if ($employee_id > 0) {
    // Quick fetch for prefill
    $pfQ = $conn->query("SELECT first_name, last_name, employee_image, organizations_code FROM employees e JOIN organizations o ON e.organization_id = o.organization_id WHERE employee_id = $employee_id");
    if($pfQ && $r = $pfQ->fetch_assoc()){
        $prefill_emp_name = $r['first_name'] . ' ' . $r['last_name'];
        if($r['employee_image']){
            $prefill_emp_avatar = "uploads/" . $r['organizations_code'] . "/employees/avatars/" . $r['employee_image'];
        }
    }
}

// Fetch All Employees for Filter Dropdown
$allEmpSql = "SELECT e.employee_id, e.first_name, e.last_name, e.employee_image, o.organizations_code, r.role_name 
              FROM employees e 
              JOIN organizations o ON e.organization_id = o.organization_id 
              LEFT JOIN roles_listing r ON e.role_id = r.role_id
              WHERE e.organization_id = $organization_id AND e.is_active = 1 ORDER BY e.first_name ASC";
$allEmpRes = $conn->query($allEmpSql);

// Fetch All Roles for Filter Dropdown
$rolesQ = $conn->query("SELECT role_id, role_name FROM roles_listing WHERE is_active = 1 ORDER BY role_name ASC");
?>
