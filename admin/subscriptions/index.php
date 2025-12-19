<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";
include __DIR__ . "/../includes/header.php";

// --- START: Search & Filter Logic ---
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT s.*, u.full_name 
        FROM newsletter_subscribers s 
        LEFT JOIN users u ON s.user_id = u.user_id 
        WHERE (s.email LIKE :search OR u.full_name LIKE :search)";
$params = [':search' => '%' . $searchTerm . '%'];

if ($statusFilter) {
    $sql .= " AND s.status = :status";
    $params[':status'] = $statusFilter;
}

$sql .= " ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats logic
$totalCount = $pdo->query("SELECT COUNT(*) FROM newsletter_subscribers")->fetchColumn();
$activeCount = $pdo->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'subscribed'")->fetchColumn();
$unsubCount = $pdo->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'unsubscribed'")->fetchColumn();
// --- END: Search & Filter Logic ---

$msg = $_GET['msg'] ?? null;
$error = $_GET['error'] ?? null;
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-0">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Subscription Management</h1>
            <p class="text-sm text-gray-500 mt-1">Manage newsletter signups and user opt-outs</p>
        </div>
        <button type="button" id="bulkDeleteBtn" onclick="submitBulkDelete()" class="hidden items-center justify-center px-3 md:px-4 py-2 md:py-2.5 bg-gradient-to-r from-red-500 to-pink-600 text-white text-xs md:text-sm font-medium rounded-lg hover:from-red-600 hover:to-pink-700 transition-all duration-200 shadow-sm">
            <svg class="w-4 h-4 md:w-5 md:h-5 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            <span>Unsubscribe Selected (<span id="selectedCount">0</span>)</span>
        </button>
    </div>
</div>

<?php if ($msg): ?>
<div class="mb-6 bg-green-50 border-l-4 border-green-500 rounded-lg p-4 shadow-sm">
    <div class="flex items-center"><p class="text-sm font-medium text-green-800"><?= htmlspecialchars($msg) ?></p></div>
</div>
<?php endif; ?>


<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h3 class="text-lg font-semibold text-gray-800">Subscriber Accounts</h3>
            
            <form id="filterForm" method="GET" action="index.php" class="flex items-center space-x-3">
                <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-200 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-red-500">
                    <option value="">All Status</option>
                    <option value="subscribed" <?= $statusFilter == 'subscribed' ? 'selected' : '' ?>>Subscribed</option>
                    <option value="unsubscribed" <?= $statusFilter == 'unsubscribed' ? 'selected' : '' ?>>Unsubscribed</option>
                </select>

                <div class="relative">
                    <input type="text" name="search" id="searchInput" placeholder="Search emails..." value="<?= htmlspecialchars($searchTerm) ?>" class="pl-10 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </form>
        </div>
    </div>

    <form id="bulkActionForm" action="bulk_delete.php" method="POST">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left w-10"><input type="checkbox" id="selectAll" class="rounded border-gray-300 text-red-600 focus:ring-red-500"></th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Subscriber</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Profile Link</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($subscribers) > 0): foreach ($subscribers as $s): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4"><input type="checkbox" name="ids[]" value="<?= $s['subscriber_id'] ?>" class="sub-checkbox rounded border-gray-300 text-red-600 focus:ring-red-500"></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-400 to-pink-600 flex items-center justify-center text-white font-semibold">
                                    <?= strtoupper(substr($s['email'], 0, 1)) ?>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($s['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if($s['status'] == 'subscribed'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Subscribed</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Unsubscribed</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-800"><?= $s['user_id'] ? htmlspecialchars($s['full_name']) : '<span class="text-gray-400 italic">Guest</span>' ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-3">
                                <a href="delete.php?id=<?= $s['subscriber_id'] ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Delete?')" title="Delete"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr class="bg-white"><td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No subscribers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>

    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
        <div class="flex items-center justify-between text-sm text-gray-600">
            <div>Showing <span class="font-semibold text-gray-800"><?= count($subscribers) ?></span> subscriber(s)</div>
            <div class="flex items-center space-x-2">
                <button class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg opacity-50" disabled>Previous</button>
                <button class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg opacity-50" disabled>Next</button>
            </div>
        </div>
    </div>
</div>

<script>
    // 1. Bulk Select Logic
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.sub-checkbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const selectedCount = document.getElementById('selectedCount');

    function updateBulkUI() {
        const count = document.querySelectorAll('.sub-checkbox:checked').length;
        selectedCount.textContent = count;
        bulkDeleteBtn.style.display = count > 0 ? 'inline-flex' : 'none';
    }

    selectAll.addEventListener('change', (e) => {
        checkboxes.forEach(cb => cb.checked = e.target.checked);
        updateBulkUI();
    });

    checkboxes.forEach(cb => cb.addEventListener('change', updateBulkUI));

    function submitBulkDelete() {
        if(confirm('Permanently remove selected?')) document.getElementById('bulkActionForm').submit();
    }

    // 2. Auto-search on Enter
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('filterForm').submit();
        }
    });
</script>

<?php include __DIR__ . "/../includes/footer.php"; ?>