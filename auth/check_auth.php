<?php
// auth/check_auth.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/jwt_helper.php';
// session_start();

// helper to redirect to login
function redirectToLogin() {
    global $BASE_URL;
    // Use BASE_URL from config if available, otherwise calculate it
    if (isset($BASE_URL)) {
        $loginUrl = rtrim($BASE_URL, '/') . '/auth/admin/login.php';
    } else {
        // Fallback: Get base path from script name - find the project root
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        // Remove /admin/ or /auth/ and everything after to get base path
        if (preg_match('#^(/.+?)/(admin|auth)/#', $script, $matches)) {
            $basePath = $matches[1];
        } else {
            // Fallback: go up from current script location
            $basePath = dirname(dirname($script));
            $basePath = rtrim($basePath, '/');
        }
        if (empty($basePath) || $basePath === '.') {
            $basePath = '';
        }
        $loginUrl = $basePath . '/auth/admin/login.php';
    }
    header("Location: " . $loginUrl);
    exit;
}

// Check if there's an existing session but no valid tokens - clear it and redirect
$hasSession = isset($_SESSION['admin_id']);
$accessToken = $_COOKIE['access_token'] ?? null;
$refreshToken = $_COOKIE['refresh_token'] ?? null;

// If there's a session but no tokens at all, clear session and redirect
if ($hasSession && !$accessToken && !$refreshToken) {
    session_unset();
    session_destroy();
    redirectToLogin();
}

// get token from cookie
if ($accessToken) {
    $data = decodeAccessToken($accessToken);
    if ($data && isset($data['sub'])) {
        $_SESSION['admin_id'] = $data['sub'];
        $_SESSION['admin_role'] = $data['role'] ?? null;

        // ensure it's an admin
        if ($_SESSION['admin_role'] !== 'admin') {
            http_response_code(403);
            exit("Forbidden: Not authorized as admin");
        }
        return;
    }
}

// Try refreshing token if access token expired
if (!$refreshToken) redirectToLogin();

// Find matching refresh token
$stmt = $pdo->prepare("SELECT * FROM refresh_tokens WHERE revoked = 0 AND expires_at > NOW()");
$stmt->execute();
$found = null;
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (verifyRefreshTokenHash($refreshToken, $row['token_hash'])) {
        $found = $row;
        break;
    }
}
if (!$found) {
    clearAuthCookies();
    redirectToLogin();
}

// Fetch user
$userStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'admin'");
$userStmt->execute([$found['user_id']]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    clearAuthCookies();
    redirectToLogin();
}

// Rotate tokens
$newAccess = generateAccessToken($user);
$newRefreshRaw = generateRefreshTokenString();
$newRefreshHash = hashToken($newRefreshRaw);
$newExp = date('Y-m-d H:i:s', time() + REFRESH_TOKEN_EXP_SECONDS);

$pdo->prepare("UPDATE refresh_tokens SET revoked = 1 WHERE id = ?")->execute([$found['id']]);
$pdo->prepare("INSERT INTO refresh_tokens (user_id, token_hash, user_agent, ip_address, expires_at)
               VALUES (?, ?, ?, ?, ?)")
    ->execute([$user['user_id'], $newRefreshHash, $_SERVER['HTTP_USER_AGENT'] ?? '', $_SERVER['REMOTE_ADDR'] ?? '', $newExp]);

setAuthCookies($newAccess, $newRefreshRaw, time() + REFRESH_TOKEN_EXP_SECONDS);

$_SESSION['admin_id'] = $user['user_id'];
$_SESSION['admin_role'] = $user['role'];

// Final check: ensure admin session is set, otherwise redirect to login
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    redirectToLogin();
}
