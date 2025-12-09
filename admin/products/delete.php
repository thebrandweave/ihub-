<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

function deleteImageFile($filename, $uploadDir): bool
{
    if (!$filename) {
        return false;
    }
    
    // Handle both old format (full path) and new format (filename only)
    $actualFilename = strpos($filename, 'uploads/products/') === 0 
        ? basename($filename) 
        : $filename;
    
    $fullPath = $uploadDir . '/' . $actualFilename;
    
    if (file_exists($fullPath)) {
        if (@unlink($fullPath)) {
            return true;
        }
    }
    
    return false;
}

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$uploadDir = __DIR__ . '/../../uploads/products';

if ($productId > 0) {
    try {
        // Get product images and thumbnail BEFORE deleting from database
        $productStmt = $pdo->prepare("SELECT thumbnail FROM products WHERE product_id = ?");
        $productStmt->execute([$productId]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            header("Location: index.php?error=Product not found.");
            exit;
        }
        
        // Get all product images from product_images table
        $imageStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ?");
        $imageStmt->execute([$productId]);
        $images = $imageStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Start transaction to ensure data consistency
        $pdo->beginTransaction();
        
        try {
            // Delete product_images records first (explicitly, in case cascade doesn't work)
            $deleteImagesStmt = $pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
            $deleteImagesStmt->execute([$productId]);
            
            // Delete the product
            $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
            $stmt->execute([$productId]);

            if ($stmt->rowCount() > 0) {
                // Commit database changes
                $pdo->commit();
                
                // Delete image files from filesystem
                $deletedFiles = [];
                $failedFiles = [];
                
                // Delete thumbnail
                if (!empty($product['thumbnail'])) {
                    if (deleteImageFile($product['thumbnail'], $uploadDir)) {
                        $deletedFiles[] = $product['thumbnail'];
                    } else {
                        $failedFiles[] = $product['thumbnail'];
                    }
                }
                
                // Delete all product images
                foreach ($images as $image) {
                    if (!empty($image)) {
                        if (deleteImageFile($image, $uploadDir)) {
                            $deletedFiles[] = $image;
                        } else {
                            $failedFiles[] = $image;
                        }
                    }
                }
                
                // Show success message (even if some files couldn't be deleted)
                $message = "Product deleted successfully";
                if (!empty($failedFiles)) {
                    $message .= ". Note: Some image files could not be deleted from the server.";
                }
                
                header("Location: index.php?msg=" . urlencode($message));
                exit;
            } else {
                $pdo->rollBack();
                header("Location: index.php?error=Product not found or already deleted.");
                exit;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
    } catch (PDOException $e) {
        header("Location: index.php?error=" . urlencode("Failed to delete product: " . $e->getMessage()));
        exit;
    }
}

header("Location: index.php?error=Invalid product selection.");
exit;

