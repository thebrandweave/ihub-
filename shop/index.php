<?php
require_once __DIR__ . '/../auth/customer_auth.php';
require_once __DIR__ . '/../config/config.php'; // if needed

// Fetch categories
try {
    $catStmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Search & filter handling
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'latest';

// Build query
$query = "
    SELECT 
        p.*, 
        c.name AS category_name,
        b.name AS brand_name,
        b.logo AS brand_logo,
        (SELECT image_url FROM product_images 
         WHERE product_id = p.product_id AND is_primary = 1 
         LIMIT 1) AS primary_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE p.status = 'active'
";

$params = [];

// Search - enhanced to search in name and description
if (!empty($search)) {
    $query .= " AND (p.name LIKE :search OR p.description LIKE :search) ";
    $params[':search'] = "%$search%";
}

// Category filter
if (!empty($categoryFilter)) {
    $query .= " AND p.category_id = :category_id ";
    $params[':category_id'] = $categoryFilter;
}

// Sorting
if ($sort == "price_low") {
    $query .= " ORDER BY p.price ASC ";
} elseif ($sort == "price_high") {
    $query .= " ORDER BY p.price DESC ";
} else {
    $query .= " ORDER BY p.created_at DESC ";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$wishlistProductMap = [];
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

$heroAds = getActiveAdvertisements('hero_banner', 1);
$popupAd = getActiveAdvertisements('popup', 1)[0] ?? null;

// UPDATED: Now prepends 'uploads/ads/' correctly
function formatAdImageUrl(?string $filename): string {
    global $BASE_URL;
    if (empty($filename)) {
        return 'https://via.placeholder.com/1400x500?text=Campaign';
    }
    if (preg_match('#^https?://#i', $filename)) {
        return $filename;
    }
    return rtrim($BASE_URL, '/') . '/uploads/ads/' . ltrim($filename, '/');
}

function getProductImage($p, $base = null) {
    global $BASE_URL;
    if ($base === null) {
        $base = $BASE_URL . 'uploads/products/';
    }
    if (!empty($p['primary_image'])) return $base . $p['primary_image'];
    if (!empty($p['thumbnail'])) return $base . $p['thumbnail'];
    return 'https://via.placeholder.com/300x300?text=No+Image';
}

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
  <title>Shop Collection — iHub Electronics</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  
  <style>
    :root {
        --text-color: #1a1a1a;
        --accent-color: #e3000e;
        --bg-gray: #f5f5f5;
        --card-radius: 12px;
        --brand-primary: #0d6efd;
    }

    body {
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        color: var(--text-color);
        background-color: #ffffff;
    }

    .filter-title {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #eee;
    }
    
    .form-control, .form-select {
        border-radius: 4px;
        border: 1px solid #e0e0e0;
        padding: 0.6rem;
        font-size: 0.9rem;
    }
    
    .form-control:focus, .form-select:focus {
        box-shadow: none;
        border-color: var(--text-color);
    }
    
    /* Search input styling */
    #searchInput:focus {
        border-color: var(--brand-primary);
    }
    
    .input-group .btn {
        border-left: none;
    }
    
    .input-group .btn-outline-secondary:hover {
        background-color: #f8f9fa;
        border-color: #dee2e6;
    }

    /* Product Card Styles */
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
        font-size: 1.1rem;
    }

    .price-wrapper .old-price {
        font-size: 0.95rem;
        color: #94a3b8;
        text-decoration: line-through;
        margin-left: 8px;
    }

    /* --- ADVERTISEMENT STYLES --- */
    .promo-banner {
        position: relative;
        border-radius: var(--card-radius);
        overflow: hidden;
        height: 280px; /* Controls height - not too big */
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
        object-fit: cover; /* Fixes blur/stretch issues */
        object-position: center;
        transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    .promo-banner:hover img {
        transform: scale(1.03);
    }

    /* Dark gradient overlay to make text pop */
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

    .page-header {
        padding: 3rem 0;
        text-align: center;
    }
    
    .page-header h2 {
        font-weight: 800;
        font-size: 2.5rem;
        letter-spacing: -1px;
    }
  </style>
</head>
<body>

<?php include __DIR__ . "/../components/navbar.php"; ?>

<div class="container pb-5">
    
    <div class="page-header">
        <h2>All Products</h2>
        <p class="text-muted">Explore our latest electronics and gadgets</p>
    </div>

    <?php if (!empty($heroAds)): ?>
        <div class="mb-5">
            <?php foreach ($heroAds as $ad): ?>
                <a class="promo-banner" href="<?= htmlspecialchars($ad['target_url']) ?>" target="_blank" rel="noopener">
                    <img src="<?= htmlspecialchars(formatAdImageUrl($ad['image_url'])) ?>" 
                         alt="<?= htmlspecialchars($ad['title']) ?>">
                    
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="GET" id="filterForm">
        <div class="row">
            
            <div class="col-lg-3 col-md-4 mb-4">
                <div class="pe-md-4">
                    
                    <div class="mb-4">
                        <h6 class="filter-title">Search</h6>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="searchInput" value="<?= htmlspecialchars($search) ?>" 
                                   class="form-control border-start-0 ps-0" placeholder="Find product..." 
                                   autocomplete="off">
                            <?php if (!empty($search)): ?>
                                <button type="button" class="btn btn-outline-secondary border-start-0" 
                                        onclick="clearSearch()" title="Clear search">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            <?php endif; ?>
                            <button type="submit" form="filterForm" class="btn btn-dark" title="Search">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <?php if (!empty($search)): ?>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="bi bi-funnel"></i> Searching for: <strong><?= htmlspecialchars($search) ?></strong>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <h6 class="filter-title">Category</h6>
                        <div class="d-grid gap-2">
                            <label class="d-flex align-items-center" style="cursor:pointer; font-size:0.95rem;">
                                <input type="radio" name="category" value="" 
                                       <?= empty($categoryFilter) ? 'checked' : '' ?> 
                                       onchange="this.form.submit()" class="form-check-input me-2">
                                <span>All Categories</span>
                            </label>
                            <?php foreach ($categories as $cat): ?>
                                <label class="d-flex align-items-center" style="cursor:pointer; font-size:0.95rem;">
                                    <input type="radio" name="category" value="<?= $cat['category_id'] ?>" 
                                           <?= $categoryFilter == $cat['category_id'] ? 'checked' : '' ?> 
                                           onchange="this.form.submit()" class="form-check-input me-2">
                                    <span><?= htmlspecialchars($cat['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button class="btn btn-dark w-100 mt-3 d-md-none">Apply Filters</button>
                </div>
            </div>

            <div class="col-lg-9 col-md-8">
                
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                    <span class="text-muted small">
                        Showing <strong><?= count($products) ?></strong> results
                    </span>
                    
                    <div class="d-flex align-items-center">
                        <label for="sort" class="me-2 small fw-bold text-uppercase d-none d-sm-block">Sort by:</label>
                        <select name="sort" id="sort" class="form-select form-select-sm" style="width: auto; min-width: 140px;" onchange="this.form.submit()">
                            <option value="latest" <?= $sort=='latest'?'selected':'' ?>>Latest Arrivals</option>
                            <option value="price_low" <?= $sort=='price_low'?'selected':'' ?>>Price: Low to High</option>
                            <option value="price_high" <?= $sort=='price_high'?'selected':'' ?>>Price: High to Low</option>
                        </select>
                    </div>
                </div>

                <div class="row g-4">
                    <?php foreach ($products as $p): ?>
                        <?php
                            $final = getFinalPrice($p['price'], $p['discount']);
                            $isWishlisted = !empty($wishlistProductMap[$p['product_id']]);
                            $wishlistIconClasses = $isWishlisted ? 'bi bi-heart-fill' : 'bi bi-heart';
                            $wishlistActiveClass = $isWishlisted ? 'wishlist-active' : '';
                        ?>
                        <div class="col-6 col-md-4">
                            <div class="product-card bg-white p-3 h-100">
                                <?php if (($p['discount'] ?? 0) > 0): ?>
                                    <span class="badge-sale">-<?= number_format($p['discount'], 0) ?>%</span>
                                <?php endif; ?>

                                <div class="image-wrapper">
                                    <a href="product_details.php?id=<?= $p['product_id'] ?>">
                                        <img src="<?= htmlspecialchars(getProductImage($p)) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                                    </a>

                                    <div class="overlay-actions">
                                        <?php if ($customer_logged_in): ?>
                                            <button type="button" class="overlay-btn" title="Add to Cart"
                                                    onclick="event.stopPropagation(); addToCart(
                                                        <?= $p['product_id'] ?>,
                                                        '<?= addslashes($p['name']) ?>',
                                                        <?= $final ?>,
                                                        '<?= htmlspecialchars(getProductImage($p)) ?>'
                                                    )">
                                                <i class="bi bi-cart-plus"></i>
                                            </button>
                                            <button type="button" class="overlay-btn wishlist-heart <?= $wishlistActiveClass ?>" 
                                                    title="Add to Wishlist"
                                                    data-id="<?= $p['product_id'] ?>"
                                                    data-name="<?= htmlspecialchars($p['name']) ?>"
                                                    data-price="<?= $final ?>"
                                                    data-image="<?= htmlspecialchars(getProductImage($p)) ?>"
                                                    onclick="event.stopPropagation(); toggleWishlist(this)">
                                                <i class="<?= $wishlistIconClasses ?>"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="overlay-btn" title="Add to Cart" data-bs-toggle="modal" data-bs-target="#loginModal" onclick="event.stopPropagation();">
                                                <i class="bi bi-cart-plus"></i>
                                            </button>
                                            <button type="button" class="overlay-btn text-danger" title="Add to Wishlist" data-bs-toggle="modal" data-bs-target="#loginModal" onclick="event.stopPropagation();">
                                                <i class="bi bi-heart"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="category-label text-uppercase"><?= htmlspecialchars($p['category_name']) ?></span>
                                    <?php if (!empty($p['brand_name'])): ?>
                                        <span class="text-muted">•</span>
                                        <?php if (!empty($p['brand_logo'])): ?>
                                            <img src="<?= $BASE_URL . 'uploads/brands/' . htmlspecialchars($p['brand_logo']) ?>" 
                                                 alt="<?= htmlspecialchars($p['brand_name']) ?>" 
                                                 class="align-middle" 
                                                 style="height: 16px; width: auto;"
                                                 onerror="this.style.display='none';">
                                        <?php endif; ?>
                                        <span class="small text-muted"><?= htmlspecialchars($p['brand_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="product-title mb-2">
                                    <a href="product_details.php?id=<?= $p['product_id'] ?>" class="text-decoration-none text-reset">
                                        <?= htmlspecialchars($p['name']) ?>
                                    </a>
                                </h3>
                                
                                <div class="price-wrapper mb-3">
                                    <span class="current-price">₹<?= number_format($final, 2) ?></span>
                                    <?php if (($p['discount'] ?? 0) > 0): ?>
                                        <span class="old-price">₹<?= number_format($p['price'], 2) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (count($products) == 0): ?>
                        <div class="col-12 py-5 text-center">
                            <div class="py-5 bg-light rounded">
                                <i class="bi bi-basket3 display-4 text-muted mb-3"></i>
                                <h5>No products found</h5>
                                <p class="text-muted">Try adjusting your search or filter options.</p>
                                <a href="shop.php" class="btn btn-outline-dark mt-2">Clear Filters</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </form>
</div>

<?php include __DIR__ . "/../components/newsletter.php"; ?>
<?php include __DIR__ . "/../components/footer.php"; ?>


<?php if (!empty($popupAd)): ?>
<div class="modal fade" id="adPopupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <button class="btn-close ms-auto me-2 mt-2" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-4">
                <a href="<?= htmlspecialchars($popupAd['target_url']) ?>" target="_blank" rel="noopener">
                    <img src="<?= htmlspecialchars(formatAdImageUrl($popupAd['image_url'])) ?>" alt="<?= htmlspecialchars($popupAd['title']) ?>" class="img-fluid rounded-3">
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<?php if (!empty($popupAd)): ?>
<script>
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
</script>
<?php endif; ?>

<script>
// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterForm = document.getElementById('filterForm');
    
    if (searchInput) {
        // Submit form on Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                filterForm.submit();
            }
        });
        
        // Optional: Auto-submit after user stops typing (debounced)
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            // Uncomment below for auto-search as user types (with 500ms delay)
            // searchTimeout = setTimeout(function() {
            //     filterForm.submit();
            // }, 500);
        });
    }
});

// Clear search function
function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    const filterForm = document.getElementById('filterForm');
    
    if (searchInput) {
        searchInput.value = '';
        // Preserve category and sort filters when clearing search
        filterForm.submit();
    }
}
</script>

</body>
</html>