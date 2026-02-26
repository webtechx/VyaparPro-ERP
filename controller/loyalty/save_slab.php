<?php
include __DIR__ . '/../../config/auth_guard.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $organization_id = $_SESSION['organization_id'];
    $action = $_POST['action'] ?? 'add';
    $slab_id = intval($_POST['slab_id'] ?? 0);
    
    $slab_no = $_POST['slab_no'] ?? '';
    $from_sale_amount = floatval($_POST['from_sale_amount'] ?? 0);
    $to_sale_amount = floatval($_POST['to_sale_amount'] ?? 0);
    $points_per_100_rupees = floatval($_POST['points_per_100_rupees'] ?? 0);
    $valid_for_days = !empty($_POST['valid_for_days']) ? intval($_POST['valid_for_days']) : NULL;
    $applicable_from_date = $_POST['applicable_from_date'] ?? date('Y-m-d');
    $applicable_to_date = !empty($_POST['applicable_to_date']) ? $_POST['applicable_to_date'] : NULL;

    // Validation
    /*
    if(empty($slab_no)) {
        echo json_encode(['success' => false, 'message' => 'Slab Name/No is required.']);
        exit;
    }
    */
    if ($to_sale_amount <= $from_sale_amount) {
        echo json_encode(['success' => false, 'message' => 'To Amount must be greater than From Amount.']);
        exit;
    }

    if ($action === 'add') {
        // Fetch Org Short Code
        $orgSql = "SELECT organization_short_code FROM organizations WHERE organization_id = ?";
        $stmtOrg = $conn->prepare($orgSql);
        $stmtOrg->bind_param("i", $organization_id);
        $stmtOrg->execute();
        $resOrg = $stmtOrg->get_result();
        $organization_short_code = 'ORG'; 
        if($resOrg->num_rows > 0){
            $rowOrg = $resOrg->fetch_assoc();
            if(!empty($rowOrg['organization_short_code'])) {
                $organization_short_code = $rowOrg['organization_short_code'];
            }
        }
        $stmtOrg->close();

        // Auto Generate Slab No
        $slab_no = 'SLAB-' . $organization_short_code . '-001';
        $lastQ = $conn->query("SELECT slab_no FROM loyalty_point_slabs WHERE organization_id = $organization_id ORDER BY slab_id DESC LIMIT 1");
        if ($lastQ && $lastQ->num_rows > 0) {
            $lastRow = $lastQ->fetch_assoc();
            // Extract the simple numeric part at the end
            if (preg_match('/-(\d+)$/', $lastRow['slab_no'], $matches)) {
                $nextNum = intval($matches[1]) + 1;
                $slab_no = 'SLAB-' . $organization_short_code . '-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
            }
        }

        // Check for overlap? For simplicity, we skip complex range overlap calculation for now, 
        // but typically one should ensure ranges don't conflict.
        
        $stmt = $conn->prepare("INSERT INTO loyalty_point_slabs (organization_id, slab_no, from_sale_amount, to_sale_amount, points_per_100_rupees, valid_for_days, applicable_from_date, applicable_to_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("isdddiss", $organization_id, $slab_no, $from_sale_amount, $to_sale_amount, $points_per_100_rupees, $valid_for_days, $applicable_from_date, $applicable_to_date);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Loyalty Slab added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database Error: ' . $stmt->error]);
        }
        $stmt->close();

    } elseif ($action === 'update') {
        if ($slab_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid Slab ID']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE loyalty_point_slabs SET slab_no=?, from_sale_amount=?, to_sale_amount=?, points_per_100_rupees=?, valid_for_days=?, applicable_from_date=?, applicable_to_date=?, updated_at=NOW() WHERE slab_id=? AND organization_id=?");
        $stmt->bind_param("sdddissii", $slab_no, $from_sale_amount, $to_sale_amount, $points_per_100_rupees, $valid_for_days, $applicable_from_date, $applicable_to_date, $slab_id, $organization_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Loyalty Slab updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database Error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
