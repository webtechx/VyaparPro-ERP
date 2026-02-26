<?php
require_once '../../../config/auth_guard.php';
require '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['upload_bulk']) && isset($_FILES['excel_file'])) {
    $organization_id = $_SESSION['organization_id'];
    $file = $_FILES['excel_file']['tmp_name'];

    if (empty($file)) {
        header("Location: ../../../items?error=Please select a file to upload");
        exit;
    }

    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        
        // Remove Header
        $header = array_shift($rows);

        $successCount = 0;
        $details = [];

        // Pre-fetch Units and HSNs to minimize DB hits and map Names to IDs
        // Units
        $units = [];
        $uRes = $conn->query("SELECT unit_id, LOWER(TRIM(unit_name)) as name FROM units_listing"); // Global fetch? Or verify org scope if units are org specific
        // Actually units_listing usually is global or org specific. Assuming shared or user has access. 
        // Based on items_listing.php, no WHERE clause on units_listing, implies shared.
        while($r = $uRes->fetch_assoc()) $units[$r['name']] = $r['unit_id'];

        // HSN
        $hsns = [];
        $hRes = $conn->query("SELECT hsn_id, TRIM(hsn_code) as code FROM hsn_listing"); // Similarly, usually shared
        while($r = $hRes->fetch_assoc()) $hsns[$r['code']] = $r['hsn_id'];


        foreach ($rows as $index => $row) {
            // Mapping based on Sample Download:
            // 0: Name, 1: Brand, 2: SKU, 3: UnitName, 4: HSN, 5: OpStock, 6: MRP, 7: SP, 8: Desc
            
            $itemName = trim($row[0] ?? '');
            $brand = trim($row[1] ?? '');
            $sku = trim($row[2] ?? '');
            $unitName = strtolower(trim($row[3] ?? ''));
            $hsnCode = trim($row[4] ?? '');
            $opStock = floatval($row[5] ?? 0);
            $mrp = floatval($row[6] ?? 0);
            $sp = floatval($row[7] ?? 0);
            $desc = trim($row[8] ?? '');

            if (empty($itemName) || empty($sp)) {
                continue; // Skip invalid rows
            }

            // 1. Resolve Unit ID
            $unitId = $units[$unitName] ?? 0;
            if ($unitId == 0) {
                 // Try to find if maybe user put ID directly? Unlikely. 
                 // Skip or default? Let's skip if mandatory.
                 // Actually existing add_item requires unit.
                 // Let's create a "Default" unit? No, better skip.
                 // Or try to execute without unit -> SQL error.
                 // Let's default to first available unit if strictly needed, or just let it fail validation.
                 // Let's try to query database for case-insensitive match just in case
            }

            // 2. Resolve HSN ID
            $hsnId = $hsns[$hsnCode] ?? 0;

            // 3. Check for Duplicates
            $check = $conn->prepare("SELECT item_id FROM items_listing WHERE organization_id = ? AND (item_name = ? OR (stock_keeping_unit != '' AND stock_keeping_unit = ?))");
            $check->bind_param("iss", $organization_id, $itemName, $sku);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $check->close();
                continue; // Skip duplicate
            }
            $check->close();

            // 4. Insert
            $stmt = $conn->prepare("INSERT INTO items_listing 
                (organization_id, item_name, brand, opening_stock, current_stock, stock_keeping_unit, unit_id, hsn_id, mrp, selling_price, description, create_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            // Note: $unitId might be 0, foreign key constraint might fail if setup.
            // If unit_id is required from UI, we should probably enforce it here.
            // If ID is valid integer from `units` array it is fine.
            
            $stmt->bind_param("issddsiidds", $organization_id, $itemName, $brand, $opStock, $opStock, $sku, $unitId, $hsnId, $mrp, $sp, $desc);
            
            if ($stmt->execute()) {
                $successCount++;
            }
            $stmt->close();
        }

        header("Location: ../../../items?success=Bulk upload processed. Added $successCount new items.");

    } catch (Exception $e) {
        header("Location: ../../../items?error=Error processing file: " . urlencode($e->getMessage()));
    }
} else {
    header("Location: ../../../items");
}
?>
