<?php
// ===========================================
// Admin Dashboard - Le Rouge Style
// ===========================================
require_once "../auth/check_auth.php";
require_once "../config/config.php";
include __DIR__ . "/includes/header.php";

// Current admin
$adminId = $_SESSION['admin_id'] ?? null;

// Notifications for admin header (latest 10)
$adminNotifications = [];
$adminUnreadCount   = 0;
if ($adminId) {
    $notifStmt = $pdo->prepare("
        SELECT notification_id, type, title, message, image_url, target_url, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $notifStmt->execute([$adminId]);
    $adminNotifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0
    ");
    $countStmt->execute([$adminId]);
    $adminUnreadCount = (int)($countStmt->fetchColumn() ?? 0);
}

// Basic stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

// Calculate total revenue
$totalRevenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?? 0;

// Low stock alerts
$lowStockProducts = $pdo->query("SELECT name, stock FROM products WHERE stock < 5 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Sales data (last 7 days)
$salesData = $pdo->query("
    SELECT DATE(order_date) as date, SUM(total_amount) as total
    FROM orders
    WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(order_date)
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for chart
$dates = [];
$totals = [];
foreach ($salesData as $row) {
    $dates[] = date('M d', strtotime($row['date']));
    $totals[] = (float)$row['total'];
}

// Fetch recent orders
$orders = $pdo->query("
    SELECT o.order_id, u.full_name, o.total_amount, o.status, o.order_date
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    ORDER BY o.order_date DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Status badge colors
function getStatusBadge($status) {
    $badges = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'processing' => 'bg-blue-100 text-blue-800',
        'shipped' => 'bg-purple-100 text-purple-800',
        'delivered' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800'
    ];
    return $badges[$status] ?? 'bg-gray-100 text-gray-800';
}
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6 mb-4 md:mb-6">
    <!-- Total Revenue -->
    <div class="bg-white p-4 md:p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs md:text-sm font-medium text-gray-500 mb-1">Total Revenue</p>
                <p class="text-xl md:text-2xl font-bold text-gray-800">₹<?= number_format($totalRevenue, 0) ?></p>
                <p class="text-xs text-green-600 mt-2 flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/>
                    </svg>
                    <span class="hidden sm:inline">+12.5% from last month</span>
                    <span class="sm:hidden">+12.5%</span>
                </p>
            </div>
            <div class="w-12 h-12 md:w-14 md:h-14 bg-gradient-to-br from-green-400 to-emerald-600 rounded-xl flex items-center justify-center flex-shrink-0 ml-2">
                <svg class="w-5 h-5 md:w-7 md:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">Total Orders</p>
                <p class="text-2xl font-bold text-gray-800"><?= $totalOrders ?></p>
                <p class="text-xs text-blue-600 mt-2 flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/>
                    </svg>
                    <?= $pendingOrders ?> pending
                </p>
            </div>
            <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-indigo-600 rounded-xl flex items-center justify-center">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Products -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">Total Products</p>
                <p class="text-2xl font-bold text-gray-800"><?= $totalProducts ?></p>
                <p class="text-xs text-purple-600 mt-2 flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd"/>
                    </svg>
                    <?= count($lowStockProducts) ?> low stock
                </p>
            </div>
            <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-pink-600 rounded-xl flex items-center justify-center">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Customers -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">Total Customers</p>
                <p class="text-2xl font-bold text-gray-800"><?= $totalUsers ?></p>
                <p class="text-xs text-orange-600 mt-2 flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/>
                    </svg>
                    +8.2% new users
                </p>
            </div>
            <div class="w-14 h-14 bg-gradient-to-br from-orange-400 to-red-600 rounded-xl flex items-center justify-center">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Low Stock Alert -->
<?php if (count($lowStockProducts) > 0): ?>
<div class="mb-6">
    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-l-4 border-yellow-500 rounded-lg p-4 shadow-sm">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-semibold text-yellow-800 mb-2">⚠️ Low Stock Alert</h3>
                <div class="text-sm text-yellow-700 space-y-1">
                    <?php foreach ($lowStockProducts as $p): ?>
                        <div class="flex items-center justify-between py-1">
                            <span><?= htmlspecialchars($p['name']) ?></span>
                            <span class="font-semibold bg-yellow-200 px-2 py-0.5 rounded text-xs"><?= $p['stock'] ?> left</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Main Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
    <!-- Sales Chart (2 columns) -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">Sales Analytics</h2>
                    <p class="text-sm text-gray-500">Last 7 days performance</p>
                </div>
                <div class="flex items-center space-x-2">
                    <button class="px-3 py-1.5 text-xs font-medium text-white bg-gradient-to-r from-red-500 to-pink-600 rounded-lg">Week</button>
                    <button class="px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded-lg">Month</button>
                    <button class="px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded-lg">Year</button>
                </div>
            </div>
            <div style="height: 250px; min-height: 250px;" class="md:h-[300px]">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Orders (1 column) -->
    <div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Recent Orders</h2>
                        <p class="text-sm text-gray-500">Latest customer orders</p>
                    </div>
                    <a href="<?= htmlspecialchars($BASE_URL . 'admin/orders/index.php', ENT_QUOTES, 'UTF-8') ?>" class="text-sm font-medium text-red-600 hover:text-red-700">View all</a>
                </div>
            </div>
            
            <div class="divide-y divide-gray-100">
                <?php if ($orders): foreach ($orders as $order): ?>
                    <div class="p-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800 text-sm mb-1"><?= htmlspecialchars($order['full_name']) ?></p>
                                <p class="text-xs text-gray-500">#<?= $order['order_id'] ?></p>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?= getStatusBadge($order['status']) ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-800">₹<?= number_format($order['total_amount'], 2) ?></p>
                            <p class="text-xs text-gray-500"><?= date("d M, Y", strtotime($order['order_date'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                    <div class="p-8 text-center">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p class="text-sm text-gray-500">No recent orders found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($dates) ?>,
        datasets: [{
            label: 'Sales',
            data: <?= json_encode($totals) ?>,
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointRadius: 5,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#ef4444',
            pointBorderWidth: 2,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: '#1a1d29',
                titleColor: '#fff',
                bodyColor: '#fff',
                padding: 12,
                borderColor: '#374151',
                borderWidth: 1,
                cornerRadius: 8,
                callbacks: {
                    label: function(context) {
                        return 'Sales: ₹' + context.parsed.y.toLocaleString();
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f3f4f6',
                    drawBorder: false
                },
                ticks: {
                    callback: function(value) {
                        return '₹' + value.toLocaleString();
                    },
                    color: '#6b7280',
                    font: {
                        size: 11
                    }
                }
            },
            x: {
                grid: {
                    display: false,
                    drawBorder: false
                },
                ticks: {
                    color: '#6b7280',
                    font: {
                        size: 11
                    }
                }
            }
        }
    }
});
</script>

<?php include __DIR__ . "/includes/footer.php"; ?>