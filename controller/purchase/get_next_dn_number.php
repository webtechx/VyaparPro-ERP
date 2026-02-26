<?php
session_start();
require_once '../../config/conn.php';

header('Content-Type: application/json');

$org_id = $_SESSION['organization_id'] ?? 0;
$org_short_code = $_SESSION['organization_short_code'] ?? 'DN';

// Default Next Number
$next_dn_number = 'DN-' . $org_short_code . '-0001';

// Check last DB entry for this Org
$sql = "SELECT debit_note_number FROM debit_notes WHERE organization_id = ? ORDER BY debit_note_id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
if($stmt){
    $stmt->bind_param("i", $org_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if($res->num_rows > 0){
        $last_dn = $res->fetch_assoc()['debit_note_number'];
        
        // Regex to extract number from format: DN-CODE-XXXX
        // We use preg_quote to safe escape the short code
        if (preg_match('/DN-' . preg_quote($org_short_code, '/') . '-(\d+)/', $last_dn, $matches)) {
            $next_num = intval($matches[1]) + 1;
            $next_dn_number = 'DN-' . $org_short_code . '-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
        }
    }
    $stmt->close();
}

echo json_encode(['next_dn_number' => $next_dn_number]);
?>
