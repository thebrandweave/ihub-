<?php
// 1. Logic and Redirects MUST come first
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * 1️⃣ ACTION HANDLERS (Before any HTML output)
 */
// Mark as Read/Unread
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $newStatus = $_GET['toggle_status'] === 'read' ? 'read' : 'unread';
    $stmt = $pdo->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);
    header("Location: index.php?msg=Status updated");
    exit;
}

// Delete Message
if (isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php?msg=Message deleted");
    exit;
}

/**
 * 2️⃣ DATA FETCHING
 */
$statusFilter = $_GET['status'] ?? 'all';
$where = "1=1";
$params = [];

if ($statusFilter !== 'all') {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE {$where} ORDER BY created_at DESC");
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unreadCount = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn();
$totalCount  = $pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();

// 3. Now it is safe to include the header and sidebar (HTML output starts here)
include __DIR__ . "/../includes/header.php";
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Customer Messages</h1>
            <p class="text-sm text-gray-500 mt-1">Manage inquiries received via the Contact Us form.</p>
        </div>
    </div>
</div>

<div class="mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">Total Inquiries</p>
                <p class="text-2xl font-bold text-gray-800"><?= $totalCount ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                <i class="bi bi-chat-left-text text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">Unread</p>
                <p class="text-2xl font-bold text-red-600"><?= $unreadCount ?></p>
            </div>
            <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center">
                <i class="bi bi-envelope-exclamation text-red-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <h2 class="text-lg font-semibold text-gray-800">Inquiry History</h2>
        <form method="GET" class="flex items-center gap-2">
            <select name="status" class="px-4 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Status</option>
                <option value="unread" <?= $statusFilter === 'unread' ? 'selected' : '' ?>>Unread</option>
                <option value="read" <?= $statusFilter === 'read' ? 'selected' : '' ?>>Read</option>
            </select>
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-all">
                Filter
            </button>
        </form>
    </div>

    <?php if (empty($messages)): ?>
        <div class="p-8 text-center text-sm text-gray-500">No messages found.</div>
    <?php else: ?>
        <div class="divide-y divide-gray-100">
            <?php foreach ($messages as $m): 
                $isUnread = ($m['status'] === 'unread');
            ?>
                <div class="px-6 py-5 flex flex-col md:flex-row items-start gap-4 <?= $isUnread ? 'bg-red-50/30' : 'bg-white' ?> hover:bg-gray-50 transition-colors">
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="text-sm font-bold text-gray-900"><?= htmlspecialchars($m['name']) ?></span>
                            <span class="text-xs text-gray-500"><?= htmlspecialchars($m['email']) ?></span>
                            <span class="ml-auto text-[11px] text-gray-400 uppercase">
                                <?= date('d M Y, h:i A', strtotime($m['created_at'])) ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-700 italic mb-3">"<?= nl2br(htmlspecialchars($m['message'])) ?>"</p>
                        <div class="flex items-center gap-3">
                            <a href="index.php?toggle_status=<?= $isUnread ? 'read' : 'unread' ?>&id=<?= $m['id'] ?>" class="text-xs font-semibold <?= $isUnread ? 'text-green-600' : 'text-gray-500' ?>">
                                <?= $isUnread ? 'Mark Read' : 'Mark Unread' ?>
                            </a>
                            <a href="mailto:<?= $m['email'] ?>" class="text-xs font-semibold text-blue-600">Reply</a>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete permanently?');">
                                <input type="hidden" name="delete_id" value="<?= $m['id'] ?>">
                                <button type="submit" class="text-xs font-semibold text-red-500">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>