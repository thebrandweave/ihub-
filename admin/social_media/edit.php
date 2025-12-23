<?php
// --- START: Server-Side Logic for Social Media Edit ---
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

$social_id = $_GET['id'] ?? null;
$error = null;
$message = $_GET['msg'] ?? null;
$social = null;

// 1. Fetch existing social media data
if (!$social_id || !is_numeric($social_id)) {
    header("Location: index.php?error=" . urlencode("Invalid platform ID provided."));
    exit;
} else {
    try {
        $stmt = $pdo->prepare("SELECT * FROM social_media WHERE social_id = :id");
        $stmt->execute([':id' => $social_id]);
        $social = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$social) {
            header("Location: index.php?error=" . urlencode("Social media platform not found."));
            exit;
        }
    } catch (PDOException $e) {
        $error = "Database error while fetching platform details.";
    }
}

// 2. Handle POST submission for update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    
    $platform_name = trim($_POST['platform_name'] ?? '');
    $link_url = trim($_POST['link_url'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if (empty($platform_name)) {
        $error = "Platform name is required.";
    } elseif (empty($link_url)) {
        $error = "Link URL is required.";
    } elseif (!filter_var($link_url, FILTER_VALIDATE_URL)) {
        $error = "Please enter a valid URL (including http:// or https://).";
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE social_media 
                SET platform_name = :name, link_url = :url, status = :status
                WHERE social_id = :id
            ");

            $stmt->execute([
                ':name' => $platform_name,
                ':url' => $link_url,
                ':status' => $status,
                ':id' => $social_id
            ]);

            $success_message = "Platform '$platform_name' updated successfully!";
            header("Location: edit.php?id=" . $social_id . "&msg=" . urlencode($success_message));
            exit;

        } catch (PDOException $e) {
            $error = "Database error: Could not update social media platform.";
        }
    }
    
    // Retain input if error occurs for the form fields
    $social['platform_name'] = $platform_name;
    $social['link_url'] = $link_url;
    $social['status'] = $status;
}

$platformNameValue = htmlspecialchars($social['platform_name'] ?? '');
$linkUrlValue = htmlspecialchars($social['link_url'] ?? '');
$statusValue = $social['status'] ?? 'active';

// Helper to get domain for initial favicon load
$initialDomain = parse_url($social['link_url'], PHP_URL_HOST) ?? 'google.com';

include __DIR__."/../includes/header.php";
?>

<div class="mb-4 md:mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-800">Edit Social Media</h1>
            <p class="text-xs md:text-sm text-gray-500 mt-1">Update platform links and visibility status.</p>
        </div>
        <a href="index.php"
            class="flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl shadow-sm hover:bg-gray-200 transition-all duration-200">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to List
        </a>
    </div>
</div>

<?php if ($message): ?>
<div class="mb-6 bg-green-50 border-l-4 border-green-500 rounded-lg p-4 shadow-sm">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($message) ?></p>
    </div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-lg p-4 shadow-sm">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-xl border border-gray-200 p-6 md:p-8">
            <form method="POST" action="edit.php?id=<?= $social_id ?>" class="space-y-6">
                
                <div>
                    <label for="platform_name" class="block text-sm font-medium text-gray-700 mb-2">Platform Name <span class="text-red-500">*</span></label>
                    <input type="text" name="platform_name" id="platform_name" required
                        value="<?= $platformNameValue ?>" placeholder="e.g. Facebook, Instagram, LinkedIn"
                        class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition duration-200">
                </div>

                <div>
                    <label for="link_url" class="block text-sm font-medium text-gray-700 mb-2">Platform URL <span class="text-red-500">*</span></label>
                    <input type="url" name="link_url" id="link_url" required
                        value="<?= $linkUrlValue ?>" placeholder="https://instagram.com/your-username"
                        class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition duration-200">
                    <p class="mt-2 text-xs text-gray-500">The full destination link including https://</p>
                </div>

                <div class="p-4 border border-gray-200 rounded-lg bg-gray-50 shadow-inner">
                    <h3 class="text-sm font-bold text-gray-700 mb-4 uppercase tracking-wider">Visibility Settings</h3>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Link Status</label>
                        <select name="status" id="status" 
                            class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition duration-200 bg-white">
                            <option value="active" <?= $statusValue === 'active' ? 'selected' : '' ?>>Active (Visible)</option>
                            <option value="inactive" <?= $statusValue === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                        </select>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end">
                    <button type="submit"
                        class="inline-flex items-center px-6 py-3 text-base font-semibold text-white bg-gradient-to-r from-red-600 to-pink-700 hover:from-red-700 hover:to-pink-800 rounded-xl shadow-lg transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="space-y-4">
        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Live Preview</h3>
        
        <div id="previewCard" class="rounded-xl p-6 shadow-xl relative h-40 flex flex-col justify-between overflow-hidden transition-all duration-500">
            <div>
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 bg-white rounded-xl flex items-center justify-center shadow-lg border border-white/50">
                            <img id="faviconPreview" src="https://www.google.com/s2/favicons?sz=64&domain=<?= $initialDomain ?>" 
                                 class="w-7 h-7 object-contain" alt="logo">
                        </div>
                        <h3 id="namePreview" class="text-xl font-bold text-red-600 drop-shadow-md truncate max-w-[120px]">
                            <?= $platformNameValue ?: 'Platform' ?>
                        </h3>
                    </div>
                    <span id="statusPreview" class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-widest bg-red-600 text-white border border-white/30 backdrop-blur-sm">
                        <?= $statusValue ?>
                    </span>
                </div>
                <p id="urlPreview" class="text-[10px]  mt-5 break-all font-mono bg-white p-2 rounded backdrop-blur-md line-clamp-1 border border-white/10">
                    <?= $linkUrlValue ?: 'https://example.com' ?>
                </p>
            </div>
        </div>

        <div class="p-4 border border-gray-200 rounded-lg bg-gray-50 shadow-inner space-y-3">
            <div class="flex items-center space-x-2 text-gray-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span class="text-xs font-semibold">Created At:</span>
                <span class="text-xs font-mono"><?= $social['created_at'] ?></span>
            </div>
            <div class="flex items-center space-x-2 text-gray-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span class="text-xs font-semibold">Last Updated:</span>
                <span class="text-xs font-mono"><?= $social['updated_at'] ?></span>
            </div>
        </div>
    </div>
</div>

<style>
/* Matches the Category Edit focus theme */
input:focus,
select:focus {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}
</style>

<script>
    const platformInput = document.getElementById('platform_name');
    const urlInput = document.getElementById('link_url');
    const statusInput = document.getElementById('status');
    
    const namePreview = document.getElementById('namePreview');
    const urlPreview = document.getElementById('urlPreview');
    const statusPreview = document.getElementById('statusPreview');
    const faviconPreview = document.getElementById('faviconPreview');
    const previewCard = document.getElementById('previewCard');

    function updatePreview() {
        // Update Text contents
        namePreview.textContent = platformInput.value || "Platform";
        urlPreview.textContent = urlInput.value || "https://example.com";
        statusPreview.textContent = statusInput.value;

        // Update Card Color based on visibility status
        if(statusInput.value === 'inactive') {
            previewCard.classList.remove('from-red-600', 'to-pink-700');
            previewCard.classList.add('from-gray-500', 'to-gray-700');
        } else {
            previewCard.classList.add('from-red-600', 'to-pink-700');
            previewCard.classList.remove('from-gray-500', 'to-gray-700');
        }

        // Update Favicon logic
        try {
            if(urlInput.value) {
                const url = new URL(urlInput.value);
                faviconPreview.src = `https://www.google.com/s2/favicons?sz=64&domain=${url.hostname}`;
            }
        } catch(e) {
            // Wait for user to type valid URL
        }
    }

    // Initialize the preview on page load
    window.onload = updatePreview;

    // Listeners for real-time updates
    platformInput.addEventListener('input', updatePreview);
    urlInput.addEventListener('input', updatePreview);
    statusInput.addEventListener('change', updatePreview);
</script>

<?php include __DIR__."/../includes/footer.php"; ?>