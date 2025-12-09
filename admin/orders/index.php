<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";
include __DIR__ . "/../includes/header.php";

// --- START: Search and Filter Logic ---

// Get filter parameters from GET
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? ''; // 'pending', 'processing', etc.

$sql = "
    SELECT o.*, u.full_name, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    WHERE 1=1
";

$params = [];

// 1. Add Search Term Condition (Search by order number, customer name, or email)
if ($searchTerm) {
    $sql .= " AND (o.order_number LIKE :search_term OR u.full_name LIKE :search_term OR u.email LIKE :search_term)";
    $params[':search_term'] = '%' . $searchTerm . '%';
}

// 2. Add Status Filter Condition
$valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if ($statusFilter && in_array($statusFilter, $valid_statuses)) {
    $sql .= " AND o.status = :status_filter";
    $params[':status_filter'] = $statusFilter;
}

$sql .= " ORDER BY o.order_date DESC";

// Execute the filtered query using prepared statements
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- END: Search and Filter Logic ---

// Recalculate stats based on the FILTERED results displayed on the page
$total_orders = count($orders);
$total_revenue = array_sum(array_column($orders, 'total_amount'));
// Note: Pending/Delivered counts now reflect the currently *displayed* (filtered) orders.
$pending_orders = count(array_filter($orders, fn($o) => $o['status'] === 'pending'));
$delivered_orders = count(array_filter($orders, fn($o) => $o['status'] === 'delivered'));

// Success/Error messages
$msg = $_GET['msg'] ?? null;
$error = $_GET['error'] ?? null;
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-0">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Order Management</h1>
            <p class="text-sm text-gray-500 mt-1">Manage and track all customer orders</p>
        </div>
        <div class="flex items-center space-x-3">
            <button class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-all duration-200 shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="hidden sm:inline">Export</span>
            </button>
        </div>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($msg): ?>
<div class="mb-6 bg-green-50 border-l-4 border-green-500 rounded-lg p-4 shadow-sm">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($msg) ?></p>
    </div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-lg p-4 shadow-sm">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Order Stats -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-6 mb-4 md:mb-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">Total Orders</p>
                <p class="text-2xl font-bold text-gray-800"><?= $total_orders ?></p>
            </div>
            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">Total Revenue</p>
                <p class="text-2xl font-bold text-gray-800">₹<?= number_format($total_revenue, 2) ?></p>
            </div>
            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">Pending Orders</p>
                <p class="text-2xl font-bold text-gray-800"><?= $pending_orders ?></p>
            </div>
            <div class="w-12 h-12 bg-gradient-to-br from-red-400 to-red-500 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">Delivered</p>
                <p class="text-2xl font-bold text-gray-800"><?= $delivered_orders ?></p>
            </div>
            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <!-- Table Header with Search and Filter Form -->
    <div class="px-6 py-4 border-b border-gray-100">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h3 class="text-lg font-semibold text-gray-800">Recent Orders</h3>
            
            <!-- Search and Filter Form -->
            <form method="GET" action="index.php" id="orderFilterForm" class="flex flex-col sm:flex-row items-center space-x-3 w-full sm:w-auto">
                
                <!-- Search Input -->
                <div class="relative w-full sm:w-auto sm:flex-initial">
                    <input type="text" name="search" id="searchInput" placeholder="Search orders..." value="<?= htmlspecialchars($searchTerm) ?>" class="w-full pl-10 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                
                <!-- Status Filter Dropdown -->
                <select name="status" id="statusFilter" class="w-full sm:w-auto px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="" <?= $statusFilter == '' ? 'selected' : '' ?>>All Status</option>
                    <?php
                    // Re-declare statuses for the filter list if needed, but using $valid_statuses from PHP block is better
                    $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
                    foreach($statuses as $s):
                    ?>
                    <option value="<?= $s ?>" <?= ($statusFilter == $s ? 'selected' : '') ?>>
                        <?= ucfirst($s) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Hidden submit button - allows hitting enter in search field to submit -->
                <button type="submit" class="hidden"></button>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Order ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Customer</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($orders) > 0): ?>
                <?php foreach ($orders as $o): 
                    // Status color mapping
                    $status_colors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'processing' => 'bg-blue-100 text-blue-800',
                        'shipped' => 'bg-indigo-100 text-indigo-800',
                        'delivered' => 'bg-green-100 text-green-800',
                        'cancelled' => 'bg-red-100 text-red-800'
                    ];
                    $status_color = $status_colors[$o['status']] ?? 'bg-gray-100 text-gray-800';
                ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-gray-800">
                            <?= !empty($o['order_number']) ? htmlspecialchars($o['order_number']) : '#' . $o['order_id'] ?>
                        </div>
                        <?php if (!empty($o['order_number'])): ?>
                        <div class="text-xs text-gray-500">ID: #<?= $o['order_id'] ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-500 to-pink-600 flex items-center justify-center text-white font-semibold">
                                <?= strtoupper(substr($o['full_name'], 0, 1)) ?>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($o['full_name']) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($o['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-gray-800">₹<?= number_format($o['total_amount'], 2) ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <form method="POST" action="update_status.php" class="inline-block">
                            <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                            <div class="flex items-center space-x-2">
                                <select name="status" class="text-xs font-medium px-2.5 py-1.5 rounded-full border border-gray-200 focus:outline-none focus:ring-2 focus:ring-red-500 <?= $status_color ?>" onchange="this.form.submit()">
                                    <?php
                                    foreach($valid_statuses as $s):
                                    ?>
                                    <option value="<?= $s ?>" <?= ($o['status'] == $s ? 'selected' : '') ?>>
                                        <?= ucfirst($s) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-800"><?= date("d M Y", strtotime($o['order_date'])) ?></div>
                        <div class="text-xs text-gray-500"><?= date("h:i A", strtotime($o['order_date'])) ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end space-x-3">
                            <a href="view.php?id=<?= $o['order_id'] ?>" class="text-red-600 hover:text-red-800 font-medium transition-colors" title="View Details">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <a href="delete.php?id=<?= $o['order_id'] ?>" class="text-red-600 hover:text-red-800 font-medium transition-colors" title="Delete" onclick="return confirm('Are you sure you want to delete this order? This action cannot be undone.')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <p class="text-gray-500 font-medium mb-2">No orders found matching the criteria.</p>
                        <p class="text-sm text-gray-400">Try adjusting your search query or filters.</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Table Footer -->
    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Showing <span class="font-semibold text-gray-800"><?= count($orders) ?></span> order(s)
            </div>
            <div class="flex items-center space-x-2">
                <button class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50" disabled>
                    Previous
                </button>
                <button class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50" disabled>
                    Next
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// --- START: Instant Submission Logic ---
const searchInput = document.getElementById("searchInput");
const statusFilter = document.getElementById("statusFilter");
const filterForm = document.getElementById("orderFilterForm");

// Function to submit the form
function submitFilter() {
    filterForm.submit();
}

// 1. Submit immediately when status filter changes
statusFilter.addEventListener("change", submitFilter);

// 2. Submit search after a short delay (debounce) to avoid too many requests while typing
let searchTimeout = null;
searchInput.addEventListener("input", () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(submitFilter, 500); // Wait 500ms before submitting
});
// --- END: Instant Submission Logic ---
</script>
 
<?php include __DIR__ . "/../includes/footer.php"; ?>