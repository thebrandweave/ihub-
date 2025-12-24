<?php
// ======================
// SESSION START
// ======================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
// ✅ AUTO BASE URL (BULLETPROOF METHOD)
// ======================
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$pathParts = array_filter(explode('/', $scriptPath)); 
$pathParts = array_values($pathParts); 

$skipDirs = ['htdocs', 'www', 'wwwroot', 'public_html', 'html'];
$projectName = '';
foreach ($pathParts as $part) {
    if (!in_array(strtolower($part), $skipDirs)) {
        $projectName = $part;
        break;
    }
}

$BASE_URL = !empty($projectName) ? '/' . $projectName . '/' : '/';
if (substr($BASE_URL, -1) !== '/') $BASE_URL .= '/';
$asset_path = $BASE_URL;

// ======================
// JWT SETTINGS
// ======================
const JWT_SECRET = 'replace_with_a_very_long_random_secret_string_!@#123';
const ACCESS_TOKEN_EXP_SECONDS = 900;
const REFRESH_TOKEN_EXP_SECONDS = 60 * 60 * 24 * 30;

// ======================
// COOKIE SETTINGS
// ======================
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