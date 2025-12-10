<?php
// --- START: Server-Side Logic for Categories ---
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

$message = $_GET['msg'] ?? null;
$error = $_GET['error'] ?? null;

// Get filter parameter from GET
$searchTerm = $_GET['search'] ?? '';

// Base SQL to fetch categories and count associated products
$sql = "
    SELECT 
        c.category_id,
        c.name,
        c.description,
        c.image_url, 
        c.created_at,
        COUNT(p.product_id) AS total_products
    FROM categories c
    LEFT JOIN products p ON c.category_id = p.category_id
    WHERE 1=1
";

$params = [];

// 1. Search Term (c.name or c.description)
if ($searchTerm) {
    $sql .= " AND (c.name LIKE :search_term OR c.description LIKE :search_term)";
    $params[':search_term'] = '%' . $searchTerm . '%';
}

// Group by to aggregate the product count
$sql .= " GROUP BY c.category_id, c.name, c.description, c.image_url, c.created_at
          ORDER BY c.created_at DESC";

// Execute the filtered query
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
    $error = "Database error: Could not fetch categories.";
}

// Function to handle image path retrieval
function getImagePath($filename){
    global $BASE_URL;
    if(!$filename) return '';
    if(strpos($filename,'http')===0) return $filename;
    return $BASE_URL . 'uploads/categories/' . $filename;
}

// --- Calculate statistics for dashboard ---
$totalCategoriesCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn() ?? 0;
$unassignedProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE category_id IS NULL")->fetchColumn() ?? 0;
$totalProductsInSystem = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() ?? 0;

// --- END: Server-Side Logic for Categories ---

include __DIR__."/../includes/header.php";
?>

<div class="mb-4 md:mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-0">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-800">Category Management</h1>
            <p class="text-xs md:text-sm text-gray-500 mt-1">Organize your products with categories</p>
        </div>
        <a href="add.php"
            class="flex items-center justify-center px-4 md:px-5 py-2 md:py-2.5 bg-gradient-to-r from-red-500 to-pink-600 text-white text-sm md:text-base font-medium rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
            <svg class="w-4 h-4 md:w-5 md:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span class="hidden sm:inline">Add New Category</span>
            <span class="sm:hidden">Add</span>
        </a>
    </div>
</div>

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

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Total Categories</p>
                <p class="text-2xl font-bold text-gray-900"><?= number_format($totalCategoriesCount) ?></p>
                <p class="text-xs text-gray-500 mt-1">Total existing groups</p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Total Products</p>
                <p class="text-2xl font-bold text-red-600"><?= number_format($totalProductsInSystem) ?></p>
                <p class="text-xs text-red-600 mt-1">Inventory count</p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Unassigned Products</p>
                <p class="text-2xl font-bold <?= $unassignedProducts > 0 ? 'text-red-600' : 'text-gray-900' ?>"><?= number_format($unassignedProducts) ?></p>
                <p class="text-xs text-gray-500 mt-1">Need a category assigned</p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<hr>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 mb-6">
    <form method="GET" action="index.php" id="filterForm" class="space-y-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Search</h3>
            <?php if ($searchTerm): ?>
                <a href="index.php" class="text-xs font-medium text-red-600 hover:text-red-700 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Clear Search
                </a>
            <?php endif; ?>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="relative lg:col-span-3">
                <label for="searchInput" class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Search Categories</label>
                <div class="relative">
                    <input type="search" name="search" id="searchInput"
                        placeholder="Search by name or description..."
                        value="<?= htmlspecialchars($searchTerm) ?>"
                        class="w-full pl-11 pr-4 py-3 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition duration-200 bg-white">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </form>
</div>

<hr>

<?php if (count($categories) > 0): ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6" id="categoryGrid">
    <?php foreach ($categories as $c): ?>
    <div class="category-card group">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 flex flex-col relative h-full overflow-hidden transition-all duration-300 hover:shadow-xl hover:border-red-300">
            
            <div class="relative h-40 bg-gray-100 overflow-hidden">
                <img src="<?= getImagePath($c['image_url']) ?>"
                    alt="<?= htmlspecialchars($c['name']) ?>"
                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                    onerror="this.onerror=null;this.src='https://via.placeholder.com/400x200?text=Category'">
                
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-gray-900/80 to-transparent p-3 flex justify-between items-end">
                    </div>
            </div>

            <div class="p-4 flex-1 flex flex-col">
                
                <div class="flex justify-between items-start mb-2">
                    <h3 class="text-lg font-bold text-gray-900 leading-snug line-clamp-1">
                        <?= htmlspecialchars($c['name']) ?>
                    </h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        <?= number_format($c['total_products'] ?? 0) ?> Items
                    </span>
                </div>
                
                <p class="text-sm text-gray-600 line-clamp-2 mb-2" title="<?= htmlspecialchars($c['description'] ?: 'No description.') ?>">
                    <?= htmlspecialchars($c['description'] ?: 'No description provided.') ?>
                </p>

                <p class="text-xs text-gray-500 mb-4">
                    Added: <?= $c['created_at'] ? date('M d, Y', strtotime($c['created_at'])) : 'N/A' ?>
                </p>

                <div class="mt-auto pt-3 border-t border-gray-100 flex justify-between gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    <a href="edit.php?id=<?= $c['category_id'] ?>"
                       class="flex items-center justify-center flex-1 px-3 py-2 text-sm font-semibold text-red-700 bg-red-50 hover:bg-red-100 rounded-lg transition-all duration-150 hover:shadow-sm"
                       title="Edit Category">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </a>
                    
                    <a href="delete.php?id=<?= $c['category_id'] ?>"
                       onclick="return confirm('Are you sure you want to delete the category: <?= htmlspecialchars($c['name']) ?>? All associated products will be marked as Unassigned.')"
                       class="flex items-center justify-center w-1/3 px-3 py-2 text-sm font-semibold text-gray-500 bg-gray-100 hover:bg-red-500 hover:text-white rounded-lg transition-all duration-150 hover:shadow-sm"
                       title="Delete Category">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </a>
                </div>
                <div class="mt-auto pt-3 border-t border-gray-100 flex justify-between gap-2 group-hover:hidden">
                    <a href="edit.php?id=<?= $c['category_id'] ?>" class="text-xs text-red-500 hover:text-red-700">Edit Details</a>
                    <a href="delete.php?id=<?= $c['category_id'] ?>" onclick="return confirm('Delete <?= htmlspecialchars($c['name']) ?>?')" class="text-xs text-gray-400 hover:text-red-500">Delete</a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-16 text-center">
        <div class="max-w-md mx-auto">
            <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">No Categories Found</h3>
            <p class="text-sm text-gray-600 mb-2">No categories match your current search criteria.</p>
            <?php if ($searchTerm): ?>
                <p class="text-xs text-gray-500 mb-6">Try clearing your search terms.</p>
                <a href="index.php" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200 mr-3">
                    Clear Search
                </a>
            <?php else: ?>
                <p class="text-xs text-gray-500 mb-6">Start organizing your inventory by adding your first category.</p>
            <?php endif; ?>
            <a href="add.php" class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-red-500 to-pink-600 rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add New Category
            </a>
        </div>
    </div>
<?php endif; ?>

<style>
/* Custom styles for the category card */
.category-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.category-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Statistics card hover effect */
.grid > div:hover {
    transform: translateY(-2px);
}
input:focus,
select:focus {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}
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
</style>

<script>
// --- START: Instant Submission Logic (Only Search Remains) ---
const searchInput = document.getElementById("searchInput");
const filterForm = document.getElementById("filterForm");

// Function to submit the form
function submitFilter() {
    filterForm.submit();
}

// Submit search after a short delay (debounce) to avoid too many requests while typing
let searchTimeout = null;
searchInput.addEventListener("input", () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(submitFilter, 500); // Wait 500ms before submitting
});
// --- END: Instant Submission Logic ---
</script>

<?php include __DIR__."/../includes/footer.php"; ?>