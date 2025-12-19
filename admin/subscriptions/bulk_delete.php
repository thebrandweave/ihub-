<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids'])) {
    $ids = $_POST['ids'];
    
    // Create placeholders for the IN clause (?, ?, ?)
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    $sql = "DELETE FROM newsletter_subscribers WHERE subscriber_id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute($ids)) {
        header("Location: index.php?msg=" . count($ids) . " subscribers removed successfully.");
    } else {
        header("Location: index.php?error=Bulk delete failed.");
    }
    exit();
}

header("Location: index.php");
exit();