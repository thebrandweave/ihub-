<?php
require_once __DIR__ . "/../auth/check_auth.php";
require_once __DIR__ . "/../config/config.php";

header('Content-Type: application/json');

$adminId = $_SESSION['admin_id'] ?? null;
if (!$adminId) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
$stmt->execute([$adminId]);

echo json_encode(['success' => true]);


