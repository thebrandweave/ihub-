<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

$message = $_GET['msg'] ?? null;
$error = $_GET['error'] ?? null;

// --- 1. Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Updated keys to include Opening Hours and Map Embed
        $settings_to_save = [
            'site_name', 'contact_email', 'contact_phone', 
            'contact_address', 'low_stock_threshold',
            'auto_approve_reviews', 'hours_weekday', 
            'hours_saturday', 'hours_sunday', 
            'google_maps_link', 'google_maps_embed'
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
        if ($pdo->inTransaction()) $pdo->rollBack();
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
<div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl p-4 shadow-sm">
    <div class="flex items-center">
        <p class="text-sm font-bold text-emerald-800"><?= htmlspecialchars($message) ?></p>
    </div>
</div>
<?php endif; ?>

<form method="POST">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center">Branding & Contact Info</h2>
                </div>
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Site Name</label>
                        <input type="text" name="site_name" value="<?= getSet('site_name', 'iHub Electronics') ?>" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500/20 focus:border-red-500 focus:bg-white outline-none">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Company Email</label>
                            <input type="email" name="contact_email" value="<?= getSet('contact_email') ?>" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Contact Phone</label>
                            <input type="text" name="contact_phone" value="<?= getSet('contact_phone') ?>" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Address</label>
                        <textarea name="contact_address" rows="2" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl outline-none"><?= getSet('contact_address') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50">
                    <h2 class="text-lg font-bold text-gray-800">Opening Hours</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-2">Mon - Fri</label>
                            <input type="text" name="hours_weekday" value="<?= getSet('hours_weekday', '10am - 8pm') ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-2">Saturday</label>
                            <input type="text" name="hours_saturday" value="<?= getSet('hours_saturday', '11am - 7pm') ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-2">Sunday</label>
                            <input type="text" name="hours_sunday" value="<?= getSet('hours_sunday', '11am - 5pm') ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50">
                    <h2 class="text-lg font-bold text-gray-800">Google Maps Integration</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-2">Google Maps Embed URL (Found in iframe src)</label>
                        <input type="text" name="google_maps_embed" value="<?= getSet('google_maps_embed') ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none" placeholder="https://www.google.com/maps/embed?pb=...">
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-sm font-bold text-gray-800 mb-6 uppercase tracking-widest">Store Rules</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-2">Low Stock Threshold</label>
                        <input type="number" name="low_stock_threshold" value="<?= getSet('low_stock_threshold', '5') ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none">
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full py-4 bg-gradient-to-r from-red-600 to-pink-600 text-white font-black text-sm uppercase tracking-widest rounded-2xl shadow-lg hover:shadow-xl transition-all">
                Save All Settings
            </button>
        </div>
    </div>
</form>

<?php include __DIR__ . "/../includes/footer.php"; ?>