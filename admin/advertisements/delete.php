<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$adId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($adId <= 0) {
    header("Location: index.php?error=" . urlencode("Invalid campaign selected."));
    exit;
}

$stmt = $pdo->prepare("SELECT image_url FROM advertisements WHERE ad_id = ?");
$stmt->execute([$adId]);
$ad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ad) {
    header("Location: index.php?error=" . urlencode("Campaign not found."));
    exit;
}

try {
    $pdo->prepare("DELETE FROM advertisements WHERE ad_id = ?")->execute([$adId]);

    if (!empty($ad['image_url']) && !preg_match('#^https?://#i', $ad['image_url'])) {
        // CHANGED: Prepend path to filename to locate the file
        $absolute = __DIR__ . '/../../uploads/ads/' . ltrim($ad['image_url'], '/');
        if (file_exists($absolute)) {
            @unlink($absolute);
        }
    }

    header("Location: index.php?msg=" . urlencode("Campaign deleted."));
    exit;
} catch (PDOException $e) {
    header("Location: index.php?error=" . urlencode("Unable to delete campaign."));
    exit;
}