<?php
// auth/jwt_helper.php
require_once __DIR__ . '/../config/config.php';

// Safely load JWT library. On some live servers, a missing vendor file causes a fatal error (HTTP 500).
$jwtAutoloadPath = __DIR__ . '/../admin/vendor/autoload.php';
if (file_exists($jwtAutoloadPath)) {
    require_once $jwtAutoloadPath; // firebase/php-jwt
} else {
    error_log('JWT autoload file missing at: ' . $jwtAutoloadPath);
}
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function generateAccessToken($user) {
    $now = time();
    $payload = [
        'iss' => $_SERVER['HTTP_HOST'] ?? 'your-site',
        'iat' => $now,
        'exp' => $now + ACCESS_TOKEN_EXP_SECONDS,
        'sub' => $user['user_id'],
        'role' => $user['role'] ?? 'customer',
    ];
    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

function decodeAccessToken($token) {
    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        // return as array
        return json_decode(json_encode($decoded), true);
    } catch (Exception $e) {
        return null;
    }
}

function generateRefreshTokenString() {
    // long random string
    return bin2hex(random_bytes(64)); // 128 chars hex
}

function hashToken($token) {
    // store hashed token in DB, never store raw token
    return password_hash($token, PASSWORD_DEFAULT);
}

function verifyRefreshTokenHash($rawToken, $hash) {
    return password_verify($rawToken, $hash);
}

function setAuthCookies($accessToken, $refreshTokenRaw, $expiresAt) {
    // access token cookie
    setcookie('access_token', $accessToken, [
        'expires' => time() + ACCESS_TOKEN_EXP_SECONDS,
        'path' => COOKIE_PATH,
        'secure' => COOKIE_SECURE,
        'httponly' => COOKIE_HTTPONLY,
        'samesite' => COOKIE_SAMESITE
    ]);

    // refresh token cookie (raw token, but only in cookie; hashed copy stored server-side)
    setcookie('refresh_token', $refreshTokenRaw, [
        'expires' => $expiresAt,
        'path' => COOKIE_PATH,
        'secure' => COOKIE_SECURE,
        'httponly' => COOKIE_HTTPONLY,
        'samesite' => COOKIE_SAMESITE
    ]);
}

function clearAuthCookies() {
    // Cookies were set WITHOUT domain parameter, so clear them WITHOUT domain
    setcookie('access_token', '', [
        'expires' => time() - 3600,
        'path' => COOKIE_PATH,
        'secure' => COOKIE_SECURE,
        'httponly' => COOKIE_HTTPONLY,
        'samesite' => COOKIE_SAMESITE
    ]);
    setcookie('refresh_token', '', [
        'expires' => time() - 3600,
        'path' => COOKIE_PATH,
        'secure' => COOKIE_SECURE,
        'httponly' => COOKIE_HTTPONLY,
        'samesite' => COOKIE_SAMESITE
    ]);
}

