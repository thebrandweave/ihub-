<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$adminId = $_SESSION['admin_id'] ?? null;
if (!$adminId) {
    header("Location: ../login.php");
    exit;
}

/**
 * 1️⃣ AUTO-CLEANUP LOGIC
 */
try {
    $cleanup = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $cleanup->execute([$adminId]);
} catch (PDOException $e) {
    error_log("Admin notification cleanup error: " . $e->getMessage());
}

/**
 * 2️⃣ ACTION HANDLERS
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$adminId]);
    header("Location: index.php?msg=All notifications marked as read");
    exit;
}

/**
 * 3️⃣ DATA FETCHING
 */
$typeFilter  = $_GET['type']  ?? 'all';
$readFilter  = $_GET['read']  ?? '';

$where  = "user_id = ?";
$params = [$adminId];

if ($typeFilter !== 'all') {
    $where   .= " AND type = ?";
    $params[] = $typeFilter;
}

if ($readFilter === 'unread') {
    $where .= " AND is_read = 0";
} elseif ($readFilter === 'read') {
    $where .= " AND is_read = 1";
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE {$where} ORDER BY created_at DESC LIMIT 100");
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCount  = $pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = $adminId")->fetchColumn();
$unreadCount = $pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = $adminId AND is_read = 0")->fetchColumn();

$types = ['all', 'order_update', 'promotion', 'account_security', 'inventory_alert', 'system_alert'];

include __DIR__ . "/../includes/header.php";
?>

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Admin Notifications</h1>
        <p class="text-sm text-gray-500 mt-1">Logs from last 30 days.</p>
    </div>
    <form method="POST">
        <button type="submit" name="mark_all_read" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 shadow-sm transition-colors">
            <i class="bi bi-check2-all mr-2"></i> Mark all as read
        </button>
    </form>
</div>

<div class="mb-6 grid grid-cols-2 sm:grid-cols-4 gap-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <p class="text-sm font-medium text-gray-500">Total Recent</p>
        <p class="text-2xl font-bold text-gray-800"><?= $totalCount ?></p>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <p class="text-sm font-medium text-gray-500">Unread</p>
        <p class="text-2xl font-bold text-red-600"><?= $unreadCount ?></p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <h2 class="text-lg font-semibold text-gray-800">Alert History</h2>
        <form method="GET" class="flex items-center gap-2">
            <select name="type" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500">
                <?php foreach ($types as $t): ?>
                    <option value="<?= $t ?>" <?= $typeFilter === $t ? 'selected' : '' ?>>
                        <?= $t === 'all' ? 'All Types' : ucfirst(str_replace('_', ' ', $t)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-all">Filter</button>
        </form>
    </div>

    <div class="divide-y divide-gray-100">
        <?php if (empty($notifications)): ?>
            <div class="p-8 text-center text-gray-500 text-sm">No notifications found.</div>
        <?php else: ?>
            <?php foreach ($notifications as $n): 
                $isUnread = empty($n['is_read']);
                // Custom color for System Alerts (Inquiries)
                $typeColor = ($n['type'] === 'system_alert') ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700';
            ?>
                <div class="px-6 py-4 flex items-start gap-4 <?= $isUnread ? 'bg-red-50/40' : 'bg-white' ?> hover:bg-gray-50 transition-colors">
                    <div class="mt-1">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full <?= $n['type'] === 'system_alert' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-600' ?>">
                            <i class="bi <?= $n['type'] === 'system_alert' ? 'bi-chat-dots' : 'bi-bell' ?> text-lg"></i>
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] px-2 py-0.5 rounded-full uppercase font-bold <?= $typeColor ?>">
                                <?= str_replace('_', ' ', $n['type']) ?>
                            </span>
                            <span class="text-[11px] text-gray-400"><?= date('d M, H:i', strtotime($n['created_at'])) ?></span>
                        </div>
                        <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($n['title']) ?></p>
                        <p class="text-xs text-gray-600 mt-1"><?= nl2br(htmlspecialchars($n['message'])) ?></p>
                        <?php if ($n['target_url']): ?>
                            <a href="<?= $n['target_url'] ?>" class="inline-block mt-2 text-xs font-semibold text-red-600 hover:underline">View Inquiry →</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>