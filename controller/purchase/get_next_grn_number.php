<?php
session_start();
require_once '../../config/conn.php';

header('Content-Type: application/json');

$org_id = $_SESSION['organization_id'] ?? 0;
$org_short_code = $_SESSION['organization_short_code'] ?? 'GRN';

// Default Next Number
$next_grn_number = 'GRN-' . $org_short_code . '-0001';

// Check last DB entry for this Org with specific pattern
$prefix = 'GRN-' . $org_short_code . '-';
$sql = "SELECT grn_number FROM goods_received_notes WHERE organization_id = ? AND grn_number LIKE CONCAT(?, '%') ORDER BY grn_id DESC LIMIT 1";

$stmt = $conn->prepare($sql);
if($stmt){
    $stmt->bind_param("is", $org_id, $prefix);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if($res->num_rows > 0){
        $last_grn = $res->fetch_assoc()['grn_number'];
        
        // Regex to extract number from format: GRN-CODE-XXXX
        // We use preg_quote to safe escape the short code
        if (preg_match('/GRN-' . preg_quote($org_short_code, '/') . '-(\d+)/', $last_grn, $matches)) {
            $next_num = intval($matches[1]) + 1;
            $next_grn_number = 'GRN-' . $org_short_code . '-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
        }
    }
    $stmt->close();
}

echo json_encode(['next_grn_number' => $next_grn_number]);
?>
