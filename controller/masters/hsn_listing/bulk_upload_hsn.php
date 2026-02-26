<?php
require_once '../../../config/auth_guard.php';
require '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['upload_bulk']) && isset($_FILES['excel_file'])) {
    $organization_id = $_SESSION['organization_id'];
    $file = $_FILES['excel_file']['tmp_name'];

    if (empty($file)) {
        header("Location: ../../../hsn_list?error=Please select a file to upload");
        exit;
    }

    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        
        // Remove Header
        $header = array_shift($rows);

        $successCount = 0;

        foreach ($rows as $index => $row) {
            // Mapping based on Sample Download:
            // 0: HSN Code, 1: Description, 2: GST Rate
            
            $hsnCode = trim($row[0] ?? '');
            $description = trim($row[1] ?? '');
            $gstRate = trim($row[2] ?? '');

            if (empty($hsnCode) || $gstRate === '') {
                continue; // Skip invalid rows
            }
            
            $gstRateFormatted = floatval($gstRate);

            // Check for Duplicates
            $check = $conn->prepare("SELECT hsn_id FROM hsn_listing WHERE organization_id = ? AND hsn_code = ?");
            $check->bind_param("is", $organization_id, $hsnCode);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $check->close();
                continue; // Skip duplicate
            }
            $check->close();

            // Insert
            $stmt = $conn->prepare("INSERT INTO hsn_listing (organization_id, hsn_code, description, gst_rate, created_at) VALUES (?, ?, ?, ?, NOW())");
            
            $stmt->bind_param("issd", $organization_id, $hsnCode, $description, $gstRateFormatted);
            
            if ($stmt->execute()) {
                $successCount++;
            }
            $stmt->close();
        }

        header("Location: ../../../hsn_list?success=Bulk upload processed. Added $successCount new HSN records.");

    } catch (Exception $e) {
        header("Location: ../../../hsn_list?error=Error processing file: " . urlencode($e->getMessage()));
    }
} else {
    header("Location: ../../../hsn_list");
}
?>
