<?php
// Check if a target already exists for a given Month and Year
require_once '../../config/conn.php';

header('Content-Type: application/json');

// Only check if POST params exist
if (isset($_POST['month']) && isset($_POST['year'])) {
    $month = $_POST['month'];
    $year = (int)$_POST['year'];
    $exclude_id = isset($_POST['exclude_id']) ? (int)$_POST['exclude_id'] : 0;

    $sql = "SELECT id FROM monthly_targets WHERE month = ? AND year = ?";
    
    // Add exclusion if editing
    if ($exclude_id > 0) {
        $sql .= " AND id != ?";
    }

    $stmt = $conn->prepare($sql);
    
    if ($exclude_id > 0) {
        $stmt->bind_param("sii", $month, $year, $exclude_id);
    } else {
        $stmt->bind_param("si", $month, $year);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
         echo json_encode(['exists' => true]);
    } else {
         echo json_encode(['exists' => false]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['exists' => false, 'error' => 'Missing parameters']);
}
?>
