<?php
require_once __DIR__ . '/../../config/conn.php';
$result = $conn->query("DESCRIBE employees");
$out = "";
while($row = $result->fetch_assoc()) {
    $out .= $row['Field'] . "\n";
}
file_put_contents('cols.txt', $out);
?>
