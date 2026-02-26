<?php
ob_start();
require_once '../../config/auth_guard.php';
require_once '../../config/conn.php';

// Set headers for Excel download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Debit_Notes_Export_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Get Search Query
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$org_id = $_SESSION['organization_id'];

// Base SQL
$sql = "SELECT 
            dn.debit_note_number, 
            dn.debit_note_date, 
            v.display_name as vendor_name, 
            po.po_number,
            il.item_name, 
            u.unit_name,
            dni.return_qty, 
            dni.return_reason, 
            dni.remarks as item_remarks,
            poi.rate as po_rate
        FROM debit_notes dn
        JOIN debit_note_items dni ON dn.debit_note_id = dni.debit_note_id
        LEFT JOIN vendors_listing v ON dn.vendor_id = v.vendor_id
        LEFT JOIN purchase_orders po ON dn.po_id = po.purchase_orders_id
        LEFT JOIN items_listing il ON dni.item_id = il.item_id
        LEFT JOIN units_listing u ON il.unit_id = u.unit_id
        LEFT JOIN purchase_order_items poi ON dni.po_item_id = poi.id
        WHERE dn.organization_id = ?";

// Add Search Filter
$params = array();
$types = "";

if (!empty($search_query)) {
    $sql .= " AND (dn.debit_note_number LIKE ? OR po.po_number LIKE ? OR v.display_name LIKE ?)";
    $like = "%$search_query%";
    $params[] = &$org_id;   // 1
    $params[] = &$like;     // 2
    $params[] = &$like;     // 3
    $params[] = &$like;     // 4
    $types = "isss";        
} else {
    $params[] = &$org_id;
    $types = "i";
}

$sql .= " ORDER BY dn.debit_note_date DESC, dn.debit_note_id DESC";

$stmt = $conn->prepare($sql);
if(!$stmt) die("SQL Error: " . $conn->error);

// Need to bind params dynamically using call_user_func_array for bind_param
$bindParams = array(&$types);
for ($i = 0; $i < count($params); $i++) {
    $bindParams[] = &$params[$i];
}
call_user_func_array(array($stmt, 'bind_param'), $bindParams);
$stmt->execute();
$result = $stmt->get_result();

// Output HTML Table for Excel
?>
<table border="1">
    <thead>
        <tr style="background-color: #f2f2f2; font-weight: bold;">
            <th>DN Number</th>
            <th>Date</th>
            <th>Vendor</th>
            <th>PO Number</th>
            <th>Item Name</th>
            <th>Unit</th>
            <th>Rate</th>
            <th>Return Qty</th>
            <th>Return Value</th>
            <th>Reason</th>
            <th>Item Remarks</th>
        </tr>
    </thead>
    <tbody>
        <?php
ob_start();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rate = floatval($row['po_rate']);
                $qty = floatval($row['return_qty']);
                $val = $rate * $qty;
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['debit_note_number']) ?></td>
                    <td><?= date('d-M-Y', strtotime($row['debit_note_date'])) ?></td>
                    <td><?= htmlspecialchars($row['vendor_name']) ?></td>
                    <td><?= htmlspecialchars($row['po_number']) ?></td>
                    <td><?= htmlspecialchars($row['item_name']) ?></td>
                    <td><?= htmlspecialchars($row['unit_name'] ?? '') ?></td>
                    <td style="text-align: right;"><?= number_format($rate, 2) ?></td>
                    <td style="text-align: center;"><?= number_format($qty, 2) ?></td>
                    <td style="text-align: right;"><?= number_format($val, 2) ?></td>
                    <td><?= htmlspecialchars($row['return_reason']) ?></td>
                    <td><?= htmlspecialchars($row['item_remarks']) ?></td>
                </tr>
                <?php
ob_start();
            }
        } else {
            echo '<tr><td colspan="11" style="text-align: center;">No records found</td></tr>';
        }
        ?>
    </tbody>
</table>
<?php
ob_start();
exit;
