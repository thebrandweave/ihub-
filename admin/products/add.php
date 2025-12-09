<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

function generateSlug(string $text): string
{
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

function validateImageFile($file): array
{
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            $errors[] = "File size exceeds maximum allowed size (5MB).";
        } elseif ($file['error'] === UPLOAD_ERR_NO_FILE) {
            // No file uploaded is okay (optional field)
            return ['valid' => false, 'errors' => []];
        } else {
            $errors[] = "File upload error: " . $file['error'];
        }
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check file size (5MB max)
    $maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $maxSize) {
        $errors[] = "File size exceeds maximum allowed size (5MB).";
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes, true)) {
        $errors[] = "Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.";
        return ['valid' => false, 'errors' => $errors];
    }
    
    return ['valid' => true, 'errors' => []];
}

function uploadImageFile($file, $uploadDir): ?string
{
    $validation = validateImageFile($file);
    if (!$validation['valid']) {
        return null;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('product_', true) . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . '/' . $filename;
    
    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Return only filename (not full path) to store in database
        return $filename;
    }
    
    return null;
}

$categories = $pdo->query("SELECT category_id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$brands = $pdo->query("SELECT brand_id, name FROM brands ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$errors = [];
$uploadDir = __DIR__ . '/../../uploads/products';

$formData = [
    'name' => '',
    'slug' => '',
    'description' => '',
    'category_id' => '',
    'brand_id' => '',
    'price' => '',
    'stock' => '',
    'discount' => '',
    'thumbnail' => '',
    'status' => 'active'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($formData as $key => $default) {
        if ($key === 'category_id' || $key === 'brand_id') {
            $formData[$key] = $_POST[$key] ?? '';
        } else {
            $formData[$key] = trim($_POST[$key] ?? '');
        }
    }

    if ($formData['name'] === '') {
        $errors[] = "Product name is required.";
    }

    if ($formData['price'] === '' || !is_numeric($formData['price'])) {
        $errors[] = "A valid price is required.";
    }

    if ($formData['stock'] === '' || !ctype_digit((string) $formData['stock'])) {
        $errors[] = "Stock must be a whole number.";
    }

    if ($formData['discount'] !== '' && !is_numeric($formData['discount'])) {
        $errors[] = "Discount must be a number.";
    }

    $statusOptions = ['active', 'inactive'];
    if (!in_array($formData['status'], $statusOptions, true)) {
        $errors[] = "Invalid status selected.";
    }

    if ($formData['slug'] === '' && $formData['name'] !== '') {
        $formData['slug'] = generateSlug($formData['name']);
    }

    if (!empty($formData['slug'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = ?");
        $stmt->execute([$formData['slug']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Slug already exists. Please provide a unique slug.";
        }
    }

    // Handle thumbnail upload
    $thumbnailPath = null;
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] !== UPLOAD_ERR_NO_FILE) {
        $validation = validateImageFile($_FILES['thumbnail']);
        if (!$validation['valid']) {
            $errors = array_merge($errors, $validation['errors']);
        } else {
            $thumbnailPath = uploadImageFile($_FILES['thumbnail'], $uploadDir);
            if ($thumbnailPath === null) {
                $errors[] = "Failed to upload thumbnail image.";
            }
        }
    }

    // Handle product images upload
    $uploadedImages = [];
    if (isset($_FILES['product_images']) && is_array($_FILES['product_images']['name'])) {
        $imageCount = count($_FILES['product_images']['name']);
        for ($i = 0; $i < $imageCount; $i++) {
            if ($_FILES['product_images']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            
            $file = [
                'name' => $_FILES['product_images']['name'][$i],
                'type' => $_FILES['product_images']['type'][$i],
                'tmp_name' => $_FILES['product_images']['tmp_name'][$i],
                'error' => $_FILES['product_images']['error'][$i],
                'size' => $_FILES['product_images']['size'][$i]
            ];
            
            $validation = validateImageFile($file);
            if (!$validation['valid']) {
                $errors = array_merge($errors, $validation['errors']);
            } else {
                $imagePath = uploadImageFile($file, $uploadDir);
                if ($imagePath !== null) {
                    $uploadedImages[] = $imagePath;
                } else {
                    $errors[] = "Failed to upload image: " . $file['name'];
                }
            }
        }
    }

    // Set thumbnail to first uploaded image if not provided
    if ($thumbnailPath === null && !empty($uploadedImages)) {
        $thumbnailPath = $uploadedImages[0];
    }

    if (empty($errors)) {
        $price = number_format((float)$formData['price'], 2, '.', '');
        $stock = (int)$formData['stock'];
        $discount = ($formData['discount'] === '') ? null : number_format((float)$formData['discount'], 2, '.', '');
        $categoryId = $formData['category_id'] !== '' ? (int)$formData['category_id'] : null;

        try {
            $pdo->beginTransaction();

            $brandId = $formData['brand_id'] !== '' ? (int)$formData['brand_id'] : null;

            $insertProduct = $pdo->prepare("
                INSERT INTO products
                    (name, slug, description, category_id, brand_id, price, stock, discount, thumbnail, status)
                VALUES
                    (:name, :slug, :description, :category_id, :brand_id, :price, :stock, :discount, :thumbnail, :status)
            ");

            $insertProduct->execute([
                ':name' => $formData['name'],
                ':slug' => $formData['slug'] !== '' ? $formData['slug'] : null,
                ':description' => $formData['description'] !== '' ? $formData['description'] : null,
                ':category_id' => $categoryId,
                ':brand_id' => $brandId,
                ':price' => $price,
                ':stock' => $stock,
                ':discount' => $discount,
                ':thumbnail' => $thumbnailPath,
                ':status' => $formData['status']
            ]);

            $productId = (int)$pdo->lastInsertId();

            // Handle product attributes
            if (isset($_POST['attributes']) && is_array($_POST['attributes'])) {
                $insertAttr = $pdo->prepare("INSERT INTO product_attributes (product_id, attribute, value) VALUES (?, ?, ?)");
                foreach ($_POST['attributes'] as $attr) {
                    $attrName = trim($attr['name'] ?? '');
                    $attrValue = trim($attr['value'] ?? '');
                    if ($attrName !== '' && $attrValue !== '') {
                        $insertAttr->execute([$productId, $attrName, $attrValue]);
                    }
                }
            }

            if (!empty($uploadedImages)) {
                $insertImage = $pdo->prepare("
                    INSERT INTO product_images (product_id, image_url, is_primary)
                    VALUES (:product_id, :image_url, :is_primary)
                ");

                foreach ($uploadedImages as $index => $imagePath) {
                    $insertImage->execute([
                        ':product_id' => $productId,
                        ':image_url' => $imagePath,
                        ':is_primary' => $index === 0 ? 1 : 0
                    ]);
                }
            }

            $pdo->commit();

            header("Location: " . $BASE_URL . "admin/products/index.php?msg=Product created successfully");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            // Clean up uploaded files on error
            if ($thumbnailPath && file_exists($uploadDir . '/' . $thumbnailPath)) {
                @unlink($uploadDir . '/' . $thumbnailPath);
            }
            foreach ($uploadedImages as $imagePath) {
                if (file_exists($uploadDir . '/' . $imagePath)) {
                    @unlink($uploadDir . '/' . $imagePath);
                }
            }
            $errors[] = "Failed to save product: " . $e->getMessage();
        }
    }
}

include __DIR__ . "/../includes/header.php";
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Add New Product</h1>
            <p class="text-sm text-gray-500 mt-1">Create a new product in your catalog</p>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-lg p-4 shadow-sm">
        <ul class="list-disc ml-6 text-sm text-red-700">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

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
                    <input type="text" name="name" id="name" value="<?= htmlspecialchars($formData['name'], ENT_QUOTES, 'UTF-8') ?>"
                        required class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                    <p class="mt-1 text-xs text-gray-500">The name of the product as it appears to customers.</p>
                </div>

                <!-- Slug -->
                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">URL Slug</label>
                    <input type="text" name="slug" id="slug" value="<?= htmlspecialchars($formData['slug'], ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150 font-mono">
                    <p class="mt-1 text-xs text-gray-500">URL-friendly version of the product name (auto-generated).</p>
                </div>

                <!-- Category and Brand -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <div class="flex gap-2">
                            <select name="category_id" id="category_id" class="flex-1 w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                                <option value="">-- Select category --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int)$category['category_id'] ?>"
                                        <?= ($formData['category_id'] !== '' && (int)$formData['category_id'] === (int)$category['category_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" id="createCategoryBtn"
                                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm transition-colors duration-150">
                                + New
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="brand_id" class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                        <div class="flex gap-2">
                            <select name="brand_id" id="brand_id" class="flex-1 w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                                <option value="">-- Select brand --</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?= (int)$brand['brand_id'] ?>"
                                        <?= ($formData['brand_id'] !== '' && (int)$formData['brand_id'] === (int)$brand['brand_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($brand['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
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
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price (₹) <span class="text-red-500">*</span></label>
                        <input type="number" name="price" id="price" step="0.01" min="0"
                               value="<?= htmlspecialchars($formData['price'], ENT_QUOTES, 'UTF-8') ?>"
                               required class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                    </div>
                    <div>
                        <label for="stock" class="block text-sm font-medium text-gray-700 mb-1">Stock <span class="text-red-500">*</span></label>
                        <input type="number" name="stock" id="stock" min="0"
                               value="<?= htmlspecialchars($formData['stock'], ENT_QUOTES, 'UTF-8') ?>"
                               required class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                    </div>
                </div>

                <!-- Discount -->
                <div>
                    <label for="discount" class="block text-sm font-medium text-gray-700 mb-1">Discount (%)</label>
                    <input type="number" name="discount" id="discount" step="0.01" min="0" max="100"
                           value="<?= htmlspecialchars($formData['discount'], ENT_QUOTES, 'UTF-8') ?>"
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
                    <select name="status" id="status" class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150" required>
                        <option value="active" <?= $formData['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $formData['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Active products are visible to customers.</p>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="description" rows="6"
                        class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150 resize-none"><?= htmlspecialchars($formData['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    <p class="mt-1 text-xs text-gray-500">Detailed product description for customers.</p>
                </div>

                <!-- Product Attributes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Attributes</label>
                    <div id="attributesContainer" class="space-y-3">
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
                        <p id="seoTitle" class="text-base font-semibold text-gray-900 line-clamp-1 mb-1"><?= $formData['name'] ? htmlspecialchars($formData['name'], ENT_QUOTES, 'UTF-8') : 'Product title preview' ?></p>
                        <p class="text-xs text-gray-600 font-mono mb-2"><?= rtrim($BASE_URL, '/') ?>/product/<span id="seoSlug"><?= $formData['slug'] ? htmlspecialchars($formData['slug'], ENT_QUOTES, 'UTF-8') : 'product-slug' ?></span></p>
                        <p id="seoDesc" class="text-xs text-gray-600 line-clamp-2"><?= $formData['description'] ? htmlspecialchars(mb_substr($formData['description'], 0, 160), ENT_QUOTES, 'UTF-8') : 'Product description snippet will appear here as a preview in search results.' ?></p>
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
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Create Product
        </button>
    </div>

</form>

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

<!-- Create Category Modal (unchanged from your original) -->
<div id="categoryModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Create New Category</h3>
                <button type="button" id="closeCategoryModal" class="text-gray-400 hover:text-gray-600">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>
            <form id="createCategoryForm" class="space-y-4">
                <div>
                    <label for="new_category_name" class="block text-sm font-medium text-gray-700">Category Name *</label>
                    <input type="text" name="name" id="new_category_name" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label for="new_category_description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="new_category_description" rows="3" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500"></textarea>
                </div>
                <div>
                    <label for="new_category_image" class="block text-sm font-medium text-gray-700">Category Image (Optional)</label>
                    <input type="file" name="image" id="new_category_image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                    <p class="text-xs text-gray-500 mt-1">JPEG, PNG, GIF, WebP — max 5MB</p>
                </div>
                <div id="categoryModalErrors" class="hidden text-red-600 text-sm"></div>
                <div class="flex items-center justify-end space-x-3 pt-4">
                    <button type="button" id="cancelCategoryBtn" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Brand modal JS -->
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

    // Category Modal
    const modal = document.getElementById('categoryModal');
    const createBtn = document.getElementById('createCategoryBtn');
    const closeBtn = document.getElementById('closeCategoryModal');
    const cancelBtn = document.getElementById('cancelCategoryBtn');
    const form = document.getElementById('createCategoryForm');
    const categorySelect = document.getElementById('category_id');
    const errorDiv = document.getElementById('categoryModalErrors');

    function openModal() {
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
        form.reset();
        errorDiv.classList.add('hidden');
        errorDiv.textContent = '';
    }

    createBtn && createBtn.addEventListener('click', openModal);
    closeBtn && closeBtn.addEventListener('click', closeModal);
    cancelBtn && cancelBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating...';
        errorDiv.classList.add('hidden');
        errorDiv.textContent = '';

        fetch('<?= $BASE_URL ?>admin/products/create_category.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const option = document.createElement('option');
                option.value = data.category.category_id;
                option.textContent = data.category.name;
                option.selected = true;
                categorySelect.appendChild(option);
                
                closeModal();
            } else {
                let errorText = '';
                if (data.errors && Array.isArray(data.errors)) {
                    errorText = data.errors.join('<br>');
                } else if (data.error) {
                    errorText = data.error;
                } else {
                    errorText = 'Failed to create category.';
                }
                errorDiv.innerHTML = errorText;
                errorDiv.classList.remove('hidden');
            }
        })
        .catch(error => {
            errorDiv.textContent = 'An error occurred. Please try again.';
            errorDiv.classList.remove('hidden');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });
});
</script>

<!-- Advanced form JS: slug, SEO, price, drag & drop previews -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Elements
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    const descInput = document.getElementById('description');
    const seoTitle = document.getElementById('seoTitle');
    const seoSlug = document.getElementById('seoSlug');
    const seoDesc = document.getElementById('seoDesc');
    const priceInput = document.getElementById('price');
    const discountInput = document.getElementById('discount');
    const finalPriceEl = document.getElementById('finalPrice');

    // Auto slug + SEO
    function slugify(text) {
        return text.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
    }

    function updateSlugFromName() {
        if (slugInput && !slugInput.value.trim() && nameInput) {
            const s = slugify(nameInput.value);
            slugInput.value = s;
            if (seoSlug) seoSlug.textContent = s || 'product-slug';
        }
    }

    if (nameInput) {
        nameInput.addEventListener('input', function () {
            if (seoTitle) seoTitle.textContent = this.value || 'Product title preview';
            updateSlugFromName();
        });
    }

    if (slugInput) {
        slugInput.addEventListener('input', function () {
            if (seoSlug) seoSlug.textContent = this.value || 'product-slug';
        });
    }

    if (descInput) {
        descInput.addEventListener('input', function () {
            if (seoDesc) seoDesc.textContent = this.value
                ? this.value.substring(0, 160)
                : 'Product description snippet will appear here as a preview in search results.';
        });
    }

    // Initialize SEO with existing values
    if (nameInput && seoTitle) seoTitle.textContent = nameInput.value || 'Product title preview';
    if (slugInput && seoSlug) seoSlug.textContent = slugInput.value || 'product-slug';
    if (descInput && seoDesc) {
        seoDesc.textContent = descInput.value
            ? descInput.value.substring(0, 160)
            : 'Product description snippet will appear here as a preview in search results.';
    }

    // Price / discount
    function updateFinalPrice() {
        if (!priceInput || !finalPriceEl) return;
        const price = parseFloat(priceInput.value) || 0;
        const discount = discountInput ? (parseFloat(discountInput.value) || 0) : 0;
        const finalPrice = price - (price * discount / 100);
        finalPriceEl.textContent = finalPrice.toFixed(2);
    }

    if (priceInput) priceInput.addEventListener('input', updateFinalPrice);
    if (discountInput) discountInput.addEventListener('input', updateFinalPrice);
    updateFinalPrice();

    // Drag & drop file handling
    function setupDropZone(zoneId, inputId, previewId, multiple = false) {
        const zone = document.getElementById(zoneId);
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        if (!zone || !input || !preview) return;

        function createPreview(file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const wrapper = document.createElement('div');
                wrapper.className = "relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200 bg-gray-100";

                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = "w-full h-full object-cover";

                const removeBtn = document.createElement('button');
                removeBtn.type = "button";
                removeBtn.className = "absolute -top-2 -right-2 w-6 h-6 rounded-full bg-red-500 text-white text-xs flex items-center justify-center shadow";
                removeBtn.textContent = "×";
                removeBtn.addEventListener('click', () => {
                    wrapper.remove();
                    // WARNING: This only removes the preview, not the file from input.files (cannot modify FileList easily)
                });

                wrapper.appendChild(img);
                wrapper.appendChild(removeBtn);
                preview.appendChild(wrapper);
            };
            reader.readAsDataURL(file);
        }

        zone.addEventListener('click', () => input.click());

        input.addEventListener('change', () => {
            if (!multiple) preview.innerHTML = '';
            Array.from(input.files).forEach(createPreview);
        });

        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('border-red-500', 'bg-red-50');
        });

        zone.addEventListener('dragleave', () => {
            zone.classList.remove('border-red-500', 'bg-red-50');
        });

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('border-red-500', 'bg-red-50');
            if (!e.dataTransfer.files.length) return;
            input.files = e.dataTransfer.files;
            input.dispatchEvent(new Event('change'));
        });
    }

    setupDropZone('thumbDropZone', 'thumbnail', 'thumbPreview', false);
    setupDropZone('imagesDropZone', 'product_images', 'imagesPreviewGrid', true);
});

// Product Attributes Management
let attributeIndex = 1;
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
