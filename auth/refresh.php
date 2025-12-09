<?php
// auth/refresh.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/jwt_helper.php';

$refreshRaw = $_COOKIE['refresh_token'] ?? null;
if (!$refreshRaw) {
    http_response_code(401);
    echo json_encode(['error' => 'No refresh token']);
    exit;
}

// Find refresh token row similar to check_auth
$stmt = $pdo->prepare("SELECT * FROM refresh_tokens WHERE revoked = 0 AND expires_at > NOW()");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$found = null;
foreach ($rows as $r) {
    if (verifyRefreshTokenHash($refreshRaw, $r['token_hash'])) {
        $found = $r;
        break;
    }
}
if (!$found) {
    clearAuthCookies();
    http_response_code(401);
    echo json_encode(['error' => 'Invalid refresh token']);
    exit;
}

// load user
$userStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$userStmt->execute([$found['user_id']]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    clearAuthCookies();
    http_response_code(401);
    echo json_encode(['error' => 'User not found']);
    exit;
}

// rotate
$newAccess = generateAccessToken($user);
$newRefreshRaw = generateRefreshTokenString();
$newRefreshHash = hashToken($newRefreshRaw);
$newExpiresAt = date('Y-m-d H:i:s', time() + REFRESH_TOKEN_EXP_SECONDS);

$pdo->prepare("UPDATE refresh_tokens SET revoked = 1 WHERE id = ?")->execute([$found['id']]);
$pdo->prepare("INSERT INTO refresh_tokens (user_id, token_hash, user_agent, ip_address, expires_at) VALUES (?, ?, ?, ?, ?)")
    ->execute([$user['user_id'], $newRefreshHash, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null, $newExpiresAt]);

setAuthCookies($newAccess, $newRefreshRaw, time() + REFRESH_TOKEN_EXP_SECONDS);

echo json_encode(['success' => true]);
exit;

