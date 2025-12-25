<?php
// auth/customer_login.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/jwt_helper.php';
require_once __DIR__ . '/customer_auth.php';

// If already logged in, redirect to home or return URL
if (!empty($customer_logged_in)) {
    $returnUrl = $_GET['return'] ?? $BASE_URL . 'index.php';
    header("Location: " . $returnUrl);
    exit;
}

$error = '';
$success = '';
$returnUrl = $_GET['return'] ?? $_POST['return'] ?? $BASE_URL . 'index.php';

// Sanitize return URL to prevent open redirect
$returnUrl = filter_var($returnUrl, FILTER_SANITIZE_URL);
if (!str_starts_with($returnUrl, $BASE_URL) && !str_starts_with($returnUrl, '/')) {
    $returnUrl = $BASE_URL . 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_start(); // Prevent any output before JSON for AJAX
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Email and password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'customer' LIMIT 1");
        $stmt->execute([$email]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer && password_verify($password, $customer['password_hash'])) {
            try {
                $accessToken = generateAccessToken($customer);
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
                    echo json_encode(['success' => true, 'message' => 'Login successful', 'redirect' => $returnUrl]);
                    exit;
                }
                // Redirect to return URL or home
                header("Location: " . $returnUrl);
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
    
    // If not AJAX POST, continue to show form with error
    if (isset($_POST['ajax'])) {
        ob_clean(); // Clear any output
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $error ?: 'Login failed']);
        exit;
    }
}

// GET request - Show login form
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$SITE_URL = $scheme . "://" . $host . $BASE_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - iHub Electronics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --brand-primary: #e3000e;
            --brand-primary-dark: #b0000b;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 450px;
            width: 100%;
        }
        .login-header {
            background: var(--brand-primary);
            color: white;
            padding: 30px;
            border-radius: 12px 12px 0 0;
            text-align: center;
        }
        .login-body {
            padding: 40px;
        }
        .btn-primary {
            background-color: var(--brand-primary);
            border-color: var(--brand-primary);
        }
        .btn-primary:hover {
            background-color: var(--brand-primary-dark);
            border-color: var(--brand-primary-dark);
        }
        .form-control:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 0.2rem rgba(227, 0, 14, 0.25);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h3 class="mb-0 fw-bold"><i class="bi bi-box-arrow-in-right me-2"></i>Customer Login</h3>
            <p class="mb-0 mt-2 small opacity-90">Sign in to your account</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="return" value="<?= htmlspecialchars($returnUrl) ?>">
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Enter your email" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter your password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>

            <div class="text-center">
                <p class="mb-2 small text-muted">Don't have an account?</p>
                <a href="<?= $BASE_URL ?>auth/signup.php" class="text-decoration-none text-primary fw-semibold">
                    <i class="bi bi-person-plus me-1"></i>Create Account
                </a>
            </div>

            <hr class="my-4">

            <div class="text-center">
                <a href="<?= $BASE_URL ?>" class="text-decoration-none text-muted small">
                    <i class="bi bi-arrow-left me-1"></i>Back to Home
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

