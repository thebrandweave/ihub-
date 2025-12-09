<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

$uploadDir = __DIR__ . '/../../uploads/ads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$errors = [];
$formData = [
    'title' => '',
    'ad_type' => 'hero_banner',
    'target_url' => '',
    'start_date' => date('Y-m-d\TH:i'),
    'end_date' => date('Y-m-d\TH:i', strtotime('+30 days')),
    'priority' => 0,
    'status' => 'active'
];

function uploadAdImage(array $file, string $uploadDir): array
{
    $maxSize = 4 * 1024 * 1024;
    $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['filename' => null, 'error' => 'Please upload a banner image.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['filename' => null, 'error' => 'Failed to upload image.'];
    }

    if ($file['size'] > $maxSize) {
        return ['filename' => null, 'error' => 'Image must be less than 4MB.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedMime, true)) {
        return ['filename' => null, 'error' => 'Unsupported image type.'];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('ad_', true) . '_' . time() . '.' . strtolower($extension);
    $target = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return ['filename' => null, 'error' => 'Unable to save uploaded image.'];
    }

    // CHANGED: Returns just the filename now, not the full relative path
    return ['filename' => $filename, 'error' => null];
}

$uploadedFilename = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['title'] = trim($_POST['title'] ?? '');
    $formData['ad_type'] = $_POST['ad_type'] ?? 'hero_banner';
    $formData['target_url'] = trim($_POST['target_url'] ?? '');
    $formData['start_date'] = $_POST['start_date'] ?? '';
    $formData['end_date'] = $_POST['end_date'] ?? '';
    $formData['priority'] = (int)($_POST['priority'] ?? 0);
    $formData['status'] = $_POST['status'] ?? 'inactive';

    // ... [Validation logic remains the same] ...
    if ($formData['title'] === '') $errors[] = "Campaign title is required.";
    if (!in_array($formData['ad_type'], ['hero_banner', 'popup'], true)) $errors[] = "Invalid ad type.";
    if (!in_array($formData['status'], ['active', 'inactive'], true)) $errors[] = "Invalid status.";
    if ($formData['target_url'] === '') $errors[] = "Target URL is required.";

    $startObj = DateTime::createFromFormat('Y-m-d\TH:i', $formData['start_date']);
    $endObj = DateTime::createFromFormat('Y-m-d\TH:i', $formData['end_date']);
    if (!$startObj || !$endObj) {
        $errors[] = "Invalid start or end date.";
    } elseif ($startObj >= $endObj) {
        $errors[] = "End date must be after start date.";
    }

    $uploadResult = uploadAdImage($_FILES['image_file'] ?? ['error' => UPLOAD_ERR_NO_FILE], $uploadDir);
    if ($uploadResult['error']) {
        $errors[] = $uploadResult['error'];
    } else {
        $uploadedFilename = $uploadResult['filename'];
    }

    if (empty($errors) && $uploadedFilename) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO advertisements
                (title, ad_type, image_url, target_url, start_date, end_date, priority, clicks, views, status)
                VALUES
                (:title, :ad_type, :image_url, :target_url, :start_date, :end_date, :priority, 0, 0, :status)
            ");
            $stmt->execute([
                ':title' => $formData['title'],
                ':ad_type' => $formData['ad_type'],
                ':image_url' => $uploadedFilename, // Storing ONLY filename
                ':target_url' => $formData['target_url'],
                ':start_date' => $startObj->format('Y-m-d H:i:s'),
                ':end_date' => $endObj->format('Y-m-d H:i:s'),
                ':priority' => $formData['priority'],
                ':status' => $formData['status']
            ]);

            header("Location: index.php?msg=" . urlencode("Campaign created successfully."));
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
            <h1 class="text-2xl font-bold text-gray-800">Create Campaign</h1>
            <p class="text-sm text-gray-500 mt-1">Launch a new hero banner or popup promotion.</p>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-lg p-4 shadow-sm">
        <ul class="list-disc ml-5 text-sm text-red-700 space-y-1">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-rose-50 to-red-50">
        <h3 class="text-lg font-semibold text-gray-800 flex items-center">
            <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c1.657 0 3-1.343 3-3S13.657 2 12 2 9 3.343 9 5s1.343 3 3 3zm0 0v6m0 4h.01"></path></svg>
            Campaign Details
        </h3>
        <p class="text-xs text-gray-500 mt-1">Scheduling determines when the ad is eligible to appear.</p>
    </div>

    <form method="POST" enctype="multipart/form-data" class="p-5 space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="<?= htmlspecialchars($formData['title']) ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="Diwali Super Sale" required>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Placement</label>
                        <select name="ad_type" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            <option value="hero_banner" <?= $formData['ad_type'] === 'hero_banner' ? 'selected' : '' ?>>Hero Banner</option>
                            <option value="popup" <?= $formData['ad_type'] === 'popup' ? 'selected' : '' ?>>Popup Overlay</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            <option value="active" <?= $formData['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $formData['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Target URL <span class="text-red-500">*</span></label>
                    <input type="url" name="target_url" value="<?= htmlspecialchars($formData['target_url']) ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="https://example.com/collection" required>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Start Date</label>
                        <input type="datetime-local" name="start_date" value="<?= htmlspecialchars($formData['start_date']) ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">End Date</label>
                        <input type="datetime-local" name="end_date" value="<?= htmlspecialchars($formData['end_date']) ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Priority</label>
                    <input type="number" name="priority" value="<?= (int)$formData['priority'] ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="Higher values show first">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Upload Banner <span class="text-red-500">*</span></label>
                <label class="flex flex-col items-center justify-center px-4 py-6 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50 hover:border-red-400 hover:bg-red-50/40 transition cursor-pointer">
                    <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m2-2l1.586-1.586a2 2 0 012.828 0L24 14M3 7h18"></path></svg>
                    <span class="text-sm font-medium text-gray-700">Click to upload</span>
                    <span class="text-xs text-gray-400">JPG, PNG, GIF, WebP — max 4MB</span>
                    <input type="file" name="image_file" accept="image/*" class="hidden" required>
                </label>
            </div>
        </div>
        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100">
            <a href="index.php" class="px-4 py-2.5 border border-gray-200 text-sm font-semibold text-gray-600 rounded-lg hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-red-500 to-pink-600 text-white text-sm font-semibold rounded-lg shadow hover:from-red-600 hover:to-pink-700">Save Campaign</button>
        </div>
    </form>
</div>
<?php include __DIR__ . "/../includes/footer.php"; ?>