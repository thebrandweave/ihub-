<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";
include __DIR__ . "/../includes/header.php";

// Get filter parameters
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? ''; // 'pending', 'approved', 'rejected'

$sql = "
    SELECT 
        r.review_id,
        r.rating,
        r.comment,
        r.status,
        r.created_at,
        u.full_name AS user_name,
        u.email AS user_email,
        p.product_id,
        p.name AS product_name,
        p.thumbnail,
        (SELECT image FROM review_images ri WHERE ri.review_id = r.review_id ORDER BY ri.id ASC LIMIT 1) AS first_image,
        (SELECT COUNT(*) FROM review_images ri WHERE ri.review_id = r.review_id) AS image_count
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    JOIN products p ON r.product_id = p.product_id
    WHERE 1=1
";

$params = [];

// Search filter
if ($searchTerm) {
    $sql .= " AND (p.name LIKE :search_term OR u.full_name LIKE :search_term OR u.email LIKE :search_term)";
    $params[':search_term'] = '%' . $searchTerm . '%';
}

// Status filter
if ($statusFilter && in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
    $sql .= " AND r.status = :status_filter";
    $params[':status_filter'] = $statusFilter;
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistics
$totalReviews = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$pendingReviews = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn();
$approvedReviews = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'approved'")->fetchColumn();
$rejectedReviews = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'rejected'")->fetchColumn();

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
            <h1 class="text-xl md:text-2xl font-bold text-gray-800">Review Management</h1>
            <p class="text-xs md:text-sm text-gray-500 mt-1">Approve or reject customer reviews</p>
        </div>
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

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 uppercase">Total Reviews</p>
                <p class="text-2xl font-bold text-gray-800"><?= $totalReviews ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 uppercase">Pending</p>
                <p class="text-2xl font-bold text-yellow-600"><?= $pendingReviews ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 uppercase">Approved</p>
                <p class="text-2xl font-bold text-green-600"><?= $approvedReviews ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 uppercase">Rejected</p>
                <p class="text-2xl font-bold text-red-600"><?= $rejectedReviews ?></p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <input type="text" 
                   name="search" 
                   value="<?= htmlspecialchars($searchTerm) ?>" 
                   placeholder="Search by product name, customer name, or email..."
                   class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
        </div>
        <div class="w-full md:w-48">
            <select name="status" 
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                <option value="">All Status</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>
        <button type="submit" 
                class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
            Filter
        </button>
        <?php if ($searchTerm || $statusFilter): ?>
            <a href="index.php" 
               class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                Clear
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Reviews Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <?php if (empty($reviews)): ?>
        <div class="p-12 text-center text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
            </svg>
            <p class="text-lg font-medium">No reviews found</p>
            <p class="text-sm">Try adjusting your filters</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($reviews as $review): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <img src="<?= htmlspecialchars(getImagePath($review['thumbnail'])) ?>" 
                                         alt="<?= htmlspecialchars($review['product_name']) ?>" 
                                         class="w-12 h-12 object-cover rounded-lg mr-3">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($review['product_name']) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">ID: <?= $review['product_id'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($review['user_name']) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($review['user_email']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="w-4 h-4 <?= $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>" 
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                    <?php endfor; ?>
                                    <span class="ml-2 text-sm text-gray-600"><?= $review['rating'] ?>/5</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs">
                                    <?= !empty($review['comment']) ? htmlspecialchars(substr($review['comment'], 0, 100)) . (strlen($review['comment']) > 100 ? '...' : '') : '<span class="text-gray-400">No comment</span>' ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($review['first_image'])): ?>
                                    <div class="flex items-center gap-2">
                                        <img src="<?= htmlspecialchars(getReviewImagePath($review['first_image'])) ?>" 
                                             alt="Review image" 
                                             class="w-16 h-16 object-cover rounded-lg cursor-pointer hover:opacity-75"
                                             onclick="window.open('<?= htmlspecialchars(getReviewImagePath($review['first_image'])) ?>', '_blank')">
                                        <?php if ((int)$review['image_count'] > 1): ?>
                                            <span class="text-xs text-gray-600">+<?= (int)$review['image_count'] - 1 ?> more</span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">No image</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'approved' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800'
                                ];
                                $color = $statusColors[$review['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?= $color ?>">
                                    <?= ucfirst($review['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date("d M Y", strtotime($review['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-2">
                                    <a href="view.php?id=<?= $review['review_id'] ?>" 
                                       class="text-blue-600 hover:text-blue-900 font-medium">
                                        View
                                    </a>
                                    <span class="text-gray-300">|</span>
                                    <?php if ($review['status'] === 'pending'): ?>
                                        <form method="POST" action="update_status.php" class="inline">
                                            <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" 
                                                    onclick="return confirm('Approve this review?')"
                                                    class="text-green-600 hover:text-green-900">
                                                Approve
                                            </button>
                                        </form>
                                        <span class="text-gray-300">|</span>
                                        <form method="POST" action="update_status.php" class="inline">
                                            <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" 
                                                    onclick="return confirm('Reject this review?')"
                                                    class="text-red-600 hover:text-red-900">
                                                Reject
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="update_status.php" class="inline">
                                            <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                                            <input type="hidden" name="status" value="<?= $review['status'] === 'approved' ? 'rejected' : 'approved' ?>">
                                            <button type="submit" 
                                                    onclick="return confirm('Change review status?')"
                                                    class="text-blue-600 hover:text-blue-900">
                                                <?= $review['status'] === 'approved' ? 'Reject' : 'Approve' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>

