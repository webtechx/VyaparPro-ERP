<?php
include __DIR__ . '/../../config/auth_guard.php';

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_SESSION['organization_id'])) {
             throw new Exception("Session Organization ID not set");
        }
        $organization_id = $_SESSION['organization_id'];

        $action = $_POST['action'] ?? 'add';
        $customers_type_id = intval($_POST['customers_type_id'] ?? 0);
        $customers_type_name = trim($_POST['customers_type_name'] ?? '');


        if (empty($customers_type_name)) {
            echo json_encode(['success' => false, 'message' => 'Type Name is required']);
            exit;
        }

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO customers_type_listing (organization_id, customers_type_name) VALUES (?, ?)");
            $stmt->bind_param("is", $organization_id, $customers_type_name);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Customer Type added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database Error: ' . $stmt->error]);
            }
            $stmt->close();
        } elseif ($action === 'update') {
            if ($customers_type_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid Type ID']);
                exit;
            }

            $stmt = $conn->prepare("UPDATE customers_type_listing SET customers_type_name=? WHERE customers_type_id=? AND organization_id=?");
            $stmt->bind_param("sii", $customers_type_name, $customers_type_id, $organization_id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Customer Type updated successfully']);
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
