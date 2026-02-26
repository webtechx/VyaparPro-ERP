<?php
// Debugging
ini_set('display_errors', 0); // Disable display errors to prevent invalid JSON
error_reporting(E_ALL);

$debug = true;
function log_debug($msg) {
    global $debug;
    if($debug) file_put_contents(__DIR__ . '/debug_search.log', date('Y-m-d H:i:s') . ": " . $msg . "\n", FILE_APPEND);
}

log_debug("Script started. QUERY: " . ($_GET['q'] ?? 'NONE'));

$connPath = __DIR__ . '/../../../config/conn.php';
if (!file_exists($connPath)) {
    log_debug("Error: conn.php not found at $connPath");
    echo json_encode(['error' => 'Database configuration file not found']);
    exit;
}

require_once $connPath;
log_debug("Database connected.");

header('Content-Type: application/json');

try {
    $q = $_GET['q'] ?? '';
    
    // Check if table column 'avatar' exists - straightforward query for now
    $sql = "SELECT vendor_id as id, display_name, email, company_name, avatar 
            FROM vendors_listing 
            WHERE (status='Active' OR status='active') 
            AND (display_name LIKE ? OR email LIKE ? OR company_name LIKE ?) 
            LIMIT 10";

    $stmt = $conn->prepare($sql);
    if(!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $param = "%$q%";
    $stmt->bind_param("sss", $param, $param, $param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $vendors = [];
    while($row = $result->fetch_assoc()){
        $vendors[] = $row;
    }
    
    log_debug("Found " . count($vendors) . " vendors.");
    echo json_encode($vendors);

} catch (Exception $e) {
    log_debug("Exception: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>
