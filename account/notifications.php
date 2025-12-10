<?php
// Customer notifications endpoint (JSON)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/customer_auth.php';

header('Content-Type: application/json');

// Use the customer_id from customer_auth.php
$user_id = $customer_id ?? null;

if (!$user_id || !$customer_logged_in) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$action = $_POST['action'] ?? 'get';

switch ($action) {
    case 'count':
        try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$user_id]);
        $count = (int)($stmt->fetchColumn() ?? 0);
        echo json_encode(['success' => true, 'count' => $count]);
        } catch (PDOException $e) {
            error_log("Notification count error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Database error', 'count' => 0]);
        }
        exit;

    case 'mark_read':
        $notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
        if (!$notification_id) {
            echo json_encode(['success' => false, 'error' => 'Notification ID required']);
            exit;
        }
        try {
            $stmt = $pdo->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW()
                WHERE notification_id = ? AND user_id = ?
            ");
            $stmt->execute([$notification_id, $user_id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            error_log("Mark read error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        exit;

    case 'mark_all_read':
        try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW()
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            error_log("Mark all read error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        exit;

    case 'get':
    default:
        try {
        $stmt = $pdo->prepare("
                SELECT notification_id, type, title, message, image_url, target_url, is_read, created_at, read_at
            FROM notifications
            WHERE user_id = ?
                ORDER BY is_read ASC, created_at DESC
                LIMIT 50
        ");
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Get unread count
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $countStmt->execute([$user_id]);
            $unreadCount = (int)($countStmt->fetchColumn() ?? 0);

        echo json_encode([
            'success' => true,
            'items'   => $items,
                'unread_count' => $unreadCount
            ]);
        } catch (PDOException $e) {
            error_log("Get notifications error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Database error',
                'items' => []
        ]);
        }
        exit;
}



