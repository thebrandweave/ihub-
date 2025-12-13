<?php
require_once __DIR__ . '/../auth/customer_auth.php';
require_once __DIR__ . '/../config/config.php';

$user_id = $_SESSION['customer_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Verify order belongs to user and is delivered
$orderStmt = $pdo->prepare("
    SELECT order_id, order_number, status 
    FROM orders 
    WHERE order_id = ? AND user_id = ? AND status = 'delivered'
");
$orderStmt->execute([$order_id, $user_id]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: orders.php?error=invalid_order");
    exit;
}

// Get order items
$itemsStmt = $pdo->prepare("
    SELECT 
        oi.product_id,
        p.name,
        p.thumbnail,
        (
            SELECT image_url 
            FROM product_images 
            WHERE product_id = p.product_id AND is_primary = 1 
            LIMIT 1
        ) AS primary_image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$itemsStmt->execute([$order_id]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
$productNameMap = [];
foreach ($items as $it) {
    $productNameMap[$it['product_id']] = $it['name'] ?? ('Product #' . $it['product_id']);
}

// Get already reviewed products (any status - once reviewed, can only edit)
$productIds = array_column($items, 'product_id');
$reviewedProducts = [];
$existingReviews = [];
if (!empty($productIds)) {
    $reviewedStmt = $pdo->prepare("
        SELECT product_id, review_id, rating, comment 
        FROM reviews 
        WHERE user_id = ? 
        AND product_id IN (" . implode(',', array_fill(0, count($productIds), '?')) . ")
    ");
    $reviewedStmt->execute(array_merge([$user_id], $productIds));
    $reviewedData = $reviewedStmt->fetchAll(PDO::FETCH_ASSOC);
    $reviewedProducts = array_column($reviewedData, 'product_id');
    
    // Create a map of existing reviews for editing
    foreach ($reviewedData as $rev) {
        $existingReviews[$rev['product_id']] = $rev;
    }
}

// Separate items into new reviews and existing reviews to edit
$itemsToReview = array_filter($items, function($item) use ($reviewedProducts) {
    return !in_array($item['product_id'], $reviewedProducts);
});
$itemsToEdit = array_filter($items, function($item) use ($reviewedProducts) {
    return in_array($item['product_id'], $reviewedProducts);
});

// Image upload helper functions
function uploadReviewImage($file, $uploadDir) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return null; // No file is okay
        }
        throw new Exception("File upload error: " . $file['error']);
    }
    
    // Check file size (5MB max)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception("File size exceeds 5MB");
    }
    
    // Check file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes, true)) {
        throw new Exception("Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.");
    }
    
    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'review_' . uniqid() . '_' . time() . '.' . $ext;
    
    // Create upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $targetPath = $uploadDir . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Failed to upload image");
    }
    
    return $filename;
}

function uploadReviewImages($files, $uploadDir) {
    $filenames = [];
    // Normalize the multiple file structure
    $count = count($files['name']);
    if ($count > 5) {
        throw new Exception("You can upload up to 5 images");
    }
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $file = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i],
        ];
        $filenames[] = uploadReviewImage($file, $uploadDir);
    }
    return $filenames;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reviews'])) {
    $pdo->beginTransaction();
    $uploadDir = __DIR__ . '/../uploads/reviews';
    // Fetch admins once for notifications
    $adminIds = $pdo->query("SELECT user_id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
    $adminNotifStmt = null;
    
    try {
        foreach ($_POST['reviews'] as $product_id => $reviewData) {
            $product_id = (int)$product_id;
            $rating = isset($reviewData['rating']) ? (int)$reviewData['rating'] : 0;
            $comment = trim($reviewData['comment'] ?? '');
            
            // Validate rating
            if ($rating < 1 || $rating > 5) {
                throw new Exception("Invalid rating for product ID: $product_id");
            }
            
            // Verify product was in this order
            $verifyStmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM order_items 
                WHERE order_id = ? AND product_id = ?
            ");
            $verifyStmt->execute([$order_id, $product_id]);
            if ($verifyStmt->fetchColumn() == 0) {
                throw new Exception("Product not found in order");
            }
            
            // Check if review already exists - if so, update it instead of creating new
            $existingStmt = $pdo->prepare("
                SELECT review_id 
                FROM reviews 
                WHERE user_id = ? AND product_id = ?
            ");
            $existingStmt->execute([$user_id, $product_id]);
            $existingReviewId = $existingStmt->fetchColumn();
            
            if ($existingReviewId) {
                // Update existing review
                $updateStmt = $pdo->prepare("
                    UPDATE reviews 
                    SET rating = ?, comment = ?, status = 'pending', created_at = NOW()
                    WHERE review_id = ?
                ");
                $updateStmt->execute([$rating, $comment, $existingReviewId]);
                $reviewId = (int)$existingReviewId;
            } else {
                // Insert new review
                $insertStmt = $pdo->prepare("
                    INSERT INTO reviews (user_id, product_id, rating, comment, status)
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $insertStmt->execute([$user_id, $product_id, $rating, $comment]);
                $reviewId = (int)$pdo->lastInsertId();
            }

            // Handle image uploads (multiple)
            // Delete old images when updating
            if ($existingReviewId) {
                // Get old images
                $oldImagesStmt = $pdo->prepare("SELECT image FROM review_images WHERE review_id = ?");
                $oldImagesStmt->execute([$reviewId]);
                $oldImages = $oldImagesStmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Delete old images from database
                $deleteImgStmt = $pdo->prepare("DELETE FROM review_images WHERE review_id = ?");
                $deleteImgStmt->execute([$reviewId]);
                
                // Delete old images from filesystem
                foreach ($oldImages as $oldImg) {
                    $oldImgPath = $uploadDir . '/' . $oldImg;
                    if (file_exists($oldImgPath)) {
                        @unlink($oldImgPath);
                    }
                }
            }
            
            if (isset($_FILES['reviews']['name'][$product_id]['images'])) {
                $files = [
                    'name' => $_FILES['reviews']['name'][$product_id]['images'],
                    'type' => $_FILES['reviews']['type'][$product_id]['images'],
                    'tmp_name' => $_FILES['reviews']['tmp_name'][$product_id]['images'],
                    'error' => $_FILES['reviews']['error'][$product_id]['images'],
                    'size' => $_FILES['reviews']['size'][$product_id]['images']
                ];
                $uploaded = uploadReviewImages($files, $uploadDir);
                if (!empty($uploaded)) {
                    $imgStmt = $pdo->prepare("INSERT INTO review_images (review_id, image) VALUES (?, ?)");
                    foreach ($uploaded as $img) {
                        $imgStmt->execute([$reviewId, $img]);
                    }
                }
            }

            // Notify admins about new review
            if (!empty($adminIds)) {
                if ($adminNotifStmt === null) {
                    $adminNotifStmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, type, title, message, target_url)
                        VALUES (?, 'system_alert', ?, ?, ?)
                    ");
                }
                $productName = $productNameMap[$product_id] ?? ('Product #' . $product_id);
                $title = "New review submitted";
                $message = "'{$productName}' received a new review (rating {$rating}/5). Awaiting approval.";
                $targetUrl = $BASE_URL . "admin/reviews/view.php?id=" . $reviewId;
                foreach ($adminIds as $adminId) {
                    $adminNotifStmt->execute([$adminId, $title, $message, $targetUrl]);
                }
            }
        }
        
        $pdo->commit();
        header("Location: orders.php?msg=review_submitted");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error submitting review: " . $e->getMessage();
    }
}

// Helper function
function getOrderItemImage(array $item): string {
    global $BASE_URL;
    if (!empty($item['primary_image'])) {
        return $BASE_URL . 'uploads/products/' . ltrim($item['primary_image'], '/');
    }
    if (!empty($item['thumbnail'])) {
        return $BASE_URL . 'uploads/products/' . ltrim($item['thumbnail'], '/');
    }
    return 'https://via.placeholder.com/80';
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Write Review — iHub Electronics</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body{font-family:Arial,sans-serif;color:#0f172a;background:#ffffff;}
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
  .profile-card{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:1.5rem;margin-bottom:1rem;}
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
  .star-rating{cursor:pointer;font-size:1.5rem;color:#ddd;transition:color 0.2s;}
  .star-rating:hover,.star-rating.active{color:#ffc107;}
  .star-rating.selected{color:#ffc107;}
</style>
</head>
<body>

<?php include __DIR__ . "/../components/navbar.php"; ?>

<section class="profile-hero">
  <div class="container">
    <h1 class="fw-bold">Write Review</h1>
    <p class="text-secondary mb-0">Share your experience with products you've purchased.</p>
  </div>
</section>

<section class="py-4">
  <div class="container">
    <div class="row">
      <aside class="col-lg-3">
        <?php include __DIR__ . "/sidebar.php"; ?>
      </aside>

      <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h4 class="mb-0">Write Review</h4>
          <a href="orders.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Orders
          </a>
        </div>

        <?php if (isset($error)): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($itemsToReview) && empty($itemsToEdit)): ?>
          <div class="profile-card text-center text-muted">
            <i class="bi bi-check-circle fs-1 mb-2 text-success"></i>
            <p class="mb-1">All products from this order have been reviewed.</p>
            <a href="orders.php" class="btn btn-primary btn-sm mt-3">View Orders</a>
          </div>
        <?php else: ?>
          <div class="profile-card mb-3">
            <p class="text-muted mb-0">
              <strong>Order:</strong> <?= htmlspecialchars($order['order_number'] ?? 'Order #' . $order['order_id']) ?>
            </p>
            <p class="text-muted mb-0">
              <strong>Status:</strong> <span class="text-success">Delivered</span>
            </p>
          </div>

          <form method="POST" id="reviewForm" enctype="multipart/form-data">
            <?php if (!empty($itemsToReview)): ?>
              <h5 class="mb-3">Write New Reviews</h5>
            <?php endif; ?>
            <?php foreach ($itemsToReview as $item): ?>
              <div class="profile-card mb-3">
                <div class="d-flex gap-3 mb-3">
                  <img src="<?= htmlspecialchars(getOrderItemImage($item)) ?>" 
                       alt="<?= htmlspecialchars($item['name']) ?>" 
                       width="80" height="80"
                       class="rounded border object-fit-cover">
                  <div class="flex-grow-1">
                    <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                    <p class="text-muted small mb-0">Product ID: <?= $item['product_id'] ?></p>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Rating <span class="text-danger">*</span></label>
                  <div class="star-rating-container" data-product-id="<?= $item['product_id'] ?>">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                      <i class="bi bi-star-fill star-rating" 
                         data-rating="<?= $i ?>" 
                         onclick="selectRating(<?= $item['product_id'] ?>, <?= $i ?>)"></i>
                    <?php endfor; ?>
                  </div>
                  <input type="hidden" name="reviews[<?= $item['product_id'] ?>][rating]" 
                         id="rating_<?= $item['product_id'] ?>" required>
                </div>

                <div class="mb-3">
                  <label for="comment_<?= $item['product_id'] ?>" class="form-label fw-semibold">
                    Your Review (Optional)
                  </label>
                  <textarea class="form-control" 
                            name="reviews[<?= $item['product_id'] ?>][comment]" 
                            id="comment_<?= $item['product_id'] ?>" 
                            rows="3" 
                            placeholder="Share your experience with this product..."></textarea>
                </div>

                <div class="mb-0">
                  <label for="image_<?= $item['product_id'] ?>" class="form-label fw-semibold">
                    Add Photos (Optional)
                  </label>
                  <input type="file" 
                         class="form-control" 
                         name="reviews[<?= $item['product_id'] ?>][images][]" 
                         id="image_<?= $item['product_id'] ?>" 
                         accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                         multiple>
                  <small class="text-muted">Up to 5 images, each max 5MB. Formats: JPEG, PNG, GIF, WebP</small>
                  <div class="mt-2" id="preview_<?= $item['product_id'] ?>"></div>
                </div>
              </div>
            <?php endforeach; ?>
            
            <?php if (!empty($itemsToEdit)): ?>
              <h5 class="mb-3 mt-4">Edit Existing Reviews</h5>
              <?php foreach ($itemsToEdit as $item): ?>
                <?php 
                  $existingReview = $existingReviews[$item['product_id']] ?? null;
                  $existingRating = $existingReview['rating'] ?? 0;
                  $existingComment = $existingReview['comment'] ?? '';
                ?>
                <div class="profile-card mb-3">
                  <div class="d-flex gap-3 mb-3">
                    <img src="<?= htmlspecialchars(getOrderItemImage($item)) ?>" 
                         alt="<?= htmlspecialchars($item['name']) ?>" 
                         width="80" height="80"
                         class="rounded border object-fit-cover">
                    <div class="flex-grow-1">
                      <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                      <p class="text-muted small mb-0">Product ID: <?= $item['product_id'] ?></p>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold">Rating <span class="text-danger">*</span></label>
                    <div class="star-rating-container" data-product-id="<?= $item['product_id'] ?>">
                      <?php for ($i = 5; $i >= 1; $i--): ?>
                        <i class="bi bi-star-fill star-rating <?= $i <= $existingRating ? 'selected active' : '' ?>" 
                           data-rating="<?= $i ?>" 
                           onclick="selectRating(<?= $item['product_id'] ?>, <?= $i ?>)"></i>
                      <?php endfor; ?>
                    </div>
                    <input type="hidden" name="reviews[<?= $item['product_id'] ?>][rating]" 
                           id="rating_<?= $item['product_id'] ?>" value="<?= $existingRating ?>" required>
                  </div>

                  <div class="mb-3">
                    <label for="comment_<?= $item['product_id'] ?>" class="form-label fw-semibold">
                      Your Review (Optional)
                    </label>
                    <textarea class="form-control" 
                              name="reviews[<?= $item['product_id'] ?>][comment]" 
                              id="comment_<?= $item['product_id'] ?>" 
                              rows="3" 
                              placeholder="Share your experience with this product..."><?= htmlspecialchars($existingComment) ?></textarea>
                  </div>

                  <div class="mb-0">
                    <label for="image_<?= $item['product_id'] ?>" class="form-label fw-semibold">
                      Add Photos (Optional)
                    </label>
                    <input type="file" 
                           class="form-control" 
                           name="reviews[<?= $item['product_id'] ?>][images][]" 
                           id="image_<?= $item['product_id'] ?>" 
                           accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                           multiple>
                    <small class="text-muted">Up to 5 images, each max 5MB. Formats: JPEG, PNG, GIF, WebP. Uploading new images will replace existing ones.</small>
                    <div class="mt-2" id="preview_<?= $item['product_id'] ?>"></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i> Submit Review
              </button>
              <a href="orders.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . "/../components/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function selectRating(productId, rating) {
    // Set hidden input
    document.getElementById('rating_' + productId).value = rating;
    
    // Update star display
    const container = document.querySelector(`[data-product-id="${productId}"]`);
    const stars = container.querySelectorAll('.star-rating');
    stars.forEach((star, index) => {
        const starRating = parseInt(star.getAttribute('data-rating'));
        if (starRating <= rating) {
            star.classList.add('selected', 'active');
        } else {
            star.classList.remove('selected', 'active');
        }
    });
}

// Image previews (multiple)
<?php foreach (array_merge($itemsToReview, $itemsToEdit ?? []) as $item): ?>
document.getElementById('image_<?= $item['product_id'] ?>').addEventListener('change', function(e) {
    const files = Array.from(e.target.files || []);
    const preview = document.getElementById('preview_<?= $item['product_id'] ?>');
    preview.innerHTML = '';

    const maxFiles = 5;
    if (files.length > maxFiles) {
        alert('You can upload up to 5 images.');
        e.target.value = '';
        return;
    }

    files.forEach(file => {
        if (file.size > 5 * 1024 * 1024) {
            alert('File size exceeds 5MB');
            e.target.value = '';
            preview.innerHTML = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(ev) {
            const img = document.createElement('img');
            img.src = ev.target.result;
            img.className = 'img-thumbnail mt-2 me-2';
            img.style.maxWidth = '140px';
            img.style.maxHeight = '140px';
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
});
<?php endforeach; ?>

// Form validation
document.getElementById('reviewForm').addEventListener('submit', function(e) {
    const items = <?= json_encode(array_column(array_merge($itemsToReview, $itemsToEdit ?? []), 'product_id')) ?>;
    let allRated = true;
    
    items.forEach(productId => {
        const rating = document.getElementById('rating_' + productId).value;
        if (!rating || rating < 1 || rating > 5) {
            allRated = false;
        }
    });
    
    if (!allRated) {
        e.preventDefault();
        alert('Please provide a rating for all products.');
        return false;
    }
});
</script>

</body>
</html>

