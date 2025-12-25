<?php
require_once dirname(__DIR__) . "/config/config.php";


try {
  // Crucial: Select image_url so we can actually show the icons
  $navCatStmt = $pdo->query("SELECT category_id, name, image_url FROM categories ORDER BY name ASC LIMIT 10");
  $navCategories = $navCatStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $navCategories = [];
}
/*
|------------------------------------------------------------
| BULLETPROOF SITE URL (FIXES ALL PATH ISSUES)
|------------------------------------------------------------
*/
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host   = $_SERVER['HTTP_HOST'];
$SITE_URL = $scheme . "://" . $host . $BASE_URL;

$LOGO_URL = $SITE_URL . "assets/image/logo/ihub.png";

?>

<style>
:root {
  --brand-primary: #e3000e;
  --brand-primary-dark: #b0000b;
}

.btn-primary,
.btn-primary:focus {
  background-color: var(--brand-primary) !important;
  border-color: var(--brand-primary) !important;
}

.btn-primary:hover,
.btn-primary:active {
  background-color: var(--brand-primary-dark) !important;
  border-color: var(--brand-primary-dark) !important;
}

.btn-outline-primary {
  color: var(--brand-primary) !important;
  border-color: var(--brand-primary) !important;
}

.btn-outline-primary:hover,
.btn-outline-primary:focus {
  background-color: var(--brand-primary) !important;
  border-color: var(--brand-primary) !important;
  color: #fff !important;
}

.text-primary {
  color: var(--brand-primary) !important;
}

.bg-primary {
  background-color: var(--brand-primary) !important;
}

.border-primary {
  border-color: var(--brand-primary) !important;
}


/* Container to handle horizontal scrolling if categories exceed width */
.nav-category-scroll {
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none;  /* IE/Edge */
}
.nav-category-scroll::-webkit-scrollbar {
    display: none; /* Chrome/Safari */
}

.nav-cat-circle-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 65px; /* Ensures they don't squash */
    transition: transform 0.2s ease;
}

.nav-cat-circle-item:hover {
    transform: translateY(-2px);
}

.nav-cat-img-wrapper {
    width: 45px; /* Smaller for Navbar */
    height: 45px;
    border-radius: 50%;
    overflow: hidden;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 4px;
}

.nav-cat-img-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.nav-cat-label {
    font-size: 11px;
    font-weight: 600;
    color: #475569;
    white-space: nowrap;
    text-transform: capitalize;
}

.nav-cat-circle-item:hover .nav-cat-label {
    color: var(--brand-primary);
}


@media (max-width: 768px) {
    /* Make the bottom nav visible on mobile since we're using it for categories now */
    .bottom-nav {
        display: block !important;
        border-bottom: 1px solid #eee;
        overflow: hidden;
    }

    /* Force the flex container to stay in one line and scroll */
    .nav-category-scroll {
        display: flex !important;
        flex-wrap: nowrap !important;
        overflow-x: auto !important;
        padding: 10px 15px !important;
        gap: 20px !important; /* Spacing between circles */
        -webkit-overflow-scrolling: touch; /* Smooth momentum scrolling for iOS */
    }

    /* Shrink the circles slightly for mobile so we can see more of them */
    .nav-cat-img-wrapper {
        width: 55px !important;
        height: 55px !important;
        margin-bottom: 6px;
    }

    .nav-cat-label {
        font-size: 10px !important;
        max-width: 65px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
    }

    /* Hide the scrollbar because it's ugly and takes up space */
    .nav-category-scroll::-webkit-scrollbar {
        display: none;
    }
}


@media (max-width: 768px) {
    /* REMOVE the display: none and use this instead */
    .desktop-nav .top-info-bar, 
    .desktop-nav .w-100.py-3 { /* Hides the logo/search part of desktop nav */
        display: none !important;
    }

    .bottom-nav {
        display: block !important; /* Force show the category bar */
        border-top: none !important;
        background: #fff;
    }
}

/* Desktop search bar styling */
#desktopSearchForm .form-control:focus {
  border-color: var(--brand-primary);
  box-shadow: 0 0 0 0.2rem rgba(227, 0, 14, 0.25);
}

#desktopSearchForm .btn-dark {
  background-color: #000 !important;
  border-color: #000 !important;
}

#desktopSearchForm .btn-dark:hover {
  background-color: #333 !important;
  border-color: #333 !important;
}


/* LOGO STYLES */
.nav-logo {
  height: 40px;
  width: auto;
  object-fit: contain;
}

.mobile-logo img {
  height: 34px;
  width: auto;
}


.nav-link {
  color:rgb(0, 0, 0);
}

.nav-link:focus, .nav-link:hover {
  color: #e3000e !important;
}

@media (max-width: 768px) {
  .mobile-logo span {
    display: none;
  }
}


@media (max-width: 768px) {
.desktop-nav,
.top-info-bar {
  display: none !important;
}

.mobile-header {
  background: var(--brand-primary);
  padding: 10px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  position: sticky;
  top: 0;
  z-index: 1000;
}

.mobile-logo {
  font-size: 24px;
  font-weight: 700;
  color: #fff;
}

.mh-icon {
  color: #fff;
  font-size: 24px;
  cursor: pointer;
}

.mobile-search {
  background: #fff;
  padding: 10px 12px;
  border-bottom: 1px solid #e0e0e0;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.mobile-search .form-control {
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  font-size: 14px;
  padding: 8px 12px;
}

.mobile-search .form-control:focus {
  border-color: var(--brand-primary);
  box-shadow: 0 0 0 0.2rem rgba(227, 0, 14, 0.15);
  outline: none;
}

.mobile-search .btn-dark {
  background-color: var(--brand-primary) !important;
  border-color: var(--brand-primary) !important;
  border-radius: 4px;
  font-weight: 600;
  padding: 8px 20px;
}

.mobile-search .btn-dark:hover {
  background-color: var(--brand-primary-dark) !important;
  border-color: var(--brand-primary-dark) !important;
}

.offcanvas-custom {
  width: 280px;
  background: #ffffff;
}

.offcanvas-header {
  background:var(--brand-primary);
  color:#fff;
}

.menu-item {
  font-size:16px;
  padding:14px 0;
  border-bottom:1px solid #f2f2f2;
  cursor:pointer;
  transition: background-color 0.2s ease;
}

.menu-item:hover {
  background-color: #f8f9fa;
  margin-left: -12px;
  margin-right: -12px;
  padding-left: 12px;
  padding-right: 12px;
}

.menu-item a {
  display: flex;
  align-items: center;
  width: 100%;
  color: #212529;
  text-decoration: none;
}

.menu-item a:hover {
  color: var(--brand-primary);
}

.menu-section-header {
  font-size: 11px;
  letter-spacing: 0.5px;
  color: #6c757d;
  font-weight: 700;
  text-transform: uppercase;
  padding: 8px 12px;
  background-color: #f8f9fa;
  border-top: 1px solid #e9ecef;
  border-bottom: 1px solid #e9ecef;
  margin-top: 8px;
}
}

.heart-pop {
  animation: heartPop 0.4s ease;
}
@keyframes heartPop {
  0%   { transform: scale(1); }
  30%  { transform: scale(1.4); }
  70%  { transform: scale(0.9); }
  100% { transform: scale(1); }
}
.wishlist-heart:hover {
  transform: scale(1.15);
  transition: 0.2s ease;
}

/* OFFCANVAS HEADER THEME */
.offcanvas-header-theme {
  background: rgba(0, 0, 0, 0.95);
}

/* OFFCANVAS BODY STYLING (Amazon-like vibe) */
.offcanvas-cart-body,
.offcanvas-wishlist-body {
  background-color: #f3f3f3;
  font-size: 0.9rem;
}

.cart-line-item,
.wishlist-line-item {
  background-color: #fff;
}

.cart-summary-btn-primary {
  background-color: #ffd814;
  border-color: #fcd200;
  color: #0f1111;
  font-weight: 600;
}

.cart-summary-btn-primary:hover {
  background-color: #f7ca00;
  border-color: #f2c200;
  color: #0f1111;
}

.cart-summary-subtext {
  font-size: 0.8rem;
  color: #565959;
}

/* Free-shipping progress bar inside cart drawer */
.cart-free-ship-progress {
  height: 4px;
  background-color: #e5e7eb;
}
.cart-free-ship-progress .progress-bar {
  background-color: #22c55e;
}

.top-info-bar {
  width: 100%;
  padding: 8px 0;
}

.ticker-wrapper {
  display: flex;
  width: max-content; /* Critical: allows children to sit side-by-side without limit */
}

.ticker-track {
  display: flex;
  flex-shrink: 0;
  /* Adjust 25s for speed (Higher = Slower) */
  animation: infinite-train 25s linear infinite;
}

.ticker-track span {
  padding: 0 40px; /* Adjust spacing between messages */
  color: #ffffff;
  font-size: 14px;
  font-weight: 500;
  letter-spacing: 0.5px;
}

@keyframes infinite-train {
  0% {
    transform: translateX(0);
  }
  100% {
    /* Moves the entire first track out, bringing the second track into its exact start spot */
    transform: translateX(-100%);
  }
}

/* Pause on hover so customers can read or click specific info */
.top-info-bar:hover .ticker-track {
  animation-play-state: paused;
}


/* Typography & Interaction for Top Links */
.top-link {
  color: #ffffff;
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  transition: opacity 0.2s ease;
  white-space: nowrap;
}

.top-link:hover {
  color: #ffcccc;
  opacity: 0.9;
}

/* Fix Ticker Width for Responsiveness */
.ticker-container {
  mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
  -webkit-mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
}

/* DESKTOP NAV RESPONSIVENESS */
@media (min-width: 992px) and (max-width: 1200px) {
  #desktopSearchForm {
    max-width: 400px !important; /* Shrink search on small laptops */
  }
  .desktop-nav .gap-4 {
    gap: 1.5rem !important; /* Tighten spacing */
  }
}

/* Hiding mobile elements on desktop properly */
@media (min-width: 769px) {
  .mobile-header, .mobile-search {
    display: none !important;
  }
}

/* Ensure images don't stretch */
.nav-logo {
  max-width: 150px;
}

/* Notifications drawer */
.notification-item-unread {
  background-color: #fff5f5;
}
.notification-item-read {
  background-color: #ffffff;
}
.notification-type-pill {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}



/* Custom Dot Badge Style */
/* Red Theme Notification Dot */
.badge-dot {
    width: 10px;
    height: 10px;
    background-color: var(--brand-primary) !important; /* Uses your #e3000e red */
    border-radius: 50%;
    border: 2px solid #fff; /* White border helps it stand out */
    padding: 0 !important;
    display: none; /* Hidden until JS detects count > 0 */
    position: absolute;
    top: -2px;
    right: -2px;
    z-index: 10;
}

/* Adjustments for mobile header icons to prevent overlapping */
.mobile-header .position-relative .badge-dot {
    top: 0px;
    right: 0px;
} 

/* For mobile header dots */
.mobile-header .badge-dot {
    top: 2px !important;
    right: -2px !important;
}

/* For desktop icons dots */
.desktop-nav .badge-dot {
    position: absolute;
    top: 0;
    right: 0;
}




/* Container adjustment */
.bottom-nav .container {
    padding: 0 10px;
}

/* Hide scrollbar but keep functionality */
.nav-category-scroll {
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.nav-category-scroll::-webkit-scrollbar {
    display: none;
}

/* Scroller Button Design */
.cat-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid #e2e8f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.2s ease;
    color: #1e293b;
}

.cat-nav-btn:hover {
    background: var(--brand-primary);
    color: #ffffff;
    border-color: var(--brand-primary);
    box-shadow: 0 10px 15px -3px rgba(13, 110, 253, 0.3);
}

.cat-nav-btn.left {
    left: -5px;
}

.cat-nav-btn.right {
    right: -5px;
}

/* Mobile: Hide buttons to allow natural thumb swiping */
@media (max-width: 768px) {
    .cat-nav-btn {
        display: none !important;
    }
    .bottom-nav .container {
        padding: 0 15px;
    }
}
</style>


<!-- ================= MOBILE NAVBAR ================= -->
<div class="mobile-header d-md-none">

  <i class="bi bi-list mh-icon" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu"></i>

  <a href="<?= $SITE_URL ?>" class="mobile-logo d-flex align-items-center gap-2 text-decoration-none">
  <img src="<?= $LOGO_URL ?>" alt="iHub Logo">
  <span class="text-white fw-bold">iHub Electronics</span>
  </a>

  <div class="d-flex align-items-center gap-3">
    <?php if (!empty($customer_logged_in)): ?>
      <div data-bs-toggle="offcanvas" data-bs-target="#wishlistDrawer" class="position-relative">
        <i class="bi bi-heart mh-icon"></i>
        <span class="badge-dot bg-light text-dark wishlist-count"></span>
      </div>
    <?php endif; ?>
    
    <div class="position-relative">
      <i class="bi bi-bag mh-icon" data-bs-toggle="offcanvas" data-bs-target="#cartDrawer"></i>
      <span class="badge-dot bg-light text-dark cart-count"></span>
    </div>
  </div>

</div>

<div class="mobile-search d-md-none">
  <form method="GET" action="<?= $SITE_URL ?>shop/" id="mobileSearchForm" class="input-group">
    <input type="text" name="search" id="mobileSearchInput" class="form-control" placeholder="Enter keywords to search..." autocomplete="off">
    <button type="submit" class="btn btn-dark">SEARCH</button>
  </form>
</div>

<!-- MOBILE MENU -->
<div class="offcanvas offcanvas-start offcanvas-custom" id="mobileMenu">
  <div class="offcanvas-header">
    <h5 class="mb-0 fw-bold">Menu</h5>
    <button class="btn text-white" data-bs-dismiss="offcanvas" aria-label="Close">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>

  <div class="offcanvas-body p-0">
    <?php if (!empty($customer_logged_in)): ?>
      <div class="p-3 bg-light border-bottom">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white" style="width: 50px; height: 50px;">
            <i class="bi bi-person fs-4"></i>
          </div>
          <div>
            <div class="fw-bold"><?= htmlspecialchars($customer_name ?? 'Customer') ?></div>
            <small class="text-muted">View Profile</small>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="mt-2">
      <!-- Quick Links -->
      <div class="px-3 py-2 bg-light border-bottom">
        <small class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Quick Links</small>
      </div>
      
      <div class="menu-item">
        <a href="<?= $SITE_URL ?>shop/" class="text-decoration-none text-dark">
          <i class="bi bi-shop me-2"></i>Shop All Products
        </a>
      </div>
      <div class="menu-item">
        <a href="<?= $SITE_URL ?>contact/" class="text-decoration-none text-dark">
          <i class="bi bi-headset me-2"></i>Help & Support
        </a>
      </div>
      <div class="menu-item">
        <a href="<?= $SITE_URL ?>about/" class="text-decoration-none text-dark">
          <i class="bi bi-info-circle me-2"></i>About Us
        </a>
      </div>

      <?php if (!empty($customer_logged_in)): ?>
        <!-- Account Section -->
        <div class="px-3 py-2 bg-light border-top border-bottom mt-2">
          <small class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">My Account</small>
        </div>
        
        <div class="menu-item">
          <a href="<?= $SITE_URL ?>account/" class="text-decoration-none text-dark">
            <i class="bi bi-person-circle me-2"></i>My Account
          </a>
        </div>
        <div class="menu-item">
          <a href="<?= $SITE_URL ?>account/orders.php" class="text-decoration-none text-dark">
            <i class="bi bi-box-seam me-2"></i>My Orders
          </a>
        </div>
        <div class="menu-item">
          <a href="<?= $SITE_URL ?>account/addresses.php" class="text-decoration-none text-dark">
            <i class="bi bi-geo-alt me-2"></i>My Addresses
          </a>
        </div>
        <div class="menu-item">
          <a href="<?= $SITE_URL ?>account/update_profile.php" class="text-decoration-none text-dark">
            <i class="bi bi-pencil-square me-2"></i>Update Profile
          </a>
        </div>
        <div class="menu-item">
          <a href="<?= $SITE_URL ?>account/change_password.php" class="text-decoration-none text-dark">
            <i class="bi bi-shield-lock me-2"></i>Change Password
          </a>
        </div>
        <div class="menu-item border-0">
          <a href="<?= $SITE_URL ?>auth/customer_logout.php" class="text-decoration-none text-danger">
            <i class="bi bi-box-arrow-right me-2"></i>Logout
          </a>
        </div>
      <?php else: ?>
        <!-- Login Section -->
        <div class="px-3 py-2 bg-light border-top border-bottom mt-2">
          <small class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Account</small>
        </div>
        
        <div class="menu-item border-0" data-bs-toggle="modal" data-bs-target="#loginModal">
          <i class="bi bi-box-arrow-in-right me-2"></i>Sign in / Register
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>


<!-- ================= DESKTOP NAVBAR ================= -->
<nav class="container-fluid p-0 desktop-nav">

<div class="top-info-bar d-none d-md-block" style="background:var(--brand-primary-dark); border-bottom: 1px solid rgba(255,255,255,0.1);">
  <div class="container d-flex flex-column flex-lg-row justify-content-between align-items-center py-1">
    
    <div class="ticker-container" style="overflow:hidden; white-space:nowrap; max-width: 600px;">
      <div class="ticker-wrapper">
        <div class="ticker-track">
          <span>Free shipping for orders over ₹2499</span>
          <span>•</span>
          <span>100% Genuine Electronics</span>
          <span>•</span>
          <span>Fast Delivery Pan India</span>
          <span>•</span>
        </div>
        <div class="ticker-track">
          <span>Free shipping for orders over ₹2499</span>
          <span>•</span>
          <span>100% Genuine Electronics</span>
          <span>•</span>
          <span>Fast Delivery Pan India</span>
          <span>•</span>
        </div>
      </div>
    </div>

    <div class="top-bar-links d-flex gap-3 mt-1 mt-lg-0">
      <a href="<?= $SITE_URL ?>contact/" class="top-link"><i class="bi bi-telephone me-1"></i>Contact</a>
    </div>

  </div>
</div>

  <div class="w-100 py-3" style="background:rgba(0, 0, 0, 0.95);">
    <div class="container d-flex justify-content-between align-items-center gap-3">

    <a class="navbar-brand d-flex align-items-center gap-2 text-white text-decoration-none" href="<?= $SITE_URL ?>">
    <img src="<?= $LOGO_URL ?>" class="nav-logo" alt="iHub Logo">
    <span class="fw-bold fs-4">iHub Electronics</span>
    </a>


      <form method="GET" action="<?= $SITE_URL ?>shop/" id="desktopSearchForm" class="d-flex flex-grow-1 mx-4" style="max-width:700px;">
        <input type="text" name="search" id="desktopSearchInput" class="form-control rounded-0" placeholder="Search products..." autocomplete="off">
        <button type="submit" class="btn btn-dark rounded-0 px-4">SEARCH</button>
      </form>

      <div class="d-flex gap-4 text-white align-items-center">

        <?php if (!empty($customer_logged_in)): ?>
          <div class="dropdown">
            <div style="cursor:pointer" data-bs-toggle="dropdown">
              <i class="bi bi-person fs-4"></i>
              <small><?= htmlspecialchars($customer_name ?? 'Customer') ?></small>
            </div>

            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= $SITE_URL ?>account/"><i class="bi bi-person-circle me-2"></i>My Account</a></li>
              <li><a class="dropdown-item" href="<?= $SITE_URL ?>account/orders.php"><i class="bi bi-box-seam me-2"></i>My Orders</a></li>
              <li><a class="dropdown-item" href="<?= $SITE_URL ?>account/addresses.php"><i class="bi bi-geo-alt me-2"></i>My Addresses</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= $SITE_URL ?>account/update_profile.php"><i class="bi bi-pencil-square me-2"></i>Update Profile</a></li>
              <li><a class="dropdown-item" href="<?= $SITE_URL ?>account/change_password.php"><i class="bi bi-shield-lock me-2"></i>Change Password</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= $SITE_URL ?>auth/customer_logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </div>
        <?php else: ?>
          <div style="cursor:pointer" data-bs-toggle="modal" data-bs-target="#loginModal">
            <i class="bi bi-person fs-4"></i>
            <small>Account</small>
          </div>
        <?php endif; ?>

        <?php if (!empty($customer_logged_in)): ?>
          <div data-bs-toggle="offcanvas" data-bs-target="#notificationDrawer" class="position-relative">
            <i class="bi bi-bell fs-4"></i>
            <span class="badge-dot bg-light text-dark notification-count"></span>
          </div>
          <div data-bs-toggle="offcanvas" data-bs-target="#wishlistDrawer">
            <i class="bi bi-heart fs-4"></i>
            <span class="badge-dot bg-light text-dark wishlist-count"></span>
          </div>
        <?php else: ?>
          <div style="cursor:pointer" data-bs-toggle="modal" data-bs-target="#loginModal">
            <i class="bi bi-heart fs-4"></i>
          </div>
        <?php endif; ?>

        <div data-bs-toggle="offcanvas" data-bs-target="#cartDrawer">
          <i class="bi bi-bag fs-4"></i>
          <span class="badge-dot bg-light text-dark cart-count"></span>
        </div>

      </div>
    </div>
  </div>

  <div class="bg-white border-top shadow-sm bottom-nav">
    <div class="container position-relative">
        <button class="cat-nav-btn left" onclick="navSideScroll('navCategoryRow', 'left')">
            <i class="bi bi-chevron-left"></i>
        </button>
        <button class="cat-nav-btn right" onclick="navSideScroll('navCategoryRow', 'right')">
            <i class="bi bi-chevron-right"></i>
        </button>

        <div class="nav-category-scroll d-flex align-items-center py-2 gap-3 overflow-x-auto" id="navCategoryRow">
            <?php foreach ($navCategories as $cat): ?>
                <a href="<?= $SITE_URL ?>shop/?category=<?= $cat['category_id'] ?>" class="nav-cat-circle-item text-decoration-none text-center">
                    <div class="nav-cat-img-wrapper">
                        <?php if (!empty($cat['image_url'])): ?>
                            <img src="<?= $BASE_URL ?>uploads/categories/<?= htmlspecialchars($cat['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($cat['name']) ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center h-100 w-100 bg-light">
                                <i class="bi bi-tag text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <span class="nav-cat-label"><?= htmlspecialchars($cat['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function navSideScroll(elementId, direction) {
    const container = document.getElementById(elementId);
    const scrollAmount = 250; 
    container.scrollBy({ 
        left: direction === 'left' ? -scrollAmount : scrollAmount, 
        behavior: 'smooth' 
    });
}
</script>
</nav>


<!-- LOGIN MODAL -->
<div class="modal fade" id="loginModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Customer Login</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="loginForm">
          <div id="loginError" class="alert alert-danger d-none mb-3"></div>
          <input type="hidden" name="return" id="loginReturnUrl" value="">
          <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
          <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
          <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>

        <div class="text-center mt-3">
          <a href="<?= $SITE_URL ?>auth/signup.php">Create account</a>
        </div>
      </div>
    </div>
  </div>
</div>




<!-- CART DRAWER -->
<div class="offcanvas offcanvas-end" id="cartDrawer">
  <div class="offcanvas-header offcanvas-header-theme text-white">
    <h5 class="mb-0 fw-semibold d-flex align-items-center gap-2">
      Your cart
      <span class="badge-dot bg-light text-dark" id="cartDrawerItemCount"></span>
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body offcanvas-cart-body" id="cartDrawerBody">
    <p class="text-center mt-5">Loading...</p>
  </div>
</div>


<!-- WISHLIST DRAWER -->
<div class="offcanvas offcanvas-end" id="wishlistDrawer">
  <div class="offcanvas-header offcanvas-header-theme text-white">
    <h5 class="mb-0 fw-semibold d-flex align-items-center gap-2">
      Your wishlist
      <span class="badge-dot bg-light text-dark" id="wishlistDrawerItemCount"></span>
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body offcanvas-wishlist-body" id="wishlistDrawerBody">
    <p class="text-center mt-5">Loading...</p>
  </div>
</div>


<!-- NOTIFICATIONS DRAWER -->
<?php if (!empty($customer_logged_in)): ?>
<div class="offcanvas offcanvas-end" id="notificationDrawer">
  <div class="offcanvas-header offcanvas-header-theme text-white">
    <h5 class="mb-0 fw-semibold d-flex align-items-center gap-2">
      Notifications
      <span class="badge-dot bg-light text-dark notification-count"></span>
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body offcanvas-wishlist-body" id="notificationDrawerBody">
    <p class="text-center mt-5">Loading...</p>
  </div>
</div>
<?php endif; ?>


<!-- cart functionality -->
<script>
document.addEventListener("DOMContentLoaded", function() {
  updateCartCount();
  loadCartDrawer();
  <?php if (!empty($customer_logged_in)): ?>
  updateNotificationCount();
  // Load notifications when drawer is opened (not on page load)
  const notificationDrawer = document.getElementById('notificationDrawer');
  if (notificationDrawer) {
    notificationDrawer.addEventListener('show.bs.offcanvas', function() {
  loadNotificationDrawer();
    });
  }
  <?php endif; ?>
});


function updateCartCount() {
  fetch("<?= $BASE_URL ?>cart/cart_action.php", {
    method: "POST",
    body: new URLSearchParams({ action: "count" })
  })
  .then(res => res.json())
  .then(data => {
    document.querySelectorAll(".cart-count").forEach(el => {
      el.style.display = (data.count > 0) ? "block" : "none";
    });
  });
}



// ADD TO CART
function addToCart(id, name, price, image) {

  fetch("<?= $BASE_URL ?>cart/cart_action.php", {
    method: "POST",
    body: new URLSearchParams({
      action: "add",
      id: id,
      name: name,
      price: price,
      image: image
    })
  })
  .then(() => {
    updateCartCount();
    loadCartDrawer();

    let cartDrawer = new bootstrap.Offcanvas(document.getElementById('cartDrawer'));
    cartDrawer.show();
  });
}



// ================= NOTIFICATIONS (CUSTOMER) =================

function updateNotificationCount() {
  fetch("<?= $BASE_URL ?>account/notifications.php", {
    method: "POST",
    body: new URLSearchParams({ action: "count" })
  })
  .then(res => {
    if (!res.ok) {
      throw new Error(`HTTP ${res.status}`);
    }
    return res.json();
  })
  .then(data => {
    // Determine the count (some APIs use data.count, others unread_count)
    const count = data.count || data.unread_count || 0;
    
    document.querySelectorAll(".notification-count").forEach(el => {
      if (data.success && count > 0) {
        // Show the dot if there are notifications
        el.style.setProperty('display', 'block', 'important');
      } else {
        // Hide the dot if count is 0 or request failed
        el.style.display = "none";
      }
    });
  })
  .catch(error => {
    console.error("Failed to update notification count:", error);
    document.querySelectorAll(".notification-count").forEach(el => {
      el.style.display = "none";
    });
  });
} 


function loadNotificationDrawer() {
  const container = document.getElementById("notificationDrawerBody");
  if (!container) return;

  fetch("<?= $BASE_URL ?>account/notifications.php", {
    method: "POST",
    body: new URLSearchParams({ action: "get" })
  })
    .then(res => {
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
      }
      return res.json();
    })
    .then(data => {
      if (!data.success) {
        console.error("Notification load error:", data.error);
        container.innerHTML = "<p class='text-center mt-5 text-danger'>Unable to load notifications. " + (data.error || '') + "</p>";
        return;
      }

      const items = data.items || [];
      const unreadCount = data.unread_count || 0;

      if (items.length === 0) {
        container.innerHTML = `
          <div class="text-center mt-5">
            <i class="bi bi-bell fs-1 text-muted mb-2"></i>
            <h5 class="fw-semibold">No notifications yet</h5>
            <p class="text-muted small mb-0">We'll keep you posted about your orders and offers.</p>
          </div>
        `;
        return;
      }

      // Add header with mark all as read button if there are unread notifications
      let headerHTML = '';
      if (unreadCount > 0) {
        headerHTML = `
          <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <span class="small text-muted">${unreadCount} unread notification${unreadCount !== 1 ? 's' : ''}</span>
            <button onclick="markAllNotificationsRead()" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-check-all me-1"></i>Mark all as read
            </button>
          </div>
        `;
      }

      container.innerHTML = headerHTML;

      items.forEach(item => {
        const createdAt = item.created_at ? new Date(item.created_at) : null;
        const createdText = createdAt
          ? createdAt.toLocaleString(undefined, { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
          : '';

        const isUnread = !Number(item.is_read);
        const notificationId = item.notification_id;

        let typeLabel = (item.type || '').replace(/_/g, ' ') || 'update';

        container.innerHTML += `
          <div class="p-2 mb-2 rounded ${isUnread ? 'notification-item-unread' : 'notification-item-read'}" 
               data-notification-id="${notificationId}">
            <div class="d-flex gap-2">
              <div class="flex-shrink-0 mt-1">
                ${item.image_url ? `
                  <img src="${item.image_url}" width="40" height="40" class="rounded border object-fit-cover" alt="">
                ` : `
                  <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                    <i class="bi bi-bell text-secondary"></i>
                  </div>
                `}
              </div>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <div class="d-flex align-items-center gap-2">
                    ${isUnread ? `<span class="badge-dot bg-danger" style="width:8px;height:8px;padding:0;border-radius:50%;"></span>` : ''}
                  <span class="badge-dot bg-light text-muted notification-type-pill">${typeLabel}</span>
                  </div>
                  <span class="small text-muted">${createdText}</span>
                </div>
                <div class="fw-semibold small mb-1">${item.title}</div>
                <div class="small text-muted mb-1">${item.message}</div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                ${item.target_url ? `
                  <a href="${item.target_url}" class="small text-primary text-decoration-none">View details</a>
                  ` : `<span></span>`}
                  ${isUnread ? `
                    <button onclick="markNotificationRead(${notificationId}, this)" 
                            class="btn btn-sm btn-link text-muted p-0 small text-decoration-none">
                      <i class="bi bi-check-circle me-1"></i>Mark as read
                    </button>
                  ` : `
                    <span class="small text-muted">
                      <i class="bi bi-check-circle-fill text-success me-1"></i>Read
                    </span>
                  `}
                </div>
              </div>
            </div>
          </div>
        `;
      });
    })
    .catch(error => {
      console.error("Failed to load notifications:", error);
      container.innerHTML = "<p class='text-center mt-5 text-danger'>Unable to load notifications. Please check console for details.</p>";
    });
}

// Mark individual notification as read
function markNotificationRead(notificationId, buttonElement) {
  fetch("<?= $BASE_URL ?>account/notifications.php", {
    method: "POST",
    body: new URLSearchParams({
      action: "mark_read",
      notification_id: notificationId
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      // Update the notification item UI
      const notificationItem = buttonElement.closest('[data-notification-id]');
      if (notificationItem) {
        notificationItem.classList.remove('notification-item-unread');
        notificationItem.classList.add('notification-item-read');
        
        // Remove unread badge
        const unreadBadge = notificationItem.querySelector('.badge.bg-danger');
        if (unreadBadge) unreadBadge.remove();
        
        // Update button to show "Read" status
        buttonElement.outerHTML = `
          <span class="small text-muted">
            <i class="bi bi-check-circle-fill text-success me-1"></i>Read
          </span>
        `;
      }
      
      // Update count
      updateNotificationCount();
      
      // Reload drawer to refresh header if needed
      setTimeout(() => {
        loadNotificationDrawer();
      }, 300);
    } else {
      console.error("Failed to mark notification as read:", data.error);
      alert("Failed to mark notification as read. Please try again.");
    }
  })
  .catch(error => {
    console.error("Error marking notification as read:", error);
    alert("An error occurred. Please try again.");
  });
}

// Mark all notifications as read
function markAllNotificationsRead() {
  if (!confirm("Mark all notifications as read?")) {
    return;
  }
  
      fetch("<?= $BASE_URL ?>account/notifications.php", {
        method: "POST",
        body: new URLSearchParams({ action: "mark_all_read" })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      // Reload the drawer
      loadNotificationDrawer();
      // Update count
        updateNotificationCount();
    } else {
      console.error("Failed to mark all as read:", data.error);
      alert("Failed to mark all notifications as read. Please try again.");
    }
    })
  .catch(error => {
    console.error("Error marking all as read:", error);
    alert("An error occurred. Please try again.");
    });
}



// LOAD CART ITEMS INTO DRAWER
function loadCartDrawer() {
  fetch("<?= $BASE_URL ?>cart/cart_action.php", {
    method: "POST",
    body: new URLSearchParams({ action: "get" })
  })
    .then(res => res.json())
    .then(data => {
      const container = document.getElementById("cartDrawerBody");
      container.innerHTML = "";

      const itemsCount = data.items ? data.items.length : 0;

      // Update header badge count
      const headerCountEl = document.getElementById("cartDrawerItemCount");
      if (headerCountEl) {
        headerCountEl.textContent = itemsCount;
      }

      if (!data.items || data.items.length === 0) {
        container.innerHTML = `
          <div class="text-center mt-5">
            <i class="bi bi-bag fs-1 text-muted mb-2"></i>
            <h5 class="fw-semibold">Your cart is empty</h5>
            <p class="text-muted small mb-3">Add some products to see them here.</p>
            <a href="<?= $BASE_URL ?>shop/" class="btn btn-primary btn-sm px-4">Browse Products</a>
          </div>
        `;
        return;
      }

      // Free-shipping style banner (based on ₹2499 threshold like navbar message)
      const freeShipThreshold = 2499;
      const remaining = Math.max(0, freeShipThreshold - Number(data.total || 0));
      const progress = Math.min(100, (Number(data.total || 0) / freeShipThreshold) * 100);

      const freeShipText = remaining > 0
        ? `Spend ₹${remaining.toFixed(0)} more to enjoy <span class="fw-semibold text-uppercase">FREE SHIPPING!</span>`
        : `You have unlocked <span class="fw-semibold text-uppercase">FREE SHIPPING!</span>`;

      container.innerHTML = `
        <div class="mb-3">
          <div class="small">${freeShipText}</div>
          <div class="progress cart-free-ship-progress mt-2">
            <div class="progress-bar" role="progressbar" style="width: ${progress}%;"></div>
          </div>
        </div>
      `;

      data.items.forEach(item => {
        const itemTotal = (item.qty * item.price).toFixed(2);

        // Normalize image URL (prepend uploads/products/ if it's just a filename)
        let imageSrc = item.image || "";
        if (!imageSrc) {
          imageSrc = "https://via.placeholder.com/72";
        } else if (!/^https?:\/\//i.test(imageSrc)) {
          imageSrc = "<?= $BASE_URL ?>uploads/products/" + imageSrc.replace(/^\/+/, "");
        }

        container.innerHTML += `
          <div class="d-flex gap-3 pb-3 mb-3 border-bottom">
            <div class="flex-shrink-0">
              <img src="${imageSrc}"
                   width="72"
                   height="72"
                   class="rounded border object-fit-cover"
                   alt="${item.name}">
            </div>

            <div class="flex-grow-1 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <div class="me-2">
                  <div class="fw-semibold text-truncate" style="max-width: 220px;">
                    ${item.name}
                  </div>
                  <div class="small text-muted">
                    Price: <span class="fw-semibold text-dark">₹${Number(item.price).toFixed(2)}</span>
                  </div>
                </div>

                <button onclick="removeFromCart('${item.id}')"
                        class="btn btn-link text-danger p-0 ms-2">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-1">
                <div class="d-flex align-items-center gap-2">
                  <span class="small text-muted">Qty:</span>
                  <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-secondary"
                            onclick="updateQty('${item.id}', 'decrease')">−</button>
                    <span class="btn btn-outline-secondary disabled px-3 fw-semibold">
                      ${item.qty}
                    </span>
                    <button class="btn btn-outline-secondary"
                            onclick="updateQty('${item.id}', 'increase')">+</button>
                  </div>
                </div>

                <div class="text-end">
                  <div class="small text-muted">Item total</div>
                  <div class="fw-bold">₹${itemTotal}</div>
                </div>
              </div>
            </div>
          </div>
        `;
      });

      container.innerHTML += `
        <div class="border-top pt-3 mt-3">
          <div class="d-flex justify-content-between align-items-baseline mb-1">
            <span class="fw-semibold">Subtotal
              <span class="text-muted small">(${itemsCount} ${itemsCount === 1 ? 'item' : 'items'})</span>
            </span>
            <span class="fw-bold fs-5 text-success">₹${Number(data.total).toFixed(2)}</span>
          </div>
          <p class="cart-summary-subtext mb-3">Prices and availability are subject to change. Taxes and shipping calculated at checkout.</p>
          <a href="<?= $BASE_URL ?>checkout/" class="btn cart-summary-btn-primary w-100 mb-2 rounded-pill">
            Proceed to checkout
          </a>
          <button onclick="clearCart()" class="btn btn-outline-danger w-100">
            Clear Cart
          </button>
        </div>
      `;
    });
}



// REMOVE
function removeFromCart(id) {
  fetch("<?= $BASE_URL ?>cart/cart_action.php", {
    method: "POST",
    body: new URLSearchParams({
      action: "remove",
      id: id
    })
  })
  .then(() => {
    updateCartCount();
    loadCartDrawer();
  });
}


function updateQty(id, action) {

fetch("<?= $BASE_URL ?>cart/cart_action.php", {
  method: "POST",
  body: new URLSearchParams({
    action: action,
    id: id
  })
})
.then(() => {
  updateCartCount();
  loadCartDrawer();
});

}


function clearCart() {

if (!confirm("Are you sure you want to clear the entire cart?")) {
  return;
}

fetch("<?= $BASE_URL ?>cart/cart_action.php", {
  method: "POST",
  body: new URLSearchParams({ action: "clear" })
})
.then(() => {
  updateCartCount();
  loadCartDrawer();
});

}


</script>


<!-- wishlist functionality -->

<script>
document.addEventListener("DOMContentLoaded", function() {
  updateWishlistCount();
  loadWishlistDrawer();
});


// COUNT
function updateWishlistCount() {
  fetch("<?= $BASE_URL ?>wishlist/wishlist_action.php", {
    method: "POST",
    body: new URLSearchParams({ action: "count" })
  })
  .then(res => res.json())
  .then(data => {
    document.querySelectorAll(".wishlist-count").forEach(el => {
      el.style.display = (data.count > 0) ? "block" : "none";
    });
  });
}


// ADD TO WISHLIST
function addToWishlist(id, name, price, image) {

  fetch("<?= $BASE_URL ?>wishlist/wishlist_action.php", {
    method: "POST",
    body: new URLSearchParams({
      action: "add",
      id: id,
      name: name,
      price: price,
      image: image
    })
  })
  .then(() => {
    updateWishlistCount();
    loadWishlistDrawer();

    let drawer = new bootstrap.Offcanvas(document.getElementById('wishlistDrawer'));
    drawer.show();
  });

}


function moveToCart(id, name, price, image) {

// ✅ First add to cart
fetch("<?= $BASE_URL ?>cart/cart_action.php", {
  method: "POST",
  body: new URLSearchParams({
    action: "add",
    id: id,
    name: name,
    price: price,
    image: image
  })
})
.then(() => {

  fetch("<?= $BASE_URL ?>wishlist/wishlist_action.php", {
    method: "POST",
    body: new URLSearchParams({
      action: "remove",
      id: id
    })
  })
  .then(() => {
    updateCartCount();
    updateWishlistCount();

    loadCartDrawer();
    loadWishlistDrawer();

    showWishlistToast("Moved to cart 🛒", "bg-primary");

    let cartDrawer = new bootstrap.Offcanvas(document.getElementById('cartDrawer'));
    cartDrawer.show();

  });

});

}

function moveAllToCart() {

if (!confirm("Move all wishlist items to cart?")) return;

fetch("<?= $BASE_URL ?>wishlist/wishlist_action.php", {
  method: "POST",
  body: new URLSearchParams({ action: "move_all" })
})
.then(() => {

  updateCartCount();
  updateWishlistCount();

  loadCartDrawer();
  loadWishlistDrawer();

  showWishlistToast("All items moved to cart 🛒", "bg-primary");

  let cartDrawer = new bootstrap.Offcanvas(document.getElementById('cartDrawer'));
  cartDrawer.show();

});
}




// LOAD WISHLIST DRAWER
function loadWishlistDrawer() {

  fetch("<?= $BASE_URL ?>wishlist/wishlist_action.php", {
    method: "POST",
    body: new URLSearchParams({ action: "get" })
  })
    .then(res => res.json())
    .then(data => {
      const container = document.getElementById("wishlistDrawerBody");
      container.innerHTML = "";

      const itemsCount = data.items ? data.items.length : 0;

      // Update header badge count
      const headerCountEl = document.getElementById("wishlistDrawerItemCount");
      if (headerCountEl) {
        headerCountEl.textContent = itemsCount;
      }

      if (!data.items || data.items.length === 0) {
        container.innerHTML = `
          <div class="text-center mt-5">
            <i class="bi bi-heart fs-1 text-muted mb-2"></i>
            <h5 class="fw-semibold">Your wishlist is empty</h5>
            <p class="text-muted small mb-3">Save products you love and get back to them anytime.</p>
            <a href="<?= $BASE_URL ?>shop/" class="btn btn-primary btn-sm px-4">Start Exploring</a>
          </div>
        `;
        return;
      }

      data.items.forEach(item => {
        // Normalize image URL (prepend uploads/products/ if it's just a filename)
        let imageSrc = item.image || "";
        if (!imageSrc) {
          imageSrc = "https://via.placeholder.com/72";
        } else if (!/^https?:\/\//i.test(imageSrc)) {
          imageSrc = "<?= $BASE_URL ?>uploads/products/" + imageSrc.replace(/^\/+/, "");
        }

        container.innerHTML += `
          <div class="d-flex gap-3 pb-3 mb-3 border-bottom">
            <div class="flex-shrink-0">
              <img src="${imageSrc}"
                   width="72"
                   height="72"
                   class="rounded border object-fit-cover"
                   alt="${item.name}">
            </div>

            <div class="flex-grow-1 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <div class="me-2">
                  <div class="fw-semibold text-truncate" style="max-width: 220px;">
                    ${item.name}
                  </div>
                  <div class="text-muted small">
                    <span class="fw-semibold text-dark">₹${Number(item.price).toFixed(2)}</span>
                  </div>
                </div>
                <button onclick="removeFromWishlist('${item.id}')"
                        class="btn btn-link text-danger p-0 ms-2 small">
                  Delete
                </button>
              </div>

              <div class="d-flex gap-2 mt-1">
                <button class="btn btn-sm cart-summary-btn-primary flex-grow-1 rounded-pill"
                        onclick="moveToCart(
                          '${item.id}',
                          '${item.name.replace(/'/g, "\\'")}',
                          '${item.price}',
                          '${item.image}'
                        )">
                  Move to cart
                </button>
              </div>
            </div>
          </div>
        `;
      });

      container.innerHTML += `
        <div class="border-top pt-3 mt-3">
          <button onclick="moveAllToCart()" class="btn cart-summary-btn-primary w-100 mb-2 rounded-pill">
            Move all to cart
          </button>
          <button onclick="clearWishlist()" class="btn btn-outline-secondary w-100">
            Clear wishlist
          </button>
        </div>
      `;
    });
}


// REMOVE
function removeFromWishlist(id) {

  fetch("<?= $BASE_URL ?>wishlist/wishlist_action.php", {
    method: "POST",
    body: new URLSearchParams({
      action: "remove",
      id: id
    })
  })
  .then(() => {
    updateWishlistCount();
    loadWishlistDrawer();
  });

}


// CLEAR ALL
function clearWishlist() {

  if (!confirm("Clear all items in wishlist?")) return;

  fetch("<?= $BASE_URL ?>wishlist/wishlist_action.php", {
    method: "POST",
    body: new URLSearchParams({ action: "clear" })
  })
  .then(() => {
    updateWishlistCount();
    loadWishlistDrawer();
  });

}
</script>


<script>
// function toggleWishlist(productId, name, price, image, btn) {
//     const icon = btn.querySelector("i");

//     fetch("<?= $BASE_URL ?>wishlist/wishlist_action.php", {
//         method: "POST",
//         body: new URLSearchParams({ action: "add", id: productId })
//     })
//     .then(res => res.json())
//     .then(data => {

//         if (icon.classList.contains("bi-heart")) {
//             icon.classList.remove("bi-heart");
//             icon.classList.add("bi-heart-fill");
//             icon.style.color = "red";
//             showWishlistToast("Added to wishlist", "bg-danger");
//         } else {
//             icon.classList.remove("bi-heart-fill");
//             icon.classList.add("bi-heart");
//             icon.style.color = "black";
//             showWishlistToast("Removed from wishlist", "bg-dark");

//             // Remove from wishlist table
//             fetch("<?= $BASE_URL ?>wishlist/wishlist_action.php", {
//                 method: "POST",
//                 body: new URLSearchParams({ action: "remove", id: productId })
//             });
//         }

//         updateWishlistCount();
//         loadWishlistDrawer();
//     });
// }



function toggleWishlist(btn) {

const id = btn.dataset.id;
const name = btn.dataset.name;
const price = btn.dataset.price;
const image = btn.dataset.image;

const icon = btn.querySelector("i");

// If already filled → REMOVE
if (icon.classList.contains("bi-heart-fill")) {

    fetch("<?= $BASE_URL ?>wishlist/wishlist_action.php", {
        method: "POST",
        body: new URLSearchParams({
            action: "remove",
            id: id
        })
    }).then(() => {

        icon.classList.remove("bi-heart-fill");
        icon.classList.add("bi-heart");
        icon.style.color = "black";

        updateWishlistCount();
        loadWishlistDrawer();

        showWishlistToast("Removed from wishlist", "bg-dark");
    });

} 
else {
    // ADD
    fetch("<?= $BASE_URL ?>wishlist/wishlist_action.php", {
        method: "POST",
        body: new URLSearchParams({
            action: "add",
            id: id
        })
    }).then(() => {

        icon.classList.remove("bi-heart");
        icon.classList.add("bi-heart-fill");
        icon.style.color = "red";

        updateWishlistCount();
        loadWishlistDrawer();

        showWishlistToast("Added to wishlist", "bg-danger");
    });
}
}



function showWishlistToast(message, color) {

const toastEl = document.getElementById("wishlistToast");
const toastMsg = document.getElementById("wishlistToastMsg");

toastMsg.innerText = message;
toastEl.classList.remove("bg-success", "bg-danger");
toastEl.classList.add(color);

const toast = new bootstrap.Toast(toastEl, { delay: 1500 });
toast.show();
}


</script>



<script>
document.addEventListener("DOMContentLoaded", () => {

  const form = document.getElementById("loginForm");
  const errorBox = document.getElementById("loginError");
  const returnUrlInput = document.getElementById("loginReturnUrl");
  const loginModal = document.getElementById("loginModal");

  // Capture return URL from query parameters
  const urlParams = new URLSearchParams(window.location.search);
  const returnUrl = urlParams.get('return');
  if (returnUrl && returnUrlInput) {
    returnUrlInput.value = decodeURIComponent(returnUrl);
  }

  // Also capture return URL when modal is shown (for links that open modal)
  if (loginModal) {
    loginModal.addEventListener('show.bs.modal', function (event) {
      // Check if the trigger has a data-return attribute
      const trigger = event.relatedTarget;
      if (trigger && trigger.dataset.return && returnUrlInput) {
        returnUrlInput.value = trigger.dataset.return;
      } else if (!returnUrlInput.value && returnUrl) {
        returnUrlInput.value = decodeURIComponent(returnUrl);
      }
    });
  }

  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      // Hide any previous errors
      if (errorBox) {
        errorBox.classList.add("d-none");
      }

      const formData = new FormData(form);
      formData.append('ajax', '1'); // Tell PHP to return JSON

      fetch("<?= $SITE_URL ?>auth/customer_login.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin"
      })
      .then(async res => {
        // First check if response is OK
        if (!res.ok) {
          const text = await res.text();
          throw new Error(`HTTP ${res.status}: ${text.substring(0, 100)}`);
        }
        
        // Try to parse as JSON
        const text = await res.text();
        try {
          return JSON.parse(text);
        } catch (e) {
          // If not JSON, show the actual response
          console.error("Response is not JSON:", text);
          throw new Error("Server returned invalid response: " + text.substring(0, 200));
        }
      })
      .then(data => {

        if (data.success === true) {
          // Close modal if open
          const modal = bootstrap.Modal.getInstance(loginModal);
          if (modal) {
            modal.hide();
          }
          
          // Redirect to return URL if provided, otherwise reload
          if (data.redirect) {
            window.location.href = data.redirect;
          } else if (returnUrlInput && returnUrlInput.value) {
            window.location.href = returnUrlInput.value;
          } else {
            location.reload();   // refresh to show logged-in navbar
          }
        } else {
          if (errorBox) {
            errorBox.classList.remove("d-none");
            errorBox.innerText = data.error || data.message || "Login failed";
          } else {
            alert(data.error || data.message || "Login failed");
          }
        }

      })
      .catch((error) => {
        console.error("Login error:", error);
        if (errorBox) {
          errorBox.classList.remove("d-none");
          errorBox.innerText = error.message || "Server error. Please check console for details.";
        } else {
          alert(error.message || "Server error. Please check console for details.");
        }
      });

    });
  }

});
</script>

<!-- Search functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Desktop search
    const desktopSearchForm = document.getElementById('desktopSearchForm');
    const desktopSearchInput = document.getElementById('desktopSearchInput');
    
    if (desktopSearchForm && desktopSearchInput) {
        // Handle Enter key
        desktopSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitSearch(desktopSearchForm, desktopSearchInput);
            }
        });
        
        // Handle form submission
        desktopSearchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitSearch(desktopSearchForm, desktopSearchInput);
        });
    }
    
    // Mobile search
    const mobileSearchForm = document.getElementById('mobileSearchForm');
    const mobileSearchInput = document.getElementById('mobileSearchInput');
    
    if (mobileSearchForm && mobileSearchInput) {
        // Handle Enter key
        mobileSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitSearch(mobileSearchForm, mobileSearchInput);
            }
        });
        
        // Handle form submission
        mobileSearchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitSearch(mobileSearchForm, mobileSearchInput);
        });
    }
    
    // Function to submit search
    function submitSearch(form, input) {
        const searchTerm = input.value.trim();
        
        if (searchTerm === '') {
            // If empty, just go to shop page
            window.location.href = form.action;
            return;
        }
        
        // Redirect to shop page with search parameter
        const url = new URL(form.action);
        url.searchParams.set('search', searchTerm);
        window.location.href = url.toString();
    }
    
    // Pre-fill search input if coming from shop page with search parameter
    const urlParams = new URLSearchParams(window.location.search);
    const searchParam = urlParams.get('search');
    
    if (searchParam) {
        if (desktopSearchInput) {
            desktopSearchInput.value = decodeURIComponent(searchParam);
        }
        if (mobileSearchInput) {
            mobileSearchInput.value = decodeURIComponent(searchParam);
        }
    }
});
</script>

