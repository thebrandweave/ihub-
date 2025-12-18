<?php
// Connect to database and check customer authentication
require_once __DIR__ . '/auth/customer_auth.php';

// Fetch all categories (Removed LIMIT 4)
$categories = [];
try {
    $catStmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}


// --- Updated Trending Products Query ---
$trendingProducts = [];
try {
    $trendStmt = $pdo->query("
        SELECT p.*, c.name as category_name,
               (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image,
               IFNULL(AVG(r.rating), 0) as avg_rating,
               COUNT(r.review_id) as total_reviews
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN reviews r ON p.product_id = r.product_id AND r.status = 'approved'
        WHERE p.status = 'active' AND p.stock > 0
        GROUP BY p.product_id
        ORDER BY p.created_at DESC
        LIMIT 4
    ");
    $trendingProducts = $trendStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $trendingProducts = []; }

// --- Updated Featured Products Query ---
$featuredProducts = []; 
try {
    $featStmt = $pdo->query("
        SELECT p.*, c.name as category_name,
               (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image,
               IFNULL(AVG(r.rating), 0) as avg_rating,
               COUNT(r.review_id) as total_reviews
        FROM featured_products fp
        JOIN products p ON fp.product_id = p.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN reviews r ON p.product_id = r.product_id AND r.status = 'approved'
        WHERE p.status = 'active'
        GROUP BY p.product_id
        ORDER BY fp.added_at DESC
        LIMIT 4
    ");
    $featuredProducts = $featStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $featuredProducts = []; }

// --- Updated Popular Products Query ---
$popularProducts = [];
try {
    $popStmt = $pdo->query("
        SELECT p.*, c.name as category_name,
               (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image,
               IFNULL(AVG(r.rating), 0) as avg_rating,
               COUNT(r.review_id) as total_reviews
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN reviews r ON p.product_id = r.product_id AND r.status = 'approved'
        WHERE p.status = 'active'
        GROUP BY p.product_id
        ORDER BY p.created_at DESC
        LIMIT 6
    ");
    $popularProducts = $popStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $popularProducts = []; }


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

/* --- Unified Hero Boxed Design --- */
.hero-carousel-wrapper {
    padding: 2rem 0;
    background-color: #f8fafc;
}

.hero-boxed-container {
    position: relative;
    border-radius: 12px; /* Matches your --card-radius */
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.hero-boxed-container .carousel-item {
    height: 280px;
    background-color: #f5f7fb;
}

.hero-boxed-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.hero-boxed-container img:hover {
    transform: scale(1.03);
}


/* Responsive Scaling */
@media (max-width: 991px) {
    .hero-boxed-container .carousel-item { height: 400px; }
}

@media (max-width: 768px) {
    .hero-boxed-container .carousel-item { height: 350px; }
}

/* --- Circular Category Bar --- */
.category-nav-container {
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    padding: 20px 0;
    margin-bottom: 0;
}

.category-wrapper {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    gap: 30px;
    flex-wrap: nowrap;
}

.cat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    color: #334155;
    font-weight: 500;
    font-size: 13px;
    transition: all 0.3s ease;
    width: 80px; /* Fixed width for better alignment */
    text-align: center;
}

.cat-img-box {
    width: 70px;
    height: 70px;
    margin-bottom: 8px;
    border-radius: 50%; /* This makes it a circle */
    overflow: hidden;
    background: #f1f5f9;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cat-img-box img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* Ensures image fills the circle without distortion */
}

/* Hover Effects */
.cat-item:hover .cat-img-box {
    border-color: var(--brand-primary);
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
}

.cat-item:hover span {
    color: var(--brand-primary);
}

@media (max-width: 768px) {
    .category-wrapper { 
        gap: 20px; 
        overflow-x: auto; 
        justify-content: flex-start; 
        padding: 0 15px;
        -webkit-overflow-scrolling: touch;
    }
    .category-wrapper::-webkit-scrollbar { display: none; } /* Hide scrollbar for mobile */
    .cat-img-box { width: 60px; height: 60px; }
    .cat-item { width: 70px; font-size: 12px; }
}





    /* --- Product Card Styles --- */
    .product-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      position: relative;
    }

    .product-card:hover {
      transform: translateY(-5px);
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
        transform: translateY(20px); 
        transition: all 0.3s ease-in-out;
        z-index: 5;
    }

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


    /* Popup Ad */
    .ad-popup-img {
      width: 100%;
      border-radius: 12px;
    }
  </style>
</head>
<body>

<?php include __DIR__ . "/components/navbar.php"; ?>

<section class="category-nav-container bg-light">
  <div class="container">
    <div class="category-wrapper">
      <?php 
      $visibleLimit = 8; // Showing 8 items for a better spread
      $visibleCats = array_slice($categories, 0, $visibleLimit);
      $moreCats = array_slice($categories, $visibleLimit);
      ?>

      <?php foreach ($visibleCats as $cat): ?>
        <a href="<?= $BASE_URL ?>shop/?category=<?= $cat['category_id'] ?>" class="cat-item">
          <div class="cat-img-box">
            <?php if (!empty($cat['image_url'])): ?>
              <img src="<?= $BASE_URL ?>uploads/categories/<?= htmlspecialchars($cat['image_url']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>">
            <?php else: ?>
              <img src="https://via.placeholder.com/100" alt="No Image">
            <?php endif; ?>
          </div>
          <span><?= htmlspecialchars($cat['name']) ?></span>
        </a>
      <?php endforeach; ?>

      <?php if (!empty($moreCats)): ?>
        <div class="cat-item cat-more-dropdown">
          <div class="cat-img-box">
             <i class="bi bi-grid-3x3-gap" style="font-size: 24px; color: #64748b;"></i>
          </div>
          <span>More <i class="bi bi-chevron-down ms-1" style="font-size: 10px;"></i></span>
          
          <div class="cat-more-menu">
            <?php foreach ($moreCats as $cat): ?>
              <a href="<?= $BASE_URL ?>shop/?category=<?= $cat['category_id'] ?>">
                <?= htmlspecialchars($cat['name']) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="hero-carousel-wrapper">
    <div class="container">
        <div class="hero-boxed-container">
            <div id="heroAdCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
                
                <div class="carousel-indicators">
                    <?php foreach ($heroAds as $index => $ad): ?>
                        <button type="button" data-bs-target="#heroAdCarousel" data-bs-slide-to="<?= $index ?>" 
                                class="<?= $index === 0 ? 'active' : '' ?>" aria-current="true"></button>
                    <?php endforeach; ?>
                </div>

                <div class="carousel-inner">
                    <?php if (!empty($heroAds)): ?>
                        <?php foreach ($heroAds as $index => $ad): ?>
                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>" data-bs-interval="5000">
                              <a href="<?= htmlspecialchars($ad['target_url']) ?>">
                                <img src="<?= htmlspecialchars(formatAdImageUrl($ad['image_url'])) ?>" 
                                     alt="<?= htmlspecialchars($ad['title']) ?>">

                        </a>

                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="carousel-item active">
                            <img src="https://via.placeholder.com/1600x600?text=Welcome+to+iHub" alt="Placeholder">
                        </div>
                    <?php endif; ?>
                </div>

                <button class="carousel-control-prev" type="button" data-bs-target="#heroAdCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#heroAdCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>

            </div>
        </div>
    </div>
</section>





<section class="py-5 container-fluid bg-light">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold mb-0">Trending Electronics</h2>
      <a href="<?= $BASE_URL ?>shop/" class="text-primary text-decoration-none fw-semibold">View All <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-4">
      <?php foreach ($trendingProducts as $product): ?>
        <?php
          $finalPrice = getFinalPrice($product['price'], $product['discount'] ?? 0);
          $isWishlisted = !empty($wishlistProductMap[$product['product_id']]);
          $wishlistIconClasses = $isWishlisted ? 'bi bi-heart-fill' : 'bi bi-heart';
          $wishlistActiveClass = $isWishlisted ? 'wishlist-active' : '';
        ?>
        <div class="col-6 col-md-3">
          <div class="product-card p-3 h-100"> <?php if (($product['discount'] ?? 0) > 0): ?>
              <span class="badge-sale">-<?= number_format($product['discount'], 0) ?>%</span>
            <?php endif; ?>

            <div class="image-wrapper">
              <a href="<?= $BASE_URL ?>shop/product_details.php?id=<?= $product['product_id'] ?>">
                <img src="<?= htmlspecialchars(getProductImage($product)) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
              </a>
              
              <div class="overlay-actions">
                  <?php if ($customer_logged_in): ?>
                    <button class="overlay-btn" title="Add to Cart"
                            onclick="event.stopPropagation(); addToCart(<?= $product['product_id'] ?>,'<?= addslashes($product['name']) ?>',<?= $finalPrice ?>,'<?= htmlspecialchars(getProductImage($product)) ?>')">
                        <i class="bi bi-cart-plus"></i>
                    </button>
                    <button class="overlay-btn wishlist-heart <?= $wishlistActiveClass ?>" 
                            title="Add to Wishlist"
                            data-id="<?= $product['product_id'] ?>"
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
              <span class="text-warning">
                  <i class="bi bi-star-fill"></i> 
                  <?= number_format($product['avg_rating'], 1) ?> 
                  <span class="text-secondary">(<?= $product['total_reviews'] ?>)</span>
              </span>
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
  </div>
</section>

<?php if (!empty($featuredProducts)): ?>
<section class="py-5 container-fluid">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Featured Selections</h2>
        <a href="<?= $BASE_URL ?>shop/" class="text-primary text-decoration-none fw-semibold">View All <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-4">
      <?php foreach ($featuredProducts as $product): ?>
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
                            onclick="event.stopPropagation(); addToCart(<?= $product['product_id'] ?>,'<?= addslashes($product['name']) ?>',<?= $finalPrice ?>,'<?= htmlspecialchars(getProductImage($product)) ?>')">
                        <i class="bi bi-cart-plus"></i>
                    </button>
                    <button class="overlay-btn wishlist-heart <?= $wishlistActiveClass ?>" 
                            title="Add to Wishlist"
                            data-id="<?= $product['product_id'] ?>"
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
              <span class="text-warning">
                  <i class="bi bi-star-fill"></i> 
                  <?= number_format($product['avg_rating'], 1) ?> 
                  <span class="text-secondary">(<?= $product['total_reviews'] ?>)</span>
              </span>
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
  </div>
</section>
<?php endif; ?>

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
      <a href="<?= $BASE_URL ?>shop/" class="text-primary text-decoration-none fw-semibold">View All <i class="bi bi-arrow-right"></i></a>
    </div>

    <div class="row g-4">
      <?php foreach ($popularProducts as $product): ?>
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

            <div class="d-flex justify-content-between align-items-center small mb-1">
              <span class="category-label text-uppercase"><?= htmlspecialchars($product['category_name'] ?? 'Electronics') ?></span>
              <span class="text-warning">
                  <i class="bi bi-star-fill"></i> 
                  <?= number_format($product['avg_rating'], 1) ?> 
                  <span class="text-secondary">(<?= $product['total_reviews'] ?>)</span>
              </span>
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

document.querySelectorAll('a[href*="customer_logout"]').forEach(function(logoutLink) {
  logoutLink.addEventListener('click', function(e) {
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



// Function to scroll the category row
function scrollCategories(direction) {
  const container = document.getElementById('categoryScroll');
  const scrollAmount = 300;
  if (direction === 'left') {
    container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
  } else {
    container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
  }
}
</script>
</body>
</html>