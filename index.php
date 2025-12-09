<?php
// Connect to database and check customer authentication
require_once __DIR__ . '/auth/customer_auth.php';

// Fetch categories
$categories = [];
try {
    $catStmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC LIMIT 4");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Fetch trending products (active products with stock)
$trendingProducts = [];
try {
    $trendStmt = $pdo->query("
        SELECT p.*, c.name as category_name,
               (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.status = 'active' AND p.stock > 0
        ORDER BY p.created_at DESC
        LIMIT 4
    ");
    $trendingProducts = $trendStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $trendingProducts = [];
}

// Fetch popular products (active products)
$popularProducts = [];
try {
    $popStmt = $pdo->query("
        SELECT p.*, c.name as category_name,
               (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.status = 'active'
        ORDER BY p.created_at DESC
        LIMIT 6
    ");
    $popularProducts = $popStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $popularProducts = [];
}

function getActiveAdvertisements($type, $limit = 1) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT ad_id, title, ad_type, image_url, target_url, priority
            FROM advertisements
            WHERE status = 'active'
              AND ad_type = :type
              AND start_date <= NOW()
              AND end_date >= NOW()
            ORDER BY priority DESC, start_date DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':type', $type);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// UPDATED FUNCTION: Prepends 'uploads/ads/' to the filename
function formatAdImageUrl(?string $filename): string {
    global $BASE_URL;
    if (empty($filename)) {
        return 'https://via.placeholder.com/1600x600?text=Campaign';
    }
    // If it's already a full URL (external link), return as is
    if (preg_match('#^https?://#i', $filename)) {
        return $filename;
    }
    // Otherwise, assume it's a file in the uploads/ads directory
    return rtrim($BASE_URL, '/') . '/uploads/ads/' . ltrim($filename, '/');
}

$wishlistProductMap = [];
$heroAds = getActiveAdvertisements('hero_banner', 2);
$popupAd = getActiveAdvertisements('popup', 1)[0] ?? null;

if (!empty($customer_logged_in) && !empty($customer_id)) {
    try {
        $wishlistStmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $wishlistStmt->execute([$customer_id]);
        $wishlistIds = $wishlistStmt->fetchAll(PDO::FETCH_COLUMN, 0);
        if ($wishlistIds) {
            $wishlistProductMap = array_fill_keys($wishlistIds, true);
        }
    } catch (PDOException $e) {
        $wishlistProductMap = [];
    }
}

// Helper function to get product image URL
function getProductImage($product, $basePath = null) {
    global $BASE_URL;
    if ($basePath === null) {
        $basePath = $BASE_URL . 'uploads/products/';
    }
    if (!empty($product['primary_image'])) {
        return $basePath . $product['primary_image'];
    }
    if (!empty($product['thumbnail'])) {
        return $basePath . $product['thumbnail'];
    }
    return 'https://via.placeholder.com/300';
}

// Helper function to calculate price with discount
function getFinalPrice($price, $discount) {
    if ($discount > 0) {
        return $price - ($price * $discount / 100);
    }
    return $price;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>iHub Electronics</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    body{font-family:Arial, sans-serif; color:#0f172a;}
    .hero{padding:80px 0;}
    .newsletter{background: var(--brand-primary);padding:40px;color:white;border-radius:12px;}

    :root {
      --text-color: #1a1a1a;
      --accent-color: #e3000e;
      --bg-gray: #f5f5f5;
      --card-radius: 12px;
      --brand-primary: #0d6efd;
    }

    /* --- Product Card Styles --- */
    .product-card {
      border-radius: var(--card-radius);
      border: none;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      position: relative;
    }

    .product-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 30px rgba(15, 23, 42, 0.1);
    }

    .product-card .image-wrapper {
      position: relative;
      border-radius: var(--card-radius);
      overflow: hidden;
      background: #f5f7fb;
      padding-top: 100%;
      margin-bottom: 1rem;
    }

    .product-card .image-wrapper a {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      display: block;
      z-index: 1;
    }

    .product-card .image-wrapper img {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.4s ease;
    }

    .product-card:hover .image-wrapper img {
      transform: scale(1.04);
    }

    /* --- HOVER ACTION BUTTONS (ICONS) --- */
    .overlay-actions {
        position: absolute;
        bottom: 20px;
        left: 0;
        right: 0;
        display: flex;
        justify-content: center;
        gap: 12px;
        opacity: 0;
        transform: translateY(20px); /* Start slightly down */
        transition: all 0.3s ease-in-out;
        z-index: 5;
    }

    /* Show icons when hovering the card */
    .product-card:hover .overlay-actions {
        opacity: 1;
        transform: translateY(0);
    }

    .overlay-btn {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: #fff;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-color);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transition: all 0.2s ease;
        font-size: 1.25rem;
        cursor: pointer;
    }

    .overlay-btn:hover {
        background: var(--text-color);
        color: #fff;
        transform: scale(1.1);
    }

    .overlay-btn.wishlist-active {
        color: red;
    }
    
    .overlay-btn.wishlist-active:hover {
        background: red;
        color: white;
    }

    .badge-sale {
      position: absolute;
      top: 14px;
      left: 14px;
      background: var(--text-color);
      color: #fff;
      font-size: 0.75rem;
      padding: 4px 10px;
      border-radius: 4px;
      z-index: 2;
      font-weight: 600;
      letter-spacing: 0.5px;
    }

    .category-label {
      color: #94a3b8;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.5px;
    }

    .product-title {
      font-size: 1rem;
      font-weight: 700;
      color: var(--text-color);
    }

    .price-wrapper .current-price {
      font-weight: 700;
      color: var(--accent-color);
      font-size: 1.25rem;
    }

    .price-wrapper .old-price {
      font-size: 0.95rem;
      color: #94a3b8;
      text-decoration: line-through;
      margin-left: 8px;
    }

    /* --- ADVERTISEMENT STYLES --- */
    .hero-ad-section {
        padding: 2rem 0;
    }

    .promo-banner {
        position: relative;
        border-radius: var(--card-radius);
        overflow: hidden;
        height: 280px; 
        background-color: #f5f7fb;
        display: block;
        text-decoration: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .promo-banner:hover {
        transform: translateY(-4px); 
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.15);
    }

    .promo-banner img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    .promo-banner:hover img {
        transform: scale(1.03);
    }

    .promo-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to right, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.1) 70%, transparent 100%);
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 2.5rem;
        z-index: 2;
    }

    .promo-content h3 {
        color: #fff;
        font-weight: 700;
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        max-width: 80%;
    }

    .promo-btn {
        display: inline-flex;
        align-items: center;
        background: #fff;
        color: #0f172a;
        padding: 8px 24px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.9rem;
        margin-top: 15px;
        width: fit-content;
        transition: background 0.2s, color 0.2s;
    }

    .promo-banner:hover .promo-btn {
        background: var(--brand-primary);
        color: white;
    }

    /* Popup Ad */
    .ad-popup-img {
      width: 100%;
      border-radius: 12px;
    }
  </style>
</head>
<body>

<?php include __DIR__ . "/components/navbar.php"; ?>

<section class="hero container-fluid bg-light">
  <div class="container py-5">
    <div class="row align-items-center">

      <div class="col-lg-6 mb-4">
        <h1 class="fw-bold display-5">Gear up for adventure — built for every journey</h1>
        <p class="text-secondary mt-3">Lightweight tents, thermal sleeping bags, portable stoves & more. Free shipping over ₹2,499.</p>

        <div class="d-flex gap-3 mt-4">
          <a class="btn btn-primary px-4" href="#products">Shop Bestsellers</a>
          <a class="btn btn-outline-secondary px-4" href="#features">Learn More</a>
        </div>
      </div>

      <div class="col-lg-6">
         <img src="https://via.placeholder.com/600x400?text=Hero+Image" class="img-fluid rounded shadow-sm" alt="Hero">
      </div>

    </div>
  </div>
</section>

<section class="py-5 container-fluid bg-white">
  <div class="container">
    <h2 class="fw-bold mb-4">Top Categories</h2>
    <div class="row g-4">
      <?php foreach ($categories as $cat): ?>
        <div class="col-6 col-md-3">
          <a href="<?= $BASE_URL ?>shop/?category=<?= $cat['category_id'] ?>" class="text-decoration-none text-dark">
            <div class="p-3 bg-light rounded text-center shadow-sm">
              <?php if (!empty($cat['image_url'])): ?>
                <img src="<?= $BASE_URL ?>uploads/categories/<?= htmlspecialchars($cat['image_url']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>" class="w-50 mb-2" style="object-fit: cover; border-radius: 8px;">
              <?php else: ?>
                <img src="https://via.placeholder.com/150" alt="<?= htmlspecialchars($cat['name']) ?>" class="w-50 mb-2">
              <?php endif; ?>
              <h6 class="fw-bold"><?= htmlspecialchars($cat['name']) ?></h6>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<section class="py-5 container-fluid bg-light">
  <div class="container">
    <h2 class="fw-bold mb-4">Trending Electronics</h2>
    <div class="row g-4">
      <?php foreach ($trendingProducts as $product): ?>
        <?php
          $finalPrice = getFinalPrice($product['price'], $product['discount'] ?? 0);
          $isWishlisted = !empty($wishlistProductMap[$product['product_id']]);
          $wishlistIconClasses = $isWishlisted ? 'bi bi-heart-fill' : 'bi bi-heart';
          $wishlistActiveClass = $isWishlisted ? 'wishlist-active' : '';
        ?>
        <div class="col-6 col-md-3">
          <div class="product-card bg-white p-3 h-100">
            <?php if (($product['discount'] ?? 0) > 0): ?>
              <span class="badge-sale">-<?= number_format($product['discount'], 0) ?>%</span>
            <?php endif; ?>

            <div class="image-wrapper">
              <a href="<?= $BASE_URL ?>shop/product_details.php?id=<?= $product['product_id'] ?>">
                <img src="<?= htmlspecialchars(getProductImage($product)) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
              </a>
              
              <div class="overlay-actions">
                  <?php if ($customer_logged_in): ?>
                    <button class="overlay-btn" title="Add to Cart"
                            onclick="event.stopPropagation(); addToCart(
                              <?= $product['product_id'] ?>,
                              '<?= addslashes($product['name']) ?>',
                              <?= $finalPrice ?>,
                              '<?= htmlspecialchars(getProductImage($product)) ?>'
                            )">
                        <i class="bi bi-cart-plus"></i>
                    </button>
                    <button class="overlay-btn wishlist-heart <?= $wishlistActiveClass ?>" 
                            title="Add to Wishlist"
                            data-id="<?= $product['product_id'] ?>"
                            data-name="<?= htmlspecialchars($product['name']) ?>"
                            data-price="<?= $finalPrice ?>"
                            data-image="<?= htmlspecialchars(getProductImage($product)) ?>"
                            onclick="event.stopPropagation(); toggleWishlist(this)">
                        <i class="<?= $wishlistIconClasses ?>"></i>
                    </button>
                  <?php else: ?>
                    <button class="overlay-btn" title="Add to Cart" data-bs-toggle="modal" data-bs-target="#loginModal" onclick="event.stopPropagation();">
                        <i class="bi bi-cart-plus"></i>
                    </button>
                    <button class="overlay-btn text-danger" title="Add to Wishlist" data-bs-toggle="modal" data-bs-target="#loginModal" onclick="event.stopPropagation();">
                        <i class="bi bi-heart"></i>
                    </button>
                  <?php endif; ?>
              </div>
            </div>

            <span class="category-label"><?= htmlspecialchars($product['category_name'] ?? 'Electronics') ?></span>
            <h3 class="product-title">
              <a href="<?= $BASE_URL ?>shop/product_details.php?id=<?= $product['product_id'] ?>" class="text-decoration-none text-reset">
                <?= htmlspecialchars($product['name']) ?>
              </a>
            </h3>

            <div class="price-wrapper mb-3">
              <span class="current-price">₹<?= number_format($finalPrice, 2) ?></span>
              <?php if (($product['discount'] ?? 0) > 0): ?>
                <span class="old-price">₹<?= number_format($product['price'], 2) ?></span>
              <?php endif; ?>
            </div>
            
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if (!empty($heroAds)): ?>
<section class="hero-ad-section container-fluid">
  <div class="container">
    <div class="row g-4">
      <?php foreach ($heroAds as $ad): ?>
        <div class="col-12 <?= count($heroAds) > 1 ? 'col-lg-6' : '' ?>">
          <a class="promo-banner" href="<?= htmlspecialchars($ad['target_url']) ?>" target="_blank" rel="noopener">
            <img src="<?= htmlspecialchars(formatAdImageUrl($ad['image_url'])) ?>" 
                 alt="<?= htmlspecialchars($ad['title']) ?>" 
                 loading="lazy">
            
            <div class="promo-overlay">
                <div class="promo-content">
                    <h3><?= htmlspecialchars($ad['title']) ?></h3>
                    <div class="promo-btn">
                        Explore Now <i class="bi bi-arrow-right ms-2"></i>
                    </div>
                </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section id="products" class="py-5 container-fluid">
  <div class="container">
    <div class="d-flex justify-content-between mb-4">
      <h2 class="fw-bold">Popular Products</h2>
      <span class="text-secondary">Showing <?= count($popularProducts) ?> results</span>
    </div>

    <div class="row g-4">
      <?php foreach ($popularProducts as $product): ?>
        <?php
          $finalPrice = getFinalPrice($product['price'], $product['discount'] ?? 0);
          $isWishlisted = !empty($wishlistProductMap[$product['product_id']]);
          $wishlistIconClasses = $isWishlisted ? 'bi bi-heart-fill' : 'bi bi-heart';
          $wishlistActiveClass = $isWishlisted ? 'wishlist-active' : '';
        ?>
        <div class="col-6 col-md-4">
          <div class="product-card bg-white p-3 h-100">
            <?php if (($product['discount'] ?? 0) > 0): ?>
              <span class="badge-sale">-<?= number_format($product['discount'], 0) ?>%</span>
            <?php endif; ?>

            <div class="image-wrapper">
              <a href="<?= $BASE_URL ?>shop/product_details.php?id=<?= $product['product_id'] ?>">
                <img src="<?= htmlspecialchars(getProductImage($product)) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
              </a>
              
               <div class="overlay-actions">
                  <?php if ($customer_logged_in): ?>
                    <button class="overlay-btn" title="Add to Cart"
                            onclick="event.stopPropagation(); addToCart(
                              <?= $product['product_id'] ?>,
                              '<?= addslashes($product['name']) ?>',
                              <?= $finalPrice ?>,
                              '<?= htmlspecialchars(getProductImage($product)) ?>'
                            )">
                        <i class="bi bi-cart-plus"></i>
                    </button>
                    <button class="overlay-btn wishlist-heart <?= $wishlistActiveClass ?>" 
                            title="Add to Wishlist"
                            data-id="<?= $product['product_id'] ?>"
                            data-name="<?= htmlspecialchars($product['name']) ?>"
                            data-price="<?= $finalPrice ?>"
                            data-image="<?= htmlspecialchars(getProductImage($product)) ?>"
                            onclick="event.stopPropagation(); toggleWishlist(this)">
                        <i class="<?= $wishlistIconClasses ?>"></i>
                    </button>
                  <?php else: ?>
                    <button class="overlay-btn" title="Add to Cart" data-bs-toggle="modal" data-bs-target="#loginModal" onclick="event.stopPropagation();">
                        <i class="bi bi-cart-plus"></i>
                    </button>
                    <button class="overlay-btn text-danger" title="Add to Wishlist" data-bs-toggle="modal" data-bs-target="#loginModal" onclick="event.stopPropagation();">
                        <i class="bi bi-heart"></i>
                    </button>
                  <?php endif; ?>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center small mb-1">
              <span class="category-label text-uppercase"><?= htmlspecialchars($product['category_name'] ?? 'Electronics') ?></span>
              <span class="text-warning"><i class="bi bi-star-fill"></i> 0.00 (0)</span>
            </div>

            <h3 class="product-title mb-2">
              <a href="<?= $BASE_URL ?>shop/product_details.php?id=<?= $product['product_id'] ?>" class="text-decoration-none text-reset">
                <?= htmlspecialchars($product['name']) ?>
              </a>
            </h3>

            <div class="price-wrapper mb-3">
              <span class="current-price">₹<?= number_format($finalPrice, 2) ?></span>
              <?php if (($product['discount'] ?? 0) > 0): ?>
                <span class="old-price">₹<?= number_format($product['price'], 2) ?></span>
              <?php endif; ?>
            </div>

          </div>
        </div>
      <?php endforeach; ?>
    </div>

<section id="features" class="py-5 bg-light container-fluid mt-5">
  <div class="container">
    <h2 class="fw-bold mb-4">Why campers choose Aceno</h2>
    <div class="row g-3">
      <div class="col-md-4"><div class="p-4 bg-white shadow-sm rounded">Durable materials — lab tested</div></div>
      <div class="col-md-4"><div class="p-4 bg-white shadow-sm rounded">Sustainable packaging</div></div>
      <div class="col-md-4"><div class="p-4 bg-white shadow-sm rounded">Fast support & warranty</div></div>
    </div>
  </div>
</section>

<?php include __DIR__ . "/components/newsletter.php"; ?>

<?php include __DIR__ . "/components/footer.php"; ?>

<?php if (!empty($popupAd)): ?>
<div class="modal fade" id="adPopupModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0">
      <button class="btn-close ms-auto me-2 mt-2" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-body p-4">
        <a href="<?= htmlspecialchars($popupAd['target_url']) ?>" target="_blank" rel="noopener">
          <img src="<?= htmlspecialchars(formatAdImageUrl($popupAd['image_url'])) ?>" alt="<?= htmlspecialchars($popupAd['title']) ?>" class="ad-popup-img">
        </a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Handle login form submission
document.getElementById('loginForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  formData.append('ajax', '1');
  
  const errorDiv = document.getElementById('loginError');
  errorDiv.classList.add('d-none');
  
  try {
    const response = await fetch('<?= $BASE_URL ?>auth/customer_login.php', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Reload page to show logged in state
      window.location.reload();
    } else {
      errorDiv.textContent = data.error || 'Login failed';
      errorDiv.classList.remove('d-none');
    }
  } catch (error) {
    errorDiv.textContent = 'An error occurred. Please try again.';
    errorDiv.classList.remove('d-none');
  }
});

// Ensure logout links work - handle Bootstrap dropdown navigation
document.querySelectorAll('a[href*="customer_logout"]').forEach(function(logoutLink) {
  logoutLink.addEventListener('click', function(e) {
    // If it's a dropdown item, Bootstrap might prevent navigation
    if (this.classList.contains('dropdown-item')) {
      e.preventDefault();
      e.stopPropagation();
      setTimeout(function() {
        window.location.href = logoutLink.getAttribute('href');
      }, 50);
    }
  });
});

<?php if (!empty($popupAd)): ?>
document.addEventListener('DOMContentLoaded', () => {
  const adId = '<?= $popupAd['ad_id'] ?>';
  const storageKey = `ihub_ad_popup_${adId}`;
  if (!sessionStorage.getItem(storageKey)) {
    const modalEl = document.getElementById('adPopupModal');
    if (modalEl) {
      const popupModal = new bootstrap.Modal(modalEl);
      popupModal.show();
      sessionStorage.setItem(storageKey, 'shown');
    }
  }
});
<?php endif; ?>
</script>
</body>
</html>