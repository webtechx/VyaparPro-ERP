<?php
include __DIR__ . '/../../../config/auth_guard.php';

if (isset($_POST['update_item'])) {

    $item_id = (int) $_POST['item_id'];
    $item_name = trim($_POST['item_name']);
    $brand = trim($_POST['brand']);
    $opening_stock = floatval($_POST['opening_stock']);
    $sku = trim($_POST['stock_keeping_unit']);
    $unit_id = (int) $_POST['unit_id'];
    $hsn_id = !empty($_POST['hsn_id']) ? (int)$_POST['hsn_id'] : NULL;
    $mrp = floatval($_POST['mrp']);
    $selling_price = floatval($_POST['selling_price']);
    $description = trim($_POST['description']);

    if (empty($item_id) || empty($item_name) || empty($selling_price)) {
        header("Location: ../../../items?error=Required fields missing");
        exit;
    }

    $organization_id = $_SESSION['organization_id'];

    // Check if duplicate Name or SKU exists for this org (excluding self)
    $checkSql = "SELECT item_id FROM items_listing WHERE organization_id = ? AND item_id != ? AND (item_name = ? OR (stock_keeping_unit != '' AND stock_keeping_unit = ?))";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("iiss", $organization_id, $item_id, $item_name, $sku);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if($checkStmt->num_rows > 0){
        $checkStmt->close();
        header("Location: ../../../items?error=Item Name or SKU already exists");
        exit;
    }
    $checkStmt->close();

    // 1. Fetch existing opening_stock to calculate difference
    $query = $conn->prepare("SELECT opening_stock FROM items_listing WHERE item_id = ?");
    $query->bind_param("i", $item_id);
    $query->execute();
    $res = $query->get_result();
    $old_opening_stock = 0;
    if ($row = $res->fetch_assoc()) {
        $old_opening_stock = floatval($row['opening_stock']);
    }
    $query->close();

    // Calculate difference
    $diff = $opening_stock - $old_opening_stock;

    // 2. Update with current_stock adjustment
    $stmt = $conn->prepare("UPDATE items_listing SET 
        item_name = ?, 
        brand = ?, 
        opening_stock = ?,
        current_stock = COALESCE(current_stock, 0) + ?,
        stock_keeping_unit = ?, 
        unit_id = ?, 
        hsn_id = ?,
        mrp = ?, 
        selling_price = ?, 
        description = ?, 
        update_at = NOW() 
        WHERE item_id = ?");

    // Bind: name(s), brand(s), open(d), diff(d), sku(s), unit(i), hsn(i), mrp(d), sp(d), desc(s), id(i)
    $stmt->bind_param("ssddsiiddsi", $item_name, $brand, $opening_stock, $diff, $sku, $unit_id, $hsn_id, $mrp, $selling_price, $description, $item_id);

    if ($stmt->execute()) {
        
        // Update Commissions: Delete old -> Insert new
        $delComm = $conn->prepare("DELETE FROM item_commissions WHERE item_id = ? AND organization_id = ?");
        $delComm->bind_param("ii", $item_id, $organization_id);
        $delComm->execute();
        $delComm->close();

        if (isset($_POST['commission']) && is_array($_POST['commission'])) {
            $commSql = "INSERT INTO item_commissions (item_id, customers_type_id, commission_percentage, organization_id) VALUES (?, ?, ?, ?)";
            $commStmt = $conn->prepare($commSql);
            
            foreach ($_POST['commission'] as $type_id => $percent) {
                // If the input is empty string, we might want to skip or treat as 0. 
                // Let's treat valid numbers >= 0 as valid entries.
                if($percent === '') continue; 
                
                $p = floatval($percent);
                if ($p >= 0) { 
                    $commStmt->bind_param("iidi", $item_id, $type_id, $p, $organization_id);
                    $commStmt->execute();
                }
            }
            $commStmt->close();
        }

        header("Location: ../../../items?success=Item updated successfully");
    } else {
        header("Location: ../../../items?error=Update failed: " . $stmt->error);
    }
    exit;
} else {
    header("Location: ../../../items");
    exit;
}
