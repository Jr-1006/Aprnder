<?php
require_once __DIR__ . '/../../src/bootstrap.php';
header('Content-Type: application/json');
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$uid = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Return all notifications (with is_read status)
    $limit = isset($_GET['count']) ? min((int)$_GET['count'], 100) : 50;
    $stmt = db()->prepare('SELECT id, message, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
    $stmt->execute([$uid, $limit]);
    $notifications = $stmt->fetchAll();
    
    // Get unread count
    $countStmt = db()->prepare('SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0');
    $countStmt->execute([$uid]);
    $unreadCount = $countStmt->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'items' => $notifications,
        'unread_count' => (int)$unreadCount
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mark all as read
    $stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$uid]);
    echo json_encode(['ok' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
    // Mark individual notification as read
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['notification_id']) || !is_numeric($input['notification_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid notification_id']);
        exit;
    }
    
    $notifId = (int)$input['notification_id'];
    
    // Mark as read (only if it belongs to the current user)
    $stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([$notifId, $uid]);
    
    // Get updated unread count
    $countStmt = db()->prepare('SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0');
    $countStmt->execute([$uid]);
    $unreadCount = $countStmt->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'unread_count' => (int)$unreadCount
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);


