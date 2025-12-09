<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    header("Location: " . $BASE_URL . "admin/products/index.php?error=Invalid product selection.");
    exit;
}

// Fetch product details
$productStmt = $pdo->prepare("
    SELECT 
        p.*,
        c.name AS category_name,
        b.name AS brand_name,
        b.logo AS brand_logo
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE p.product_id = ?
");
$productStmt->execute([$productId]);
$product = $productStmt->fetch(PDO::FETCH_ASSOC);

// Fetch product attributes
$attrStmt = $pdo->prepare("SELECT attribute, value FROM product_attributes WHERE product_id = ?");
$attrStmt->execute([$productId]);
$attributes = $attrStmt->fetchAll(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: " . $BASE_URL . "admin/products/index.php?error=Product not found.");
    exit;
}

// Fetch all product images
$imageStmt = $pdo->prepare("
    SELECT image_url, is_primary 
    FROM product_images 
    WHERE product_id = ? 
    ORDER BY is_primary DESC, image_id ASC
");
$imageStmt->execute([$productId]);
$images = $imageStmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to get image path
function getImagePath($filename): string
{
    global $BASE_URL;
    if (!$filename) {
        return '';
    }
    
    // If it's already a full URL or absolute path, return as is
    if (strpos($filename, 'http') === 0 || strpos($filename, '/') === 0) {
        return $filename;
    }
    
    // Construct path using BASE_URL
    return $BASE_URL . 'uploads/products/' . $filename;
}

// Calculate discounted price if discount exists
$originalPrice = (float)$product['price'];
$discount = $product['discount'] ? (float)$product['discount'] : 0;
$discountedPrice = $discount > 0 ? $originalPrice * (1 - ($discount / 100)) : $originalPrice;

include __DIR__ . "/../includes/header.php";
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Product Details</h1>
            <p class="text-sm text-gray-500 mt-1">View complete product information</p>
        </div>
        <div class="flex gap-2">
            <a href="edit.php?id=<?= (int)$productId ?>" 
               class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 rounded-lg shadow-sm transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit Product
            </a>
            <a href="index.php" 
               class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition-colors duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Products
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Product Information Section -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3 flex items-center">
            <svg class="w-6 h-6 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Product Information
        </h2>
        
        <div class="space-y-6">

            <!-- Product Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Product Name <span class="text-red-500">*</span></label>
                <div class="w-full px-4 py-2 border rounded-lg shadow-sm bg-gray-50 text-gray-900">
                    <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <p class="mt-1 text-xs text-gray-500">The name of the product as it appears to customers.</p>
            </div>

            <!-- Slug -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">URL Slug</label>
                <div class="w-full px-4 py-2 border rounded-lg shadow-sm bg-gray-50 text-gray-900 font-mono">
                    <?= htmlspecialchars($product['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </div>
                <p class="mt-1 text-xs text-gray-500">URL-friendly version of the product name (auto-generated).</p>
            </div>

            <!-- Category and Brand -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <div class="w-full px-4 py-2 border rounded-lg shadow-sm bg-gray-50 text-gray-900">
                        <?= htmlspecialchars($product['category_name'] ?? 'Unassigned', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                    <div class="w-full px-4 py-2 border rounded-lg shadow-sm bg-gray-50 text-gray-900">
                        <?php if ($product['brand_name']): ?>
                            <div class="flex items-center gap-2">
                                <?php if ($product['brand_logo']): ?>
                                    <img src="<?= $BASE_URL . 'uploads/brands/' . htmlspecialchars($product['brand_logo']) ?>" 
                                         alt="<?= htmlspecialchars($product['brand_name']) ?>" 
                                         class="w-6 h-6 object-contain">
                                <?php endif; ?>
                                <span><?= htmlspecialchars($product['brand_name'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php else: ?>
                            <span>—</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Price and Stock -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price (₹)</label>
                    <div class="w-full px-4 py-2 border rounded-lg shadow-sm bg-gray-50 text-gray-900">
                        ₹<?= number_format($originalPrice, 2) ?>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
                    <div class="w-full px-4 py-2 border rounded-lg shadow-sm bg-gray-50 text-gray-900">
                        <?= (int)$product['stock'] ?>
                    </div>
                </div>
            </div>

            <!-- Discount -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Discount (%)</label>
                <div class="w-full px-4 py-2 border rounded-lg shadow-sm bg-gray-50 text-gray-900">
                    <?= number_format($discount, 2) ?>%
                </div>
                <p class="mt-1 text-xs text-gray-500">Percentage discount applied to the product price.</p>
            </div>

            <!-- Final Price Display -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Final Price</label>
                <p class="text-2xl font-bold text-red-600">₹<?= number_format($discountedPrice, 2) ?></p>
                <p class="mt-1 text-xs text-gray-500">Price after discount is applied.</p>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <div class="w-full px-4 py-2 border rounded-lg shadow-sm bg-gray-50">
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold <?= $product['status'] === 'active' ? 'bg-red-100 text-red-800' : 'bg-red-200 text-red-900' ?>">
                        <?= htmlspecialchars(ucfirst($product['status']), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
                <p class="mt-1 text-xs text-gray-500">Active products are visible to customers.</p>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <div class="w-full px-4 py-2 border rounded-lg shadow-sm bg-gray-50 text-gray-900 whitespace-pre-wrap min-h-[150px]">
                    <?= htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8') ?: '—' ?>
                </div>
                <p class="mt-1 text-xs text-gray-500">Detailed product description for customers.</p>
            </div>

            <!-- Product Attributes -->
            <?php if (!empty($attributes)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Product Attributes</label>
                <div class="w-full px-4 py-3 border rounded-lg shadow-sm bg-gray-50">
                    <div class="grid grid-cols-2 gap-3">
                        <?php foreach ($attributes as $attr): ?>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($attr['attribute'], ENT_QUOTES, 'UTF-8') ?>:</span>
                                <span class="text-sm text-gray-900"><?= htmlspecialchars($attr['value'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <p class="mt-1 text-xs text-gray-500">Product attributes and specifications.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Product Images Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3 flex items-center">
            <svg class="w-6 h-6 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            Product Images
        </h2>
        
        <div class="space-y-6">

            <!-- Current Thumbnail -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Thumbnail</label>
                <div class="mb-3">
                    <?php 
                    $thumbnailSrc = $product['thumbnail'] ?? ($images[0]['image_url'] ?? '');
                    $thumbnailPath = $thumbnailSrc ? getImagePath($thumbnailSrc) : '';
                    ?>
                    <?php if ($thumbnailPath): ?>
                        <img src="<?= htmlspecialchars($thumbnailPath, ENT_QUOTES, 'UTF-8') ?>" 
                             alt="Current thumbnail" 
                             class="w-32 h-32 object-cover rounded-lg border border-gray-200 shadow-sm"
                             onerror="this.src='https://via.placeholder.com/128?text=No+Image'">
                    <?php else: ?>
                        <div class="w-32 h-32 bg-gray-100 rounded-lg border border-gray-200 flex items-center justify-center">
                            <span class="text-xs text-gray-400">No thumbnail</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Existing Gallery Images -->
            <?php if (!empty($images)): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Gallery Images</label>
                    <div class="flex flex-wrap gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <?php foreach ($images as $img): ?>
                            <div class="relative">
                                <?php if ($img['is_primary']): ?>
                                    <span class="absolute top-1 left-1 z-10 bg-red-600 text-white text-xs px-1.5 py-0.5 rounded-full font-semibold shadow-sm">Primary</span>
                                <?php endif; ?>
                                <img src="<?= htmlspecialchars(getImagePath($img['image_url']), ENT_QUOTES, 'UTF-8') ?>" 
                                     alt="Gallery image" 
                                     class="w-20 h-20 object-cover rounded-lg border border-gray-200 shadow-sm"
                                     onerror="this.src='https://via.placeholder.com/80?text=Image'">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Total <?= count($images) ?> gallery image<?= count($images) !== 1 ? 's' : '' ?>.</p>
                </div>
            <?php else: ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gallery Images</label>
                    <div class="flex flex-col items-center justify-center h-32 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                        <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-xs font-medium text-gray-500">No gallery images</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- SEO Preview -->
            <div class="pt-2 border-t border-gray-100">
                <label class="block text-sm font-medium text-gray-700 mb-1">SEO Preview</label>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <p class="text-base font-semibold text-gray-900 line-clamp-1 mb-1"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs text-gray-600 font-mono mb-2"><?= $BASE_URL ?>product/<?= htmlspecialchars($product['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs text-gray-600 line-clamp-2"><?= htmlspecialchars(substr($product['description'] ?? '', 0, 160)) ?></p>
                </div>
                <p class="mt-1 text-xs text-gray-500">How this product appears in search results.</p>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="mt-6 flex justify-end gap-3">
    <a href="index.php" 
       class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition-colors duration-150">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Back to Products
    </a>
    <a href="edit.php?id=<?= (int)$productId ?>" 
       class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 rounded-lg shadow-sm transition-all duration-200">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
        </svg>
        Edit Product
    </a>
    <a href="delete.php?id=<?= (int)$productId ?>" 
       class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm transition-colors duration-150"
       onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
        </svg>
        Delete Product
    </a>
</div>

<style>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php include __DIR__ . "/../includes/footer.php"; ?>

