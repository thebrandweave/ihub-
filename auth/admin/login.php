<?php
// auth/admin/login.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../jwt_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password_hash'])) {
        try {
            $accessToken = generateAccessToken($admin);
            $refreshRaw = generateRefreshTokenString();
            $refreshHash = hashToken($refreshRaw);
            $exp = date('Y-m-d H:i:s', time() + REFRESH_TOKEN_EXP_SECONDS);

            $pdo->prepare("INSERT INTO refresh_tokens (user_id, token_hash, user_agent, ip_address, expires_at)
                         VALUES (?, ?, ?, ?, ?)")
                ->execute([
                    $admin['user_id'],
                    $refreshHash,
                    $_SERVER['HTTP_USER_AGENT'] ?? '',
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $exp
                ]);

            setAuthCookies($accessToken, $refreshRaw, time() + REFRESH_TOKEN_EXP_SECONDS);
            $_SESSION['admin_id'] = $admin['user_id'];

            // Log admin login activity
            $logStmt = $pdo->prepare("
                INSERT INTO user_activity_logs (user_id, action)
                VALUES (?, ?)
            ");
            $logStmt->execute([
                $admin['user_id'],
                'Admin login from IP ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
            ]);

            header("Location: ../../admin/index.php");
            exit;
        } catch (PDOException $e) {
            error_log('Admin login refresh token error: ' . $e->getMessage());
            $error = "Login failed due to a server issue. Please try again later.";
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login - iHUB</title>

  <link rel="icon" type="image/png" sizes="32x32" href="<?= $BASE_URL ?>favicon.png">
  <link rel="shortcut icon" href="<?= $BASE_URL ?>favicon.png">
  <style>
    /* The following CSS is designed to mimic the Tailwind classes 
    used in your admin dashboard (e.g., rounded-xl, shadow-lg, red gradients).
    */
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background: #f3f4f6; /* Light gray background */
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .login-box {
      background: white;
      padding: 40px 30px;
      border-radius: 1rem; /* rounded-xl */
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* shadow-xl */
      width: 380px;
      text-align: center;
      border: 1px solid #e5e7eb; /* border-gray-200 */
      transition: all 0.3s ease;
    }
    
    .login-header {
        margin-bottom: 25px;
    }
    
    .logo-badge {
        width: 50px;
        height: 50px;
        background-image: linear-gradient(to bottom right, #ef4444, #ec4899); /* from-red-500 to-pink-600 */
        border-radius: 0.5rem; /* rounded-lg */
        margin: 0 auto 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        font-weight: 700;
        box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.5); /* red shadow */
    }

    h2 {
      color: #1f2937; /* text-gray-800 */
      font-size: 24px;
      font-weight: 700;
      margin: 0;
    }

    input {
      width: 90%;
      padding: 12px;
      margin-bottom: 20px;
      border: 1px solid #d1d5db; /* border-gray-300 */
      border-radius: 0.5rem; /* rounded-lg */
      font-size: 15px;
      box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* shadow-sm */
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    input:focus {
      outline: none;
      border-color: #ef4444; /* red-500 */
      box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.3); /* ring-red-300 */
    }

    button {
      /* Matching the dashboard's primary gradient button */
      background-image: linear-gradient(to right, #ef4444, #ec4899); /* from-red-500 to-pink-600 */
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 0.5rem; /* rounded-lg */
      cursor: pointer;
      width: 95%;
      font-size: 16px;
      font-weight: 600;
      transition: all 0.3s;
      box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.5); /* shadow-md with red color */
    }

    button:hover {
      opacity: 0.9;
      transform: translateY(-1px);
      box-shadow: 0 6px 10px -1px rgba(239, 68, 68, 0.6);
    }

    .error {
      color: #dc2626; /* red-600 */
      background: #fef2f2; /* red-50 */
      border: 1px solid #fca5a5; /* red-300 */
      padding: 10px;
      margin-bottom: 20px;
      border-radius: 0.5rem; /* rounded-lg */
      font-size: 14px;
      font-weight: 500;
    }

    a {
      color: #ef4444; /* red-500 */
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s;
    }

    a:hover {
      color: #dc2626; /* red-600 */
      text-decoration: underline;
    }

    p {
      font-size: 14px;
      margin-top: 20px;
      color: #6b7280; /* text-gray-500 */
    }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="login-header">
        <div class="logo-badge">i</div>
        <h2>Admin Login</h2>
    </div>
    
    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="email" name="email" placeholder="Email Address" required><br>
      <input type="password" name="password" placeholder="Password" required><br>
      <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="../signup.php">Sign Up</a></p>
  </div>
</body>
</html>


