<?php
require_once '../../config/conn.php';

header('Content-Type: application/json');

if (isset($_GET['po_number'])) {
    $po_number = $conn->real_escape_string($_GET['po_number']);
    $response = [];

    // Header
    $sql = "SELECT po.*, v.display_name as vendor_name, v.vendor_id 
            FROM purchase_orders po
            LEFT JOIN vendors_listing v ON po.vendor_id = v.vendor_id
            WHERE po.po_number = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $po_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $po = $result->fetch_assoc();
        
        // Status checks
        if ($po['status'] === 'received') {
            $response['status'] = 'error';
            $response['message'] = "This Purchase Order is already fully received.";
        } elseif ($po['status'] === 'draft' || $po['status'] === 'sent' || $po['status'] === 'cancelled') {
             $response['status'] = 'error';
             $response['message'] = "Purchase Order is " . $po['status'] . ". It must be Confirmed or Partially Received.";
        } else {
             // Status is 'confirmed' or 'partially_received' - Allow
             $response['po'] = $po;
             $po_id = $po['purchase_orders_id'];

            // Items
            // We join with items_listing and units_listing to get unit names
            // Items
            // We join with items_listing and units_listing to get unit names
            $itemSql = "SELECT poi.*, il.item_name, u.unit_name, h.hsn_code
                        FROM purchase_order_items poi
                        LEFT JOIN items_listing il ON poi.item_id = il.item_id
                        LEFT JOIN units_listing u ON il.unit_id = u.unit_id
                        LEFT JOIN hsn_listing h ON il.hsn_id = h.hsn_id
                        WHERE poi.purchase_order_id = ?";
                        
            $itemStmt = $conn->prepare($itemSql);
            $itemStmt->bind_param("i", $po_id);
            $itemStmt->execute();
            $itemRes = $itemStmt->get_result();
            $items = [];
            while($row = $itemRes->fetch_assoc()){
                // Calculate remaining qty
                $pk = $row['purchase_order_items_id'] ?? $row['id'] ?? 0;
                $ordered = floatval($row['quantity']);
                
                // Get total received for this item from previous GRNs
                // Note: We need to check if table exists first or wrap in try-catch if optional. 
                // Assuming it exists as per creation logic.
                $sumSql = "SELECT SUM(received_qty) as total FROM goods_received_note_items WHERE po_item_id = ?";
                $sumStmt = $conn->prepare($sumSql);
                $received_so_far = 0;
                if($sumStmt){
                    $sumStmt->bind_param("i", $pk);
                    $sumStmt->execute();
                    $sumResult = $sumStmt->get_result();
                    $sumRow = $sumResult->fetch_assoc();
                    $received_so_far = floatval($sumRow['total'] ?? 0);
                    $sumStmt->close();
                } 

                // Get total returned for this item from Debit Notes
                $retSql = "SELECT SUM(return_qty) as total FROM debit_note_items WHERE po_item_id = ?";
                $retStmt = $conn->prepare($retSql);
                $returned_so_far = 0;
                if($retStmt){
                    $retStmt->bind_param("i", $pk);
                    $retStmt->execute();
                    $retResult = $retStmt->get_result();
                    $retRow = $retResult->fetch_assoc();
                    $returned_so_far = floatval($retRow['total'] ?? 0);
                    $retStmt->close();
                }
                
                // Net Received = Received - Returned
                // If we returned items, we effectively 'un-received' them in terms of fulfilling the order (expecting replacements)
                $already_received_net = max(0, $received_so_far - $returned_so_far);

                $row['remaining_qty'] = max(0, $ordered - $already_received_net);
                $row['already_received'] = $already_received_net;
                $row['total_received_lifetime'] = $received_so_far;
                $row['total_returned_lifetime'] = $returned_so_far;

                // Only include if there is something left to receive OR if we want to show completed lines too?
                // Usually we show all, but disable/indicate separate logic.
                // User asked to "block only due".
                
                $row['hsn_code'] = $row['hsn_code'] ?? '-';
                $items[] = $row;
            }
            $response['items'] = $items;
            $response['status'] = 'success';
        }

    } else {
        $response['status'] = 'error';
        $response['message'] = "Purchase Order not found.";
    }

    echo json_encode($response);
} else {
    echo json_encode(['status' => 'error', 'message' => 'PO Number is required']);
}
?>
