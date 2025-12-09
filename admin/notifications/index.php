<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";
include __DIR__ . "/../includes/header.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$adminId = $_SESSION['admin_id'] ?? null;
if (!$adminId) {
    header("Location: ../login.php");
    exit;
}

// Handle mark-all-read action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$adminId]);
    header("Location: index.php?msg=All notifications marked as read");
    exit;
}

// Filters
$typeFilter  = $_GET['type']  ?? '';
$readFilter  = $_GET['read']  ?? '';

$where  = "user_id = ?";
$params = [$adminId];

if ($typeFilter !== '' && $typeFilter !== 'all') {
    $where   .= " AND type = ?";
    $params[] = $typeFilter;
}

if ($readFilter === 'unread') {
    $where .= " AND is_read = 0";
} elseif ($readFilter === 'read') {
    $where .= " AND is_read = 1";
}

$stmt = $pdo->prepare("
    SELECT notification_id, type, title, message, image_url, target_url, is_read, created_at, read_at
    FROM notifications
    WHERE {$where}
    ORDER BY created_at DESC
    LIMIT 100
");
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get simple counts
$totalCountStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$totalCountStmt->execute([$adminId]);
$totalCount = (int)$totalCountStmt->fetchColumn();

$unreadCountStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unreadCountStmt->execute([$adminId]);
$unreadCount = (int)$unreadCountStmt->fetchColumn();

// Types for filter dropdown
$types = ['all', 'order_update', 'promotion', 'account_security', 'inventory_alert', 'system_alert'];

?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Notifications</h1>
            <p class="text-sm text-gray-500 mt-1">
                View all system and order notifications for your admin account.
            </p>
        </div>
        <div class="flex items-center space-x-2">
            <form method="POST">
                <button type="submit" name="mark_all_read"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 shadow-sm transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Mark all as read
                </button>
            </form>
        </div>
    </div>
</div>

<div class="mb-6 grid grid-cols-2 sm:grid-cols-4 gap-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">Total</p>
                <p class="text-2xl font-bold text-gray-800"><?= $totalCount ?></p>
            </div>
            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
            </div>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">Unread</p>
                <p class="text-2xl font-bold text-red-600"><?= $unreadCount ?></p>
            </div>
            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <h2 class="text-lg font-semibold text-gray-800">All Notifications</h2>
        <form method="GET" class="flex items-center gap-2">
            <select name="type" class="px-4 py-2 text-sm border border-gray-200 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition duration-150">
                <?php foreach ($types as $t): ?>
                    <option value="<?= $t ?>" <?= $typeFilter === $t ? 'selected' : '' ?>>
                        <?= $t === 'all' ? 'All types' : ucfirst(str_replace('_', ' ', $t)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="read" class="px-4 py-2 text-sm border border-gray-200 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition duration-150">
                <option value="">All</option>
                <option value="unread" <?= $readFilter === 'unread' ? 'selected' : '' ?>>Unread only</option>
                <option value="read"   <?= $readFilter === 'read'   ? 'selected' : '' ?>>Read only</option>
            </select>
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 rounded-lg shadow-sm transition-all duration-200">
                Filter
            </button>
        </form>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="p-8 text-center text-sm text-gray-500">
            No notifications found for the selected filters.
        </div>
    <?php else: ?>
        <div class="divide-y divide-gray-100">
            <?php foreach ($notifications as $n): 
                $meta = getAdminNotificationMeta($n['type']);
                $isUnread = empty($n['is_read']);
            ?>
                <div class="px-6 py-4 flex items-start gap-3 <?= $isUnread ? 'bg-red-50/40' : 'bg-white' ?> hover:bg-gray-50 transition-colors">
                    <div class="mt-1">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-red-100 text-red-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                            </svg>
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                            <div>
                                <span class="inline-block text-xs px-2.5 py-1 rounded-full bg-red-100 text-red-800 uppercase tracking-wide font-semibold">
                                    <?= htmlspecialchars(str_replace('_', ' ', $n['type'])) ?>
                                </span>
                            </div>
                            <span class="text-[11px] text-gray-400">
                                <?= date('d M Y, H:i', strtotime($n['created_at'])) ?>
                            </span>
                        </div>
                        <p class="mt-1 text-sm font-semibold text-gray-800">
                            <?= htmlspecialchars($n['title']) ?>
                        </p>
                        <p class="mt-0.5 text-xs text-gray-600">
                            <?= nl2br(htmlspecialchars($n['message'])) ?>
                        </p>
                        <?php if (!empty($n['target_url'])): ?>
                            <div class="mt-1">
                                <a href="<?= htmlspecialchars($n['target_url']) ?>" class="text-xs text-red-600 hover:text-red-700 font-medium">
                                    View details
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>


