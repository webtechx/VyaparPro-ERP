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
            c.commissions_amount as global_balance,
            COALESCE(e.total_earned, 0) as total_earned,
            COALESCE(e.invoice_count, 0) as invoice_count,
            COALESCE(p.total_paid, 0) as total_paid
        FROM customers_listing c
        JOIN customers_type_listing ct ON c.customers_type_id = ct.customers_type_id
        LEFT JOIN (
            SELECT customer_id, SUM(commission_amount) as total_earned, COUNT(invoice_id) as invoice_count 
            FROM customers_commissions_ledger 
            WHERE organization_id = ? AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY customer_id
        ) e ON c.customer_id = e.customer_id
        LEFT JOIN (
            SELECT customer_id, SUM(amount) as total_paid
            FROM customers_commissions_payouts
            WHERE organization_id = ? AND payment_date BETWEEN ? AND ?
            GROUP BY customer_id
        ) p ON c.customer_id = p.customer_id
        WHERE c.organization_id = ? 
        AND ct.customers_type_name IN ('Architecture', 'Interior', 'Carpenter')";

$params = [$organization_id, $start_date, $end_date, $organization_id, $start_date, $end_date, $organization_id];
$types = "ississi";

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

// Ensure we only show ones taking part in activity or ones carrying balance
$sql .= " AND (e.total_earned > 0 OR p.total_paid > 0 OR c.commissions_amount > 0)";

$sql .= " ORDER BY c.commissions_amount DESC, c.customer_name ASC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $reportData = [];
    $totalEarnedAll = 0;
    $totalPaidAll = 0;
    $totalBalanceAll = 0;

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $reportData[] = $row;
            $totalEarnedAll += $row['total_earned'];
            $totalPaidAll += $row['total_paid'];
            $totalBalanceAll += $row['global_balance'];
        }
    }
    $stmt->close();
} else {
    $reportData = [];
    $totalEarnedAll = 0;
    $totalPaidAll = 0;
    $totalBalanceAll = 0;
}

// Fetch Types for Filter
$typesList = [];
$tRes = $conn->query("SELECT customers_type_id, customers_type_name FROM customers_type_listing WHERE customers_type_name IN ('Architecture', 'Interior', 'Carpenter') ORDER BY customers_type_name ASC");
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
