<?php
// ======================
// SESSION START
// ======================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ======================
// ENVIRONMENT DETECTION
// ======================
// Detect if running on localhost or a live server
$is_local = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);

if ($is_local) {
    // --- LOCAL SETTINGS ---
    define('DB_HOST', 'localhost');
    define('DB_PORT', 3306);
    define('DB_NAME', 'ihub_electronics');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    // --- LIVE SETTINGS ---
    define('DB_HOST', 'localhost');
    define('DB_PORT', 3306);
    define('DB_NAME', 'u232955123_ihubmobiles');
    define('DB_USER', 'u232955123_ihubmobiles');
    define('DB_PASS', 'Ihubmobiles@2025');
}

// ======================
// DATABASE CONNECTION
// ======================
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    // On live, you might want to log this instead of 'die' for better security
    die("Database Connection Failed: " . $e->getMessage());
}

// ======================
// ✅ BASE URL
// ======================
// On LOCAL (localhost) → base is the project root folder (e.g. /ihub/)
// On LIVE domain       → base is the domain root (/) by default
if ($is_local) {
    // e.g. /ihub/shop/product_details.php → ['ihub','shop','product_details.php'] → 'ihub'
    $scriptParts = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
    $projectFolder = $scriptParts[0] ?? '';
    $BASE_URL = '/' . ($projectFolder !== '' ? $projectFolder . '/' : '');
} else {
    // Adjust here if your live site is in a subfolder
    $BASE_URL = '/';
}

$asset_path = $BASE_URL;

// ======================
// JWT & COOKIE SETTINGS
// ======================
const JWT_SECRET = 'replace_with_a_very_long_random_secret_string_!@#123';
const ACCESS_TOKEN_EXP_SECONDS = 900;
const REFRESH_TOKEN_EXP_SECONDS = 2592000; // 30 days

define('COOKIE_SECURE', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
const COOKIE_HTTPONLY = true;
const COOKIE_SAMESITE = 'Lax';
const COOKIE_PATH = '/';

// ======================
// HELPERS
// ======================
function getProjectRoot() {
    return realpath(dirname(__DIR__));
}

function includeComponent($component) {
    $path = getProjectRoot() . "/components/$component.php";
    if (file_exists($path)) {
        include $path;
    } else {
        error_log("Component not found: " . $path);
    }
}