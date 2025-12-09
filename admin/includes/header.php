<?php
// Load notifications for the current admin on ANY admin page that includes this header.
// If a page already set $adminNotifications / $adminUnreadCount, we respect that.
if (!isset($adminNotifications) || !isset($adminUnreadCount)) {
    $adminNotifications = [];
    $adminUnreadCount   = 0;

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $adminId = $_SESSION['admin_id'] ?? null;

    if ($adminId && isset($pdo)) {
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
}

// Helper to map notification types to icons/colors
if (!function_exists('getAdminNotificationMeta')) {
    function getAdminNotificationMeta(string $type): array {
        switch ($type) {
            case 'inventory_alert':
                return ['icon' => 'exclamation-triangle', 'color' => 'text-yellow-500'];
            case 'system_alert':
                return ['icon' => 'shield-exclamation', 'color' => 'text-red-500'];
            case 'account_security':
                return ['icon' => 'lock-closed', 'color' => 'text-blue-500'];
            case 'promotion':
                return ['icon' => 'gift', 'color' => 'text-pink-500'];
            case 'order_update':
            default:
                return ['icon' => 'shopping-bag', 'color' => 'text-green-500'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'dark-bg': '#13151f',
                        'dark-secondary': '#1a1d29',
                        'dark-card': '#252836',
                    }
                },
            },
        }
    </script>
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #1a1d29;
        }
        ::-webkit-scrollbar-thumb {
            background: #374151;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #4b5563;
        }
        
        /* Mobile sidebar overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 40;
            transition: opacity 0.3s ease;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        /* Sidebar active state styling - ensure visibility on all devices */
        .sidebar a.bg-red-50 {
            background-color: #fef2f2 !important;
            color: #dc2626 !important;
            font-weight: 600 !important;
        }
        
        .sidebar a.bg-red-50 svg {
            color: #dc2626 !important;
        }
        
        /* Hover states for sidebar links */
        .sidebar a:not(.bg-red-50):hover {
            background-color: #f3f4f6 !important;
        }
        
        /* Sidebar mobile styles */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -100%;
                top: 0;
                bottom: 0;
                z-index: 50;
                transition: left 0.3s ease;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            }
            
            .sidebar.open {
                left: 0;
            }
            
            /* Make tables scrollable on mobile */
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            /* Responsive table wrapper */
            table {
                min-width: 640px;
            }
            
            /* Stack cards on mobile */
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            /* Adjust padding on mobile */
            .mobile-padding {
                padding: 1rem;
            }
        }
        
        /* Ensure main content doesn't overflow on mobile */
        @media (max-width: 768px) {
            main {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
        }
    </style>
</head>
<body class="bg-[#f8f9fa] font-sans leading-normal tracking-normal">
    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    
    <div class="flex h-screen bg-[#f8f9fa]">
        <?php include_once __DIR__ . '/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden w-full">
            <!-- Header -->
            <header class="flex justify-between items-center px-4 md:px-8 py-3 md:py-4 bg-white border-b border-gray-200 shadow-sm">
                <div class="flex items-center space-x-2 md:space-x-4">
                    <!-- Mobile Menu Button -->
                    <button id="sidebarToggle" onclick="toggleSidebar()" class="md:hidden p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    
                    <h1 class="text-lg md:text-2xl font-bold text-gray-800">Dashboard</h1>
                    
                    <!-- Breadcrumb -->
                    <div class="hidden md:flex items-center space-x-2 text-sm text-gray-500">
                        <span>Home</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <span class="text-gray-700 font-medium">Dashboard</span>
                    </div>
                </div>
                
                <div class="flex items-center space-x-2 md:space-x-4">
                    <!-- Search Bar -->
                    <div class="hidden md:flex items-center bg-gray-100 rounded-lg px-4 py-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="text" placeholder="Search..." class="bg-transparent border-none outline-none ml-2 text-sm text-gray-700 w-48">
                    </div>
                    
                    <!-- Mobile Search Button -->
                    <button class="md:hidden p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                    
                    <!-- Notifications -->
                    <div class="relative">
                        <button id="adminNotifButton" class="relative p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <?php if ($adminUnreadCount > 0): ?>
                                <span id="adminNotifDot" class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                            <?php endif; ?>
                        </button>

                        <!-- Notifications dropdown -->
                        <div id="adminNotifDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-xl shadow-lg z-50">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Notifications</p>
                                    <p class="text-xs text-gray-500">
                                        <?= $adminUnreadCount > 0 ? $adminUnreadCount . ' unread' : 'All caught up' ?>
                                    </p>
                                </div>
                                <?php if ($adminUnreadCount > 0): ?>
                                    <button id="adminNotifMarkRead" class="text-xs text-red-600 hover:text-red-700 font-medium">
                                        Mark all as read
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="max-h-80 overflow-y-auto">
                                <?php if (empty($adminNotifications)): ?>
                                    <div class="px-4 py-6 text-center text-sm text-gray-500">
                                        No notifications yet.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($adminNotifications as $n): 
                                        $meta = getAdminNotificationMeta($n['type']);
                                        $isUnread = empty($n['is_read']);
                                    ?>
                                        <div class="px-4 py-3 border-b border-gray-100 last:border-b-0 <?php echo $isUnread ? 'bg-red-50/40' : 'bg-white'; ?>">
                                            <div class="flex items-start gap-3">
                                                <div class="mt-1">
                                                    <!-- Simple icon using Heroicons style via SVG path -->
                                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 <?php echo $meta['color']; ?>">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                  d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="text-xs text-gray-500 mb-0.5 text-uppercase">
                                                        <?= htmlspecialchars(str_replace('_', ' ', $n['type'])) ?>
                                                    </p>
                                                    <p class="text-sm font-medium text-gray-800">
                                                        <?= htmlspecialchars($n['title']) ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500 mt-0.5">
                                                        <?= htmlspecialchars($n['message']) ?>
                                                    </p>
                                                    <div class="mt-1 flex items-center justify-between">
                                                        <span class="text-[11px] text-gray-400">
                                                            <?= date('d M Y, H:i', strtotime($n['created_at'])) ?>
                                                        </span>
                                                        <?php if (!empty($n['target_url'])): ?>
                                                            <a href="<?= htmlspecialchars($n['target_url']) ?>" class="text-[11px] text-red-600 hover:text-red-700 font-medium">
                                                                View
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Profile -->
                    <div class="flex items-center space-x-2 md:space-x-3 md:pl-4 md:border-l md:border-gray-200">
                        <div class="text-right hidden md:block">
                            <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></p>
                            <p class="text-xs text-gray-500">Administrator</p>
                        </div>
                        <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-gradient-to-br from-red-500 to-pink-600 flex items-center justify-center text-white font-semibold cursor-pointer text-sm md:text-base">
                            <?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-[#f8f9fa] p-3 md:p-6">
            
            <script>
                // Sidebar toggle functionality
                function toggleSidebar() {
                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('sidebarOverlay');
                    
                    sidebar.classList.toggle('open');
                    overlay.classList.toggle('active');
                }
                
                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(event) {
                    const sidebar = document.getElementById('sidebar');
                    const toggle = document.getElementById('sidebarToggle');
                    const overlay = document.getElementById('sidebarOverlay');
                    
                    if (window.innerWidth <= 768) {
                        if (!sidebar.contains(event.target) && !toggle.contains(event.target) && sidebar.classList.contains('open')) {
                            sidebar.classList.remove('open');
                            overlay.classList.remove('active');
                        }
                    }
                });
                
                // Close sidebar on window resize if desktop
                window.addEventListener('resize', function() {
                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('sidebarOverlay');
                    
                    if (window.innerWidth > 768) {
                        sidebar.classList.remove('open');
                        overlay.classList.remove('active');
                    }
                });

                // Admin notifications dropdown
                const notifButton  = document.getElementById('adminNotifButton');
                const notifDropdown = document.getElementById('adminNotifDropdown');
                const notifMarkRead = document.getElementById('adminNotifMarkRead');
                const notifDot      = document.getElementById('adminNotifDot');

                if (notifButton && notifDropdown) {
                    notifButton.addEventListener('click', function (e) {
                        e.stopPropagation();
                        notifDropdown.classList.toggle('hidden');
                    });

                    document.addEventListener('click', function (e) {
                        if (!notifDropdown.classList.contains('hidden') &&
                            !notifDropdown.contains(e.target) &&
                            e.target !== notifButton && !notifButton.contains(e.target)) {
                            notifDropdown.classList.add('hidden');
                        }
                    });
                }

                if (notifMarkRead) {
                    notifMarkRead.addEventListener('click', function () {
                        fetch('notifications_mark_read.php', {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        }).then(() => {
                            // Hide red dot and "Mark all as read" text
                            if (notifDot) notifDot.classList.add('hidden');
                            notifMarkRead.classList.add('hidden');
                        }).catch(() => {});
                    });
                }
            </script>