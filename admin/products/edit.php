<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

/* ---------------- FUNCTIONS ---------------- */

function generateSlug(string $text): string
{
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    return trim($slug, '-');
}

function uploadImageFile($file, $uploadDir): ?string
{
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('product_', true) . '_' . time() . '.' . $ext;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    return move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename) 
        ? $filename 
        : null;
}

function getImagePath($file)
{
    if (!$file) return '';
    if (strpos($file, 'http') === 0) return $file;
    return $GLOBALS['BASE_URL'] . "uploads/products/" . $file;
}

/* ---------------- FETCH PRODUCT ---------------- */

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header("Location:index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location:index.php?error=not_found");
    exit;
}

$imageStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY is_primary DESC");
$imageStmt->execute([$id]);
$existingImages = $imageStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch existing product attributes
$attrStmt = $pdo->prepare("SELECT attribute, value FROM product_attributes WHERE product_id = ?");
$attrStmt->execute([$id]);
$existingAttributes = $attrStmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query("SELECT category_id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$brands = $pdo->query("SELECT brand_id, name FROM brands ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$formData = $product;
$uploadDir = __DIR__ . '/../../uploads/products';
$errors = [];

/* ---------------- UPDATE ---------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $oldProduct = $product; // keep original for inventory_logs

    foreach ($formData as $key => $val) {
        $formData[$key] = trim($_POST[$key] ?? $val);
    }

    if ($formData['slug'] === '') {
        $formData['slug'] = generateSlug($formData['name']);
    }

    /* Thumbnail */
    $thumbnailPath = $product['thumbnail'];

    if ($_FILES['thumbnail']['error'] !== UPLOAD_ERR_NO_FILE) {
        $new = uploadImageFile($_FILES['thumbnail'], $uploadDir);
        if ($new) $thumbnailPath = $new;
    }

    /* Gallery */
    $uploadedImages = [];

    if (isset($_FILES['product_images'])) {
        for ($i = 0; $i < count($_FILES['product_images']['name']); $i++) {

            if ($_FILES['product_images']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;

            $file = [
              'name' => $_FILES['product_images']['name'][$i],
              'tmp_name' => $_FILES['product_images']['tmp_name'][$i]
            ];

            if ($img = uploadImageFile($file, $uploadDir)) {
                $uploadedImages[] = $img;
            }
        }
    }

    if (empty($errors)) {

        $pdo->beginTransaction();

        $update = $pdo->prepare("
            UPDATE products SET
                name=?, slug=?, description=?,
                category_id=?, brand_id=?, price=?, stock=?, discount=?, thumbnail=?, status=?
            WHERE product_id=?
        ");

        $update->execute([
            $formData['name'],
            $formData['slug'],
            $formData['description'],
            $formData['category_id'] ?: null,
            $formData['brand_id'] ?: null,
            $formData['price'],
            $formData['stock'],
            $formData['discount'] ?: null,
            $thumbnailPath,
            $formData['status'],
            $id
        ]);

        // Update product attributes
        // Delete existing attributes
        $pdo->prepare("DELETE FROM product_attributes WHERE product_id = ?")->execute([$id]);
        
        // Insert new attributes
        if (isset($_POST['attributes']) && is_array($_POST['attributes'])) {
            $insertAttr = $pdo->prepare("INSERT INTO product_attributes (product_id, attribute, value) VALUES (?, ?, ?)");
            foreach ($_POST['attributes'] as $attr) {
                $attrName = trim($attr['name'] ?? '');
                $attrValue = trim($attr['value'] ?? '');
                if ($attrName !== '' && $attrValue !== '') {
                    $insertAttr->execute([$id, $attrName, $attrValue]);
                }
            }
        }

        if (!empty($uploadedImages)) {

            $pdo->prepare("DELETE FROM product_images WHERE product_id=?")->execute([$id]);

            $insert = $pdo->prepare("INSERT INTO product_images (product_id,image_url,is_primary) VALUES (?,?,?)");

            foreach ($uploadedImages as $i => $img) {
                $insert->execute([$id, $img, $i === 0 ? 1 : 0]);
            }
        }

        $pdo->commit();

        // Check for discount changes and notify customers
        $oldDiscount = (float)($oldProduct['discount'] ?? 0);
        $newDiscount = (float)($formData['discount'] ?? 0);
        
        if ($oldDiscount != $newDiscount) {
            // Calculate old and new final prices for messaging
            $oldPrice = (float)($oldProduct['price'] ?? 0);
            $newPrice = (float)($formData['price'] ?? 0);
            
            $oldFinalPrice = $oldPrice;
            if ($oldDiscount > 0) {
                $oldFinalPrice = $oldPrice - ($oldPrice * $oldDiscount / 100);
            }
            
            $newFinalPrice = $newPrice;
            if ($newDiscount > 0) {
                $newFinalPrice = $newPrice - ($newPrice * $newDiscount / 100);
            }
            
            // Find all users who have this product in cart or wishlist
            $usersStmt = $pdo->prepare("
                SELECT DISTINCT user_id 
                FROM (
                    SELECT user_id FROM cart WHERE product_id = ?
                    UNION
                    SELECT user_id FROM wishlist WHERE product_id = ?
                ) AS users_with_product
            ");
            $usersStmt->execute([$id, $id]);
            $affectedUsers = $usersStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Send notifications to affected users
            if (!empty($affectedUsers)) {
                $productName = $formData['name'];
                $productUrl = $BASE_URL . "shop/product_details.php?id=" . $id;
                
                // Determine notification message based on discount change
                if ($newDiscount > $oldDiscount) {
                    // Discount increased
                    $title = "🎉 Discount Increased!";
                    if ($oldDiscount == 0) {
                        $message = "Great news! '{$productName}' is now on sale with {$newDiscount}% off! ";
                        $message .= "New price: ₹" . number_format($newFinalPrice, 2);
                        $message .= " (was ₹" . number_format($oldFinalPrice, 2) . ")";
                    } else {
                        $message = "Great news! '{$productName}' now has {$newDiscount}% off (was {$oldDiscount}%)! ";
                        $message .= "New price: ₹" . number_format($newFinalPrice, 2);
                        $message .= " (was ₹" . number_format($oldFinalPrice, 2) . ")";
                    }
                } else if ($newDiscount < $oldDiscount) {
                    // Discount decreased
                    $title = "Price Update";
                    if ($newDiscount == 0) {
                        $message = "The discount on '{$productName}' has been removed. ";
                        $message .= "Current price: ₹" . number_format($newFinalPrice, 2);
                        $message .= " (was ₹" . number_format($oldFinalPrice, 2) . " with {$oldDiscount}% off)";
                    } else {
                        $message = "The discount on '{$productName}' has changed from {$oldDiscount}% to {$newDiscount}%. ";
                        $message .= "Current price: ₹" . number_format($newFinalPrice, 2);
                        $message .= " (was ₹" . number_format($oldFinalPrice, 2) . ")";
                    }
                }
                
                $notifStmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, title, message, target_url)
                    VALUES (?, 'promotion', ?, ?, ?)
                ");
                
                foreach ($affectedUsers as $userId) {
                    $notifStmt->execute([$userId, $title, $message, $productUrl]);
                }
            }
        }

        // Log inventory change for admin audit
        $adminId = $_SESSION['admin_id'] ?? null;
        if ($adminId) {
            $oldStock = (int)($oldProduct['stock'] ?? 0);
            $newStock = (int)$formData['stock'];

            if ($newStock !== $oldStock) {
                $changeType = $newStock > $oldStock ? 'add_stock' : 'reduce_stock';
            } else {
                $changeType = 'edit';
            }

            $logStmt = $pdo->prepare("
                INSERT INTO inventory_logs (admin_id, product_id, change_type, old_value, new_value)
                VALUES (?, ?, ?, ?, ?)
            ");

            $logStmt->execute([
                $adminId,
                $id,
                $changeType,
                json_encode($oldProduct, JSON_UNESCAPED_UNICODE),
                json_encode($formData, JSON_UNESCAPED_UNICODE),
            ]);
        }

        header("Location:index.php?msg=Updated!");
        exit;
    }
}

include __DIR__ . "/../includes/header.php";
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Edit Product</h1>
            <p class="text-sm text-gray-500 mt-1">Update product information in your catalog</p>
        </div>
    </div>
</div>

<form method="POST" enctype="multipart/form-data">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Basic Information Card -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3 flex items-center">
                <svg class="w-6 h-6 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Basic Information
            </h2>
            
            <div class="space-y-6">

                <!-- Product Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Product Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="<?= htmlspecialchars($formData['name']) ?>"
                        required class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                    <p class="mt-1 text-xs text-gray-500">The name of the product as it appears to customers.</p>
                </div>

                <!-- Slug -->
                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">URL Slug</label>
                    <input type="text" name="slug" id="slug" value="<?= htmlspecialchars($formData['slug']) ?>"
                        class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150 font-mono">
                    <p class="mt-1 text-xs text-gray-500">URL-friendly version of the product name (auto-generated).</p>
                </div>

                <!-- Category and Brand -->
                <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category_id" id="category_id" class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                        <option value="">-- Select category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>" <?= $formData['category_id']==$cat['category_id']?'selected':'' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                    </div>
                    <div>
                        <label for="brand_id" class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                        <div class="flex gap-2">
                            <select name="brand_id" id="brand_id" class="flex-1 w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                                <option value="">-- Select brand --</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?= $brand['brand_id'] ?>" <?= ($formData['brand_id'] ?? '')==$brand['brand_id']?'selected':'' ?>>
                                        <?= htmlspecialchars($brand['name']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                            <button type="button" id="createBrandBtn"
                                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm transition-colors duration-150">
                                + New
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Price and Stock -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price (₹)</label>
                        <input type="number" name="price" id="price" value="<?= $formData['price'] ?>" step="0.01" min="0"
                            required class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                    </div>
                    <div>
                        <label for="stock" class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
                        <input type="number" name="stock" id="stock" value="<?= $formData['stock'] ?>" min="0"
                            required class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                    </div>
                </div>

                <!-- Discount -->
                <div>
                    <label for="discount" class="block text-sm font-medium text-gray-700 mb-1">Discount (%)</label>
                    <input type="number" name="discount" id="discount" value="<?= $formData['discount'] ?>" step="0.01" min="0" max="100"
                        class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                    <p class="mt-1 text-xs text-gray-500">Percentage discount applied to the product price.</p>
                </div>

                <!-- Final Price Display -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Final Price</label>
                    <p class="text-2xl font-bold text-red-600">₹<span id="finalPrice">0.00</span></p>
                    <p class="mt-1 text-xs text-gray-500">Price after discount is applied.</p>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                        <option value="active" <?= $formData['status']=='active'?'selected':'' ?>>Active</option>
                        <option value="inactive" <?= $formData['status']=='inactive'?'selected':'' ?>>Inactive</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Active products are visible to customers.</p>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="description" rows="6"
                        class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150 resize-none"><?= htmlspecialchars($formData['description']) ?></textarea>
                    <p class="mt-1 text-xs text-gray-500">Detailed product description for customers.</p>
                </div>

                <!-- Product Attributes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Attributes</label>
                    <div id="attributesContainer" class="space-y-3">
                        <?php if (!empty($existingAttributes)): ?>
                            <?php foreach ($existingAttributes as $index => $attr): ?>
                                <div class="attribute-row flex gap-2">
                                    <input type="text" name="attributes[<?= $index ?>][name]" value="<?= htmlspecialchars($attr['attribute']) ?>" placeholder="Attribute (e.g., Color)" 
                                        class="flex-1 px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent">
                                    <input type="text" name="attributes[<?= $index ?>][value]" value="<?= htmlspecialchars($attr['value']) ?>" placeholder="Value (e.g., Black)" 
                                        class="flex-1 px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent">
                                    <button type="button" onclick="removeAttribute(this)" class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg border border-red-200">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="attribute-row flex gap-2">
                                <input type="text" name="attributes[0][name]" placeholder="Attribute (e.g., Color)" 
                                    class="flex-1 px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent">
                                <input type="text" name="attributes[0][value]" placeholder="Value (e.g., Black)" 
                                    class="flex-1 px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent">
                                <button type="button" onclick="removeAttribute(this)" class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg border border-red-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" onclick="addAttribute()" class="mt-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg border border-red-200">
                        + Add Attribute
                    </button>
                    <p class="mt-1 text-xs text-gray-500">Add product attributes like Color, Storage, Memory, etc.</p>
                </div>
            </div>
        </div>

        <!-- Media & SEO Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3 flex items-center">
                <svg class="w-6 h-6 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Media & SEO
            </h2>
            
            <div class="space-y-6">

                <!-- Current Thumbnail -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Thumbnail</label>
                    <div class="mb-3">
                        <img src="<?= getImagePath($product['thumbnail']) ?>" 
                             alt="Current thumbnail" 
                             class="w-32 h-32 object-cover rounded-lg border border-gray-200 shadow-sm"
                             onerror="this.src='https://via.placeholder.com/128?text=No+Image'">
                    </div>
                    <div id="thumbDropZone" class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-red-500 hover:bg-red-50 transition-colors duration-150">
                        <input type="file" name="thumbnail" id="thumbnail" accept="image/*" hidden>
                        <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="text-sm font-medium text-gray-700">Click or drag to upload</p>
                        <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF up to 5MB</p>
                    </div>
                    <div id="thumbPreview" class="mt-3"></div>
                </div>

                <!-- Existing Gallery Images -->
                <?php if (!empty($existingImages)): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Gallery Images</label>
                    <div class="flex flex-wrap gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <?php foreach($existingImages as $img): ?>
                            <img src="<?= getImagePath($img) ?>" 
                                 alt="Gallery image" 
                                 class="w-20 h-20 object-cover rounded-lg border border-gray-200 shadow-sm"
                                 onerror="this.src='https://via.placeholder.com/80?text=Image'">
                        <?php endforeach ?>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Uploading new images will replace existing ones.</p>
                </div>
                <?php endif; ?>

                <!-- Upload New Gallery Images -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Add Gallery Images</label>
                    <div id="imagesDropZone" class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-red-500 hover:bg-red-50 transition-colors duration-150">
                        <input type="file" id="product_images" name="product_images[]" multiple accept="image/*" hidden>
                        <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-sm font-medium text-gray-700">Drag & drop images here</p>
                        <p class="text-xs text-gray-500 mt-1">or click to select multiple files</p>
                    </div>
                    <div id="imagesPreviewGrid" class="flex flex-wrap gap-2 mt-3"></div>
                </div>

                <!-- SEO Preview -->
                <div class="pt-2 border-t border-gray-100">
                    <label class="block text-sm font-medium text-gray-700 mb-1">SEO Preview</label>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p id="seoTitle" class="text-base font-semibold text-gray-900 line-clamp-1 mb-1"><?= htmlspecialchars($formData['name']) ?></p>
                        <p class="text-xs text-gray-600 font-mono mb-2"><?= $BASE_URL ?>product/<span id="seoSlug"><?= htmlspecialchars($formData['slug']) ?></span></p>
                        <p id="seoDesc" class="text-xs text-gray-600 line-clamp-2"><?= htmlspecialchars(substr($formData['description'] ?? '', 0, 160)) ?></p>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">How this product appears in search results.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8 text-right">
        <button type="submit" class="inline-flex items-center px-4 py-2.5 
            bg-gradient-to-r from-red-500 to-pink-600 text-white text-sm font-medium 
            rounded-lg hover:from-red-600 hover:to-pink-700 transition-all duration-200 shadow-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-4 0V4a2 2 0 012-2h2a2 2 0 012 2v3m-4 0h4"></path></svg>
            Update Product
        </button>
    </div>

</form>

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

<script>
// Price calculation
const price = document.getElementById('price');
const discount = document.getElementById('discount');
const finalP = document.getElementById('finalPrice');

function calc() {
    let p = parseFloat(price.value || 0);
    let d = parseFloat(discount.value || 0);
    finalP.innerText = (p - (p * d / 100)).toFixed(2);
}
price.addEventListener('input', calc);
discount.addEventListener('input', calc);
calc();

// File upload setup
function setup(id, inputId, previewId, multi = true) {
    const zone = document.getElementById(id);
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    if (!zone || !input || !preview) return;

    zone.onclick = () => input.click();
    
    // Drag and drop
    zone.ondragover = (e) => {
        e.preventDefault();
        zone.classList.add('border-red-500', 'bg-red-50');
    };
    
    zone.ondragleave = () => {
        zone.classList.remove('border-red-500', 'bg-red-50');
    };
    
    zone.ondrop = (e) => {
        e.preventDefault();
        zone.classList.remove('border-red-500', 'bg-red-50');
        if (e.dataTransfer.files.length > 0) {
            input.files = e.dataTransfer.files;
            handleFiles(input, preview, multi);
        }
    };
    
    input.onchange = () => handleFiles(input, preview, multi);
}

function handleFiles(input, preview, multi) {
    preview.innerHTML = '';
    const files = multi ? [...input.files] : [input.files[0]].filter(Boolean);
    
    files.forEach(f => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = "w-20 h-20 object-cover rounded-lg border border-gray-200 shadow-sm";
            preview.appendChild(img);
        };
        reader.readAsDataURL(f);
    });
}

setup('imagesDropZone', 'product_images', 'imagesPreviewGrid', true);
setup('thumbDropZone', 'thumbnail', 'thumbPreview', false);

// Auto-generate slug and update SEO preview
const nameInput = document.getElementById('name');
const slugInput = document.getElementById('slug');
const seoTitle = document.getElementById('seoTitle');
const seoSlug = document.getElementById('seoSlug');
const descriptionInput = document.getElementById('description');
const seoDesc = document.getElementById('seoDesc');

nameInput.addEventListener('input', () => {
    slugInput.value = nameInput.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    seoTitle.textContent = nameInput.value || 'Product Name';
    seoSlug.textContent = slugInput.value || 'product-slug';
});

descriptionInput.addEventListener('input', () => {
    seoDesc.textContent = (descriptionInput.value || '').substring(0, 160);
});

// Product Attributes Management
let attributeIndex = <?= count($existingAttributes) ?>;
function addAttribute() {
    const container = document.getElementById('attributesContainer');
    const row = document.createElement('div');
    row.className = 'attribute-row flex gap-2';
    row.innerHTML = `
        <input type="text" name="attributes[${attributeIndex}][name]" placeholder="Attribute (e.g., Color)" 
            class="flex-1 px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent">
        <input type="text" name="attributes[${attributeIndex}][value]" placeholder="Value (e.g., Black)" 
            class="flex-1 px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent">
        <button type="button" onclick="removeAttribute(this)" class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg border border-red-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    `;
    container.appendChild(row);
    attributeIndex++;
}

function removeAttribute(btn) {
    btn.closest('.attribute-row').remove();
}
</script>

<!-- Create Brand Modal -->
<div id="brandModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Create New Brand</h3>
                <button type="button" id="closeBrandModal" class="text-gray-400 hover:text-gray-600">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>
            <form id="createBrandForm" class="space-y-4" enctype="multipart/form-data">
                <div>
                    <label for="new_brand_name" class="block text-sm font-medium text-gray-700">Brand Name *</label>
                    <input type="text" name="name" id="new_brand_name" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label for="new_brand_logo" class="block text-sm font-medium text-gray-700">Brand Logo (Optional)</label>
                    <input type="file" name="logo" id="new_brand_logo" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                    <p class="text-xs text-gray-500 mt-1">JPEG, PNG, GIF, WebP — max 5MB</p>
                </div>
                <div id="brandModalErrors" class="hidden text-red-600 text-sm"></div>
                <div class="flex items-center justify-end space-x-3 pt-4">
                    <button type="button" id="cancelBrandBtn" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded">Create Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Brand Modal
    const brandModal = document.getElementById('brandModal');
    const createBrandBtn = document.getElementById('createBrandBtn');
    const closeBrandBtn = document.getElementById('closeBrandModal');
    const cancelBrandBtn = document.getElementById('cancelBrandBtn');
    const brandForm = document.getElementById('createBrandForm');
    const brandSelect = document.getElementById('brand_id');
    const brandErrorDiv = document.getElementById('brandModalErrors');

    function openBrandModal() {
        brandModal.classList.remove('hidden');
    }

    function closeBrandModal() {
        brandModal.classList.add('hidden');
        brandForm.reset();
        brandErrorDiv.classList.add('hidden');
        brandErrorDiv.textContent = '';
    }

    createBrandBtn && createBrandBtn.addEventListener('click', openBrandModal);
    closeBrandBtn && closeBrandBtn.addEventListener('click', closeBrandModal);
    cancelBrandBtn && cancelBrandBtn.addEventListener('click', closeBrandModal);

    brandModal.addEventListener('click', function(e) {
        if (e.target === brandModal) {
            closeBrandModal();
        }
    });

    brandForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(brandForm);
        const submitBtn = brandForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating...';
        brandErrorDiv.classList.add('hidden');
        brandErrorDiv.textContent = '';

        fetch('<?= $BASE_URL ?>admin/products/create_brand.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const option = document.createElement('option');
                option.value = data.brand.brand_id;
                option.textContent = data.brand.name;
                option.selected = true;
                brandSelect.appendChild(option);
                
                closeBrandModal();
            } else {
                let errorText = '';
                if (data.errors && Array.isArray(data.errors)) {
                    errorText = data.errors.join('<br>');
                } else if (data.error) {
                    errorText = data.error;
                } else {
                    errorText = 'Failed to create brand.';
                }
                brandErrorDiv.innerHTML = errorText;
                brandErrorDiv.classList.remove('hidden');
            }
        })
        .catch(error => {
            brandErrorDiv.textContent = 'An error occurred. Please try again.';
            brandErrorDiv.classList.remove('hidden');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });
});
</script>

<?php include __DIR__ . "/../includes/footer.php"; ?>
