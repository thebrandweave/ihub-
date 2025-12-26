<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

// Get filter parameters
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? ''; 

$sql = "
    SELECT 
        r.review_id, r.rating, r.comment, r.status, r.created_at,
        u.full_name AS user_name, u.email AS user_email,
        p.product_id, p.name AS product_name, p.thumbnail,
        (SELECT image FROM review_images ri WHERE ri.review_id = r.review_id ORDER BY ri.id ASC LIMIT 1) AS first_image,
        (SELECT COUNT(*) FROM review_images ri WHERE ri.review_id = r.review_id) AS image_count
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    JOIN products p ON r.product_id = p.product_id
    WHERE 1=1
";

$params = [];
if ($searchTerm) {
    $sql .= " AND (p.name LIKE :search_term OR u.full_name LIKE :search_term OR u.email LIKE :search_term)";
    $params[':search_term'] = '%' . $searchTerm . '%';
}
if ($statusFilter && in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
    $sql .= " AND r.status = :status_filter";
    $params[':status_filter'] = $statusFilter;
}
$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats logic
$totalReviews = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$pendingReviews = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn();

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
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Review Management</h1>
            <p class="text-sm text-gray-500 mt-1">Moderate customer feedback and ratings</p>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl p-4 shadow-sm">
    <p class="text-sm font-bold text-emerald-800"><i class="bi bi-check-circle-fill mr-2"></i><?= htmlspecialchars($message) ?></p>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Total Feedback</p>
        <p class="text-2xl font-black text-gray-900"><?= number_format($totalReviews) ?></p>
        <div class="mt-2 w-8 h-8 bg-red-50 text-red-600 rounded-lg flex items-center justify-center">
            <i class="bi bi-chat-right-quote-fill"></i>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Pending Approval</p>
        <p class="text-2xl font-black text-red-600"><?= number_format($pendingReviews) ?></p>
        <div class="mt-2 w-8 h-8 bg-orange-50 text-orange-600 rounded-lg flex items-center justify-center">
            <i class="bi bi-hourglass-split"></i>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Search Records</label>
            <input type="text" name="search" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Search product, customer or email..." 
                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none transition-all">
        </div>
        <div class="w-full md:w-48">
            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Filter Status</label>
            <select name="status" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-red-500/20">
                <option value="">All Reviews</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-red-600 to-pink-600 text-white font-black text-xs uppercase tracking-widest rounded-xl shadow-lg shadow-red-100 hover:shadow-xl transition-all">
                Filter
            </button>
            <?php if ($searchTerm || $statusFilter): ?>
                <a href="index.php" class="px-6 py-2.5 bg-gray-100 text-gray-600 font-black text-xs uppercase tracking-widest rounded-xl hover:bg-gray-200 transition-all">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-gray-50/50 border-b border-gray-100">
                <tr>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Product Info</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Customer</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Rating</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($reviews)): ?>
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">No reviews found matching your criteria.</td></tr>
                <?php else: foreach ($reviews as $review): ?>
                    <tr class="hover:bg-red-50/20 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <img src="<?= htmlspecialchars(getImagePath($review['thumbnail'])) ?>" class="w-12 h-12 rounded-lg object-cover border border-gray-100 shadow-sm">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($review['product_name']) ?></p>
                                    <p class="text-[10px] text-gray-400"><?= date("d M Y", strtotime($review['created_at'])) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($review['user_name']) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($review['user_email']) ?></p>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-1 text-red-500">
                                <span class="text-sm font-black mr-1"><?= $review['rating'] ?></span>
                                <i class="bi bi-star-fill text-xs"></i>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <?php
                            $st = $review['status'];
                            $cls = ($st == 'approved') ? 'bg-red-100 text-red-700' : (($st == 'pending') ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600');
                            ?>
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter <?= $cls ?>">
                                <?= $st ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="view.php?id=<?= $review['review_id'] ?>" class="p-2 bg-gray-50 hover:bg-red-50 text-gray-400 hover:text-red-600 rounded-lg transition-all" title="View Details">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                <?php if($review['status'] === 'pending'): ?>
                                <form method="POST" action="update_status.php" class="inline">
                                    <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="p-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-lg transition-all" title="Approve">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>