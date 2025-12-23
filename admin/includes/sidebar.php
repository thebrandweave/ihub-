<?php
if (!isset($BASE_URL)) {
    require_once __DIR__ . '/../../config/config.php';
}

// Get the current script path and URI
$script_path = $_SERVER['SCRIPT_NAME'];
$current_uri = $_SERVER['REQUEST_URI'];

// Function to check if a menu item is active
function isActive($menu_path) {
    global $script_path, $current_uri;
    
    $clean_uri = preg_replace('/\?.*$/', '', $current_uri);
    $clean_uri = rtrim($clean_uri, '/');
    $clean_script = str_replace('\\', '/', $script_path);
    
    if ($menu_path === 'dashboard') {
        if (preg_match('#/admin/index\.php$#', $clean_script) || 
            preg_match('#/admin/?$#', $clean_uri) ||
            (preg_match('#/admin/index\.php#', $clean_uri) && !preg_match('#/admin/[^/]+/#', $clean_uri))) {
            if (!preg_match('#/admin/(admin|products|users|orders|settings|social_media)/#', $clean_uri) &&
                !preg_match('#/admin/(admin|products|users|orders|settings|social_media)$#', $clean_uri)) {
                return true;
            }
        }
        return false;
    }
    
    if ($menu_path === 'admin') {
        return (preg_match('#/admin/admin(/|$|index\.php|add\.php|edit\.php)#', $clean_uri) || 
                preg_match('#/admin/admin/#', $clean_uri) ||
                preg_match('#/admin/admin/index\.php#', $clean_uri) ||
                preg_match('#/admin/admin/index\.php$#', $clean_script));
    }
    
    $path_pattern = '/admin/' . trim($menu_path, '/');
    
    if (preg_match('#' . preg_quote($path_pattern, '#') . '(/|$|index\.php|add\.php|edit\.php|view\.php)#', $clean_uri) ||
        preg_match('#' . preg_quote($path_pattern, '#') . '/#', $clean_uri) ||
        preg_match('#' . preg_quote($path_pattern, '#') . '/index\.php$#', $clean_script)) {
        return true;
    }
    
    return false;
}
?>

<aside id="sidebar" class="sidebar w-64 flex-shrink-0 bg-white border-r border-gray-200" aria-label="Sidebar">
    <div class="flex flex-col h-full">

        <div class="h-16 flex items-center justify-between px-4 md:px-6 border-b border-gray-200 bg-white">
            <a href="<?= $BASE_URL ?>admin/index.php" class="flex items-center space-x-2 md:space-x-3">
                <div class="w-8 h-8 md:w-9 md:h-9 flex items-center justify-center flex-shrink-0">
                    <img src="<?= $BASE_URL ?>admin/assets/image/logo/ihub.png" alt="iHUB Logo" class="w-full h-full object-contain">
                </div>
                <div>
                    <p class="text-base md:text-lg font-bold text-red-600">iHUB</p>
                    <span class="text-xs text-gray-400 -mt-1 block hidden md:block">Admin Management</span>
                </div>
            </a>
            <button onclick="toggleSidebar()" class="md:hidden p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto text-gray-700">

            <p class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">General</p>

            <a href="<?= $BASE_URL ?>admin/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition
                <?= isActive('dashboard') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-100 text-gray-700' ?>">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <p class="px-3 pt-6 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Management</p>

            <a href="<?= $BASE_URL ?>admin/admin/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition
                <?= isActive('admin') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-100 text-gray-700' ?>">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span>Admins</span>
            </a>

            <a href="<?= $BASE_URL ?>admin/products/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition
                <?= isActive('products') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-100 text-gray-700' ?>">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <span>Products</span>
            </a>

            <a href="<?= $BASE_URL ?>admin/categories/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition
                <?= isActive('categories') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-100 text-gray-700' ?>">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z"/>
                </svg>
                <span>Categories</span>
            </a>

            <a href="<?= $BASE_URL ?>admin/brands/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition
                <?= isActive('brands') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-100 text-gray-700' ?>">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <span>Brands</span>
            </a>

            <a href="<?= $BASE_URL ?>admin/social_media/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition
                <?= isActive('social_media') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-100 text-gray-700' ?>">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
                <span>Social Media</span>
            </a>

            <a href="<?= $BASE_URL ?>admin/users/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition
                <?= isActive('users') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-100 text-gray-700' ?>">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Users</span>
            </a>

            <a href="<?= $BASE_URL ?>admin/orders/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition
                <?= isActive('orders') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-100 text-gray-700' ?>">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <span>Orders</span>
            </a>

            <a href="<?= $BASE_URL ?>admin/advertisements/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition
                <?= isActive('advertisements') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-100 text-gray-700' ?>">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h2m-1-2v2m-7 6h14m-8 4h6m-3-4v6m-4-6v2m8-2v2"/>
                </svg>
                <span>Advertisements</span>
            </a>

            <a href="<?= $BASE_URL ?>admin/notifications/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition
                <?= isActive('notifications') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-100 text-gray-700' ?>">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span>Notifications</span>
            </a>

            <a href="<?= $BASE_URL ?>admin/reviews/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition
                <?= isActive('reviews') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-100 text-gray-700' ?>">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
                <span>Reviews</span>
            </a>

            <a href="<?= $BASE_URL ?>admin/subscriptions/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition
                <?= isActive('subscriptions') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-100 text-gray-700' ?>">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
                <span>Subscriptions</span>
            </a>

            <p class="px-3 pt-6 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Settings</p>

            <a href="<?= $BASE_URL ?>admin/settings/index.php"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition
                <?= isActive('settings') ? 'bg-red-50 text-red-600 font-semibold' : 'hover:bg-gray-100 text-gray-700' ?>">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Settings</span>
            </a>

        </nav>

        <div class="px-3 py-3 border-t border-gray-200 bg-white">
            <a href="<?= $BASE_URL ?>auth/logout.php"
                class="flex items-center px-3 py-2.5 text-sm text-red-500 hover:bg-red-50 rounded-lg transition">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3v-4a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Logout</span>
            </a>
        </div>

    </div>
</aside>