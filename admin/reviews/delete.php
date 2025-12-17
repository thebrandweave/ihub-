<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
    
    if ($review_id <= 0) {
        header("Location: index.php?error=Invalid review ID");
        exit;
    }
    
    $pdo->beginTransaction();
    try {
        // Fetch all images associated with this review before deletion
        $imgStmt = $pdo->prepare("SELECT image FROM review_images WHERE review_id = ?");
        $imgStmt->execute([$review_id]);
        $images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Also check for legacy single image column
        $legacyStmt = $pdo->prepare("SELECT image FROM reviews WHERE review_id = ?");
        $legacyStmt->execute([$review_id]);
        $legacyImage = $legacyStmt->fetchColumn();
        
        // Delete the review (CASCADE will handle review_images table)
        $deleteStmt = $pdo->prepare("DELETE FROM reviews WHERE review_id = ?");
        $deleteStmt->execute([$review_id]);
        
        if ($deleteStmt->rowCount() > 0) {
            // Delete physical image files
            $uploadDir = __DIR__ . '/../../uploads/reviews/';
            
            // Delete images from review_images table
            foreach ($images as $img) {
                if (!empty($img)) {
                    $path = $uploadDir . $img;
                    if (file_exists($path) && is_file($path)) {
                        @unlink($path);
                    }
                }
            }
            
            // Delete legacy image if exists
            if (!empty($legacyImage)) {
                $legacyPath = $uploadDir . $legacyImage;
                if (file_exists($legacyPath) && is_file($legacyPath)) {
                    @unlink($legacyPath);
                }
            }
            
            $pdo->commit();
            header("Location: index.php?msg=Review deleted successfully");
        } else {
            $pdo->rollBack();
            header("Location: index.php?error=Review not found");
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error deleting review: " . $e->getMessage());
        header("Location: index.php?error=Failed to delete review");
    }
    exit;
}

// If accessed via GET, redirect
header("Location: index.php");
exit;




