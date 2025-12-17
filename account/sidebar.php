<div class="account-sidebar">
  <div class="account-sidebar-title">Your shortcuts</div>
  <ul class="account-nav">
    <li><a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"><i class="bi bi-person-circle"></i>Account overview</a></li>
    <li><a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) === 'orders.php' || basename($_SERVER['PHP_SELF']) === 'review.php' ? 'active' : '' ?>"><i class="bi bi-box-seam"></i>Your orders</a></li>
    <li><a href="addresses.php" class="<?= basename($_SERVER['PHP_SELF']) === 'addresses.php' ? 'active' : '' ?>"><i class="bi bi-geo-alt"></i>Your addresses</a></li>
    <li><a href="../wishlist/wishlist_action.php" onclick="event.preventDefault(); document.querySelector('[data-bs-target=#wishlistDrawer]')?.click();"><i class="bi bi-heart"></i>Your wishlist</a></li>
    <li><a href="change_password.php" class="<?= basename($_SERVER['PHP_SELF']) === 'change_password.php' ? 'active' : '' ?>"><i class="bi bi-shield-lock"></i>Login &amp; security</a></li>
  </ul>
</div>






