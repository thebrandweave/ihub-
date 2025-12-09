<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";
include __DIR__ . "/../includes/header.php";

$adsStmt = $pdo->query("SELECT * FROM advertisements ORDER BY priority DESC, created_at DESC");
$ads = $adsStmt->fetchAll(PDO::FETCH_ASSOC);

function getAdRuntimeState(array $ad): string
{
    if (($ad['status'] ?? 'inactive') !== 'active') return 'inactive';
    $now = new DateTimeImmutable();
    $start = new DateTimeImmutable($ad['start_date']);
    $end = new DateTimeImmutable($ad['end_date']);
    if ($now < $start) return 'scheduled';
    if ($now > $end) return 'expired';
    return 'running';
}

function formatAdImageUrl(string $filename = null): string
{
    global $BASE_URL;
    if (empty($filename)) {
        return 'https://via.placeholder.com/600x300?text=No+Image';
    }

    if (preg_match('#^https?://#i', $filename)) {
        return $filename;
    }

    // CHANGED: Prepend 'uploads/ads/' here because DB only has filename
    return rtrim($BASE_URL, '/') . '/uploads/ads/' . ltrim($filename, '/');
}

// ... [Remainder of PHP logic for counts matches previous] ...
$activeAds = array_filter($ads, function ($ad) { return getAdRuntimeState($ad) === 'running'; });
$heroCount = count(array_filter($ads, fn($ad) => $ad['ad_type'] === 'hero_banner'));
$popupCount = count(array_filter($ads, fn($ad) => $ad['ad_type'] === 'popup'));
$msg = $_GET['msg'] ?? null;
$error = $_GET['error'] ?? null;
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Advertisement Campaigns</h1>
            <p class="text-sm text-gray-500 mt-1">Manage hero banners and popup campaigns.</p>
        </div>
        <a href="add.php" class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-red-500 to-pink-600 text-white text-sm font-medium rounded-lg hover:from-red-600 hover:to-pink-700 shadow-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Campaign
        </a>
    </div>
</div>

<?php if ($msg): ?>
    <div class="mb-6 bg-green-50 border-l-4 border-green-500 rounded-lg p-4 shadow-sm"><p class="text-sm font-medium text-green-800"><?= htmlspecialchars($msg) ?></p></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-lg p-4 shadow-sm"><p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p></div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mb-6">
    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between">
            <div><p class="text-sm font-medium text-gray-500 mb-1">Live Campaigns</p><p class="text-3xl font-bold text-gray-900"><?= count($activeAds) ?></p></div>
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-pink-600 flex items-center justify-center text-white"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        </div>
    </div>
    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between">
            <div><p class="text-sm font-medium text-gray-500 mb-1">Hero Banners</p><p class="text-3xl font-bold text-gray-900"><?= $heroCount ?></p></div>
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center text-white"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l9-4 9 4-9 4-9-4zm0 4l9 4 9-4m-9 4v6"/></svg></div>
        </div>
    </div>
    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between">
            <div><p class="text-sm font-medium text-gray-500 mb-1">Popup Campaigns</p><p class="text-3xl font-bold text-gray-900"><?= $popupCount ?></p></div>
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center text-white"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h2m-1-2v2m-7 6h14m-8 4h6m-3-4v6m-4-6v2m8-2v2"/></svg></div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div><h3 class="text-lg font-semibold text-gray-800">All Campaigns</h3></div>
    </div>

    <?php if (empty($ads)): ?>
        <div class="p-12 text-center text-gray-500"><p class="font-semibold">No campaigns yet</p></div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold">Campaign</th>
                    <th class="px-4 py-3 text-left font-semibold">Placement</th>
                    <th class="px-4 py-3 text-left font-semibold">Schedule</th>
                    <th class="px-4 py-3 text-left font-semibold">Priority</th>
                    <th class="px-4 py-3 text-left font-semibold">Performance</th>
                    <th class="px-4 py-3 text-right font-semibold">Actions</th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100 text-gray-700">
                <?php foreach ($ads as $ad): ?>
                    <?php $state = getAdRuntimeState($ad); ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-4">
                            <div class="flex items-start space-x-3">
                                <div class="w-20 h-12 rounded-lg overflow-hidden bg-gray-100 border border-gray-200 flex-shrink-0">
                                    <img src="<?= htmlspecialchars(formatAdImageUrl($ad['image_url'])) ?>" alt="<?= htmlspecialchars($ad['title']) ?>" class="w-full h-full object-cover">
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900"><?= htmlspecialchars($ad['title']) ?></p>
                                    <p class="text-xs text-gray-400 uppercase tracking-wide">ID #<?= (int)$ad['ad_id'] ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4"><span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $ad['ad_type'] === 'popup' ? 'bg-red-100 text-red-700' : 'bg-red-100 text-red-700' ?>"><?= $ad['ad_type'] === 'popup' ? 'Popup Overlay' : 'Hero Banner' ?></span><div class="mt-2 text-xs"><span class="inline-flex items-center px-2 py-0.5 rounded-full <?= match ($state) { 'running' => 'bg-green-50 text-green-700 border border-green-100', 'scheduled' => 'bg-amber-50 text-amber-700 border border-amber-100', 'expired' => 'bg-gray-100 text-gray-500 border border-gray-200', default => 'bg-red-50 text-red-600 border border-red-100' } ?>"><?= ucfirst($state) ?></span></div></td>
                        <td class="px-4 py-4 text-sm"><div class="font-medium text-gray-900"><?= date('M d, Y g:i a', strtotime($ad['start_date'])) ?></div><div class="text-xs text-gray-500">to <?= date('M d, Y g:i a', strtotime($ad['end_date'])) ?></div></td>
                        <td class="px-4 py-4"><span class="inline-flex items-center px-3 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-semibold"><?= (int)$ad['priority'] ?></span></td>
                        <td class="px-4 py-4 text-xs text-gray-600"><div class="flex items-center space-x-3"><span><?= number_format((int)$ad['views']) ?> views</span><span><?= number_format((int)$ad['clicks']) ?> clicks</span></div></td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="edit.php?id=<?= (int)$ad['ad_id'] ?>" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-red-600 hover:text-red-800">Edit</a>
                                <form method="POST" action="delete.php" onsubmit="return confirm('Delete this campaign?');"><input type="hidden" name="id" value="<?= (int)$ad['ad_id'] ?>"><button type="submit" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-red-600 hover:text-red-800">Delete</button></form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . "/../includes/footer.php"; ?>