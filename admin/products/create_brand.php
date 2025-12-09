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
    $filename = uniqid('brand_', true) . '_' . time() . '.' . $extension;
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
$uploadDir = __DIR__ . '/../../uploads/brands';

$errors = [];

if ($name === '') {
    $errors[] = "Brand name is required.";
}

// Handle logo upload
$logoUrl = null;
if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $validation = validateImageFile($_FILES['logo']);
    if (!$validation['valid']) {
        $errors = array_merge($errors, $validation['errors']);
    } else {
        $logoUrl = uploadImageFile($_FILES['logo'], $uploadDir);
        if ($logoUrl === null) {
            $errors[] = "Failed to upload brand logo.";
        }
    }
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    // Check if brand name already exists
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM brands WHERE name = ?");
    $checkStmt->execute([$name]);
    if ($checkStmt->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => ['Brand name already exists.']]);
        exit;
    }

    // Insert new brand
    $insertStmt = $pdo->prepare("
        INSERT INTO brands (name, logo)
        VALUES (:name, :logo)
    ");

    $insertStmt->execute([
        ':name' => $name,
        ':logo' => $logoUrl
    ]);

    $brandId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'brand' => [
            'brand_id' => $brandId,
            'name' => $name,
            'logo' => $logoUrl
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create brand: ' . $e->getMessage()]);
}

