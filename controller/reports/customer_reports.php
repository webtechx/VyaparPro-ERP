<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// Report Type: birthday, anniversary, contact
$report_type = isset($_GET['type']) ? $_GET['type'] : 'contact';

// Filters
$month = isset($_GET['month']) ? intval($_GET['month']) : 0; // 0 = All
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// RESTRUCTURED QUERY BUILDING
$sql = "SELECT 
            c.customer_id,
            c.customer_name,
            c.company_name,
            c.customer_code,
            c.email,
            c.phone,
            c.date_of_birth,
            c.anniversary_date,
            c.avatar,
            ct.customers_type_name,
            c.address as address_line1, c.city, c.state
        FROM customers_listing c
        LEFT JOIN customers_type_listing ct ON c.customers_type_id = ct.customers_type_id
        WHERE c.organization_id = ?";

$params = [$organization_id];
$types = "i";

// Filter: Customer ID
if ($customer_id > 0) {
    $sql .= " AND c.customer_id = ?";
    $params[] = $customer_id;
    $types .= "i";
}

// Filter: Search
if (!empty($search_query)) {
    $sql .= " AND (c.customer_name LIKE ? OR c.company_name LIKE ? OR c.customer_code LIKE ? OR c.phone LIKE ?)";
    $like = "%$search_query%";
    array_push($params, $like, $like, $like, $like);
    $types .= "ssss";
}

// Filter: Type Specific Month
if ($month > 0) {
    if ($report_type === 'birthday') {
         $sql .= " AND MONTH(c.date_of_birth) = ?";
         $params[] = $month;
         $types .= "i";
    } elseif ($report_type === 'anniversary') {
         $sql .= " AND MONTH(c.anniversary_date) = ?";
         $params[] = $month;
         $types .= "i";
    }
}

// Filter: Type Existence Check
if ($report_type === 'birthday') {
    $sql .= " AND c.date_of_birth IS NOT NULL";
} elseif ($report_type === 'anniversary') {
    $sql .= " AND c.anniversary_date IS NOT NULL";
}

// THEN Append Order By
if ($report_type === 'birthday') {
    $sql .= " ORDER BY MONTH(c.date_of_birth) ASC, DAY(c.date_of_birth) ASC";
} elseif ($report_type === 'anniversary') {
    $sql .= " ORDER BY MONTH(c.anniversary_date) ASC, DAY(c.anniversary_date) ASC";
} else {
    $sql .= " ORDER BY c.customer_name ASC";
}


// Execute
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$customers = [];
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}
$stmt->close();

// Month List for Filter
$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// Helper to prefill customer name if ID set
$customer_name_prefill = '';
if ($customer_id > 0) {
    foreach($customers as $c) {
        if($c['customer_id'] == $customer_id) {
            $customer_name_prefill = $c['company_name'] ? $c['company_name'] . ' (' . $c['customer_name'] . ')' : $c['customer_name'];
            break;
        }
    }
    if(empty($customer_name_prefill)){
        // fetch if not in result
        $cStmt = $conn->prepare("SELECT customer_name FROM customers_listing WHERE customer_id = ?");
        $cStmt->bind_param("i", $customer_id);
        $cStmt->execute();
        $cStmt->bind_result($cn);
        if($cStmt->fetch()) $customer_name_prefill = $cn;
        $cStmt->close();
    }
}
?>
