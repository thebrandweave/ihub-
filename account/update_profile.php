<?php
require_once __DIR__ . '/../auth/customer_auth.php';
require_once __DIR__ . '/../config/config.php';

$user_id = $_SESSION['customer_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Save profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name  = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    $update = $pdo->prepare("
      UPDATE users 
      SET full_name = ?, phone = ?, address = ?
      WHERE user_id = ?
    ");

    $update->execute([$name, $phone, $address, $user_id]);

    header("Location: update_profile.php?success=1");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Update Profile — iHub Electronics</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{font-family:Arial,sans-serif;color:#0f172a;background:#ffffff;}
.profile-hero {background:#f1f5f9;padding:56px 0;text-align:left;border-bottom:1px solid #e5e7eb;}
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
    <h2 class="fw-bold">Your profile</h2>
    <p class="text-secondary mb-0">Update your name, phone number and default address.</p>
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
            <li><a href="../wishlist/wishlist_action.php" onclick="event.preventDefault(); document.querySelector('[data-bs-target=#wishlistDrawer]')?.click();"><i class="bi bi-heart"></i>Your wishlist</a></li>
            <li><a href="change_password.php"><i class="bi bi-shield-lock"></i>Login &amp; security</a></li>
          </ul>
        </div>
      </aside>

      <!-- MAIN CONTENT -->
      <div class="col-lg-9">
        <div class="card-box">

          <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success">Profile updated successfully ✅</div>
          <?php endif; ?>

          <h5 class="fw-bold mb-3">Edit your details</h5>

          <form method="POST">

            <div class="mb-3">
              <label class="form-label">Full name</label>
              <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Email (read only)</label>
              <input type="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control" disabled>
            </div>

            <div class="mb-3">
              <label class="form-label">Phone number</label>
              <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Default address</label>
              <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($user['address']) ?></textarea>
            </div>

            <button class="btn btn-primary w-100">Update profile</button>

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
