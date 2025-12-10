<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

/* ---------------- FUNCTIONS ---------------- */

/**
 * Deletes a file from the server's filesystem.
 * Handles different path formats and uses @unlink for suppressed errors.
 * * NOTE: This function is adapted from the provided products/delete.php.
 * * @param string $filename The image URL or filename stored in the database.
 * @param string $uploadDir The server path to the image directory.
 * @return bool True if the file was deleted or did not exist, false on permission/other failure.
 */
function deleteImageFile($filename, $uploadDir): bool
{
    if (!$filename || strpos($filename, 'http') === 0) {
        // Don't attempt to delete external URLs or empty strings
        return true; 
    }
    
    // Attempt to handle full paths vs just filenames 
    // (Assuming the category image column stores a filename or path relative to $uploadDir)
    $actualFilename = basename($filename); 
    
    $fullPath = $uploadDir . '/' . $actualFilename;
    
    if (file_exists($fullPath)) {
        // Use @unlink to suppress errors (like permission denied)
        if (@unlink($fullPath)) {
            return true;
        }
        // If unlink fails (e.g., permission issue), return false
        return false;
    }
    
    // File didn't exist, which is fine
    return true;
}

/* ---------------- MAIN LOGIC ---------------- */

$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Define the specific upload directory for categories
$uploadDir = __DIR__ . '/../../uploads/categories';

if ($categoryId > 0) {
    try {
        // 1. Get category name and image URL BEFORE deletion
        $categoryStmt = $pdo->prepare("SELECT name, image_url FROM categories WHERE category_id = ?");
        $categoryStmt->execute([$categoryId]);
        $category = $categoryStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$category) {
            header("Location: index.php?error=" . urlencode("Category not found or already deleted."));
            exit;
        }
        
        $categoryName = $category['name'];
        $imageToDelete = $category['image_url'];
        
        // 2. Start transaction for data consistency
        $pdo->beginTransaction();
        
        try {
            // 3. Delete the category record
            // Due to ON DELETE SET NULL on products.category_id, associated products will be updated.
            $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
            $stmt->execute([$categoryId]);

            if ($stmt->rowCount() > 0) {
                // 4. Commit database changes
                $pdo->commit();
                
                // 5. Delete the image file from the filesystem
                $fileDeleted = true;
                if (!empty($imageToDelete)) {
                    $fileDeleted = deleteImageFile($imageToDelete, $uploadDir);
                }
                
                // 6. Redirect with success message
                $message = "Category '{$categoryName}' deleted successfully.";
                if (!$fileDeleted) {
                    $message .= " Note: The associated image file could not be deleted from the server.";
                }
                
                header("Location: index.php?msg=" . urlencode($message));
                exit;
            } else {
                $pdo->rollBack();
                header("Location: index.php?error=" . urlencode("Category not found or already deleted."));
                exit;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            // 7. Handle transaction errors
            // Use specific error codes if needed, otherwise rethrow or use generic message
            throw $e; 
        }
    } catch (PDOException $e) {
        // 8. Handle initial query or connection errors
        header("Location: index.php?error=" . urlencode("Failed to delete category due to a database error."));
        exit;
    }
}

// Default redirect for invalid ID
header("Location: index.php?error=" . urlencode("Invalid category selection for deletion."));
exit;