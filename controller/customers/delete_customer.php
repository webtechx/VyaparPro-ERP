<?php
require_once '../../config/conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = intval($_POST['customer_id'] ?? 0);

    if ($customer_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Customer ID']);
        exit;
    }

    // Strict Backend Check: Dependencies
    $checkInvoice = $conn->query("SELECT COUNT(invoice_id) as count FROM sales_invoices WHERE customer_id = $customer_id");
    if($checkInvoice){
        $invCount = $checkInvoice->fetch_assoc()['count'];
        if($invCount > 0){
             echo json_encode(['success' => false, 'message' => 'Cannot delete customer: they have associated invoices.']);
             exit;
        }
    }

    try {
        // 1. Fetch and unlink avatar
        $checkStmt = $conn->prepare("SELECT avatar FROM customers_listing WHERE customer_id = ?");
        $checkStmt->bind_param("i", $customer_id);
        if ($checkStmt->execute()) {
            $res = $checkStmt->get_result();
            if ($row = $res->fetch_assoc()) {
                if (!empty($row['avatar']) && file_exists('../../' . $row['avatar'])) {
                    unlink('../../' . $row['avatar']);
                }
            }
        }
        $checkStmt->close();

        $stmt = $conn->prepare("DELETE FROM customers_listing WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);

        if ($stmt->execute()) {
             if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Customer deleted successfully']);
             } else {
                echo json_encode(['success' => false, 'message' => 'Customer not found']);
             }
        } else {
             // Likely foreign key constraint if invoices exist
            throw new Exception($stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete customer. It might be used in invoices. Error: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
