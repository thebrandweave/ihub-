<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

// Helper for Favicon
function getFavicon($url) {
    $domain = parse_url($url, PHP_URL_HOST);
    if (!$domain) return 'https://www.google.com/s2/favicons?sz=64&domain=google.com';
    return "https://www.google.com/s2/favicons?sz=64&domain=" . $domain;
}

$message = $_GET['msg'] ?? null;
$error = $_GET['error'] ?? null;
$searchTerm = $_GET['search'] ?? '';

// Fetch Data
$sql = "SELECT * FROM social_media WHERE (platform_name LIKE :search OR link_url LIKE :search) ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':search' => "%$searchTerm%"]);
$social_links = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total = $pdo->query("SELECT COUNT(*) FROM social_media")->fetchColumn();
$active = $pdo->query("SELECT COUNT(*) FROM social_media WHERE status='active'")->fetchColumn();

include __DIR__."/../includes/header.php";
?>

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Social Media</h1>
        <p class="text-sm text-gray-500">Manage your brand's online presence icons.</p>
    </div>
    <a href="add.php" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-red-500 to-pink-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
        Add Platform
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-bold text-gray-400 uppercase">Total Platforms</p>
            <p class="text-2xl font-black text-gray-800"><?= $total ?></p>
        </div>
        <div class="w-12 h-12 bg-red-50 text-red-500 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
        </div>
    </div>
    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-bold text-gray-400 uppercase">Live Links</p>
            <p class="text-2xl font-black text-green-600"><?= $active ?></p>
        </div>
        <div class="w-12 h-12 bg-green-50 text-green-500 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <?php foreach ($social_links as $s): ?>
    <div class="group relative bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden h-52 transition-all hover:-translate-y-1 hover:shadow-2xl">
        
        <div class="relative p-4 h-full flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <div class="w-12 h-12 flex items-center justify-center">
                    <img src="<?= getFavicon($s['link_url']) ?>" class="w-full h-full object-contain" alt="icon">
                </div>
                <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase bg-red-600 text-white ">
                    <?= $s['status'] ?>
                </span>
            </div>

            <div class="mt-2">
                <h3 class="font-bold text-red-600 text-lg drop-shadow-md truncate"><?= htmlspecialchars($s['platform_name']) ?></h3>
                <p class="text-[10px] font-mono text-gray-700 bg-gray-100 p-2 rounded mt-2 truncate border border-gray-200"><?= htmlspecialchars($s['link_url']) ?></p>
            </div>

            <div class="mt-2 flex gap-2 opacity-0 group-hover:opacity-100 transition-all">
                <a href="edit.php?id=<?= $s['social_id'] ?>" class="flex-1 text-center py-2 bg-red-600 text-white text-xs font-bold rounded-lg hover:bg-red-700">Edit</a>
                <a href="delete.php?id=<?= $s['social_id'] ?>" onclick="return confirm('Delete?')" class="px-3 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-red-50 hover:text-red-600 border border-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__."/../includes/footer.php"; ?>