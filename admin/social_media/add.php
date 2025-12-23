<?php
// --- START: Server-Side Logic for Social Media Add ---
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $platform_name = trim($_POST['platform_name'] ?? '');
    $link_url = trim($_POST['link_url'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if (empty($platform_name) || empty($link_url)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($link_url, FILTER_VALIDATE_URL)) {
        $error = "Please enter a valid URL (including http:// or https://).";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO social_media (platform_name, link_url, status) VALUES (?, ?, ?)");
            $stmt->execute([$platform_name, $link_url, $status]);
            
            header("Location: index.php?msg=" . urlencode("Social platform added successfully!"));
            exit;
        } catch (PDOException $e) {
            $error = "Database error: Could not add platform. " . $e->getMessage();
        }
    }
}

include __DIR__ . "/../includes/header.php";
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Add Social Platform</h1>
            <p class="text-sm text-gray-500 mt-1">Add a new connection link for your customers</p>
        </div>
        <a href="index.php" class="text-sm font-medium text-gray-600 hover:text-red-600 flex items-center transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to List
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 md:px-8 py-4 md:py-6 border-b border-gray-100 bg-gradient-to-r from-red-50 to-pink-50">
                <h3 class="text-lg md:text-xl font-semibold text-gray-800 flex items-center">
                    <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-br from-red-500 to-pink-600 rounded-lg flex items-center justify-center mr-2 md:mr-3 flex-shrink-0">
                        <svg class="w-4 h-4 md:w-6 md:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                        </svg>
                    </div>
                    <span class="text-sm md:text-base">Platform Connection Details</span>
                </h3>
            </div>

            <?php if ($error): ?>
            <div class="mx-4 md:mx-8 mt-6 bg-red-50 border-l-4 border-red-500 p-4 text-red-700 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="p-4 md:p-8 space-y-6">
                <div>
                    <label for="platform_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        Platform Name <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                            </svg>
                        </div>
                        <input type="text" name="platform_name" id="platform_name" 
                               class="block w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg text-gray-800 focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" 
                               placeholder="e.g. YouTube, Instagram, WhatsApp" required>
                    </div>
                </div>

                <div>
                    <label for="link_url" class="block text-sm font-semibold text-gray-700 mb-2">
                        Link URL <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                            </svg>
                        </div>
                        <input type="url" name="link_url" id="link_url" 
                               class="block w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg text-gray-800 focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" 
                               placeholder="https://youtube.com/@yourchannel" required>
                    </div>
                </div>

                <div>
                    <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">Initial Status</label>
                    <select name="status" id="status" class="block w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 focus:ring-2 focus:ring-red-500 transition-all bg-white">
                        <option value="active">Active (Visible immediately)</option>
                        <option value="inactive">Inactive (Draft mode)</option>
                    </select>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-6 border-t border-gray-100">
                    <a href="index.php" class="px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-center">
                        Cancel
                    </a>
                    <button type="submit" class="px-8 py-3 text-sm font-medium text-white bg-gradient-to-r from-red-500 to-pink-600 rounded-lg hover:from-red-600 hover:to-pink-700 transition-all shadow-lg hover:shadow-xl flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Create Platform
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="space-y-6">
        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Live Preview</h3>
        
        <div id="previewCard" class="rounded-xl p-6 shadow-xl relative h-40 flex flex-col justify-between overflow-hidden transition-all duration-300">
            <div>
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 bg-white rounded-xl flex items-center justify-center shadow-lg border border-white/50">
                            <img id="faviconPreview" src="https://www.google.com/s2/favicons?sz=64&domain=google.com" 
                                 class="w-7 h-7 object-contain" alt="logo">
                        </div>
                        <h3 id="namePreview" class="text-xl font-bold text-red-600 drop-shadow-md truncate max-w-[120px]">
                            Platform
                        </h3>
                    </div>
                    <span id="statusPreview" class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-widest bg-white/20 text-white border border-white/30 backdrop-blur-sm">
                        active
                    </span>
                </div>
                <p id="urlPreview" class="text-[10px] mt-5 break-all font-mono bg-white p-2 rounded backdrop-blur-md line-clamp-1 border border-white/10">
                    https://example.com
                </p>
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-xs text-blue-700 leading-relaxed">
                    <strong>Auto-Icon:</strong> We automatically fetch the high-resolution logo from the domain you provide.
                </p>
            </div>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-5">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-yellow-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                <p class="text-xs text-yellow-700 leading-relaxed">
                    <strong>App Links:</strong> For WhatsApp, use <code class="bg-yellow-100 px-1">https://wa.me/number</code> to trigger the app directly.
                </p>
            </div>
        </div>
    </div>
</div>

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
        // Update Text
        namePreview.textContent = platformInput.value || "Platform";
        urlPreview.textContent = urlInput.value || "https://example.com";
        statusPreview.textContent = statusInput.value;

        // Update Card Color based on status
        if(statusInput.value === 'inactive') {
            previewCard.classList.remove('from-red-600', 'to-pink-700');
            previewCard.classList.add('from-gray-500', 'to-gray-700');
        } else {
            previewCard.classList.add('from-red-600', 'to-pink-700');
            previewCard.classList.remove('from-gray-500', 'to-gray-700');
        }

        // Update Favicon
        try {
            if(urlInput.value) {
                const url = new URL(urlInput.value);
                faviconPreview.src = `https://www.google.com/s2/favicons?sz=64&domain=${url.hostname}`;
            } else {
                faviconPreview.src = `https://www.google.com/s2/favicons?sz=64&domain=google.com`;
            }
        } catch(e) {
            // Wait for valid URL
        }
    }

    platformInput.addEventListener('input', updatePreview);
    urlInput.addEventListener('input', updatePreview);
    statusInput.addEventListener('change', updatePreview);
</script>

<?php include __DIR__ . "/../includes/footer.php"; ?>