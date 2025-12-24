<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../jwt_helper.php';

$userId = $_SESSION['admin_id'] ?? null;

$refreshRaw = $_COOKIE['refresh_token'] ?? null;
if ($refreshRaw) {
    $stmt = $pdo->prepare("SELECT * FROM refresh_tokens WHERE revoked = 0");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (verifyRefreshTokenHash($refreshRaw, $row['token_hash'])) {
            $pdo->prepare("UPDATE refresh_tokens SET revoked = 1 WHERE id = ?")->execute([$row['id']]);
            break;
        }
    }
}

clearAuthCookies();

// Log logout activity if we know the admin
if ($userId) {
    $logStmt = $pdo->prepare("
        INSERT INTO user_activity_logs (user_id, action)
        VALUES (?, ?)
    ");
    $logStmt->execute([
        $userId,
        'Admin logout'
    ]);
}

session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page (same directory)
header("Location: login.php?msg=Logged out");
exit;


