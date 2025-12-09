<?php
// ===========================================
// Admin Settings Page - Fully Matched to User UI
// ===========================================
// NOTE: Paths are fixed to '../../' to resolve the earlier include errors.
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

// --- 1. Handle Form Submission (Simulated) ---
$message = $_GET['msg'] ?? null;
$error = $_GET['error'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real application, you'd process form data and update the database here.
    $message = "Settings updated successfully!";
    // In a production environment, you would use header() to redirect.
}

// --- 2. Retrieve Current Settings (Simulated) ---
$settings = [
    'site_name' => 'iHUB Electronics',
    'default_currency' => 'INR',
    'contact_email' => 'support@ihub.com',
    'low_stock_threshold' => 5,
    'default_order_status' => 'pending',
    'auto_approve_reviews' => 1,
    'max_products_per_page' => 24,
    'log_prune_days' => 90,
    'shipping_rate' => 50.00
];

// Map the simulated values to variables for the form
$site_name = htmlspecialchars($settings['site_name']);
$default_currency = htmlspecialchars($settings['default_currency']);
$contact_email = htmlspecialchars($settings['contact_email']);
$low_stock_threshold = (int)$settings['low_stock_threshold'];
$default_order_status = htmlspecialchars($settings['default_order_status']);
$auto_approve_reviews = (bool)$settings['auto_approve_reviews'];
$max_products_per_page = (int)$settings['max_products_per_page'];
$log_prune_days = (int)$settings['log_prune_days'];
$shipping_rate = number_format((float)$settings['shipping_rate'], 2, '.', '');


include __DIR__ . "/../includes/header.php";
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">System Settings</h1>
            <p class="text-sm text-gray-500 mt-1">Configure application and e-commerce parameters.</p>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="mb-6 bg-green-50 border-l-4 border-green-500 rounded-lg p-4 shadow-sm">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($message) ?></p>
    </div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-lg p-4 shadow-sm">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p>
    </div>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3 flex items-center">
                <svg class="w-6 h-6 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5a2.5 2.5 0 002.5 2.5h1a2.5 2.5 0 002.5-2.5V3.935m-11.83 5.5h15.66c.21 0 .37.17.37.38v2.33c0 .21-.16.38-.37.38H4.22c-.21 0-.37-.17-.37-.38v-2.33c0-.21.16-.38.37-.38z"></path></svg>
                System & Branding
            </h2>

            <div class="space-y-6">
                
                <div>
                    <label for="site_name" class="block text-sm font-medium text-gray-700 mb-1">Site Name</label>
                    <input type="text" name="site_name" id="site_name" value="<?= $site_name ?>"
                        required class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                    <p class="mt-1 text-xs text-gray-500">The main title used throughout the site.</p>
                </div>

                <div>
                    <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
                    <input type="email" name="contact_email" id="contact_email" value="<?= $contact_email ?>"
                        required class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                    <p class="mt-1 text-xs text-gray-500">Official email for customer support and notifications.</p>
                </div>

                <div>
                    <label for="default_currency" class="block text-sm font-medium text-gray-700 mb-1">Default Currency</label>
                    <select name="default_currency" id="default_currency" class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                        <option value="INR" <?= ($default_currency == 'INR' ? 'selected' : '') ?>>₹ Indian Rupee (INR)</option>
                        <option value="USD" <?= ($default_currency == 'USD' ? 'selected' : '') ?>>$ US Dollar (USD)</option>
                        <option value="EUR" <?= ($default_currency == 'EUR' ? 'selected' : '') ?>>&euro; Euro (EUR)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3 flex items-center">
                <svg class="w-6 h-6 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.27a11.956 11.956 0 010 4.546 11.956 11.956 0 01-5.618 4.27L12 21.5l-4.22-2.193a11.956 11.956 0 01-5.618-4.27 11.956 11.956 0 010-4.546 11.956 11.956 0 015.618-4.27L12 2.5l4.22 2.193z"></path></svg>
                Security & Integrity
            </h2>
            
            <div class="space-y-6">

                <div>
                    <label for="log_prune_days" class="block text-sm font-medium text-gray-700 mb-1">Prune Logs Older Than (Days)</label>
                    <input type="number" name="log_prune_days" id="log_prune_days" value="<?= $log_prune_days ?>"
                        min="7" max="365" required class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                    <p class="mt-1 text-xs text-gray-500">Logs older than this will be deleted by the cleanup job.</p>
                </div>

                <div class="pt-2 border-t border-gray-100">
                    <button type="button" class="w-full px-4 py-2 text-sm font-medium text-white 
                        bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700
                        rounded-lg shadow-md transition-all duration-200">
                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Backup Database Now
                    </button>
                    <p class="mt-1 text-xs text-gray-500 text-center">Download a current snapshot of the DB.</p>
                </div>

            </div>
        </div>

        <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3 flex items-center">
                <svg class="w-6 h-6 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19v-2a3 3 0 013-3h8a3 3 0 013 3v2M5 19h14M5 19h-2a1 1 0 01-1-1v-5a1 1 0 011-1h16a1 1 0 011 1v5a1 1 0 01-1 1h-2M8 10h8m-4-7v7m0-7H9a2 2 0 00-2 2v2m7-2h-3"></path></svg>
                E-commerce & Logistics
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <div class="space-y-6">
                    
                    <div>
                        <label for="default_order_status" class="block text-sm font-medium text-gray-700 mb-1">New Order Status</label>
                        <select name="default_order_status" id="default_order_status" class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                            <option value="pending" <?= ($default_order_status == 'pending' ? 'selected' : '') ?>>Pending</option>
                            <option value="processing" <?= ($default_order_status == 'processing' ? 'selected' : '') ?>>Processing</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Initial status for all successful orders.</p>
                    </div>

                    <div>
                        <label for="max_products_per_page" class="block text-sm font-medium text-gray-700 mb-1">Products Per Page (Frontend)</label>
                        <input type="number" name="max_products_per_page" id="max_products_per_page" value="<?= $max_products_per_page ?>"
                            min="12" max="100" required class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                    </div>

                    <div class="flex items-center justify-between py-2">
                        <span class="flex-grow text-sm font-medium text-gray-700">Auto-Approve Reviews</span>
                        
                        <label for="auto_approve_reviews" class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="auto_approve_reviews" id="auto_approve_reviews" value="1" class="sr-only peer" <?= $auto_approve_reviews ? 'checked' : '' ?>>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                        </label>
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <label for="low_stock_threshold" class="block text-sm font-medium text-gray-700 mb-1">Low Stock Threshold</label>
                        <input type="number" name="low_stock_threshold" id="low_stock_threshold" value="<?= $low_stock_threshold ?>"
                            min="1" required class="w-full px-4 py-2 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                        <p class="mt-1 text-xs text-gray-500">Triggers the low stock warning on the Dashboard.</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <label for="shipping_rate" class="block text-sm font-medium text-gray-700 mb-1">Default Shipping Rate</label>
                        <div class="relative mt-1 rounded-lg shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500 sm:text-sm">₹</span>
                            </div>
                            <input type="number" name="shipping_rate" id="shipping_rate" value="<?= $shipping_rate ?>" step="0.01" min="0"
                                class="w-full px-4 py-2 pl-7 border rounded-lg shadow-sm focus:ring-red-500 focus:border-transparent transition duration-150">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Standard shipping cost.</p>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <div class="mt-8 text-right">
        <button type="submit" class="inline-flex items-center px-4 py-2.5 
            bg-gradient-to-r from-red-500 to-pink-600 text-white text-sm font-medium 
            rounded-lg hover:from-red-600 hover:to-pink-700 transition-all duration-200 shadow-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-4 0V4a2 2 0 012-2h2a2 2 0 012 2v3m-4 0h4"></path></svg>
            Save All Settings
        </button>
    </div>

</form>

<?php include __DIR__ . "/../includes/footer.php"; ?>