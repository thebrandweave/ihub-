<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";
include __DIR__ . "/../includes/header.php";

$review_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch review details
$stmt = $pdo->prepare("
    SELECT 
        r.*,
        u.full_name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone,
        p.product_id,
        p.name AS product_name,
        p.thumbnail,
        p.price,
        p.description AS product_description
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    JOIN products p ON r.product_id = p.product_id
    WHERE r.review_id = ?
");
$stmt->execute([$review_id]);
$review = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$review) {
    header("Location: index.php?error=Review not found");
    exit;
}

// Fetch review images
$imagesStmt = $pdo->prepare("SELECT image FROM review_images WHERE review_id = ? ORDER BY id ASC");
$imagesStmt->execute([$review_id]);
$reviewImages = $imagesStmt->fetchAll(PDO::FETCH_COLUMN);

function getImagePath($filename) {
    global $BASE_URL;
    if (!$filename) return '';
    if (strpos($filename, 'http') === 0) return $filename;
    return $BASE_URL . 'uploads/products/' . $filename;
}

function getReviewImagePath($filename) {
    global $BASE_URL;
    if (!$filename) return '';
    if (strpos($filename, 'http') === 0) return $filename;
    return $BASE_URL . 'uploads/reviews/' . $filename;
}

$message = $_GET['msg'] ?? null;
$error = $_GET['error'] ?? null;
?>

<!-- Page Header -->
<div class="mb-4 md:mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-0">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-800">Review Details</h1>
            <p class="text-xs md:text-sm text-gray-500 mt-1">View complete review information</p>
        </div>
        <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
            <i class="bi bi-arrow-left mr-2"></i> Back to Reviews
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Review Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Review Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-800 mb-2">Review #<?= $review['review_id'] ?></h2>
                    <div class="flex items-center gap-2">
                        <?php
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'approved' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800'
                        ];
                        $color = $statusColors[$review['status']] ?? 'bg-gray-100 text-gray-800';
                        ?>
                        <span class="px-3 py-1 text-sm font-medium rounded-full <?= $color ?>">
                            <?= ucfirst($review['status']) ?>
                        </span>
                        <span class="text-sm text-gray-500">
                            <?= date("d M Y, h:i A", strtotime($review['created_at'])) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Rating -->
            <div class="mb-4">
                <label class="text-sm font-medium text-gray-500 mb-2 block">Rating</label>
                <div class="flex items-center gap-2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <svg class="w-6 h-6 <?= $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>" 
                             fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    <?php endfor; ?>
                    <span class="text-lg font-semibold text-gray-800 ml-2"><?= $review['rating'] ?>/5</span>
                </div>
            </div>

            <!-- Comment -->
            <div class="mb-4">
                <label class="text-sm font-medium text-gray-500 mb-2 block">Comment</label>
                <div class="bg-gray-50 rounded-lg p-4 min-h-[100px]">
                    <?php if (!empty($review['comment'])): ?>
                        <p class="text-gray-800 whitespace-pre-wrap"><?= htmlspecialchars($review['comment']) ?></p>
                    <?php else: ?>
                        <p class="text-gray-400 italic">No comment provided</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Review Images -->
            <?php if (!empty($reviewImages)): ?>
                <div>
                    <label class="text-sm font-medium text-gray-500 mb-2 block">Review Images</label>
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach ($reviewImages as $img): ?>
                            <div class="position-relative">
                                <img src="<?= htmlspecialchars(getReviewImagePath($img)) ?>" 
                                     alt="Review image" 
                                     class="rounded border border-gray-200" 
                                     style="width: 160px; height: 160px; object-fit: cover; cursor: pointer;"
                                     onclick="window.open('<?= htmlspecialchars(getReviewImagePath($img)) ?>', '_blank')">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Product Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Product Information</h3>
            <div class="flex items-start gap-4 mb-4">
                <img src="<?= htmlspecialchars(getImagePath($review['thumbnail'])) ?>" 
                     alt="<?= htmlspecialchars($review['product_name']) ?>" 
                     class="w-20 h-20 object-cover rounded-lg border border-gray-200">
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-800 mb-1"><?= htmlspecialchars($review['product_name']) ?></h4>
                    <p class="text-sm text-gray-500">Product ID: <?= $review['product_id'] ?></p>
                    <p class="text-sm font-medium text-gray-700 mt-1">₹<?= number_format($review['price'], 2) ?></p>
                </div>
            </div>
            <a href="<?= $BASE_URL ?>admin/products/view.php?id=<?= $review['product_id'] ?>" 
               class="text-sm text-blue-600 hover:text-blue-800">
                View Product Details →
            </a>
        </div>

        <!-- Customer Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Customer Information</h3>
            <div class="space-y-2">
                <div>
                    <label class="text-xs text-gray-500">Name</label>
                    <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($review['user_name']) ?></p>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Email</label>
                    <p class="text-sm text-gray-800"><?= htmlspecialchars($review['user_email']) ?></p>
                </div>
                <?php if (!empty($review['user_phone'])): ?>
                    <div>
                        <label class="text-xs text-gray-500">Phone</label>
                        <p class="text-sm text-gray-800"><?= htmlspecialchars($review['user_phone']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Actions</h3>
            <div class="space-y-2">
                <?php if ($review['status'] === 'pending'): ?>
                    <form method="POST" action="update_status.php" class="w-full">
                        <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" 
                                onclick="return confirm('Approve this review?')"
                                class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            <i class="bi bi-check-circle mr-2"></i> Approve Review
                        </button>
                    </form>
                    <form method="POST" action="update_status.php" class="w-full">
                        <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" 
                                onclick="return confirm('Reject this review?')"
                                class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            <i class="bi bi-x-circle mr-2"></i> Reject Review
                        </button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="update_status.php" class="w-full">
                        <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                        <input type="hidden" name="status" value="<?= $review['status'] === 'approved' ? 'rejected' : 'approved' ?>">
                        <button type="submit" 
                                onclick="return confirm('Change review status?')"
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="bi bi-arrow-repeat mr-2"></i> 
                            <?= $review['status'] === 'approved' ? 'Reject Review' : 'Approve Review' ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>

