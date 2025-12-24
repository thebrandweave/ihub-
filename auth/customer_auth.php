<?php
// auth/customer_auth.php - Customer authentication helper
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/jwt_helper.php';

// Initialize variables
$customer_logged_in = false;
$customer_id = null;
$customer_name = null;
$customer_email = null;

// Helper function to verify refresh token is still active
function verifyActiveRefreshToken($pdo, $user_id, $refreshToken) {
    if (!$refreshToken || !$user_id) {
        return false;
    }
    try {
        $stmt = $pdo->prepare("SELECT * FROM refresh_tokens WHERE user_id = ? AND revoked = 0 AND expires_at > NOW()");
        $stmt->execute([$user_id]);
        $activeTokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($activeTokens as $tokenRow) {
            if (verifyRefreshTokenHash($refreshToken, $tokenRow['token_hash'])) {
                return true;
            }
        }
    } catch (PDOException $e) {
        error_log("Refresh token verification error: " . $e->getMessage());
    }
    return false;
}

// If logout parameter is present, force logout and don't check anything
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_unset();
    clearAuthCookies();
    unset($_COOKIE['access_token']);
    unset($_COOKIE['refresh_token']);
    // Don't process any authentication - user is logged out
    $customer_logged_in = false;
    $customer_id = null;
    $customer_name = null;
    $customer_email = null;
} else {
    // Check if customer is logged in via session
    // IMPORTANT: Also verify refresh token is still active to prevent login after logout
    if (isset($_SESSION['customer_id']) && isset($_SESSION['customer_role']) && $_SESSION['customer_role'] === 'customer') {
        // Verify that refresh token is still active (not revoked during logout)
        $refreshToken = $_COOKIE['refresh_token'] ?? null;
        $user_id = $_SESSION['customer_id'];
        
        // If we have a refresh token, verify it's still active
        // If no refresh token or it's revoked, clear session (user logged out)
        if ($refreshToken) {
            if (verifyActiveRefreshToken($pdo, $user_id, $refreshToken)) {
                $customer_logged_in = true;
                $customer_id = $_SESSION['customer_id'];
                $customer_name = $_SESSION['customer_name'] ?? null;
                $customer_email = $_SESSION['customer_email'] ?? null;
            } else {
                // Refresh token was revoked - user logged out, clear session
                $_SESSION = [];
                session_unset();
                clearAuthCookies();
                unset($_COOKIE['access_token']);
                unset($_COOKIE['refresh_token']);
            }
        } else {
            // No refresh token but session exists - likely logged out, clear session
            $_SESSION = [];
            session_unset();
            clearAuthCookies();
            unset($_COOKIE['access_token']);
            unset($_COOKIE['refresh_token']);
        }
    } else {
        // Check JWT token from cookie (like check_auth.php does for admin)
        $accessToken = $_COOKIE['access_token'] ?? null;
        $refreshToken = $_COOKIE['refresh_token'] ?? null;
        
        // MUST have refresh token to auto-login (prevents auto-login after logout)
        if (!$refreshToken) {
            // No refresh token = logged out, clear everything
            clearAuthCookies();
            unset($_COOKIE['access_token']);
            unset($_COOKIE['refresh_token']);
        } else if ($accessToken) {
            $data = decodeAccessToken($accessToken);
            if ($data && isset($data['sub']) && isset($data['role']) && $data['role'] === 'customer') {
                // Find matching refresh token (must be active and not revoked)
                $stmt = $pdo->prepare("SELECT * FROM refresh_tokens WHERE revoked = 0 AND expires_at > NOW()");
                $stmt->execute();
                $found = null;
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if (verifyRefreshTokenHash($refreshToken, $row['token_hash'])) {
                        $found = $row;
                        break;
                    }
                }
                
                // Only auto-login if we found an active refresh token
                if ($found && $found['user_id'] == $data['sub']) {
                    // Fetch customer details
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'customer' LIMIT 1");
                    $stmt->execute([$data['sub']]);
                    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($customer) {
                        $customer_logged_in = true;
                        $customer_id = $customer['user_id'];
                        $customer_name = $customer['full_name'];
                        $customer_email = $customer['email'];
                        
                        // Set session
                        $_SESSION['customer_id'] = $customer_id;
                        $_SESSION['customer_name'] = $customer_name;
                        $_SESSION['customer_email'] = $customer_email;
                        $_SESSION['customer_role'] = 'customer';
                    }
                } else {
                    // Refresh token was revoked or doesn't match - clear everything (logged out)
                    clearAuthCookies();
                    unset($_COOKIE['access_token']);
                    unset($_COOKIE['refresh_token']);
                    if (isset($_SESSION['customer_id'])) {
                        unset($_SESSION['customer_id']);
                        unset($_SESSION['customer_name']);
                        unset($_SESSION['customer_email']);
                        unset($_SESSION['customer_role']);
                    }
                }
            } else {
                // Invalid or non-customer token - clear cookies
                clearAuthCookies();
                unset($_COOKIE['access_token']);
                unset($_COOKIE['refresh_token']);
            }
        }
    }
} // End else block - only check auth if NOT logout request

// Function to get cart count
function getCartCount($pdo, $customer_id) {
    if (!$customer_id) return 0;
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$customer_id]);
    return (int)($stmt->fetchColumn() ?? 0);
}

// Function to get wishlist count
function getWishlistCount($pdo, $customer_id) {
    if (!$customer_id) return 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmt->execute([$customer_id]);
    return (int)($stmt->fetchColumn() ?? 0);
}

$cart_count = $customer_logged_in ? getCartCount($pdo, $customer_id) : 0;
$wishlist_count = $customer_logged_in ? getWishlistCount($pdo, $customer_id) : 0;

