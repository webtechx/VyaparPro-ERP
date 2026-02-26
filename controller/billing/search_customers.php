<?php
require_once __DIR__ . '/../../config/conn.php';

header('Content-Type: application/json');

$q = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

$sql = "SELECT customer_id as id, customer_name as text, company_name, email, gst_number, avatar FROM customers_listing WHERE customer_name LIKE '%$q%' OR company_name LIKE '%$q%' LIMIT 20";

if ($result = $conn->query($sql)) {
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['display_name'] = $row['company_name'] ? $row['company_name'] . ' (' . $row['text'] . ')' : $row['text'];
        $data[] = $row;
    }
    echo json_encode($data);
} else {
    // If table doesn't exist, return empty or dummy
    echo json_encode([]);
}
?>
