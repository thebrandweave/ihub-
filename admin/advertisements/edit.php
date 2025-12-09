<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

$adId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// ... [Ad fetching logic same as before] ...
$stmt = $pdo->prepare("SELECT * FROM advertisements WHERE ad_id = ?");
$stmt->execute([$adId]);
$ad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ad) { header("Location: index.php?error=" . urlencode("Campaign not found.")); exit; }

$uploadDir = __DIR__ . '/../../uploads/ads';
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

$errors = [];
$formData = [
    'title' => $ad['title'],
    'ad_type' => $ad['ad_type'],
    'image_url' => $ad['image_url'],
    'target_url' => $ad['target_url'],
    'start_date' => date('Y-m-d\TH:i', strtotime($ad['start_date'])),
    'end_date' => date('Y-m-d\TH:i', strtotime($ad['end_date'])),
    'priority' => $ad['priority'],
    'status' => $ad['status']
];

function uploadAdImage(array $file, string $uploadDir): ?string
{
    $maxSize = 4 * 1024 * 1024;
    $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > $maxSize) return null;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedMime, true)) return null;

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('ad_', true) . '_' . time() . '.' . strtolower($extension);
    $target = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) return null;

    // CHANGED: Return only filename
    return $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... [POST data collection same as before] ...
    $formData['title'] = trim($_POST['title'] ?? '');
    $formData['ad_type'] = $_POST['ad_type'] ?? 'hero_banner';
    $formData['target_url'] = trim($_POST['target_url'] ?? '');
    $formData['start_date'] = $_POST['start_date'] ?? '';
    $formData['end_date'] = $_POST['end_date'] ?? '';
    $formData['priority'] = (int)($_POST['priority'] ?? 0);
    $formData['status'] = $_POST['status'] ?? 'inactive';

    if ($formData['title'] === '') $errors[] = "Campaign title is required.";
    // ... [Validation logic same as before] ...
    
    $startObj = DateTime::createFromFormat('Y-m-d\TH:i', $formData['start_date']);
    $endObj = DateTime::createFromFormat('Y-m-d\TH:i', $formData['end_date']);
    if (!$startObj || !$endObj) {
        $errors[] = "Invalid start or end date.";
    } elseif ($startObj >= $endObj) {
        $errors[] = "End date must be after start date.";
    }

    // Handle Image Upload
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $newFilename = uploadAdImage($_FILES['image_file'], $uploadDir);
        if ($newFilename) {
            // Delete old image
            $oldFilename = $ad['image_url'];
            if ($oldFilename && !preg_match('#^https?://#i', $oldFilename)) {
                // CHANGED: Construct full path from filename to delete
                $absolute = __DIR__ . '/../../uploads/ads/' . ltrim($oldFilename, '/');
                if (file_exists($absolute)) {
                    @unlink($absolute);
                }
            }
            $formData['image_url'] = $newFilename;
        } else {
            $errors[] = "Unable to upload new image. Please try again.";
        }
    } elseif ($formData['image_url'] === '') {
        $errors[] = "Please upload a banner image.";
    }

    if (empty($errors)) {
        try {
            // ... [Update Query Same as Before] ...
            $update = $pdo->prepare("
                UPDATE advertisements
                SET title = :title, ad_type = :ad_type, image_url = :image_url, target_url = :target_url,
                    start_date = :start_date, end_date = :end_date, priority = :priority, status = :status
                WHERE ad_id = :id
            ");
            $update->execute([
                ':title' => $formData['title'],
                ':ad_type' => $formData['ad_type'],
                ':image_url' => $formData['image_url'], // Storing filename only
                ':target_url' => $formData['target_url'],
                ':start_date' => $startObj->format('Y-m-d H:i:s'),
                ':end_date' => $endObj->format('Y-m-d H:i:s'),
                ':priority' => $formData['priority'],
                ':status' => $formData['status'],
                ':id' => $adId
            ]);
            header("Location: index.php?msg=" . urlencode("Campaign updated successfully."));
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

include __DIR__ . "/../includes/header.php";
?>
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Edit Campaign</h1>
            <p class="text-sm text-gray-500 mt-1">Update scheduling, placement, or creatives.</p>
        </div>
    </div>
</div>

<div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Upload New Banner</label>
                    <label class="flex flex-col items-center justify-center px-4 py-5 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50 hover:border-red-400 hover:bg-red-50/40 transition cursor-pointer">
                        <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m2-2l1.586-1.586a2 2 0 012.828 0L24 14M3 7h18"></path></svg>
                        <span class="text-sm font-medium text-gray-700">Click to upload</span>
                        <span class="text-xs text-gray-400">JPG, PNG, GIF, WebP — max 4MB</span>
                        <input type="file" name="image_file" accept="image/*" class="hidden">
                    </label>
                    <p class="text-xs text-gray-500 mt-2">Leave empty to keep the current banner.</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Current Preview</p>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <?php 
                        $previewUrl = $formData['image_url'];
                        if (!preg_match('#^https?://#i', $previewUrl)) {
                            $previewUrl = $BASE_URL . 'uploads/ads/' . ltrim($previewUrl, '/');
                        }
                        ?>
                        <img src="<?= htmlspecialchars($previewUrl) ?>" alt="<?= htmlspecialchars($formData['title']) ?>" class="w-full h-40 object-cover">
                    </div>
                </div>
            </div>