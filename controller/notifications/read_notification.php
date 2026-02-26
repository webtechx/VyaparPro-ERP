<?php
require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../../config/auth_guard.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: $basePath/");
    exit;
}

$userId = $_SESSION['user_id'];
$notifId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($notifId > 0) {
    // 1. Mark as read
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notifId, $userId);
    $stmt->execute();
    $stmt->close();
    
    // 2. Fetch URL to redirect to
    $redirectUrl = "$basePath/notifications";
    $stmt = $conn->prepare("SELECT url FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notifId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['url']) && $row['url'] !== '#') {
            $dbUrl = $row['url'];
            if (strpos($dbUrl, '/purchase_orders/view/') !== false) {
                $dbUrl = str_replace('/purchase_orders/view/', '/view_purchase_order?id=', $dbUrl);
            }
            $redirectUrl = rtrim($basePath, '/') . '/' . ltrim($dbUrl, '/');
        }
    }
    $stmt->close();
    
    header("Location: " . $redirectUrl);
    exit;
}

header("Location: $basePath/notifications");
exit;
