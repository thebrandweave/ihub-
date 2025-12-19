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
// --- END: Search & Filter Logic ---

$msg = $_GET['msg'] ?? null;
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-0">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Subscription Management</h1>
            <p class="text-sm text-gray-500 mt-1">Manage newsletter signups and user opt-outs</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" id="exportBtn" class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-all shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span>Export to Excel</span>
            </button>

            <button type="button" id="bulkDeleteBtn" onclick="submitBulkDelete()" class="hidden items-center justify-center px-4 py-2.5 bg-gradient-to-r from-red-500 to-pink-600 text-white text-sm font-medium rounded-lg hover:from-red-600 hover:to-pink-700 transition-all shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                <span>Unsubscribe (<span id="selectedCount">0</span>)</span>
            </button>
        </div>
    </div>
</div>

<?php if ($msg): ?>
<div class="mb-6 bg-green-50 border-l-4 border-green-500 rounded-lg p-4 shadow-sm">
    <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($msg) ?></p>
</div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h3 class="text-lg font-semibold text-gray-800">Subscriber Accounts</h3>
            
            <form id="filterForm" method="GET" class="flex items-center space-x-3">
                <select name="status" id="statusSelect" onchange="this.form.submit()" class="text-sm border border-gray-200 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-red-500">
                    <option value="">All Status</option>
                    <option value="subscribed" <?= $statusFilter == 'subscribed' ? 'selected' : '' ?>>Subscribed</option>
                    <option value="unsubscribed" <?= $statusFilter == 'unsubscribed' ? 'selected' : '' ?>>Unsubscribed</option>
                </select>

                <div class="relative">
                    <input type="text" name="search" id="searchInput" placeholder="Search emails..." value="<?= htmlspecialchars($searchTerm) ?>" class="pl-10 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
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
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Profile</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($subscribers) > 0): foreach ($subscribers as $s): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4"><input type="checkbox" name="ids[]" value="<?= $s['subscriber_id'] ?>" class="sub-checkbox rounded border-gray-300 text-red-600 focus:ring-red-500"></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold text-xs">
                                    <?= strtoupper(substr($s['email'], 0, 1)) ?>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($s['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?= $s['status'] == 'subscribed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= ucfirst($s['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= $s['user_id'] ? htmlspecialchars($s['full_name']) : 'Guest' ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <a href="delete.php?id=<?= $s['subscriber_id'] ?>" onclick="return confirm('Delete?')" class="text-red-600 hover:text-red-900"><svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" class="px-6 py-10 text-center text-gray-500">No results found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<script>
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.sub-checkbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const selectedCount = document.getElementById('selectedCount');
    const exportBtn = document.getElementById('exportBtn');

    function updateBulkUI() {
        const checked = document.querySelectorAll('.sub-checkbox:checked');
        const count = checked.length;
        selectedCount.textContent = count;
        bulkDeleteBtn.style.display = count > 0 ? 'inline-flex' : 'none';
        
        // Update Export Button Text
        exportBtn.querySelector('span').textContent = count > 0 ? `Export Selected (${count})` : 'Export All Filtered';
    }

    selectAll.addEventListener('change', (e) => {
        checkboxes.forEach(cb => cb.checked = e.target.checked);
        updateBulkUI();
    });

    checkboxes.forEach(cb => cb.addEventListener('change', updateBulkUI));

    // Handle Export Logic
    exportBtn.addEventListener('click', function() {
        const checked = document.querySelectorAll('.sub-checkbox:checked');
        let url = 'export.php?';

        if (checked.length > 0) {
            // Case 1: Export specifically selected IDs
            const ids = Array.from(checked).map(cb => cb.value).join(',');
            url += `ids=${ids}`;
        } else {
            // Case 2: Export based on current search/filter
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusSelect').value;
            url += `search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`;
        }
        
        window.location.href = url;
    });

    function submitBulkDelete() {
        if(confirm('Unsubscribe selected users?')) document.getElementById('bulkActionForm').submit();
    }
</script>

<?php include __DIR__ . "/../includes/footer.php"; ?>