<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
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
    $filename = uniqid('category_', true) . '_' . time() . '.' . $extension;
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

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$uploadDir = __DIR__ . '/../../uploads/categories';

$errors = [];

if ($name === '') {
    $errors[] = "Category name is required.";
}

// Handle image upload
$imageUrl = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $validation = validateImageFile($_FILES['image']);
    if (!$validation['valid']) {
        $errors = array_merge($errors, $validation['errors']);
    } else {
        $imageUrl = uploadImageFile($_FILES['image'], $uploadDir);
        if ($imageUrl === null) {
            $errors[] = "Failed to upload category image.";
        }
    }
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    // Check if category name already exists
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
    $checkStmt->execute([$name]);
    if ($checkStmt->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => ['Category name already exists.']]);
        exit;
    }

    // Insert new category
    $insertStmt = $pdo->prepare("
        INSERT INTO categories (name, description, image_url)
        VALUES (:name, :description, :image_url)
    ");

    $insertStmt->execute([
        ':name' => $name,
        ':description' => $description !== '' ? $description : null,
        ':image_url' => $imageUrl
    ]);

    $categoryId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'category' => [
            'category_id' => $categoryId,
            'name' => $name,
            'description' => $description,
            'image_url' => $imageUrl
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create category: ' . $e->getMessage()]);
}

