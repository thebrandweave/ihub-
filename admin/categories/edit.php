<?php
// --- START: Server-Side Logic for Category Edit ---
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

$category_id = $_GET['id'] ?? null;
$error = null;
$message = $_GET['msg'] ?? null;
$category = null;
$uploadDir = __DIR__ . '/../../uploads/categories'; // Define category upload directory

/* ---------------- FUNCTIONS ---------------- */

/**
 * Handles the file upload process and returns the new filename.
 * @param array $file The $_FILES array entry for the file.
 * @param string $uploadDir The server directory path to save the file.
 * @return string|null The saved filename or null on failure/no file.
 */
function uploadImageFile($file, $uploadDir): ?string
{
    // Check if a file was actually uploaded and is not an error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('category_', true) . '_' . time() . '.' . $ext;

    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            // Log error: Failed to create directory
            return null;
        }
    }

    return move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename) 
        ? $filename 
        : null;
}

/**
 * Returns the full accessible URL for an image.
 */
function getImagePath($filename){
    global $BASE_URL;
    if(!$filename) return '';
    if(strpos($filename,'http')===0) return $filename;
    return $BASE_URL . 'uploads/categories/' . $filename;
}
/* ---------------- END FUNCTIONS ---------------- */


// 1. Fetch existing category data
if (!$category_id || !is_numeric($category_id)) {
    header("Location: index.php?error=" . urlencode("Invalid category ID provided."));
    exit;
} else {
    try {
        // Fetch category details AND count associated products
        $stmt = $pdo->prepare("
            SELECT 
                c.category_id, 
                c.name, 
                c.description, 
                c.image_url, 
                COUNT(p.product_id) AS total_products
            FROM categories c
            LEFT JOIN products p ON c.category_id = p.category_id
            WHERE c.category_id = :id
            GROUP BY c.category_id, c.name, c.description, c.image_url
        ");
        $stmt->execute([':id' => $category_id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            header("Location: index.php?error=" . urlencode("Category not found."));
            exit;
        }
    } catch (PDOException $e) {
        $error = "Database error while fetching category details. Please check logs.";
    }
}

// 2. Handle POST submission for update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    
    $name = trim($_POST['name'] ?? $category['name']);
    $description = trim($_POST['description'] ?? $category['description']);
    $current_image_url = $category['image_url']; // Start with existing image filename

    /* --- Image Processing --- */
    
    // Check for a new file upload first
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $new_image = uploadImageFile($_FILES['category_image'], $uploadDir);
        if ($new_image) {
            // File uploaded successfully, update URL to new filename
            $current_image_url = $new_image;
        } else {
            $error = "File upload failed. Check server configuration or folder permissions.";
        }
    } else if (isset($_POST['image_action']) && $_POST['image_action'] === 'clear') {
        // Explicitly clear the image if the hidden action field is set
        $current_image_url = null;
    } else if (isset($_POST['current_image_url']) && !empty($_POST['current_image_url'])) {
        // Retain the existing image URL/filename if no new file uploaded and not cleared
        $current_image_url = trim($_POST['current_image_url']);
    } else {
        // Fallback: clear the image if the current_image_url field is empty 
        // (This happens if the user clears the text field, though we are now using a hidden field)
        $current_image_url = null;
    }


    if (empty($name)) {
        $error = "Category Name is required.";
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE categories 
                SET name = :name, description = :description, image_url = :image_url
                WHERE category_id = :id
            ");

            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':image_url' => $current_image_url, 
                ':id' => $category_id
            ]);

            $success_message = "Category '$name' updated successfully!";
            header("Location: edit.php?id=" . $category_id . "&msg=" . urlencode($success_message));
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23000' && strpos($e->getMessage(), 'name') !== false) {
                 $error = "A category with this name already exists.";
            } else {
                $error = "Database error: Could not update category.";
            }
        }
    }
    
    // If POST fails, update $category array so the form retains user input
    $category['name'] = $name;
    $category['description'] = $description;
    $category['image_url'] = $current_image_url;
}


// Set form values for display
$nameValue = htmlspecialchars($category['name'] ?? '');
$descriptionValue = htmlspecialchars($category['description'] ?? '');
$imageUrlValue = htmlspecialchars($category['image_url'] ?? '');

// --- END: Server-Side Logic for Category Edit ---

include __DIR__."/../includes/header.php";
?>

<div class="mb-4 md:mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-800">Edit Category: <?= $nameValue ?></h1>
            <p class="text-xs md:text-sm text-gray-500 mt-1">Update category details and organization.</p>
        </div>
        <a href="index.php"
            class="flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl shadow-sm hover:bg-gray-200 transition-all duration-200">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to List
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

<div class="bg-white rounded-lg shadow-xl border border-gray-200 p-6 md:p-8">
    <form method="POST" action="edit.php?id=<?= $category_id ?>" enctype="multipart/form-data">
        
        <input type="hidden" name="image_action" id="imageAction" value="">
        <input type="hidden" name="current_image_url" id="currentImageURL" value="<?= $imageUrlValue ?>">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 space-y-6">
                
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Category Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" required
                        value="<?= $nameValue ?>"
                        class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition duration-200">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="description" rows="4"
                        class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition duration-200"><?= $descriptionValue ?></textarea>
                    <p class="mt-2 text-xs text-gray-500">A brief summary of the category's contents.</p>
                </div>

                <?php if ($category): ?>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <p class="text-sm font-medium text-gray-700 mb-1">Associated Products</p>
                    <p class="text-2xl font-bold text-red-600"><?= number_format($category['total_products']) ?></p>
                    <p class="mt-1 text-xs text-gray-500">Number of products currently assigned to this category.</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <div class="p-4 border border-gray-200 rounded-lg bg-gray-50 shadow-inner">
                    <h3 class="text-sm font-bold text-gray-700 mb-4 uppercase tracking-wider">Category Image</h3>

                    <div class="mb-4 aspect-video border border-dashed border-gray-300 rounded-lg overflow-hidden flex items-center justify-center bg-white relative">
                        <img id="image-preview" 
                            src="<?= $imageUrlValue ? getImagePath($imageUrlValue) : 'https://via.placeholder.com/400x200?text=No+Image' ?>" 
                            alt="Category Image Preview" 
                            onerror="this.onerror=null;this.src='https://via.placeholder.com/400x200?text=Image+Load+Error'"
                            class="w-full h-full object-cover">
                        
                        <button type="button" id="clearImageBtn" title="Remove current image"
                            class="absolute top-2 right-2 p-1 rounded-full bg-red-600 text-white shadow-lg hover:bg-red-700 transition <?= $imageUrlValue ? '' : 'hidden' ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div id="imageDropZone" class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-red-500 hover:bg-red-50 transition-colors duration-150">
                        <input type="file" name="category_image" id="categoryImageInput" accept="image/*" hidden> 
                        
                        <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="text-sm font-medium text-gray-700">Drag & drop or click to upload</p>
                        <p class="text-xs text-gray-500 mt-1">Replaces current image (PNG, JPG, GIF)</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end">
            <button type="submit"
                class="inline-flex items-center px-6 py-3 text-base font-semibold text-white bg-gradient-to-r from-red-600 to-pink-700 hover:from-red-700 hover:to-pink-800 rounded-xl shadow-lg transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Save Changes
            </button>
        </div>
    </form>
</div>

<style>
/* Enhanced focus states */
input:focus,
textarea:focus {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const defaultPlaceholder = 'https://via.placeholder.com/400x200?text=No+Image';
    const imagePreview = document.getElementById('image-preview');
    const imageDropZone = document.getElementById('imageDropZone');
    const imageInput = document.getElementById('categoryImageInput');
    const currentImageURL = document.getElementById('currentImageURL'); // Hidden field for old URL/filename
    const imageAction = document.getElementById('imageAction'); // Hidden field for clear action
    const clearImageBtn = document.getElementById('clearImageBtn');

    // --- Image Upload/Preview Logic ---
    
    function handleFile(file) {
        if (!file || !file.type.startsWith('image/')) {
            alert("Please select a valid image file.");
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.src = e.target.result;
            // When a new file is dropped, ensure the 'clear' action is reset
            imageAction.value = '';
            // Hide the clear button since the file is not saved yet
            clearImageBtn.classList.add('hidden');
        };
        reader.readAsDataURL(file);

        // Assign the file to the input element so it is included in the form submission
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        imageInput.files = dataTransfer.files;
    }

    // A. Drop Zone Setup
    if (imageDropZone) {
        imageDropZone.onclick = () => imageInput.click();

        // Drag events for styling
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            imageDropZone.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });

        imageDropZone.addEventListener('dragover', () => {
            imageDropZone.classList.add('border-red-500', 'bg-red-50');
        });

        imageDropZone.addEventListener('dragleave', () => {
            imageDropZone.classList.remove('border-red-500', 'bg-red-50');
        });

        imageDropZone.addEventListener('drop', (e) => {
            imageDropZone.classList.remove('border-red-500', 'bg-red-50');
            const file = e.dataTransfer.files[0];
            if (file) handleFile(file);
        });
    }

    // B. Input Change Handler (for clicking the zone)
    if (imageInput) {
        imageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) handleFile(file);
        });
    }

    // C. Clear Image Button Handler
    if (clearImageBtn) {
        clearImageBtn.addEventListener('click', (e) => {
            e.preventDefault();
            
            // 1. Reset preview
            imagePreview.src = defaultPlaceholder;
            
            // 2. Clear the actual file input queue
            imageInput.value = ''; 

            // 3. Signal to PHP that the image should be removed from the database
            imageAction.value = 'clear'; 
            
            // 4. Hide the button and the hidden image URL to confirm clear state
            clearImageBtn.classList.add('hidden');
            currentImageURL.value = ''; // Ensure the old value isn't sent back
            
            alert("Image removed successfully. Click 'Save Changes' to confirm the change in the database.");
        });
    }
});
</script>

<?php include __DIR__."/../includes/footer.php"; ?>