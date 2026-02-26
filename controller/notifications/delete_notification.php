<?php
require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../../config/auth_guard.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: $basePath/");
    exit;
}

$userId = $_SESSION['user_id'];
$notifId = isset($_GET['id']) ? $_GET['id'] : '';

if ($notifId === 'all') {
    // Delete all read notifications (or all notifications, usually clearing read ones is sufficient)
    // Here we will clear all notifications to be safe, or just read. Let's delete ALL notifications for this user.
    $stmt = $conn->prepare("UPDATE notifications SET is_deleted = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
} elseif ((int)$notifId > 0) {
    $id = (int)$notifId;
    $stmt = $conn->prepare("UPDATE notifications SET is_deleted = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $userId);
    $stmt->execute();
    $stmt->close();
}

header("Location: $basePath/notifications");
exit;
