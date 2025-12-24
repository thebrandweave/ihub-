<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/jwt_helper.php';

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
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to index page
header("Location: " . $BASE_URL . "index.php?msg=Logged out");
exit;

