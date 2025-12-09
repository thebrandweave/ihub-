<?php
// ======================
// DATABASE CONNECTION
// ======================
$host  = "localhost";
$user  = "root";
$pass  = "";
$dbname = "ihub_electronics";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}


// ======================
// JWT SETTINGS
// ======================
// IMPORTANT: Use environment variables in production
const JWT_SECRET = 'replace_with_a_very_long_random_secret_string_!@#123';
const ACCESS_TOKEN_EXP_SECONDS = 900;           // 15 minutes
const REFRESH_TOKEN_EXP_SECONDS = 60 * 60 * 24 * 30;  // 30 days


// ======================
// COOKIE SETTINGS
// ======================
define('COOKIE_SECURE', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
const COOKIE_HTTPONLY = true;
const COOKIE_SAMESITE = 'Lax';
const COOKIE_PATH = '/';


// ======================
// ✅ AUTO BASE URL (BULLETPROOF METHOD)
// ======================

// Get the script path (e.g., /ihub/shop/index.php or /ihub/index.php)
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

// Extract directory segments
$pathParts = array_filter(explode('/', $scriptPath)); // Remove empty elements
$pathParts = array_values($pathParts); // Re-index array

// Skip common web server directories (htdocs, www, wwwroot)
$skipDirs = ['htdocs', 'www', 'wwwroot', 'public_html', 'html'];

// Find the first valid project directory
$projectName = '';
foreach ($pathParts as $part) {
    if (!in_array(strtolower($part), $skipDirs)) {
        $projectName = $part;
        break;
    }
}

// Build BASE_URL
if (!empty($projectName)) {
    $BASE_URL = '/' . $projectName . '/';
} else {
    // Fallback: if in root or can't determine, use root
    $BASE_URL = '/';
}

// Ensure BASE_URL always ends with /
if (substr($BASE_URL, -1) !== '/') {
    $BASE_URL .= '/';
}

// Also create $asset_path for consistency (same as BASE_URL)
// This matches the pattern from the example code
$asset_path = $BASE_URL;

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
