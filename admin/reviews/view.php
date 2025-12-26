<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

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
        p.price
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

// Image Path Helpers
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

include __DIR__ . "/../includes/header.php";

$message = $_GET['msg'] ?? null;
$error = $_GET['error'] ?? null;
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Review Details</h1>
            <p class="text-sm text-gray-500 mt-1">Review ID: #<?= $review['review_id'] ?></p>
        </div>
        <div class="flex gap-2">
            <a href="index.php" 
               class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition-all">
                <i class="bi bi-arrow-left mr-2"></i> Back to Reviews
            </a>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl p-4 shadow-sm animate-in fade-in duration-500">
        <div class="flex items-center">
            <i class="bi bi-check-circle-fill text-emerald-500 mr-3"></i>
            <p class="text-sm font-bold text-emerald-800"><?= htmlspecialchars($message) ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-r-xl p-4 shadow-sm">
        <div class="flex items-center">
            <i class="bi bi-exclamation-triangle-fill text-red-500 mr-3"></i>
            <p class="text-sm font-bold text-red-800"><?= htmlspecialchars($error) ?></p>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50">
                <h2 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="bi bi-chat-square-quote mr-2 text-red-500"></i>
                    Review Information
                </h2>
            </div>

            <div class="p-6 space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Review Status</label>
                        <div class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl">
                            <?php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-red-100 text-red-700', // Changed to match theme
                                'rejected' => 'bg-gray-100 text-gray-800'
                            ];
                            $colorClass = $statusColors[$review['status']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="inline-flex px-3 py-0.5 rounded-full text-xs font-black uppercase tracking-tighter <?= $colorClass ?>">
                                <?= $review['status'] ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Submitted Date</label>
                        <div class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm font-medium">
                            <?= date("d M Y, h:i A", strtotime($review['created_at'])) ?>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Customer Rating</label>
                    <div class="w-full px-4 py-4 bg-red-50/30 border border-red-100 rounded-2xl flex items-center gap-3">
                        <div class="flex text-red-500">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi <?= $i <= $review['rating'] ? 'bi-star-fill' : 'bi-star' ?> text-xl"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="text-2xl font-black text-gray-800 ml-2"><?= $review['rating'] ?>.0</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Review Content</label>
                    <div class="w-full px-5 py-5 bg-gray-50 border border-gray-200 rounded-2xl text-gray-800 text-sm leading-relaxed min-h-[150px]">
                        <?php if (!empty($review['comment'])): ?>
                            <p class="font-medium italic">"<?= nl2br(htmlspecialchars($review['comment'])) ?>"</p>
                        <?php else: ?>
                            <span class="text-gray-400 italic">No text provided.</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($reviewImages)): ?>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Attached Media</label>
                    <div class="flex flex-wrap gap-4 p-4 bg-gray-50 border border-gray-200 rounded-2xl">
                        <?php foreach ($reviewImages as $img): ?>
                            <div class="relative group">
                                <img src="<?= htmlspecialchars(getReviewImagePath($img)) ?>" 
                                     class="w-28 h-28 object-cover rounded-xl border-2 border-white shadow-sm cursor-pointer hover:scale-105 transition-transform"
                                     onclick="window.open(this.src, '_blank')">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-5">Target Product</h3>
            <div class="flex items-center gap-4 mb-5">
                <img src="<?= htmlspecialchars(getImagePath($review['thumbnail'])) ?>" 
                     class="w-20 h-20 object-cover rounded-xl border border-gray-100 shadow-sm">
                <div class="min-w-0">
                    <p class="font-bold text-gray-800 truncate leading-tight"><?= htmlspecialchars($review['product_name']) ?></p>
                    <p class="text-sm text-red-600 font-black mt-1">₹<?= number_format($review['price'], 2) ?></p>
                </div>
            </div>
            <a href="<?= $BASE_URL ?>admin/products/view.php?id=<?= $review['product_id'] ?>" 
               class="w-full inline-flex justify-center items-center py-2.5 px-4 bg-gray-50 hover:bg-red-50 hover:text-red-600 text-gray-600 text-xs font-black uppercase tracking-widest rounded-xl transition-all border border-gray-100">
                View Product Details
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-5">Customer Profile</h3>
            <div class="space-y-4">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                        <i class="bi bi-person-fill text-lg"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-tighter">Full Name</p>
                        <p class="font-bold text-gray-800 leading-none"><?= htmlspecialchars($review['user_name']) ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                        <i class="bi bi-envelope-fill text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-tighter">Email Address</p>
                        <p class="font-bold text-gray-800 truncate leading-none"><?= htmlspecialchars($review['user_email']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-sm font-bold text-gray-800 mb-6 flex items-center uppercase tracking-widest">
                <i class="bi bi-lightning-fill mr-2 text-red-500"></i>
                Review Control
            </h2>
            
            <div class="space-y-4">
                <?php if ($review['status'] === 'pending'): ?>
                    <form method="POST" action="update_status.php">
                        <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" onclick="return confirm('Publish this review on the store?')"
                                class="w-full py-4 bg-gradient-to-r from-red-600 to-pink-600 text-white font-black text-xs uppercase tracking-widest rounded-2xl shadow-lg shadow-red-200 hover:shadow-xl transition-all transform hover:-translate-y-1 active:scale-95">
                            Approve & Publish
                        </button>
                    </form>
                    <form method="POST" action="update_status.php">
                        <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" onclick="return confirm('Hide this review?')"
                                class="w-full py-3.5 bg-white border border-gray-200 text-gray-600 font-black text-xs uppercase tracking-widest rounded-2xl hover:bg-gray-50 transition-all">
                            Reject Review
                        </button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="update_status.php">
                        <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                        <input type="hidden" name="status" value="<?= $review['status'] === 'approved' ? 'rejected' : 'approved' ?>">
                        <button type="submit" 
                                class="w-full py-4 bg-gray-800 text-white font-black text-xs uppercase tracking-widest rounded-2xl transition-all hover:bg-black">
                            Set as <?= $review['status'] === 'approved' ? 'Rejected' : 'Approved' ?>
                        </button>
                    </form>
                <?php endif; ?>

                <div class="pt-4 mt-2 border-t border-gray-100">
                    <form method="POST" action="delete.php" onsubmit="return confirm('PERMANENTLY DELETE? This cannot be undone.')">
                        <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                        <button type="submit" class="w-full py-3 text-red-500 font-black text-[10px] uppercase tracking-[0.2em] rounded-xl hover:bg-red-50 transition-all flex items-center justify-center gap-2">
                            <i class="bi bi-trash3-fill"></i>
                            Delete Review Permanently
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>