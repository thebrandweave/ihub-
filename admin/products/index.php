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
        ) AS primary_image,
        -- Check if product is in featured table
        CASE WHEN fp.product_id IS NOT NULL THEN 1 ELSE 0 END as is_featured
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    LEFT JOIN featured_products fp ON p.product_id = fp.product_id
    WHERE 1=1
";

$params = [];

// 1. Search Term
if ($searchTerm) {
    $sql .= " AND (p.name LIKE :search_term OR p.description LIKE :search_term)";
    $params[':search_term'] = '%' . $searchTerm . '%';
}

// 2. Category Filter
if ($categoryFilterId) {
    if ($categoryFilterId === 'unassigned') {
        $sql .= " AND p.category_id IS NULL";
    } elseif (is_numeric($categoryFilterId)) {
        $sql .= " AND p.category_id = :category_id";
        $params[':category_id'] = $categoryFilterId;
    }
}

// 3. Status Filter
if ($statusFilter && in_array($statusFilter, ['active', 'inactive'])) {
    $sql .= " AND p.status = :status_filter";
    $params[':status_filter'] = $statusFilter;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query("SELECT category_id,name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Stats
$totalProductsCount = count($products);
$activeProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
$lowStockCount = $pdo->query("SELECT COUNT(*) FROM products WHERE stock < 5")->fetchColumn();
$totalValue = $pdo->query("SELECT SUM(price * stock) FROM products")->fetchColumn() ?? 0;

function getImagePath($filename){
    global $BASE_URL;
    if(!$filename) return '';
    if(strpos($filename,'http')===0) return $filename;
    return $BASE_URL . 'uploads/products/' . $filename;
}

include __DIR__."/../includes/header.php";
?>

<div class="mb-4 md:mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-0">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-800">Product Management</h1>
            <p class="text-xs md:text-sm text-gray-500 mt-1">Manage inventory and featured status</p>
        </div>
        <a href="add.php" class="flex items-center justify-center px-4 md:px-5 py-2 md:py-2.5 bg-gradient-to-r from-red-500 to-pink-600 text-white text-sm md:text-base font-medium rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
            <svg class="w-4 h-4 md:w-5 md:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            <span class="hidden sm:inline">Add New Product</span>
            <span class="sm:hidden">Add</span>
        </a>
    </div>
</div>

<?php if ($message): ?>
<div class="mb-6 bg-green-50 border-l-4 border-green-500 rounded-lg p-4 shadow-sm">
    <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($message) ?></p>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Total Products</p>
        <p class="text-2xl font-bold text-gray-900"><?= number_format($totalProductsCount) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Active</p>
        <p class="text-2xl font-bold text-red-600"><?= number_format($activeProducts) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Low Stock</p>
        <p class="text-2xl font-bold text-red-600"><?= number_format($lowStockCount) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Inventory Value</p>
        <p class="text-2xl font-bold text-red-600">₹<?= number_format($totalValue, 0) ?></p>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 mb-6">
    <form method="GET" action="index.php" id="filterForm" class="space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold text-gray-700 mb-2 uppercase">Search</label>
                <input type="search" name="search" id="searchInput" placeholder="Search products..." value="<?= htmlspecialchars($searchTerm) ?>" class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-2 uppercase">Category</label>
                <select name="category" id="categoryFilter" class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg">
                    <option value="">All Categories</option>
                    <option value="unassigned" <?= $categoryFilterId == 'unassigned' ? 'selected' : '' ?>>Unassigned</option>
                    <?php foreach($categories as $c): ?>
                        <option value="<?= $c['category_id'] ?>" <?= $categoryFilterId == $c['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-2 uppercase">Status</label>
                <select name="status" id="statusFilter" class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg">
                    <option value="">All Status</option>
                    <option value="active" <?= $statusFilter == 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $statusFilter == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <div class="pt-4 border-t border-gray-200 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <input type="checkbox" id="selectAll" class="w-5 h-5 text-red-600 rounded">
                <label for="selectAll" class="text-sm font-semibold text-gray-700">Select All</label>
                <span id="selectedCount" class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded">0 selected</span>
            </div>
            <button type="button" onclick="deleteSelected()" class="px-5 py-2 text-sm text-white bg-red-600 rounded-lg">Delete Selected</button>
        </div>
    </form>
</div>

<?php if (count($products) > 0): ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <?php foreach ($products as $p): ?>
    <?php 
    $finalPrice = ($p['discount'] ?? 0) > 0 ? $p['price'] * (1 - $p['discount'] / 100) : $p['price'];
    ?>
    <div class="product-card group relative h-80 bg-white rounded-xl shadow-md border overflow-hidden">
        
        <div class="absolute top-3 left-3 z-30 opacity-0 group-hover:opacity-100 transition-opacity">
            <input type="checkbox" class="w-5 h-5 rounded border-gray-300 text-red-600 selectBox" value="<?= $p['product_id'] ?>">
        </div>

        <div class="absolute top-3 right-3 z-30">
            <button onclick="toggleFeatured(<?= $p['product_id'] ?>, this)" 
                    class="p-2 rounded-full backdrop-blur-md transition-all duration-200 shadow-lg 
                    <?= $p['is_featured'] ? 'bg-yellow-400 text-white' : 'bg-white/20 text-gray-200 hover:bg-white/40' ?>"
                    title="<?= $p['is_featured'] ? 'Remove from Featured' : 'Add to Featured' ?>">
                <svg class="w-5 h-5" fill="<?= $p['is_featured'] ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                </svg>
            </button>
        </div>

        <img src="<?= getImagePath($p['primary_image']) ?>" class="absolute inset-0 w-full h-full object-cover transition-transform group-hover:scale-110">
        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
        
        <div class="absolute inset-0 p-5 flex flex-col justify-between">
            <div>
                <span class="px-2 py-1 text-[10px] font-bold rounded bg-red-600 text-white uppercase"><?= $p['status'] ?></span>
                <h3 class="text-white font-bold mt-2 line-clamp-2"><?= htmlspecialchars($p['name']) ?></h3>
                <div class="flex items-baseline gap-2 mt-1">
                    <p class="text-xl font-bold text-white">₹<?= number_format($finalPrice, 0) ?></p>
                    <?php if ($p['discount'] > 0): ?>
                        <p class="text-xs text-white/60 line-through">₹<?= number_format($p['price'], 0) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-2 group-hover:translate-y-0 grid grid-cols-3 gap-2">
                <a href="view.php?id=<?= $p['product_id'] ?>" class="p-2 text-center text-white bg-red-600 rounded text-xs">View</a>
                <a href="edit.php?id=<?= $p['product_id'] ?>" class="p-2 text-center text-white bg-red-600 rounded text-xs">Edit</a>
                <a href="delete.php?id=<?= $p['product_id'] ?>" onclick="return confirm('Delete?')" class="p-2 text-center text-white bg-white/20 rounded text-xs">Del</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
// Toggle Featured AJAX logic
function toggleFeatured(productId, btn) {
    btn.disabled = true;
    fetch('toggle_featured.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const svg = btn.querySelector('svg');
            if (data.action === 'added') {
                btn.classList.remove('bg-white/20', 'text-gray-200');
                btn.classList.add('bg-yellow-400', 'text-white');
                svg.setAttribute('fill', 'currentColor');
            } else {
                btn.classList.add('bg-white/20', 'text-gray-200');
                btn.classList.remove('bg-yellow-400', 'text-white');
                svg.setAttribute('fill', 'none');
            }
        }
    }).finally(() => btn.disabled = false);
}

// Existing Select/Delete logic
const selectAll = document.getElementById("selectAll");
const boxes = document.querySelectorAll(".selectBox");
const counter = document.getElementById("selectedCount");

selectAll.addEventListener("change", () => {
    boxes.forEach(box => box.checked = selectAll.checked);
    updateCount();
});

boxes.forEach(b => b.addEventListener("change", updateCount));

function updateCount(){
    const checked = document.querySelectorAll(".selectBox:checked").length;
    counter.textContent = `${checked} selected`;
}

function deleteSelected(){
    const selected = [...document.querySelectorAll(".selectBox:checked")].map(cb=>cb.value);
    if(selected.length && confirm('Delete selected?')) {
        fetch('bulk_delete.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ids:selected}) })
        .then(() => location.reload());
    }
}

// Instant Filter
document.getElementById("categoryFilter").addEventListener("change", () => filterForm.submit());
document.getElementById("statusFilter").addEventListener("change", () => filterForm.submit());
</script>

<?php include __DIR__."/../includes/footer.php"; ?>