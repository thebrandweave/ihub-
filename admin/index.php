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

// Review stats
$pendingReviews = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn();
$totalReviews = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'approved'")->fetchColumn();
$avgRating = $pdo->query("SELECT AVG(rating) FROM reviews WHERE status = 'approved'")->fetchColumn() ?? 0;

// Low stock alerts
$lowStockProducts = $pdo->query("SELECT name, stock FROM products WHERE stock < 5 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch dynamic threshold from settings ---
$threshold_stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'low_stock_threshold'");
$threshold_stmt->execute();
$threshold = $threshold_stmt->fetchColumn() ?: 5; // Default to 5 if not found in DB

// --- Fetch products using the dynamic threshold ---
$lowStockStmt = $pdo->prepare("SELECT name, stock FROM products WHERE stock <= ? ORDER BY stock ASC LIMIT 5");
$lowStockStmt->execute([$threshold]);
$lowStockProducts = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch total count for the small red label ---
$totalLowStockStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE stock <= ?");
$totalLowStockStmt->execute([$threshold]);
$totalLowStockCount = $totalLowStockStmt->fetchColumn();


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
// If no data, add placeholder
if (empty($dates)) {
    $dates = ['No Data'];
    $totals = [0];
}

// Revenue by category
$categoryRevenue = $pdo->query("
    SELECT c.name, SUM(oi.price_at_time * oi.quantity) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    JOIN categories c ON p.category_id = c.category_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.status != 'cancelled'
    GROUP BY c.category_id, c.name
    ORDER BY revenue DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);
// If no data, add placeholder
if (empty($categoryRevenue)) {
    $categoryRevenue = [['name' => 'No Data', 'revenue' => 0]];
}

// Top selling products
$topProducts = $pdo->query("
    SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.price_at_time * oi.quantity) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.status != 'cancelled'
    GROUP BY p.product_id, p.name
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
// If no data, add placeholder
if (empty($topProducts)) {
    $topProducts = [['name' => 'No Data', 'total_sold' => 0, 'revenue' => 0]];
}

// Categories distribution (product count per category)
$categoriesDistribution = $pdo->query("
    SELECT c.name, COUNT(p.product_id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.category_id = p.category_id
    GROUP BY c.category_id, c.name
    HAVING product_count > 0
    ORDER BY product_count DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);
// If no data, add placeholder
if (empty($categoriesDistribution)) {
    $categoriesDistribution = [['name' => 'No Data', 'product_count' => 0]];
}

// Brands distribution (product count per brand)
$brandsDistribution = $pdo->query("
    SELECT b.name, COUNT(p.product_id) as product_count
    FROM brands b
    LEFT JOIN products p ON b.brand_id = p.brand_id
    GROUP BY b.brand_id, b.name
    HAVING product_count > 0
    ORDER BY product_count DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);
// If no data, add placeholder
if (empty($brandsDistribution)) {
    $brandsDistribution = [['name' => 'No Data', 'product_count' => 0]];
}

// Monthly revenue comparison (last 6 months)
$monthlyRevenue = $pdo->query("
    SELECT DATE_FORMAT(order_date, '%Y-%m') as month, 
           DATE_FORMAT(order_date, '%b %Y') as month_label,
           SUM(total_amount) as revenue
    FROM orders
    WHERE status != 'cancelled' 
      AND order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(order_date, '%Y-%m'), DATE_FORMAT(order_date, '%b %Y')
    ORDER BY month ASC
")->fetchAll(PDO::FETCH_ASSOC);
// If no data, add placeholder
if (empty($monthlyRevenue)) {
    $monthlyRevenue = [['month' => date('Y-m'), 'month_label' => date('M Y'), 'revenue' => 0]];
}

// Fetch recent orders
$orders = $pdo->query("
    SELECT o.order_id, o.order_number, u.full_name, o.total_amount, o.status, o.order_date
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    ORDER BY o.order_date DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Pending reviews for action
$pendingReviewsList = $pdo->query("
    SELECT r.review_id, r.rating, r.comment, r.created_at,
           u.full_name AS user_name, p.name AS product_name, p.thumbnail
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    JOIN products p ON r.product_id = p.product_id
    WHERE r.status = 'pending'
    ORDER BY r.created_at DESC
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
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
    <!-- Total Revenue -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-500 mb-1">Total Revenue</p>
                <p class="text-2xl font-bold text-gray-800">₹<?= number_format($totalRevenue, 0) ?></p>
                <p class="text-xs text-red-600 mt-2">+12.5% vs last month</p>
            </div>
            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl flex items-center justify-center flex-shrink-0 ml-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-500 mb-1">Total Orders</p>
                <p class="text-2xl font-bold text-gray-800"><?= $totalOrders ?></p>
                <p class="text-xs text-red-600 mt-2"><?= $pendingOrders ?> pending</p>
            </div>
            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center flex-shrink-0 ml-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Products -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-500 mb-1">Total Products</p>
                <p class="text-2xl font-bold text-gray-800"><?= $totalProducts ?></p>
                <p class="text-xs text-red-600 mt-2"><?= count($lowStockProducts) ?> low stock</p>
            </div>
            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl flex items-center justify-center flex-shrink-0 ml-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Customers -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-500 mb-1">Total Customers</p>
                <p class="text-2xl font-bold text-gray-800"><?= $totalUsers ?></p>
                <p class="text-xs text-red-600 mt-2">+8.2% new users</p>
            </div>
            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl flex items-center justify-center flex-shrink-0 ml-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Pending Reviews -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-500 mb-1">Pending Reviews</p>
                <p class="text-2xl font-bold text-gray-800"><?= $pendingReviews ?></p>
                <a href="<?= htmlspecialchars($BASE_URL . 'admin/reviews/index.php?status=pending', ENT_QUOTES, 'UTF-8') ?>" class="text-xs text-red-600 mt-2 hover:text-red-700">Review now →</a>
            </div>
            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl flex items-center justify-center flex-shrink-0 ml-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Average Rating -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-500 mb-1">Avg Rating</p>
                <p class="text-2xl font-bold text-gray-800"><?= number_format($avgRating, 1) ?></p>
                <p class="text-xs text-red-600 mt-2"><?= $totalReviews ?> reviews</p>
            </div>
            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl flex items-center justify-center flex-shrink-0 ml-3">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Alerts Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <!-- Low Stock Alert -->
    <?php if (count($lowStockProducts) > 0): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <div class="ml-4 flex-1">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Low Stock Alert</h3>
                <div class="space-y-2">
                    <?php foreach ($lowStockProducts as $p): ?>
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <span class="text-sm text-gray-700"><?= htmlspecialchars($p['name']) ?></span>
                            <span class="font-semibold bg-yellow-100 text-yellow-800 px-2.5 py-1 rounded-full text-xs"><?= $p['stock'] ?> left</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    

    <!-- Pending Reviews Alert -->
    <?php if ($pendingReviews > 0): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4 flex-1">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Pending Reviews</h3>
                <p class="text-sm text-gray-600 mb-4">You have <?= $pendingReviews ?> review<?= $pendingReviews > 1 ? 's' : '' ?> waiting for approval.</p>
                <a href="<?= htmlspecialchars($BASE_URL . 'admin/reviews/index.php?status=pending', ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center text-sm font-medium text-red-600 hover:text-red-700">
                    Review now
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Main Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Sales Chart (2 columns) -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-800">Sales Performance</h2>
                <p class="text-sm text-gray-500 mt-1">Last 7 days trend analysis</p>
            </div>
            <div style="height: 320px; min-height: 320px;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Orders (1 column) -->
    <div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Recent Orders</h2>
                        <p class="text-sm text-gray-500 mt-1">Latest customer activity</p>
                    </div>
                    <a href="<?= htmlspecialchars($BASE_URL . 'admin/orders/index.php', ENT_QUOTES, 'UTF-8') ?>" class="text-sm font-medium text-red-600 hover:text-red-700">View all</a>
                </div>
            </div>
            
            <div class="divide-y divide-gray-100">
                <?php if ($orders): foreach ($orders as $order): ?>
                    <a href="<?= htmlspecialchars($BASE_URL . 'admin/orders/view.php?id=' . $order['order_id'], ENT_QUOTES, 'UTF-8') ?>" class="block px-6 py-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-800 text-sm mb-1 truncate"><?= htmlspecialchars($order['full_name']) ?></p>
                                <p class="text-xs text-gray-500 font-mono">
                                    <?= !empty($order['order_number']) ? htmlspecialchars($order['order_number']) : 'Order #' . $order['order_id'] ?>
                                </p>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?= getStatusBadge($order['status']) ?> whitespace-nowrap ml-2">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-800">₹<?= number_format($order['total_amount'], 2) ?></p>
                            <p class="text-xs text-gray-500"><?= date("M d, Y", strtotime($order['order_date'])) ?></p>
                        </div>
                    </a>
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

<!-- Charts Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Monthly Revenue Trend -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-800">Monthly Revenue Trend</h2>
            <p class="text-sm text-gray-500 mt-1">6-month performance overview</p>
        </div>
        <div style="height: 320px; min-height: 320px;">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>

    <!-- Category Performance -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-800">Category Performance</h2>
            <p class="text-sm text-gray-500 mt-1">Revenue distribution analysis</p>
        </div>
        <div style="height: 320px; min-height: 320px;">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
</div>

<!-- Distribution Charts Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Categories Distribution -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-800">Categories Distribution</h2>
            <p class="text-sm text-gray-500 mt-1">Product count by category</p>
        </div>
        <div style="height: 320px; min-height: 320px;">
            <canvas id="categoriesPieChart"></canvas>
        </div>
    </div>

    <!-- Brands Distribution -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-800">Brands Distribution</h2>
            <p class="text-sm text-gray-500 mt-1">Product count by brand</p>
        </div>
        <div style="height: 320px; min-height: 320px;">
            <canvas id="brandsPieChart"></canvas>
        </div>
    </div>
</div>

<!-- Bottom Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Top Products -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-800">Top Selling Products</h2>
                <p class="text-sm text-gray-500 mt-1">Sales trend by product</p>
            </div>
            <div style="height: 320px; min-height: 320px;">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Pending Reviews List -->
    <div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Pending Reviews</h2>
                        <p class="text-sm text-gray-500 mt-1">Awaiting approval</p>
                    </div>
                    <a href="<?= htmlspecialchars($BASE_URL . 'admin/reviews/index.php?status=pending', ENT_QUOTES, 'UTF-8') ?>" class="text-sm font-medium text-red-600 hover:text-red-700">View all</a>
                </div>
            </div>
            
            <div class="divide-y divide-gray-100 max-h-[320px] overflow-y-auto">
                <?php if ($pendingReviewsList): foreach ($pendingReviewsList as $review): ?>
                    <a href="<?= htmlspecialchars($BASE_URL . 'admin/reviews/view.php?id=' . $review['review_id'], ENT_QUOTES, 'UTF-8') ?>" class="block px-6 py-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-800 text-sm mb-1 truncate"><?= htmlspecialchars($review['product_name']) ?></p>
                                <p class="text-xs text-gray-500 mb-2"><?= htmlspecialchars($review['user_name']) ?></p>
                                <div class="flex items-center space-x-0.5 mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="w-3.5 h-3.5 <?= $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                    <?php endfor; ?>
                                </div>
                                <?php if ($review['comment']): ?>
                                    <p class="text-xs text-gray-600 line-clamp-2 mb-2"><?= htmlspecialchars($review['comment']) ?></p>
                                <?php endif; ?>
                                <span class="text-xs text-red-600 font-medium">View & Approve →</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; else: ?>
                    <div class="p-8 text-center">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                        <p class="text-sm text-gray-500">No pending reviews</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Chart (7 days) - Amazon style
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($dates) ?>,
        datasets: [{
            label: 'Daily Sales',
            data: <?= json_encode($totals) ?>,
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.08)',
            borderWidth: 2.5,
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#ef4444',
            pointBorderWidth: 2,
            pointHoverRadius: 6,
            pointHoverBackgroundColor: '#ef4444',
            pointHoverBorderColor: '#fff',
            pointHoverBorderWidth: 2
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
                backgroundColor: '#ffffff',
                titleColor: '#111827',
                bodyColor: '#4b5563',
                borderColor: '#e5e7eb',
                borderWidth: 1,
                padding: 12,
                cornerRadius: 6,
                displayColors: false,
                callbacks: {
                    title: function(context) {
                        return context[0].label;
                    },
                    label: function(context) {
                        return 'Sales: ₹' + context.parsed.y.toLocaleString('en-IN');
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f3f4f6',
                    drawBorder: false,
                    lineWidth: 1
                },
                ticks: {
                    callback: function(value) {
                        return '₹' + (value / 1000).toFixed(1) + 'K';
                    },
                    color: '#6b7280',
                    font: {
                        size: 12,
                        weight: '500'
                    },
                    padding: 8
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
                        size: 12,
                        weight: '500'
                    },
                    padding: 12
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});

// Revenue by Category (Bar Chart - Amazon style)
<?php
$categoryLabels = array_column($categoryRevenue, 'name');
$categoryValues = array_column($categoryRevenue, 'revenue');
?>
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
const categoryChart = new Chart(categoryCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($categoryLabels) ?>,
        datasets: [{
            label: 'Revenue',
            data: <?= json_encode($categoryValues) ?>,
            backgroundColor: '#ef4444',
            borderColor: '#ef4444',
            borderWidth: 0,
            borderRadius: 4
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
                backgroundColor: '#ffffff',
                titleColor: '#111827',
                bodyColor: '#4b5563',
                borderColor: '#e5e7eb',
                borderWidth: 1,
                padding: 12,
                cornerRadius: 6,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        return 'Revenue: ₹' + context.parsed.y.toLocaleString('en-IN');
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
                        return '₹' + (value / 1000).toFixed(0) + 'K';
                    },
                    color: '#6b7280',
                    font: {
                        size: 12,
                        weight: '500'
                    },
                    padding: 8
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
                        size: 12,
                        weight: '500'
                    },
                    padding: 12
                }
            }
        }
    }
});

// Monthly Revenue (Line Chart - Amazon style)
<?php
$monthLabels = array_column($monthlyRevenue, 'month_label');
$monthValues = array_column($monthlyRevenue, 'revenue');
?>
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($monthLabels) ?>,
        datasets: [{
            label: 'Monthly Revenue',
            data: <?= json_encode($monthValues) ?>,
            borderColor: '#ec4899',
            backgroundColor: 'rgba(236, 72, 153, 0.08)',
            borderWidth: 2.5,
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#ec4899',
            pointBorderWidth: 2,
            pointHoverRadius: 6,
            pointHoverBackgroundColor: '#ec4899',
            pointHoverBorderColor: '#fff',
            pointHoverBorderWidth: 2
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
                backgroundColor: '#ffffff',
                titleColor: '#111827',
                bodyColor: '#4b5563',
                borderColor: '#e5e7eb',
                borderWidth: 1,
                padding: 12,
                cornerRadius: 6,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        return 'Revenue: ₹' + context.parsed.y.toLocaleString('en-IN');
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f3f4f6',
                    drawBorder: false,
                    lineWidth: 1
                },
                ticks: {
                    callback: function(value) {
                        return '₹' + (value / 1000).toFixed(0) + 'K';
                    },
                    color: '#6b7280',
                    font: {
                        size: 12,
                        weight: '500'
                    },
                    padding: 8
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
                        size: 12,
                        weight: '500'
                    },
                    padding: 12
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});

// Top Products (Line Chart - Amazon style)
<?php
$productLabels = array_column($topProducts, 'name');
$productValues = array_column($topProducts, 'total_sold');
// Truncate long product names for display
$productLabels = array_map(function($name) {
    return strlen($name) > 25 ? substr($name, 0, 25) . '...' : $name;
}, $productLabels);
?>
const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
const topProductsChart = new Chart(topProductsCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($productLabels) ?>,
        datasets: [{
            label: 'Units Sold',
            data: <?= json_encode($productValues) ?>,
            borderColor: '#f43f5e',
            backgroundColor: 'rgba(244, 63, 94, 0.08)',
            borderWidth: 2.5,
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#f43f5e',
            pointBorderWidth: 2,
            pointHoverRadius: 6,
            pointHoverBackgroundColor: '#f43f5e',
            pointHoverBorderColor: '#fff',
            pointHoverBorderWidth: 2
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
                backgroundColor: '#ffffff',
                titleColor: '#111827',
                bodyColor: '#4b5563',
                borderColor: '#e5e7eb',
                borderWidth: 1,
                padding: 12,
                cornerRadius: 6,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        return 'Sold: ' + context.parsed.y + ' units';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f3f4f6',
                    drawBorder: false,
                    lineWidth: 1
                },
                ticks: {
                    color: '#6b7280',
                    font: {
                        size: 12,
                        weight: '500'
                    },
                    padding: 8,
                    stepSize: 1
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
                        size: 12,
                        weight: '500'
                    },
                    padding: 12,
                    maxRotation: 45,
                    minRotation: 45
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});

// Categories Pie Chart
<?php
$categoryPieLabels = array_column($categoriesDistribution, 'name');
$categoryPieValues = array_column($categoriesDistribution, 'product_count');
// Define color palette matching the theme (red/pink gradient colors)
$categoryColors = [
    '#ef4444', '#f43f5e', '#ec4899', '#f97316',
    '#e11d48', '#be185d', '#dc2626', '#991b1b'
];
?>
const categoriesPieCtx = document.getElementById('categoriesPieChart').getContext('2d');
const categoriesPieChart = new Chart(categoriesPieCtx, {
    type: 'pie',
    data: {
        labels: <?= json_encode($categoryPieLabels) ?>,
        datasets: [{
            data: <?= json_encode($categoryPieValues) ?>,
            backgroundColor: <?= json_encode(array_slice($categoryColors, 0, count($categoryPieLabels))) ?>,
            borderColor: '#ffffff',
            borderWidth: 2,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    padding: 12,
                    usePointStyle: true,
                    pointStyle: 'circle',
                    font: {
                        size: 12,
                        weight: '500'
                    },
                    color: '#6b7280'
                }
            },
            tooltip: {
                backgroundColor: '#ffffff',
                titleColor: '#111827',
                bodyColor: '#4b5563',
                borderColor: '#e5e7eb',
                borderWidth: 1,
                padding: 12,
                cornerRadius: 6,
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return label + ': ' + value + ' products (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Brands Pie Chart
<?php
$brandPieLabels = array_column($brandsDistribution, 'name');
$brandPieValues = array_column($brandsDistribution, 'product_count');
// Use the same color palette
$brandColors = [
    '#ef4444', '#f43f5e', '#ec4899', '#f97316',
    '#e11d48', '#be185d', '#dc2626', '#991b1b'
];
?>
const brandsPieCtx = document.getElementById('brandsPieChart').getContext('2d');
const brandsPieChart = new Chart(brandsPieCtx, {
    type: 'pie',
    data: {
        labels: <?= json_encode($brandPieLabels) ?>,
        datasets: [{
            data: <?= json_encode($brandPieValues) ?>,
            backgroundColor: <?= json_encode(array_slice($brandColors, 0, count($brandPieLabels))) ?>,
            borderColor: '#ffffff',
            borderWidth: 2,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    padding: 12,
                    usePointStyle: true,
                    pointStyle: 'circle',
                    font: {
                        size: 12,
                        weight: '500'
                    },
                    color: '#6b7280'
                }
            },
            tooltip: {
                backgroundColor: '#ffffff',
                titleColor: '#111827',
                bodyColor: '#4b5563',
                borderColor: '#e5e7eb',
                borderWidth: 1,
                padding: 12,
                cornerRadius: 6,
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return label + ': ' + value + ' products (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
</script>

<?php include __DIR__ . "/includes/footer.php"; ?>