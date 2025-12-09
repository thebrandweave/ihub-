<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

$message = $_GET['msg'] ?? null;
$error   = $_GET['error'] ?? null;

// --- START: Server-Side Filtering Logic ---

// Get filter parameters from GET
$searchTerm = $_GET['search'] ?? '';
$categoryFilterId = $_GET['category'] ?? ''; // Category ID or 'unassigned'
$statusFilter = $_GET['status'] ?? ''; // 'active' or 'inactive'

$sql = "
    SELECT
        p.product_id,
        p.name,
        p.price,
        p.stock,
        p.discount,
        p.status,
        p.description,
        COALESCE(c.name,'Unassigned') AS category_name,
        b.name AS brand_name,
        (
            SELECT image_url FROM product_images 
            WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1
        ) AS primary_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE 1=1
";

$params = [];

// 1. Search Term (p.name or description)
if ($searchTerm) {
    $sql .= " AND (p.name LIKE :search_term OR p.description LIKE :search_term)";
    $params[':search_term'] = '%' . $searchTerm . '%';
}

// 2. Category Filter (by p.category_id)
if ($categoryFilterId) {
    if ($categoryFilterId === 'unassigned') {
        $sql .= " AND p.category_id IS NULL";
    } elseif (is_numeric($categoryFilterId)) {
        $sql .= " AND p.category_id = :category_id";
        $params[':category_id'] = $categoryFilterId;
    }
}

// 3. Status Filter (p.status)
if ($statusFilter && in_array($statusFilter, ['active', 'inactive'])) {
    $sql .= " AND p.status = :status_filter";
    $params[':status_filter'] = $statusFilter;
}

$sql .= " ORDER BY p.created_at DESC";

// Execute the filtered query using prepared statements
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories for the filter dropdown
$categories = $pdo->query("SELECT category_id,name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics for dashboard
$totalProductsCount = count($products);
$activeProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
$inactiveProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'inactive'")->fetchColumn();
$lowStockCount = $pdo->query("SELECT COUNT(*) FROM products WHERE stock < 5")->fetchColumn();
$totalValue = $pdo->query("SELECT SUM(price * stock) FROM products")->fetchColumn() ?? 0;

function getImagePath($filename){
    global $BASE_URL;
    if(!$filename) return '';
    if(strpos($filename,'http')===0) return $filename;
    return $BASE_URL . 'uploads/products/' . $filename;
}
// --- END: Server-Side Filtering Logic ---

include __DIR__."/../includes/header.php";
?>

<!-- Page Header -->
<div class="mb-4 md:mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-0">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-800">Product Management</h1>
            <p class="text-xs md:text-sm text-gray-500 mt-1">Manage your product inventory</p>
        </div>
        <a href="add.php"
            class="flex items-center justify-center px-4 md:px-5 py-2 md:py-2.5 bg-gradient-to-r from-red-500 to-pink-600 text-white text-sm md:text-base font-medium rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
            <svg class="w-4 h-4 md:w-5 md:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span class="hidden sm:inline">Add New Product</span>
            <span class="sm:hidden">Add</span>
        </a>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($message): ?>
<div class="mb-6 bg-green-50 border-l-4 border-green-500 rounded-lg p-4 shadow-sm">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($message) ?></p>
    </div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-lg p-4 shadow-sm">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Total Products -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Total Products</p>
                <p class="text-2xl font-bold text-gray-900"><?= number_format($totalProductsCount) ?></p>
                <p class="text-xs text-gray-500 mt-1"><?= number_format($activeProducts) ?> active</p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Active Products -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Active Products</p>
                <p class="text-2xl font-bold text-red-600"><?= number_format($activeProducts) ?></p>
                <p class="text-xs text-red-600 mt-1"><?= $totalProductsCount > 0 ? number_format(($activeProducts / $totalProductsCount) * 100, 1) : 0 ?>% of total</p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Low Stock</p>
                <p class="text-2xl font-bold text-red-600"><?= number_format($lowStockCount) ?></p>
                <p class="text-xs text-red-600 mt-1">Needs attention</p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Inventory Value -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Inventory Value</p>
                <p class="text-2xl font-bold text-red-600">₹<?= number_format($totalValue, 0) ?></p>
                <p class="text-xs text-gray-500 mt-1">Total stock value</p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 mb-6">
    <form method="GET" action="index.php" id="filterForm" class="space-y-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Filters & Search</h3>
            <?php if ($searchTerm || $categoryFilterId || $statusFilter): ?>
                <a href="index.php" class="text-xs font-medium text-red-600 hover:text-red-700 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Clear All
                </a>
            <?php endif; ?>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search Input -->
            <div class="relative lg:col-span-2">
                <label for="searchInput" class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Search Products</label>
                <div class="relative">
                    <input type="search" name="search" id="searchInput"
                        placeholder="Search by name, brand, or description..."
                        value="<?= htmlspecialchars($searchTerm) ?>"
                        class="w-full pl-11 pr-4 py-3 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition duration-200 bg-white">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            <!-- Category Filter -->
            <div>
                <label for="categoryFilter" class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Category</label>
                <select name="category" id="categoryFilter" 
                    class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition duration-200 bg-white">
                    <option value="" <?= $categoryFilterId == '' ? 'selected' : '' ?>>All Categories</option>
                    <option value="unassigned" <?= $categoryFilterId == 'unassigned' ? 'selected' : '' ?>>Unassigned</option>
                    <?php foreach($categories as $c): ?>
                        <option value="<?= $c['category_id'] ?>" <?= $categoryFilterId == $c['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label for="statusFilter" class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Status</label>
                <select name="status" id="statusFilter" 
                    class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition duration-200 bg-white">
                    <option value="" <?= $statusFilter == '' ? 'selected' : '' ?>>All Status</option>
                    <option value="active" <?= $statusFilter == 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $statusFilter == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="pt-4 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <input type="checkbox" id="selectAll" 
                    class="w-5 h-5 text-red-600 border-gray-300 rounded focus:ring-red-500 focus:ring-2 cursor-pointer">
                <label for="selectAll" class="text-sm font-semibold text-gray-700 cursor-pointer">Select All Products</label>
                <span id="selectedCount" class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded">0 selected</span>
            </div>
            <button type="button" onclick="deleteSelected()"
                class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Delete Selected
            </button>
        </div>
    </form>
</div>


<!-- Products Grid -->
<?php if (count($products) > 0): ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6" id="productGrid">
    <?php foreach ($products as $p): ?>
    <div class="product-card group" 
         data-name="<?= strtolower($p['name']) ?>"
         data-category="<?= strtolower($p['category_name']) ?>"
         data-status="<?= $p['status'] ?>">
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 flex flex-col relative h-full overflow-hidden transition-all duration-300 hover:shadow-xl hover:border-gray-300 group">
            
            <!-- Checkbox -->
            <div class="absolute top-3 left-3 z-30 opacity-0 group-hover:opacity-100 transition-opacity">
                <input type="checkbox"
                    class="w-5 h-5 rounded border-gray-300 text-red-600 selectBox focus:ring-red-500 focus:ring-2 cursor-pointer bg-white shadow-sm"
                    value="<?= $p['product_id'] ?>">
            </div>

            <!-- Product Image -->
            <div class="relative h-56 bg-gradient-to-br from-gray-50 to-gray-100 overflow-hidden">
                <img src="<?= getImagePath($p['primary_image']) ?>"
                    alt="<?= htmlspecialchars($p['name']) ?>"
                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                    onerror="this.src='https://via.placeholder.com/400x400?text=No+Image'">
                
                <!-- Status Badge -->
                <span class="absolute top-3 right-3 px-3 py-1.5 text-xs font-bold rounded-md shadow-lg z-20 backdrop-blur-sm
                    <?= $p['status'] == 'active'
                        ? 'bg-red-500/90 text-white'
                        : 'bg-red-300/90 text-white' ?>">
                    <?= ucfirst($p['status']) ?>
                </span>

                <!-- Stock Indicator Overlay -->
                <?php if ($p['stock'] < 5): ?>
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-red-600/90 to-transparent p-3">
                        <p class="text-xs font-bold text-white flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Low Stock: <?= $p['stock'] ?> left
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Info -->
            <div class="p-5 flex-1 flex flex-col bg-white">
                <!-- Category & Brand -->
                <div class="mb-2">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                            <?= htmlspecialchars($p['category_name']) ?>
                        </span>
                        <?php if ($p['brand_name']): ?>
                            <span class="text-xs text-gray-400">•</span>
                            <span class="text-xs font-medium text-gray-600"><?= htmlspecialchars($p['brand_name']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Product Name -->
                <h3 class="text-base font-bold text-gray-900 mb-3 line-clamp-2 leading-snug cursor-pointer hover:text-red-600 transition-colors min-h-[3rem]"
                    data-description="<?= htmlspecialchars($p['description'] ?: 'No description provided.') ?>"
                    onmouseover="showDescription(this)"
                    onmouseout="hideDescription()">
                    <?= htmlspecialchars($p['name']) ?>
                </h3>

                <!-- Price Section -->
                <div class="mb-4">
                    <?php 
                    $discount = $p['discount'] ?? 0;
                    $finalPrice = $discount > 0 ? $p['price'] * (1 - $discount / 100) : $p['price'];
                    ?>
                    <div class="flex items-baseline gap-2">
                        <p class="text-2xl font-bold text-gray-900">₹<?= number_format($finalPrice, 0) ?></p>
                        <?php if ($discount > 0): ?>
                            <p class="text-sm text-gray-500 line-through">₹<?= number_format($p['price'], 0) ?></p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700">
                                -<?= number_format($discount, 0) ?>%
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Stock & Metrics -->
                <div class="grid grid-cols-2 gap-3 mb-4 pt-4 border-t border-gray-100">
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Stock</p>
                        <div class="flex items-center gap-2">
                            <p class="text-lg font-bold <?= $p['stock'] < 5 ? 'text-red-600' : ($p['stock'] < 20 ? 'text-red-500' : 'text-red-600') ?>">
                                <?= $p['stock'] ?>
                            </p>
                            <span class="text-xs text-gray-500">units</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Value</p>
                        <p class="text-lg font-bold text-gray-900">₹<?= number_format($p['price'] * $p['stock'], 0) ?></p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-auto pt-4 border-t border-gray-100">
                    <div class="grid grid-cols-3 gap-2">
                        <a href="view.php?id=<?= $p['product_id'] ?>"
                           class="flex flex-col items-center justify-center px-2 py-2.5 text-xs font-semibold text-center text-red-700 bg-red-50 hover:bg-red-100 rounded-md transition-all duration-150 hover:shadow-sm"
                           title="View Details">
                            <svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <span>View</span>
                        </a>
                        <a href="edit.php?id=<?= $p['product_id'] ?>"
                           class="flex flex-col items-center justify-center px-2 py-2.5 text-xs font-semibold text-center text-red-700 bg-red-50 hover:bg-red-100 rounded-md transition-all duration-150 hover:shadow-sm"
                           title="Edit Product">
                            <svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            <span>Edit</span>
                        </a>
                        <a href="delete.php?id=<?= $p['product_id'] ?>"
                           onclick="return confirm('Are you sure you want to delete this product?')"
                           class="flex flex-col items-center justify-center px-2 py-2.5 text-xs font-semibold text-center text-red-700 bg-red-50 hover:bg-red-100 rounded-md transition-all duration-150 hover:shadow-sm"
                           title="Delete Product">
                            <svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            <span>Delete</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Description Tooltip -->
            <div id="description-tooltip" class="absolute z-50 bottom-full right-0 mb-2 w-72 p-4 bg-gray-900 text-white text-xs rounded-lg shadow-2xl hidden pointer-events-none border border-gray-700"></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
    <!-- Empty State -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-16 text-center">
        <div class="max-w-md mx-auto">
            <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">No Products Found</h3>
            <p class="text-sm text-gray-600 mb-2">No products match your current search criteria.</p>
            <?php if ($searchTerm || $categoryFilterId || $statusFilter): ?>
                <p class="text-xs text-gray-500 mb-6">Try adjusting your filters or search terms.</p>
                <a href="index.php" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200 mr-3">
                    Clear Filters
                </a>
            <?php else: ?>
                <p class="text-xs text-gray-500 mb-6">Get started by adding your first product to the catalog.</p>
            <?php endif; ?>
            <a href="add.php" class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-red-500 to-pink-600 rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add New Product
            </a>
        </div>
    </div>
<?php endif; ?>


<style>
/* Line clamp utility for product names */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Premium product card styling */
.product-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Enhanced image hover effect */
.product-card img {
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Custom scrollbar for better UX */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
    border: 2px solid #f1f5f9;
}

::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Premium tooltip styling */
#description-tooltip {
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Statistics card hover effect */
.grid > div:hover {
    transform: translateY(-2px);
}

/* Enhanced focus states */
input:focus,
select:focus {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

/* Premium button styles */
a[href*="view.php"],
a[href*="edit.php"],
a[href*="delete.php"] {
    transition: all 0.2s ease-in-out;
}

a[href*="view.php"]:hover,
a[href*="edit.php"]:hover,
a[href*="delete.php"]:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
</style>

<script>
// SELECT ALL + COUNT
const selectAll = document.getElementById("selectAll");
const boxes = document.querySelectorAll(".selectBox");
const counter = document.getElementById("selectedCount");

function updateCount(){
    const checked = document.querySelectorAll(".selectBox:checked").length;
    if (counter) {
        counter.textContent = `${checked} selected`;
        if (checked > 0) {
            counter.classList.add('bg-red-100', 'text-red-700');
            counter.classList.remove('bg-gray-100', 'text-gray-500');
        } else {
            counter.classList.remove('bg-red-100', 'text-red-700');
            counter.classList.add('bg-gray-100', 'text-gray-500');
        }
    }
}

selectAll.addEventListener("change", () => {
    boxes.forEach(box => { 
        // Only check/uncheck visible boxes
        if (box.closest('.product-card').style.display !== 'none') {
            box.checked = selectAll.checked; 
        }
    });
    updateCount();
});

boxes.forEach(b => b.addEventListener("change", updateCount));


// BULK DELETE
function deleteSelected(){
    const selected = [...document.querySelectorAll(".selectBox:checked")].map(cb=>cb.value);

    if(selected.length === 0){
        alert("No products selected");
        return;
    }

    if(!confirm(`Delete ${selected.length} products?`)) return;

    fetch('bulk_delete.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ids:selected})
    })
    .then(res => res.json())
    .then(() => location.reload());
}

// --- START: Instant Submission Logic (Like admin/admin/index.php) ---
const searchInput = document.getElementById("searchInput");
const categoryFilter = document.getElementById("categoryFilter");
const statusFilter = document.getElementById("statusFilter");
const filterForm = document.getElementById("filterForm");

// Function to submit the form
function submitFilter() {
    filterForm.submit();
}

// 1. Submit when category or status changes
categoryFilter.addEventListener("change", submitFilter);
statusFilter.addEventListener("change", submitFilter);

// 2. Submit search after a short delay (debounce) to avoid too many requests while typing
let searchTimeout = null;
searchInput.addEventListener("input", () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(submitFilter, 500); // Wait 500ms before submitting
});
// --- END: Instant Submission Logic ---


// JAVASCRIPT FOR TOOLTIP (Description)
let currentTooltip = null;

function showDescription(element) {
    const description = element.getAttribute('data-description');
    
    // Find the closest tooltip element relative to the hovered product card
    const card = element.closest('.product-card');
    const tooltip = card.querySelector('#description-tooltip');

    // Ensure only one tooltip is active globally at a time
    if (currentTooltip && currentTooltip !== tooltip) {
        currentTooltip.classList.add('hidden');
    }
    currentTooltip = tooltip;

    // Set content and make visible
    tooltip.innerHTML = `<strong>Description:</strong><br>${description}`;
    tooltip.classList.remove('hidden');
}

function hideDescription() {
    // Hide the tooltip after a short delay if mouse leaves the product name
    setTimeout(() => {
        if (currentTooltip && !currentTooltip.matches(':hover')) {
            currentTooltip.classList.add('hidden');
            currentTooltip = null;
        }
    }, 100);
}

// Ensure tooltip hides if mouse leaves the area
document.addEventListener('mouseout', (e) => {
    if (currentTooltip) {
        // Check if the mouse is moving outside of the product name or the tooltip itself
        if (!e.relatedTarget || (!e.relatedTarget.closest('.product-card') && e.relatedTarget !== currentTooltip)) {
             hideDescription();
        }
    }
});
</script>

<?php include __DIR__."/../includes/footer.php"; ?>