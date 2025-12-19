<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

$id = $_GET['id'] ?? null;

if ($id) {
    // We don't need to check for logged-in user here as these are subscribers, not admins
    $stmt = $pdo->prepare("DELETE FROM newsletter_subscribers WHERE subscriber_id = ?");
    
    if ($stmt->execute([$id])) {
        $msg = "Subscriber removed successfully";
        header("Location: index.php?msg=" . urlencode($msg));
    } else {
        $error = "Failed to remove subscriber";
        header("Location: index.php?error=" . urlencode($error));
    }
    exit;
}

// Redirect if no ID is provided
header("Location: index.php");
exit;
?>