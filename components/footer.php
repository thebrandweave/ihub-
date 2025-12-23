<?php
require_once __DIR__ . '/../config/config.php';

// Fetch active social media links
try {
    $social_stmt = $pdo->prepare("SELECT platform_name, link_url FROM social_media WHERE status = 'active' ORDER BY created_at DESC");
    $social_stmt->execute();
    $social_links = $social_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $social_links = [];
}

// Map platform names to Bootstrap Icons
function getSocialIcon($name) {
    $name = strtolower($name);
    if (strpos($name, 'facebook') !== false) return 'bi-facebook';
    if (strpos($name, 'instagram') !== false) return 'bi-instagram';
    if (strpos($name, 'twitter') !== false || strpos($name, 'x') !== false) return 'bi-twitter-x';
    if (strpos($name, 'youtube') !== false) return 'bi-youtube';
    if (strpos($name, 'linkedin') !== false) return 'bi-linkedin';
    if (strpos($name, 'whatsapp') !== false) return 'bi-whatsapp';
    if (strpos($name, 'tiktok') !== false) return 'bi-tiktok';
    return 'bi-link-45deg';
}
?>

<footer class="container-fluid bg-white pt-5 pb-4">
  <div class="container">
    <div class="row g-4">

      <div class="col-lg-4 col-md-6">
        <h5 class="fw-bold mb-3 text-primary">iHub Electronics</h5>
        <p class="text-secondary small mb-4" style="max-width: 300px; line-height: 1.6;">
          Your one-stop destination for the latest premium electronics, trending gadgets, and top-tier tech brands.
        </p>
        <div class="text-secondary small">
          <p class="mb-2"><i class="bi bi-geo-alt me-2 text-primary"></i> 16122 Collins Street, Melbourne</p>
          <p class="mb-2"><i class="bi bi-telephone me-2 text-primary"></i> (603) 555-0123</p>
          <p class="mb-0"><i class="bi bi-envelope me-2 text-primary"></i> support@ihubelectronics.com</p>
        </div>
      </div>

      <div class="col-lg-2 col-md-3 col-6">
        <h6 class="fw-bold mb-3">Shop</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="<?= $BASE_URL ?>shop/" class="text-decoration-none text-secondary">All Products</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>#products" class="text-decoration-none text-secondary">Popular Items</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>#brand" class="text-decoration-none text-secondary">Trending Brands</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>categories/" class="text-decoration-none text-secondary">Categories</a></li>
        </ul>
      </div>

      <div class="col-lg-2 col-md-3 col-6">
        <h6 class="fw-bold mb-3">Account</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="<?= $BASE_URL ?>account/orders.php" class="text-decoration-none text-secondary">My Orders</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>wishlist/" class="text-decoration-none text-secondary">Wishlist</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>cart/" class="text-decoration-none text-secondary">My Cart</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>contact/" class="text-decoration-none text-secondary">Support</a></li>
        </ul>
      </div>

      <div class="col-lg-4 col-md-12">
        <h6 class="fw-bold mb-3">Connect With Us</h6>
        <p class="text-secondary small mb-3">Stay updated with latest offers and tech news.</p>
        <div class="d-flex gap-2">
          <?php foreach ($social_links as $link): ?>
            <a href="<?= htmlspecialchars($link['link_url']) ?>" target="_blank" class="btn btn-light rounded-circle p-0" style="width:38px; height:38px; display:flex; align-items:center; justify-content:center;">
              <i class="bi <?= getSocialIcon($link['platform_name']) ?>"></i>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

    </div>

    <hr class="my-4" style="opacity: 0.1;">

    <div class="row align-items-center">
      <div class="col-md-6 text-center text-md-start">
        <p class="mb-0 text-secondary small">
          &copy; <?= date('Y') ?> <span class="fw-bold text-dark">iHub Electronics</span>. All rights reserved.
        </p>
      </div>
      <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
        <span class="small text-secondary me-2">Developed by</span>
        <a href="https://thebrandweave.com" target="_blank">
          <img src="<?= $BASE_URL ?>assets/image/logo/brand.png" height="22" alt="Brand Weaver Logo" style="filter: grayscale(1); opacity: 0.7;">
        </a>
      </div>
    </div>

    <a href="#" class="btn btn-primary rounded-circle shadow-lg position-fixed"
       style="right:25px; bottom:25px; width:46px; height:46px; display:flex; align-items:center; justify-content:center; z-index: 1050; border: none;">
      <i class="bi bi-arrow-up text-white fs-5"></i>
    </a>
  </div>
</footer>