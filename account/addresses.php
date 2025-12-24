<?php
require_once __DIR__ . '/../auth/customer_auth.php';
require_once __DIR__ . '/../config/config.php';

// Ensure customer is logged in
if (empty($customer_logged_in) || empty($customer_id)) {
    header("Location: ../auth/customer_login.php");
    exit;
}

$user_id = $customer_id;

// Fetch user's addresses
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<title>My Addresses — iHub Electronics</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{font-family:Arial, sans-serif; color:#0f172a;background:#ffffff;}
.profile-hero{
  background:#f1f5f9; padding:56px 0; text-align:left;border-bottom:1px solid #e5e7eb;
}
.profile-hero h1{font-size:1.9rem;margin-bottom:0.25rem;}
.profile-card{
  background:#fff; padding:24px; border-radius:8px;
  border:1px solid #e5e7eb;
}
.address-box{
  border:1px solid #e5e7eb;
  border-radius:10px;
  padding:14px 16px; margin-bottom:12px;
  background:#fff;
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

<!-- HERO -->
<section class="profile-hero">
  <div class="container">
    <h1 class="fw-bold">Your Addresses</h1>
    <p class="text-secondary mb-0">Manage your delivery locations for faster checkout.</p>
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
            <li><a href="addresses.php" class="active"><i class="bi bi-geo-alt"></i>Your addresses</a></li>
            <li><a href="../wishlist/wishlist_action.php" onclick="event.preventDefault(); document.querySelector('[data-bs-target=#wishlistDrawer]')?.click();"><i class="bi bi-heart"></i>Your wishlist</a></li>
            <li><a href="change_password.php"><i class="bi bi-shield-lock"></i>Login &amp; security</a></li>
          </ul>
        </div>
      </aside>

      <!-- MAIN CONTENT -->
      <div class="col-lg-9">
        <div class="row g-4">

          <!-- Saved addresses -->
          <div class="col-md-7">
            <div class="profile-card">
              <h5 class="fw-bold mb-3">Saved addresses</h5>

              <?php if(empty($addresses)): ?>
                <p class="text-muted mb-0">You have not added any delivery addresses yet.</p>
              <?php endif; ?>

              <?php foreach($addresses as $row): ?>
                <div class="address-box">
                  <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                      <p class="fw-bold mb-1"><?= htmlspecialchars($row['full_name']) ?></p>
                      <p class="mb-1 small"><i class="bi bi-telephone"></i> <?= htmlspecialchars($row['phone']) ?></p>
                    </div>
                    <div class="text-end">
                      <?php if ($row['is_default']): ?>
                        <span class="badge bg-success mb-1">Default</span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <p class="mb-1 small">
                    <?= nl2br(htmlspecialchars($row['address_line1'])) ?>
                    <?= htmlspecialchars($row['address_line2']) ?>
                  </p>

                  <p class="mb-1 small">
                    <?= $row['city'] ?>, <?= $row['state'] ?> - <?= $row['postal_code'] ?>
                  </p>

                  <p class="small mb-2"><?= $row['country'] ?></p>

                  <div class="d-flex gap-2 mt-1">
                    <?php if(!$row['is_default']): ?>
                      <a href="set_default.php?id=<?= $row['address_id'] ?>"
                         class="btn btn-sm btn-outline-primary">
                         Set as default
                      </a>
                    <?php endif; ?>

                    <a href="delete_address.php?id=<?= $row['address_id'] ?>"
                       class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Delete this address?')">
                       Delete
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Add new address -->
          <div class="col-md-5">
            <div class="profile-card">
              <h5 class="fw-bold mb-3">Add a new address</h5>

              <form action="save_address.php" method="POST">
                <div class="mb-3">
                  <label class="form-label">Full name</label>
                  <input type="text" name="full_name" class="form-control" required>
                </div>

                <div class="mb-3">
                  <label class="form-label">Phone number</label>
                  <input type="text" name="phone" class="form-control" required>
                </div>

                <div class="mb-3">
                  <label class="form-label">Address line 1</label>
                  <textarea name="address_line1" class="form-control" rows="2" required></textarea>
                </div>

                <div class="mb-3">
                  <label class="form-label">Address line 2 (optional)</label>
                  <textarea name="address_line2" class="form-control" rows="2"></textarea>
                </div>

                <div class="row">
                  <div class="col-md-4">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" required>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" required>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Postal code</label>
                    <input type="text" name="postal_code" class="form-control" required>
                  </div>
                </div>

                <div class="form-check mt-3">
                  <input class="form-check-input" type="checkbox" name="is_default" id="defaultAddress">
                  <label class="form-check-label" for="defaultAddress">
                    Make this my default address
                  </label>
                </div>

                <button class="btn btn-primary w-100 mt-4">Save address</button>
              </form>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</section>

<?php include __DIR__ . "/../components/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


</body>
</html>
