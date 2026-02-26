<?php
require_once '../../../config/auth_guard.php';

if (isset($_POST['add_item'])) {
    $organization_id = $_SESSION['organization_id'];

    $item_name = trim($_POST['item_name']);
    $brand = trim($_POST['brand']);
    $opening_stock = floatval($_POST['opening_stock']);
    $sku = trim($_POST['stock_keeping_unit']);
    $unit_id = (int) $_POST['unit_id'];
    $hsn_id = !empty($_POST['hsn_id']) ? (int)$_POST['hsn_id'] : NULL;
    $mrp = floatval($_POST['mrp']);
    $selling_price = floatval($_POST['selling_price']);
    $description = trim($_POST['description']);

    if (empty($item_name) || empty($selling_price)) {
        header("Location: ../../../items?error=Item Name and Selling Price are required");
        exit;
    }

    // Check if duplicate Name or SKU exists for this org
    $checkSql = "SELECT item_id FROM items_listing WHERE organization_id = ? AND (item_name = ? OR (stock_keeping_unit != '' AND stock_keeping_unit = ?))";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("iss", $organization_id, $item_name, $sku);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if($checkStmt->num_rows > 0){
        $checkStmt->close();
        header("Location: ../../../items?error=Item Name or SKU already exists");
        exit;
    }
    $checkStmt->close();

    $stmt = $conn->prepare("INSERT INTO items_listing 
        (organization_id, item_name, brand, opening_stock, current_stock, stock_keeping_unit, unit_id, hsn_id, mrp, selling_price, description, create_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    // Bind: i(org) s(name) s(brand) d(open) d(curr) s(sku) i(unit) i(hsn) d(mrp) d(sp) s(desc)
    $stmt->bind_param("issddsiidds", $organization_id, $item_name, $brand, $opening_stock, $opening_stock, $sku, $unit_id, $hsn_id, $mrp, $selling_price, $description);

    if ($stmt->execute()) {
        $new_item_id = $conn->insert_id;

        // Fetch all customer types for this organization
        $typesSql = "SELECT customers_type_id FROM customers_type_listing WHERE organization_id = ?";
        $typesStmt = $conn->prepare($typesSql);
        $typesStmt->bind_param("i", $organization_id);
        $typesStmt->execute();
        $typesResult = $typesStmt->get_result();
        
        $commSql = "INSERT INTO item_commissions (item_id, customers_type_id, commission_percentage, organization_id) VALUES (?, ?, ?, ?)";
        $commStmt = $conn->prepare($commSql);
        
        // Insert commission for each customer type (default 0 if not provided)
        while ($typeRow = $typesResult->fetch_assoc()) {
            $type_id = $typeRow['customers_type_id'];
            $percent = 0.00; // Default value
            
            // Override with user-provided value if exists
            if (isset($_POST['commission'][$type_id]) && $_POST['commission'][$type_id] !== '') {
                $percent = floatval($_POST['commission'][$type_id]);
            }
            
            $commStmt->bind_param("iidi", $new_item_id, $type_id, $percent, $organization_id);
            $commStmt->execute();
        }
        
        $commStmt->close();
        $typesStmt->close();

        header("Location: ../../../items?success=Item added successfully");
    } else {
        header("Location: ../../../items?error=Something went wrong: " . $stmt->error);
    }
    exit;
} else {
    header("Location: ../../../items");
    exit;
}
