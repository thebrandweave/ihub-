<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    
    // Validate status
    if (!in_array($status, ['pending', 'approved', 'rejected'])) {
        header("Location: index.php?error=invalid_status");
        exit;
    }
    
    // If rejecting, delete associated images after status update
    $pdo->beginTransaction();
    try {
        // Fetch images before deleting
        $imgStmt = $pdo->prepare("SELECT image FROM review_images WHERE review_id = ?");
        $imgStmt->execute([$review_id]);
        $images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);
        // Legacy single image column
        $legacyStmt = $pdo->prepare("SELECT image FROM reviews WHERE review_id = ?");
        $legacyStmt->execute([$review_id]);
        $legacyImage = $legacyStmt->fetchColumn();

        // Update review status
        $stmt = $pdo->prepare("UPDATE reviews SET status = ? WHERE review_id = ?");
        $stmt->execute([$status, $review_id]);
    
        if ($stmt->rowCount() > 0) {
            // If rejected, delete files and rows
            if ($status === 'rejected' && !empty($images)) {
                $delStmt = $pdo->prepare("DELETE FROM review_images WHERE review_id = ?");
                $delStmt->execute([$review_id]);

                $uploadDir = __DIR__ . '/../../uploads/reviews/';
                foreach ($images as $img) {
                    $path = $uploadDir . $img;
                    if (is_file($path)) {
                        @unlink($path);
                    }
                }
                if (!empty($legacyImage)) {
                    $legacyPath = $uploadDir . $legacyImage;
                    if (is_file($legacyPath)) {
                        @unlink($legacyPath);
                    }
                }
            }
            $pdo->commit();
            header("Location: index.php?msg=Review status updated successfully");
        } else {
            $pdo->rollBack();
            header("Location: index.php?error=Review not found");
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: index.php?error=Failed to update review");
    }
    exit;
}

header("Location: index.php");
exit;

