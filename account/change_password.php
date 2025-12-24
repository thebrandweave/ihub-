<?php
require_once __DIR__ . '/../auth/customer_auth.php';
require_once __DIR__ . '/../config/config.php';

// Ensure customer is logged in
if (empty($customer_logged_in) || empty($customer_id)) {
    header("Location: ../auth/customer_login.php");
    exit;
}

$user_id = $customer_id;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    // Fetch user password hash
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!password_verify($current, $user['password_hash'])) {
        $error = "Current password is incorrect";
    }
    elseif (strlen($new) < 6) {
        $error = "New password must be at least 6 characters";
    }
    elseif ($new !== $confirm) {
        $error = "Passwords do not match";
    }
    else {
        $hash = password_hash($new, PASSWORD_DEFAULT);

        $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $update->execute([$hash, $user_id]);

        $success = "Password updated successfully ✅";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Change Password — iHub Electronics</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{font-family:Arial,sans-serif;color:#0f172a;background:#ffffff;}
.profile-hero{background:#f1f5f9;padding:56px 0;text-align:left;border-bottom:1px solid #e5e7eb;}
.profile-hero h2{font-size:1.9rem;margin-bottom:0.25rem;}
.card-box {
  background:#fff;
  padding:25px;
  border-radius:8px;
  border:1px solid #e5e7eb;
}
.account-sidebar{
  background:#fff;
  border-radius:8px;
  border:1px solid #e5e7eb;
  padding:18px 0;
}
.account-sidebar-title{
  font-size:1rem;
  font-weight:700;
  padding:0 18px 8px;
  border-bottom:1px solid #e5e7eb;
}
.account-nav{
  list-style:none;
  padding:8px 0 0;
  margin:0;
}
.account-nav li a{
  display:flex;
  align-items:center;
  padding:8px 18px;
  font-size:0.9rem;
  color:#111827;
  text-decoration:none;
}
.account-nav li a:hover{
  background:#f3f4f6;
}
.account-nav li a.active{
  font-weight:600;
  background:#e5f1ff;
  border-left:3px solid #0d6efd;
}
.account-nav i{
  margin-right:8px;
  font-size:1rem;
}
</style>
</head>
<body>

<?php include __DIR__ . "/../components/navbar.php"; ?>

<section class="profile-hero">
  <div class="container">
    <h2 class="fw-bold">Login &amp; security</h2>
    <p class="text-secondary mb-0">Update your password to keep your account secure.</p>
  </div>
</section>

<section class="py-4">
  <div class="container">
    <div class="row g-4">

      <!-- SIDEBAR -->
      <aside class="col-lg-3">
        <div class="account-sidebar">
          <div class="account-sidebar-title">Your shortcuts</div>
          <ul class="account-nav">
            <li><a href="index.php"><i class="bi bi-person-circle"></i>Account overview</a></li>
            <li><a href="orders.php"><i class="bi bi-box-seam"></i>Your orders</a></li>
            <li><a href="addresses.php"><i class="bi bi-geo-alt"></i>Your addresses</a></li>
            <li><a href="change_password.php" class="active"><i class="bi bi-shield-lock"></i>Login &amp; security</a></li>
          </ul>
        </div>
      </aside>

      <!-- MAIN CONTENT -->
      <div class="col-lg-9">
        <div class="card-box">

          <?php if($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
          <?php endif; ?>

          <?php if($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
          <?php endif; ?>

          <h5 class="fw-bold mb-3">Change your password</h5>

          <form method="POST">
            <div class="mb-3">
              <label class="form-label">Current password</label>
              <input type="password" name="current_password" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">New password</label>
              <input type="password" name="new_password" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Confirm new password</label>
              <input type="password" name="confirm_password" class="form-control" required>
            </div>

            <button class="btn btn-primary w-100">Update password</button>
          </form>
        </div>
      </div>

    </div>
  </div>
</section>

<?php include __DIR__ . "/../components/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


</body>
</html>
