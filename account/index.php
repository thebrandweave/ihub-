<?php
require_once __DIR__ . '/../auth/customer_auth.php';
require_once __DIR__ . '/../config/config.php';

// Ensure customer is logged in
if (empty($customer_logged_in) || empty($customer_id)) {
    header("Location: ../auth/customer_login.php");
    exit;
}

$user_id = $customer_id;

$userStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

$ordersStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$ordersStmt->execute([$user_id]);
$totalOrders = $ordersStmt->fetchColumn();

$wishStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
$wishStmt->execute([$user_id]);
$wishlistCount = $wishStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Account — iHub Electronics</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
  body{
    font-family:Arial, sans-serif;
    color:#0f172a;
    background:#ffffff;
  }

  .profile-hero{
    background:#f1f5f9;
    padding:56px 0;
    text-align:left;
    border-bottom:1px solid #e5e7eb;
  }

  .profile-hero h1{
    font-size:1.9rem;
    margin-bottom:0.25rem;
  }

  .profile-card{
    background:white;
    padding:24px;
    border-radius:8px;
    border:1px solid #e5e7eb;
  }

  .profile-icon{
    font-size:55px;
    color:#e3000e;
  }

  /* Account layout */
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

<!-- HERO / PAGE TITLE -->
<section class="profile-hero">
  <div class="container">
    <h1 class="fw-bold">Your Account</h1>
    <p class="text-secondary mb-0">Manage your profile, orders, wishlist and addresses in one place.</p>
  </div>
</section>

<!-- ACCOUNT LAYOUT -->
<section class="py-4">
  <div class="container">
    <div class="row g-4">

      <!-- SIDEBAR -->
      <aside class="col-lg-3">
        <div class="account-sidebar">
          <div class="account-sidebar-title">Your shortcuts</div>
          <ul class="account-nav">
            <li><a href="index.php" class="active"><i class="bi bi-person-circle"></i>Account overview</a></li>
            <li><a href="orders.php"><i class="bi bi-box-seam"></i>Your orders</a></li>
            <li><a href="addresses.php"><i class="bi bi-geo-alt"></i>Your addresses</a></li>
            <li><a href="../wishlist/wishlist_action.php" onclick="event.preventDefault(); document.querySelector('[data-bs-target=#wishlistDrawer]')?.click();"><i class="bi bi-heart"></i>Your wishlist</a></li>
            <li><a href="change_password.php"><i class="bi bi-shield-lock"></i>Login &amp; security</a></li>
          </ul>
        </div>
      </aside>

      <!-- MAIN CONTENT -->
      <div class="col-lg-9">
        <div class="row g-4">

          <div class="col-md-6">
            <div class="profile-card h-100 text-center text-md-start">
              <div class="d-flex align-items-center gap-3 mb-3">
                <i class="bi bi-person-circle profile-icon d-none d-md-block"></i>
                <div>
                  <h4 class="fw-bold mb-1"><?= htmlspecialchars($user['full_name']) ?></h4>
                  <p class="text-secondary mb-0"><?= htmlspecialchars($user['email']) ?></p>
                </div>
              </div>
              <p class="mb-1"><i class="bi bi-phone me-2"></i> <?= $user['phone'] ?: 'Phone not added' ?></p>
              <p class="mb-3"><i class="bi bi-geo-alt me-2"></i> <?= $user['address'] ?: 'No default address saved' ?></p>
              <a href="update_profile.php" class="btn btn-outline-primary w-100">
                Manage profile
              </a>
            </div>
          </div>

          <div class="col-md-6">
            <div class="profile-card h-100">
              <h5 class="fw-bold mb-3">Your activity</h5>
              <div class="d-flex justify-content-between mb-2">
                <span>Orders placed</span>
                <span class="fw-bold"><?= $totalOrders ?></span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span>Items in wishlist</span>
                <span class="fw-bold"><?= $wishlistCount ?></span>
              </div>
              <hr>
              <a href="orders.php" class="btn btn-primary w-100 mb-2">
                View your orders
              </a>
              <a href="addresses.php" class="btn btn-outline-primary w-100">
                Manage addresses
              </a>
            </div>
          </div>

          <div class="col-md-12">
            <div class="profile-card">
              <h5 class="fw-bold mb-2">Login &amp; security</h5>
              <p class="text-secondary mb-3">Update your password to keep your iHub account secure.</p>
              <a href="change_password.php" class="btn btn-outline-dark">
                Change your password
              </a>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</section>

<?php include __DIR__ . "/../components/newsletter.php"; ?>
<?php include __DIR__ . "/../components/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


</body>
</html>
