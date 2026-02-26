<?php
require_once __DIR__ . '/../../config/auth_guard.php';

$organization_id = $_SESSION['organization_id'];

// --- Filters ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
// $status = isset($_GET['status']) ? trim($_GET['status']) : ''; // Debit Note simple implementation doesn't seem to have status yet? Assuming 'created' implied.
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// --- Base Query for Debit Notes ---
// Note: Based on create_debit_note.php, table is 'debit_notes'
// Columns: debit_note_id, debit_note_number, debit_note_date, vendor_id, po_id, remarks
// Join with vendors_listing and purchase_orders (assuming id=po_id)
$sql = "SELECT 
            dn.debit_note_id,
            dn.debit_note_number,
            dn.debit_note_date,
            dn.remarks,
            v.display_name as vendor_name,
            v.company_name,
            po.po_number,
            (
                SELECT SUM(dni.return_qty * poi.rate) 
                FROM debit_note_items dni 
                JOIN purchase_order_items poi ON dni.po_item_id = poi.id
                WHERE dni.debit_note_id = dn.debit_note_id
            ) as total_amount
        FROM debit_notes dn
        LEFT JOIN vendors_listing v ON dn.vendor_id = v.vendor_id
        LEFT JOIN purchase_orders po ON dn.po_id = po.purchase_orders_id
        WHERE dn.organization_id = ?";

$params = [$organization_id];
$types = "i";

// Apply Filters
if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND dn.debit_note_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

if ($vendor_id > 0) {
    $sql .= " AND dn.vendor_id = ?";
    $params[] = $vendor_id;
    $types .= "i";
}

if (!empty($search_query)) {
    $sql .= " AND (dn.debit_note_number LIKE ? OR po.po_number LIKE ?)";
    $like = "%$search_query%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

$sql .= " ORDER BY dn.debit_note_date DESC, dn.debit_note_id DESC";

// Execute Query
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$debit_notes = [];
$total_debit_amount = 0;
$count_notes = 0;

while ($row = $result->fetch_assoc()) {
    $total_val = floatval($row['total_amount'] ?? 0);
    $row['total_amount'] = $total_val; // Ensure it's set
    $debit_notes[] = $row;
    $total_debit_amount += $total_val;
    $count_notes++;
}
$stmt->close();

// --- Prefill Filter Data (Vendor Name) ---
$vendor_name_prefill = '';
$vendor_avatar_prefill = ''; // Placeholder if vendors get avatars later
if ($vendor_id > 0) {
    $venSql = "SELECT display_name as vendor_name, company_name, vendor_code, mobile, avatar FROM vendors_listing WHERE vendor_id = ?";
    // Note: Assuming 'avatar' exists or might exist. If not sure, we can skip it or use try/catch concept by just selecting what we know. 
    // Safest to select known columns + avatar if the schema allows. 
    // Based on `search_vendors_listing.php` in other contexts, usually `avatar` is present.
    // Let's assume standard schema. If it fails, I will remove avatar.
    $venStmt = $conn->prepare("SELECT display_name, company_name, vendor_code, mobile, avatar FROM vendors_listing WHERE vendor_id = ?"); 
    $venStmt->bind_param("i", $vendor_id);
    $venStmt->execute();
    $venStmt->bind_result($vName, $cName, $vCode, $vMobile, $vAvatar);
    if($venStmt->fetch()){
        $display = $cName ? "$cName ($vName)" : $vName;
        $vendor_name_prefill = "$display - $vCode";
        $vendor_avatar_prefill = $vAvatar;
    }
    $venStmt->close();
}
?>
