<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM social_media WHERE social_id = ?");
        $stmt->execute([$id]);
        header("Location: index.php?msg=Platform deleted successfully");
        exit();
    } catch (PDOException $e) {
        header("Location: index.php?error=Could not delete platform: " . $e->getMessage());
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}