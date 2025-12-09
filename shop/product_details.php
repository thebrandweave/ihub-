<?php
require_once __DIR__ . '/../auth/customer_auth.php';
require_once __DIR__ . '/../config/config.php';

// 1. Get Product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 2. Fetch Product Details with Brand
$stmt = $pdo->prepare("
    SELECT 
        p.*, 
        c.name AS category_name,
        b.name AS brand_name,
        b.logo AS brand_logo
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE p.product_id = ? AND p.status = 'active'
");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if not found
if (!$product) {
    header("Location: shop.php");
    exit;
}

// 3. Fetch Product Images
$imgStmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC");
$imgStmt->execute([$product_id]);
$images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Fetch Product Attributes
$attrStmt = $pdo->prepare("SELECT attribute, value FROM product_attributes WHERE product_id = ? ORDER BY attribute ASC");
$attrStmt->execute([$product_id]);
$attributes = $attrStmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Fetch Approved Reviews
$reviewsStmt = $pdo->prepare("
    SELECT 
        r.review_id,
        r.rating,
        r.comment,
        r.image,
        r.created_at,
        u.full_name AS user_name
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.product_id = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC
    LIMIT 10
");
$reviewsStmt->execute([$product_id]);
$reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch review images for these reviews
$reviewImagesMap = [];
if (!empty($reviews)) {
    $reviewIds = array_column($reviews, 'review_id');
    $placeholders = implode(',', array_fill(0, count($reviewIds), '?'));
    $imgsStmt = $pdo->prepare("SELECT review_id, image FROM review_images WHERE review_id IN ($placeholders) ORDER BY id ASC");
    $imgsStmt->execute($reviewIds);
    $allImages = $imgsStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allImages as $ri) {
        $reviewImagesMap[$ri['review_id']][] = $ri['image'];
    }
}

// Helper for review image path
function getReviewImageUrl($filename) {
    global $BASE_URL;
    if (!$filename) return '';
    if (strpos($filename, 'http') === 0) return $filename;
    return $BASE_URL . 'uploads/reviews/' . $filename;
}

// Calculate average rating
$avgRatingStmt = $pdo->prepare("
    SELECT 
        AVG(rating) AS avg_rating,
        COUNT(*) AS total_reviews
    FROM reviews
    WHERE product_id = ? AND status = 'approved'
");
$avgRatingStmt->execute([$product_id]);
$ratingStats = $avgRatingStmt->fetch(PDO::FETCH_ASSOC);
$avgRating = $ratingStats['avg_rating'] ? round($ratingStats['avg_rating'], 1) : 0;
$totalReviews = (int)$ratingStats['total_reviews'];

// Fallback image if empty
if (empty($images)) {
    $images[] = ['image_url' => 'default_product.png']; // Ensure you have a logic for this or use placeholder
}

// Helper for image path
function getImgUrl($filename) {
    global $BASE_URL;
    if (strpos($filename, 'http') === 0) return $filename;
    return $BASE_URL . 'uploads/products/' . $filename;
}

// Helper for brand logo path
function getBrandLogoUrl($filename) {
    global $BASE_URL;
    if (!$filename) return '';
    if (strpos($filename, 'http') === 0) return $filename;
    return $BASE_URL . 'uploads/brands/' . $filename;
}

// Calculate price
$final_price = $product['price'];
if ($product['discount'] > 0) {
    $final_price = $product['price'] - ($product['price'] * $product['discount'] / 100);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($product['name']) ?> — iHub Electronics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        /* Shared Theme Variables */
        :root {
            --text-color: #1a1a1a;
            --text-muted: #555;
            --border-color: #e5e5e5;
            --accent-color: #e3000e;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: var(--text-color);
            background-color: #ffffff;
        }

        /* Breadcrumbs */
        .breadcrumb-item a {
            color: #888;
            text-decoration: none;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .breadcrumb-item.active {
            color: var(--text-color);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Image Gallery */
        .main-image-container {
            background-color: #f5f5f5;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 15px;
            cursor: zoom-in;
        }
        
        .main-image-container img {
            width: 100%;
            height: auto;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .thumbnail-strip {
            display: flex;
            gap: 10px;
            overflow-x: auto;
        }

        .thumb-btn {
            width: 80px;
            height: 80px;
            border: 2px solid transparent;
            border-radius: 6px;
            background-color: #f5f5f5;
            padding: 5px;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        
        .thumb-btn.active {
            border-color: var(--text-color);
        }
        
        .thumb-btn img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            mix-blend-mode: multiply;
        }

        /* Product Info (Sticky Side) */
        .product-info-sticky {
            position: -webkit-sticky;
            position: sticky;
            top: 100px; /* Offset for navbar */
        }

        .product-category {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
            margin-bottom: 0.5rem;
        }

        .product-title {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 1rem;
        }

        .price-area {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .text-sale { color: var(--accent-color); font-weight: 700; }
        .text-strike { text-decoration: line-through; color: #999; font-size: 1rem; margin-left: 10px; }

        /* Quantity Selector */
        .qty-container {
            display: flex;
            align-items: center;
            border: 1px solid var(--text-color);
            border-radius: 4px;
            width: 140px;
            height: 48px;
            margin-right: 15px;
        }

        .qty-btn {
            width: 40px;
            height: 100%;
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: var(--text-color);
        }

        .qty-input {
            width: 60px;
            height: 100%;
            border: none;
            text-align: center;
            font-size: 1rem;
            font-weight: 600;
            appearance: none;
            -moz-appearance: textfield;
        }
        .qty-input:focus { outline: none; }

        /* Action Buttons */
        .btn-add-cart {
            background-color: transparent;
            border: 1px solid var(--text-color);
            color: var(--text-color);
            height: 48px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }

        .btn-add-cart:hover {
            background-color: var(--text-color);
            color: #fff;
        }

        .btn-buy-now {
            background-color: var(--text-color);
            color: #fff;
            border: 1px solid var(--text-color);
            height: 48px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-buy-now:hover {
            opacity: 0.9;
        }

        /* Accordion Customization */
        .accordion-item {
            border: none;
            border-bottom: 1px solid #eee;
        }
        
        .accordion-button {
            background: none;
            box-shadow: none;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            padding: 1.2rem 0;
            color: var(--text-color);
        }

        .accordion-button:not(.collapsed) {
            color: var(--text-color);
            background: none;
            box-shadow: none;
        }

        .accordion-button:focus { box-shadow: none; }
        
        .accordion-body {
            padding: 0 0 1.5rem 0;
            color: var(--text-muted);
            line-height: 1.6;
        }
    </style>
</head>
<body>

<?php include __DIR__ . "/../components/navbar.php"; ?>

<div class="container py-5">
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
            <?php if(!empty($product['category_name'])): ?>
                <li class="breadcrumb-item"><a href="shop.php?category=<?= $product['category_id'] ?>"><?= htmlspecialchars($product['category_name']) ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        
        <div class="col-lg-7">
            <div class="main-image-container">
                <img id="mainImage" src="<?= getImgUrl($images[0]['image_url']) ?>" alt="Product Image">
            </div>

            <?php if (count($images) > 1): ?>
                <div class="thumbnail-strip">
                    <?php foreach ($images as $index => $img): ?>
                        <div class="thumb-btn <?= $index === 0 ? 'active' : '' ?>" 
                             onclick="changeImage('<?= getImgUrl($img['image_url']) ?>', this)">
                            <img src="<?= getImgUrl($img['image_url']) ?>" alt="Thumbnail">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-5">
            <div class="product-info-sticky">
                
                <div class="product-category">
                    <?= htmlspecialchars($product['category_name'] ?? 'General') ?>
                    <?php if ($product['brand_name']): ?>
                        <span class="mx-2">•</span>
                        <?php if ($product['brand_logo']): ?>
                            <img src="<?= getBrandLogoUrl($product['brand_logo']) ?>" 
                                 alt="<?= htmlspecialchars($product['brand_name']) ?>" 
                                 class="d-inline-block align-middle" 
                                 style="height: 20px; width: auto; margin-right: 5px;"
                                 onerror="this.style.display='none';">
                        <?php endif; ?>
                        <span><?= htmlspecialchars($product['brand_name']) ?></span>
                    <?php endif; ?>
                </div>
                <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>

                <div class="price-area">
                    <?php if ($product['discount'] > 0): ?>
                        <span class="text-sale">₹<?= number_format($final_price, 2) ?></span>
                        <span class="text-strike">₹<?= number_format($product['price'], 2) ?></span>
                        <span class="badge bg-danger ms-2" style="font-size:0.7rem; vertical-align:middle;">SALE</span>
                    <?php else: ?>
                        <span>₹<?= number_format($product['price'], 2) ?></span>
                    <?php endif; ?>
                </div>

                <p class="text-muted mb-4">
                    <?= nl2br(htmlspecialchars($product['description'])) ?>
                </p>

                <?php if (!empty($attributes)): ?>
                <div class="mb-4 p-3 border rounded">
                    <h6 class="small text-uppercase fw-bold text-muted mb-3">Specifications</h6>
                    <div class="row g-2">
                        <?php foreach ($attributes as $attr): ?>
                            <div class="col-6">
                                <div class="small">
                                    <span class="text-muted"><?= htmlspecialchars($attr['attribute']) ?>:</span>
                                    <strong class="ms-1"><?= htmlspecialchars($attr['value']) ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <form id="addToCartForm" class="mb-4">
                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                    
                    <div class="mb-3">
                        <label class="form-label small text-uppercase fw-bold text-muted">Quantity</label>
                        <div class="d-flex">
                            <div class="qty-container">
                                <button type="button" class="qty-btn" onclick="updateQty(-1)">-</button>
                                <input type="number" name="quantity" id="qtyInput" value="1" min="1" class="qty-input">
                                <button type="button" class="qty-btn" onclick="updateQty(1)">+</button>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <?php if ($customer_logged_in): ?>
                            <button type="button" class="btn btn-add-cart" onclick="addToCart()">
                                Add to Cart
                            </button>
                            <button type="button" class="btn btn-buy-now">
                                Buy it now
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-add-cart" data-bs-toggle="modal" data-bs-target="#loginModal">
                                Add to Cart
                            </button>
                            <button type="button" class="btn btn-buy-now" data-bs-toggle="modal" data-bs-target="#loginModal">
                                Buy it now
                            </button>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="accordion" id="productAccordion">
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                <i class="bi bi-info-circle me-2"></i> Product Details
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#productAccordion">
                            <div class="accordion-body">
                                <ul class="list-unstyled text-muted small">
                                    <li><strong>SKU:</strong> <?= $product['product_id'] ?>-GEN</li>
                                    <li><strong>Stock:</strong> <?= $product['stock'] > 0 ? 'In Stock (' . $product['stock'] . ' units)' : 'Out of Stock' ?></li>
                                    <?php if ($product['brand_name']): ?>
                                        <li>
                                            <strong>Brand:</strong> 
                                            <?php if ($product['brand_logo']): ?>
                                                <img src="<?= getBrandLogoUrl($product['brand_logo']) ?>" 
                                                     alt="<?= htmlspecialchars($product['brand_name']) ?>" 
                                                     class="d-inline-block align-middle ms-1" 
                                                     style="height: 16px; width: auto;"
                                                     onerror="this.style.display='none';">
                                            <?php endif; ?>
                                            <?= htmlspecialchars($product['brand_name']) ?>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (!empty($attributes)): ?>
                                        <?php foreach ($attributes as $attr): ?>
                                            <li><strong><?= htmlspecialchars($attr['attribute']) ?>:</strong> <?= htmlspecialchars($attr['value']) ?></li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                <i class="bi bi-truck me-2"></i> Shipping & Returns
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#productAccordion">
                            <div class="accordion-body small">
                                We offer free standard shipping on all orders over ₹500. Returns are accepted within 30 days of purchase provided the item is in original condition.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                <i class="bi bi-star me-2"></i> Reviews 
                                <?php if ($totalReviews > 0): ?>
                                    <span class="badge bg-primary ms-2"><?= $totalReviews ?></span>
                                <?php endif; ?>
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#productAccordion">
                            <div class="accordion-body">
                                <?php if ($totalReviews > 0): ?>
                                    <!-- Average Rating -->
                                    <div class="mb-4 pb-3 border-bottom">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="me-3">
                                                <span class="h3 mb-0 fw-bold"><?= number_format($avgRating, 1) ?></span>
                                                <span class="text-muted">/ 5</span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-1">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="bi bi-star-fill <?= $i <= round($avgRating) ? 'text-warning' : 'text-secondary' ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <small class="text-muted">Based on <?= $totalReviews ?> review<?= $totalReviews > 1 ? 's' : '' ?></small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reviews List -->
                                    <div class="reviews-list">
                                        <?php foreach ($reviews as $review): ?>
                                            <div class="mb-4 pb-3 border-bottom">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <strong class="d-block"><?= htmlspecialchars($review['user_name']) ?></strong>
                                                        <small class="text-muted"><?= date("d M Y", strtotime($review['created_at'])) ?></small>
                                                    </div>
                                                    <div class="d-flex">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="bi bi-star-fill <?= $i <= $review['rating'] ? 'text-warning' : 'text-secondary' ?>" style="font-size: 0.9rem;"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                                <?php if (!empty($review['comment'])): ?>
                                                    <p class="mb-2 text-muted small"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                                                <?php endif; ?>
                                                <?php 
                                                    $imgs = $reviewImagesMap[$review['review_id']] ?? [];
                                                ?>
                                                <?php if (!empty($imgs)): ?>
                                                    <div class="mt-2 d-flex flex-wrap gap-2">
                                                        <?php foreach ($imgs as $img): ?>
                                                            <img src="<?= htmlspecialchars(getReviewImageUrl($img)) ?>" 
                                                                 alt="Review image" 
                                                                 class="img-thumbnail rounded" 
                                                                 style="max-width: 160px; max-height: 160px; cursor: pointer;"
                                                                 onclick="window.open('<?= htmlspecialchars(getReviewImageUrl($img)) ?>', '_blank')"
                                                                 title="Click to view full size">
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted mb-0">No reviews yet. Be the first to review this product!</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                
                </div>

                <div class="mt-4 pt-3 border-top">
                    <span class="small text-muted fw-bold text-uppercase me-2">Share:</span>
                    <a href="#" class="text-muted me-3"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-muted me-3"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="text-muted me-3"><i class="bi bi-pinterest"></i></a>
                </div>

            </div>
        </div>
    </div>

    <div class="mt-5 pt-5">
        <h3 class="fw-bold mb-4 text-center">You May Also Like</h3>
        <p class="text-center text-muted">Related products loading...</p>
    </div>

</div>

<?php include __DIR__ . "/../components/newsletter.php"; ?>
<?php include __DIR__ . "/../components/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // 1. Image Gallery Logic
    function changeImage(src, element) {
        document.getElementById('mainImage').src = src;
        
        // Remove active class from all thumbs
        document.querySelectorAll('.thumb-btn').forEach(btn => btn.classList.remove('active'));
        // Add active class to clicked thumb
        element.classList.add('active');
    }

    // 2. Quantity Logic
    function updateQty(change) {
        const input = document.getElementById('qtyInput');
        let newVal = parseInt(input.value) + change;
        if (newVal < 1) newVal = 1;
        // Optional: Check max stock
        // if (newVal > <?= $product['stock'] ?>) newVal = <?= $product['stock'] ?>;
        input.value = newVal;
    }

    // 3. Add to Cart Logic
    function addToCart() {
        const productId = document.querySelector('input[name="product_id"]').value;
        const quantity = document.getElementById('qtyInput').value;

        // Visual Feedback
        const btn = document.querySelector('.btn-add-cart');
        const originalText = btn.innerHTML;
        btn.innerHTML = 'Adding...';
        
        // Simulate AJAX request
        // fetch('/api/add_to_cart.php', { ... })
        setTimeout(() => {
            alert(`Added ${quantity} item(s) to cart!`);
            btn.innerHTML = originalText;
        }, 500);
    }
</script>

</body>
</html>