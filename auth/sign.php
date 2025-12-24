<?php
// auth/signup.php
require_once __DIR__ . '/../config/config.php';

$message = "";

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'] ?? 'customer';

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        // Updated to use a class for styling, but keeping inline style as fallback
        $message = "<div class='alert error'>All fields are required.</div>";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $message = "<div class='alert error'>Email already exists!</div>";
        } else {
            // Insert new user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $hashed, $role]);

            // Log user signup activity
            $newUserId = $pdo->lastInsertId();
            if ($newUserId) {
                $logStmt = $pdo->prepare("
                    INSERT INTO user_activity_logs (user_id, action)
                    VALUES (?, ?)
                ");
                $logStmt->execute([
                    $newUserId,
                    'User signup as ' . $role
                ]);
            }

            $message = "<div class='alert success'>✅ Account created successfully! <br>You can now <a href='login.php'>login</a>.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up - iHUB</title>
  <style>
    /* UI Design matching login.php */
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background: #f3f4f6; /* Light gray background */
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .signup-box {
      background: white;
      padding: 40px 30px;
      border-radius: 1rem; /* rounded-xl */
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* shadow-xl */
      width: 400px; /* Slightly wider than login to fit content comfortably */
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

    /* Style both inputs and select boxes identically */
    input, select {
      width: 100%; /* Changed to 100% with box-sizing for better fit */
      box-sizing: border-box; 
      padding: 12px;
      margin-bottom: 20px;
      border: 1px solid #d1d5db; /* border-gray-300 */
      border-radius: 0.5rem; /* rounded-lg */
      font-size: 15px;
      box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* shadow-sm */
      transition: border-color 0.2s, box-shadow 0.2s;
      background-color: white;
    }

    input:focus, select:focus {
      outline: none;
      border-color: #ef4444; /* red-500 */
      box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.3); /* ring-red-300 */
    }

    button {
      background-image: linear-gradient(to right, #ef4444, #ec4899); /* from-red-500 to-pink-600 */
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 0.5rem; /* rounded-lg */
      cursor: pointer;
      width: 100%;
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

    /* Message Styles */
    .alert {
      padding: 12px;
      margin-bottom: 20px;
      border-radius: 0.5rem;
      font-size: 14px;
      font-weight: 500;
      text-align: left;
    }

    .alert.error {
      color: #dc2626; /* red-600 */
      background: #fef2f2; /* red-50 */
      border: 1px solid #fca5a5; /* red-300 */
    }

    .alert.success {
      color: #059669; /* green-600 */
      background: #ecfdf5; /* green-50 */
      border: 1px solid #6ee7b7; /* green-300 */
    }

    .alert a {
        color: inherit;
        text-decoration: underline;
        font-weight: bold;
    }

    .footer-text {
      font-size: 14px;
      margin-top: 20px;
      color: #6b7280; /* text-gray-500 */
    }

    .footer-text a {
      color: #ef4444; /* red-500 */
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s;
    }

    .footer-text a:hover {
      color: #dc2626; /* red-600 */
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="signup-box">
    <div class="login-header">
        <div class="logo-badge">i</div>
        <h2>Create Account</h2>
    </div>
    
    <?= $message ?>
    
    <form method="POST">
      <input type="text" name="name" placeholder="Full Name" required>
      <input type="email" name="email" placeholder="Email Address" required>
      <input type="password" name="password" placeholder="Password" required>
      
      <select name="role">
        <option value="customer">Customer</option>
        <option value="admin">Admin</option>
      </select>

      <button type="submit">Sign Up</button>
    </form>

    <p class="footer-text">Already have an account? <a href="login.php">Login</a></p>
  </div>
</body>
</html>