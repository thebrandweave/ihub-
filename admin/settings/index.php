<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

$message = $_GET['msg'] ?? null;
$error = $_GET['error'] ?? null;

// --- 1. Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Define all keys that should be saved to site_settings table
        $settings_to_save = [
            'site_name', 'contact_email', 'contact_phone', 
            'contact_address', 'low_stock_threshold',
            'auto_approve_reviews', 'maintenance_mode'
        ];

        foreach ($settings_to_save as $key) {
            // Handle checkboxes (if not checked, they aren't sent in POST)
            $value = $_POST[$key] ?? '0'; 

            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) 
                                   VALUES (:key, :val) 
                                   ON DUPLICATE KEY UPDATE setting_value = :val");
            $stmt->execute([':key' => $key, ':val' => $value]);
        }

        $pdo->commit();
        header("Location: index.php?msg=Settings updated successfully");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Update failed: " . $e->getMessage();
    }
}

// --- 2. Retrieve Current Settings ---
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

function getSet($key, $default = '') {
    global $db_settings;
    return htmlspecialchars($db_settings[$key] ?? $default);
}

include __DIR__ . "/../includes/header.php";
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800 tracking-tight">System Settings</h1>
    <p class="text-sm text-gray-500 mt-1">Configure your brand identity and e-commerce operations.</p>
</div>

<?php if ($message): ?>
<div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl p-4 shadow-sm animate-in fade-in duration-500">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-emerald-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <p class="text-sm font-bold text-emerald-800"><?= htmlspecialchars($message) ?></p>
    </div>
</div>
<?php endif; ?>

<form method="POST">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        Branding & Company Info
                    </h2>
                </div>

                <div class="p-6 space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Site Name</label>
                        <input type="text" name="site_name" value="<?= getSet('site_name', 'iHub Electronics') ?>" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500/20 focus:border-red-500 focus:bg-white transition-all outline-none">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Support Email</label>
                            <input type="email" name="contact_email" value="<?= getSet('contact_email') ?>" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500/20 focus:border-red-500 focus:bg-white transition-all outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Contact Phone</label>
                            <input type="text" name="contact_phone" value="<?= getSet('contact_phone') ?>" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500/20 focus:border-red-500 focus:bg-white transition-all outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Store Physical Address</label>
                        <textarea name="contact_address" rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500/20 focus:border-red-500 focus:bg-white transition-all outline-none" placeholder="Enter store location..."><?= getSet('contact_address') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            
            <div class="bg-white rounded-2xl shadow-sm border border-orange-100 p-6 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-3">
                    <span class="flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full <?= getSet('maintenance_mode') == '1' ? 'bg-orange-400' : 'bg-green-400' ?> opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 <?= getSet('maintenance_mode') == '1' ? 'bg-orange-500' : 'bg-green-500' ?>"></span>
                    </span>
                </div>

                <h2 class="text-sm font-bold text-gray-800 mb-4 flex items-center uppercase tracking-widest">
                    <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    System Status
                </h2>
                
                <div class="flex items-center justify-between p-4 bg-orange-50 rounded-2xl border border-orange-100">
                    <div>
                        <span class="text-xs font-bold text-orange-900 block">Maintenance Mode</span>
                        <span class="text-[10px] text-orange-600">Site will be hidden from public</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="maintenance_mode" value="1" <?= getSet('maintenance_mode') == '1' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500 transition-all"></div>
                    </label>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-sm font-bold text-gray-800 mb-6 flex items-center uppercase tracking-widest">
                    <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                    Store Rules
                </h2>
                
                <div class="space-y-4">

                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-2">Low Stock Threshold</label>
                        <input type="number" name="low_stock_threshold" value="<?= getSet('low_stock_threshold', '5') ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-red-500/20">
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl border border-gray-100 mt-4">
                        <span class="text-xs font-bold text-gray-700">Auto-Approve Reviews</span>
                        <input type="checkbox" name="auto_approve_reviews" value="1" <?= getSet('auto_approve_reviews') == '1' ? 'checked' : '' ?> class="w-5 h-5 text-red-600 rounded-lg focus:ring-red-500">
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full py-4 bg-gradient-to-r from-red-600 to-pink-600 text-white font-black text-sm uppercase tracking-widest rounded-2xl shadow-lg shadow-red-200 hover:shadow-xl transition-all transform hover:-translate-y-1 active:scale-95">
                Save All Settings
            </button>
        </div>
    </div>
</form>

<?php include __DIR__ . "/../includes/footer.php"; ?>