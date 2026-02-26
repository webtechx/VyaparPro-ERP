<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// --- Filters ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');
$customers_type_id = isset($_GET['customers_type_id']) ? intval($_GET['customers_type_id']) : 0;
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

$sql = "SELECT 
            c.customer_id,
            c.customer_name,
            c.company_name,
            c.customer_code,
            c.phone,
            c.email,
            c.avatar,
            ct.customers_type_name,
            c.loyalty_point_balance as global_balance,
            SUM(CASE WHEN t.transaction_type = 'EARN' THEN t.points ELSE 0 END) as total_earned,
            SUM(CASE WHEN t.transaction_type = 'REDEEM' THEN t.points ELSE 0 END) as total_redeemed,
            SUM(CASE WHEN t.transaction_type = 'EXPIRED' THEN t.points ELSE 0 END) as total_expired,
            COUNT(DISTINCT CASE WHEN t.transaction_type = 'EARN' THEN t.invoice_id END) as invoice_count
        FROM customers_listing c
        LEFT JOIN customers_type_listing ct ON c.customers_type_id = ct.customers_type_id
        LEFT JOIN loyalty_point_transactions t ON c.customer_id = t.customer_id 
            AND t.organization_id = ? 
            AND DATE(t.created_at) BETWEEN ? AND ?
        WHERE c.organization_id = ?";

$params = [$organization_id, $start_date, $end_date, $organization_id];
$types = "issi";

if ($customers_type_id > 0) {
    $sql .= " AND c.customers_type_id = ?";
    $params[] = $customers_type_id;
    $types .= "i";
}
if ($customer_id > 0) {
    $sql .= " AND c.customer_id = ?";
    $params[] = $customer_id;
    $types .= "i";
}

$sql .= " GROUP BY c.customer_id
          HAVING (total_earned > 0 OR total_redeemed > 0 OR total_expired > 0 OR global_balance > 0)
          ORDER BY c.loyalty_point_balance DESC, c.customer_name ASC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $reportData = [];
    $totalEarnedAll = 0;
    $totalRedeemedAll = 0;
    $totalExpiredAll = 0;
    $totalBalanceAll = 0;

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $reportData[] = $row;
            $totalEarnedAll += $row['total_earned'];
            $totalRedeemedAll += $row['total_redeemed'];
            $totalExpiredAll += $row['total_expired'];
            $totalBalanceAll += $row['global_balance'];
        }
    }
    $stmt->close();
} else {
    $reportData = [];
    $totalEarnedAll = 0;
    $totalRedeemedAll = 0;
    $totalExpiredAll = 0;
    $totalBalanceAll = 0;
}

// Fetch Types for Filter (All Types)
$typesList = [];
$tRes = $conn->query("SELECT customers_type_id, customers_type_name FROM customers_type_listing ORDER BY customers_type_name ASC");
if($tRes) {
    while($tRow = $tRes->fetch_assoc()) $typesList[] = $tRow;
}

// Prefill Contact Name if filtered
$customer_name_prefill = '';
$customer_avatar_prefill = '';
if ($customer_id > 0) {
    // Simple fetch
    $cStmt = $conn->prepare("SELECT customer_name, company_name, avatar FROM customers_listing WHERE customer_id = ?");
    $cStmt->bind_param("i", $customer_id);
    $cStmt->execute();
    $cStmt->bind_result($cName, $compName, $cAvatar);
    if ($cStmt->fetch()) {
        $customer_name_prefill = $compName ? "$compName ($cName)" : $cName;
        $customer_avatar_prefill = $cAvatar;
    }
    $cStmt->close();
}
?>
