<?php
// auth/customer_login.php
// Prevent any output before JSON
ob_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/jwt_helper.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Email and password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'customer' LIMIT 1");
        $stmt->execute([$email]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer && password_verify($password, $customer['password_hash'])) {
            // Try to generate JWT; if library missing, fall back to pure PHP session login (no tokens)
            $accessToken = generateAccessToken($customer);

            if ($accessToken === null) {
                // Fallback: no JWT / refresh token, but still allow session-based login
                error_log('Customer login: JWT unavailable, using session-only login.');
                $_SESSION['customer_id'] = $customer['user_id'];
                $_SESSION['customer_name'] = $customer['full_name'];
                $_SESSION['customer_email'] = $customer['email'];
                $_SESSION['customer_role'] = 'customer';

                if (isset($_POST['ajax'])) {
                    ob_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Login successful (session only)']);
                    exit;
                }
                header("Location: " . $BASE_URL . "index.php");
                exit;
            }

            try {
                $refreshRaw = generateRefreshTokenString();
                $refreshHash = hashToken($refreshRaw);
                $exp = date('Y-m-d H:i:s', time() + REFRESH_TOKEN_EXP_SECONDS);

                $pdo->prepare("INSERT INTO refresh_tokens (user_id, token_hash, user_agent, ip_address, expires_at)
                               VALUES (?, ?, ?, ?, ?)")
                    ->execute([
                        $customer['user_id'],
                        $refreshHash,
                        $_SERVER['HTTP_USER_AGENT'] ?? '',
                        $_SERVER['REMOTE_ADDR'] ?? '',
                        $exp
                    ]);

                setAuthCookies($accessToken, $refreshRaw, time() + REFRESH_TOKEN_EXP_SECONDS);
                $_SESSION['customer_id'] = $customer['user_id'];
                $_SESSION['customer_name'] = $customer['full_name'];
                $_SESSION['customer_email'] = $customer['email'];
                $_SESSION['customer_role'] = 'customer';

                $success = "Login successful!";
                // Redirect back to index or return JSON for AJAX
                if (isset($_POST['ajax'])) {
                    ob_clean(); // Clear any output
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Login successful']);
                    exit;
                }
                header("Location: " . $BASE_URL . "index.php");
                exit;
            } catch (PDOException $e) {
                // Most likely refresh_tokens table missing or DB mismatch
                error_log('Customer login refresh token error: ' . $e->getMessage());
                $error = "Login failed due to a server issue. Please try again later.";
                if (isset($_POST['ajax'])) {
                    ob_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $error]);
                    exit;
                }
            }
        } else {
            $error = "Invalid email or password.";
            if (isset($_POST['ajax'])) {
                ob_clean(); // Clear any output
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
        }
    }
}

// If not AJAX, return error message
if (isset($_POST['ajax'])) {
    ob_clean(); // Clear any output
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $error ?: 'Login failed']);
    exit;
}

