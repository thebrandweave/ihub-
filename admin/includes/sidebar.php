<?php
if (!isset($BASE_URL)) {
    require_once __DIR__ . '/../../config/config.php';
}

// Get current admin ID for notifications
$adminId = $_SESSION['admin_id'] ?? null;

/**
 * FETCH UNREAD COUNTS FOR BADGES
 */
$unreadEnquiries = 0;
$unreadNotifications = 0;
$pendingReviewsCount = 0;

if (isset($pdo)) {
    // Count unread contact messages
    $unreadEnquiries = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn();
    
    // Count unread admin notifications
    if ($adminId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$adminId]);
        $unreadNotifications = (int)$stmt->fetchColumn();
    }

    // Count pending reviews
    $pendingReviewsCount = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn();
}

// Get the current script path and URI
$script_path = $_SERVER['SCRIPT_NAME'];
$current_uri = $_SERVER['REQUEST_URI'];

// Function to check if a menu item is active
function isActive($menu_path) {
    global $script_path, $current_uri;
    $clean_uri = preg_replace('/\?.*$/', '', $current_uri);
    $clean_uri = rtrim($clean_uri, '/');
    $path_pattern = '/admin/' . trim($menu_path, '/');
    
    // Dashboard special case
    if ($menu_path === 'dashboard') {
        return (preg_match('#/admin/index\.php$#', $script_path) || preg_match('#/admin/?$#', $clean_uri));
    }
    
    return (preg_match('#' . preg_quote($path_pattern, '#') . '(/|$|index\.php)#', $clean_uri));
}
?>

<head>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<aside id="sidebar" class="sidebar w-64 flex-shrink-0 bg-white border-r border-gray-200 shadow-sm transition-all duration-300">
    <div class="flex flex-col h-full">

        <div class="h-16 flex items-center justify-between px-4 md:px-6 border-b border-gray-200 bg-white">
            <a href="<?= $BASE_URL ?>admin/index.php" class="flex items-center space-x-3">
                <img src="<?= $BASE_URL ?>admin/assets/image/logo/ihub.png" alt="Logo" class="w-9 h-9 object-contain">
                <div>
                    <p class="text-lg font-bold text-red-600 leading-none">iHUB</p>
                    <span class="text-[10px] text-gray-400 uppercase tracking-widest">Admin Panel</span>
                </div>
            </a>
            <button onclick="toggleSidebar()" class="md:hidden p-2 text-gray-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">

            <p class="px-3 py-2 text-xs font-bold text-gray-400 uppercase tracking-widest">General</p>

            <a href="<?= $BASE_URL ?>admin/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition-all
                <?= isActive('dashboard') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-50 text-gray-600' ?>">
                <i class="bi bi-speedometer2 mr-3 text-lg"></i>
                <span>Dashboard</span>
            </a>

            <a href="<?= $BASE_URL ?>admin/messages/index.php"
                class="flex items-center justify-between px-3 py-2.5 text-sm rounded-lg transition-all
                <?= isActive('messages') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-50 text-gray-600' ?>">
                <div class="flex items-center">
                    <i class="bi bi-chat-left-text mr-3 text-lg"></i>
                    <span>Enquiries</span>
                </div>
                <?php if ($unreadEnquiries > 0): ?>
                    <span class="bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $unreadEnquiries ?></span>
                <?php endif; ?>
            </a>

            <p class="px-3 pt-6 pb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">Inventory</p>

            <a href="<?= $BASE_URL ?>admin/products/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition-all
                <?= isActive('products') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-50 text-gray-600' ?>">
                <i class="bi bi-box-seam mr-3 text-lg"></i>
                <span>Products</span>
            </a>

            <a href="<?= $BASE_URL ?>admin/categories/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition-all
                <?= isActive('categories') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-50 text-gray-600' ?>">
                <i class="bi bi-grid-3x3-gap mr-3 text-lg"></i>
                <span>Categories</span>
            </a>

            <a href="<?= $BASE_URL ?>admin/brands/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition-all
                <?= isActive('brands') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-50 text-gray-600' ?>">
                <i class="bi bi-patch-check mr-3 text-lg"></i>
                <span>Brands</span>
            </a>

            <p class="px-3 pt-6 pb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">Customers & Sales</p>

            <a href="<?= $BASE_URL ?>admin/orders/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition-all
                <?= isActive('orders') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-50 text-gray-600' ?>">
                <i class="bi bi-cart-check mr-3 text-lg"></i>
                <span>Orders</span>
            </a>

            <a href="<?= $BASE_URL ?>admin/users/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition-all
                <?= isActive('users') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-50 text-gray-600' ?>">
                <i class="bi bi-people mr-3 text-lg"></i>
                <span>Customers</span>
            </a>

            <a href="<?= $BASE_URL ?>admin/reviews/index.php"
                class="flex items-center justify-between px-3 py-2.5 text-sm rounded-lg transition-all
                <?= isActive('reviews') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-50 text-gray-600' ?>">
                <div class="flex items-center">
                    <i class="bi bi-star mr-3 text-lg"></i>
                    <span>Reviews</span>
                </div>
                <?php if ($pendingReviewsCount > 0): ?>
                    <span class="bg-orange-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $pendingReviewsCount ?></span>
                <?php endif; ?>
            </a>

            <p class="px-3 pt-6 pb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">Communication</p>

            <a href="<?= $BASE_URL ?>admin/notifications/index.php"
                class="flex items-center justify-between px-3 py-2.5 text-sm rounded-lg transition-all
                <?= isActive('notifications') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-50 text-gray-600' ?>">
                <div class="flex items-center">
                    <i class="bi bi-bell mr-3 text-lg"></i>
                    <span>Notifications</span>
                </div>
                <?php if ($unreadNotifications > 0): ?>
                    <span class="bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $unreadNotifications ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= $BASE_URL ?>admin/social_media/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition-all
                <?= isActive('social_media') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-50 text-gray-600' ?>">
                <i class="bi bi-share mr-3 text-lg"></i>
                <span>Social Links</span>
            </a>

            <p class="px-3 pt-6 pb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">Settings</p>

            <a href="<?= $BASE_URL ?>admin/admin/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition-all
                <?= isActive('admin') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-50 text-gray-600' ?>">
                <i class="bi bi-person-gear mr-3 text-lg"></i>
                <span>Admin Users</span>
            </a>

            <a href="<?= $BASE_URL ?>admin/settings/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition-all
                <?= isActive('settings') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-50 text-gray-600' ?>">
                <i class="bi bi-sliders mr-3 text-lg"></i>
                <span>Site Settings</span>
            </a>

        </nav>

        <div class="px-3 py-4 border-t border-gray-200">
            <a href="<?= $BASE_URL ?>auth/admin/logout.php"
                class="flex items-center px-3 py-2.5 text-sm text-red-500 hover:bg-red-50 rounded-lg transition-all">
                <i class="bi bi-box-arrow-right mr-3 text-lg"></i>
                <span class="font-medium">Sign Out</span>
            </a>
        </div>

    </div>
</aside>