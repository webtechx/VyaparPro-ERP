<?php
require_once '../../config/auth_guard.php';

header('Content-Type: application/json');

$q = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';
$org_id = $_SESSION['organization_id'];

$sql = "SELECT i.item_id as id, i.item_name
        FROM items_listing i
        WHERE i.organization_id = '$org_id' 
        AND i.item_name LIKE '%$q%'
        ORDER BY i.item_name ASC 
        LIMIT 20";

if ($result = $conn->query($sql)) {
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Text format suitable for Select2
        $row['text'] = $row['item_name'];
        $data[] = $row;
    }
    echo json_encode($data);
} else {
    echo json_encode(['error' => $conn->error]);
}
?>
