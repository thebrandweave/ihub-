<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'admin'");
$stmt->execute([$id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    die("Admin not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, password_hash=? WHERE user_id=?");
        $stmt->execute([$name, $email, $password_hash, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=? WHERE user_id=?");
        $stmt->execute([$name, $email, $id]);
    }

    header("Location: index.php?msg=Admin updated successfully");
    exit;
}

include __DIR__ . "/../includes/header.php";
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Edit Administrator</h1>
            <p class="text-sm text-gray-500 mt-1">Update administrator account information</p>
        </div>
    </div>
</div>

<!-- Full Width Form Card -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <!-- Card Header -->
    <div class="px-4 md:px-8 py-4 md:py-6 border-b border-gray-100 bg-gradient-to-r from-red-50 to-pink-50">
        <div class="flex items-center">
            <div class="w-12 h-12 md:w-16 md:h-16 rounded-xl bg-gradient-to-br from-red-500 to-pink-600 flex items-center justify-center text-white font-bold text-lg md:text-2xl mr-3 md:mr-4 flex-shrink-0">
                <?= strtoupper(substr($admin['full_name'], 0, 1)) ?>
            </div>
            <div>
                <h3 class="text-base md:text-xl font-semibold text-gray-800"><?= htmlspecialchars($admin['full_name']) ?></h3>
                <p class="text-xs md:text-sm text-gray-600 mt-1">Account ID: #<?= $admin['user_id'] ?> • Administrator</p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" class="p-4 md:p-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8">
            <!-- Left Column -->
            <div class="space-y-6">
                <h4 class="text-lg font-semibold text-gray-800 pb-3 border-b border-gray-200">Account Information</h4>
                
                <!-- Full Name -->
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <input type="text" name="name" id="name" value="<?= htmlspecialchars($admin['full_name']) ?>"
                               class="block w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" 
                               placeholder="Enter full name" required>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Update the administrator's full legal name</p>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($admin['email']) ?>"
                               class="block w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" 
                               placeholder="admin@example.com" required>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">This email will be used for login and notifications</p>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                        New Password <span class="text-gray-400 font-normal">(Optional)</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <input type="password" name="password" id="password" 
                               class="block w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" 
                               placeholder="Leave blank to keep current password">
                    </div>
                    <div class="mt-3 bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <p class="text-xs font-medium text-gray-700 mb-2">Password Update Policy:</p>
                        <ul class="text-xs text-gray-600 space-y-1.5">
                            <li class="flex items-start">
                                <svg class="w-4 h-4 mr-2 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Leave this field empty to keep the existing password
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 mr-2 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Enter a new password only if you need to change it
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 mr-2 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Use a strong password with mixed characters for security
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <h4 class="text-lg font-semibold text-gray-800 pb-3 border-b border-gray-200">Account Status & Permissions</h4>
                
                <!-- Current Role -->
                <div class="bg-gradient-to-br from-red-50 to-pink-50 border border-red-200 rounded-xl p-6">
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-bold text-gray-800 mb-2">Current Role: Administrator</p>
                            <p class="text-xs text-gray-700 leading-relaxed">This account has full administrative privileges including:</p>
                        </div>
                    </div>
                    
                    <div class="mt-4 space-y-2 ml-16">
                        <div class="flex items-center text-xs text-gray-700">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Dashboard and analytics access
                        </div>
                        <div class="flex items-center text-xs text-gray-700">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            User and customer management
                        </div>
                        <div class="flex items-center text-xs text-gray-700">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Product catalog management
                        </div>
                        <div class="flex items-center text-xs text-gray-700">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Order processing and tracking
                        </div>
                        <div class="flex items-center text-xs text-gray-700">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Financial reports viewing
                        </div>
                    </div>
                </div>

                <!-- Account Stats -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-green-700">Account Status</span>
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                        </div>
                        <p class="text-lg font-bold text-green-800">Active</p>
                    </div>
                    
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-red-700">Access Level</span>
                            <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                        </div>
                        <p class="text-lg font-bold text-red-800">Full Access</p>
                    </div>
                </div>

                <!-- Warning Notice -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-yellow-800 mb-1">Update Confirmation</p>
                            <p class="text-xs text-yellow-700 leading-relaxed">Changes will take effect immediately after saving. If you're updating the password, the admin will need to use the new password for their next login.</p>
                        </div>
                    </div>
                </div>

                <!-- Security Tip -->
                <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-red-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-red-800 mb-1">Security Reminder</p>
                            <p class="text-xs text-red-700 leading-relaxed">Ensure the email address is valid and secure. Admin accounts should always use strong, unique passwords and enable two-factor authentication when available.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 mt-6 md:mt-8 pt-6 md:pt-8 border-t border-gray-200">
            <a href="index.php" class="w-full sm:w-auto px-4 md:px-6 py-2.5 md:py-3 text-xs md:text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center">
                <svg class="w-4 h-4 md:w-5 md:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <span class="hidden sm:inline">Cancel Changes</span>
                <span class="sm:hidden">Cancel</span>
            </a>
            <button type="submit" class="w-full sm:w-auto px-6 md:px-8 py-2.5 md:py-3 text-xs md:text-sm font-medium text-white bg-gradient-to-r from-red-500 to-pink-600 rounded-lg hover:from-red-600 hover:to-pink-700 transition-all duration-200 shadow-lg hover:shadow-xl flex items-center justify-center">
                <svg class="w-4 h-4 md:w-5 md:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="hidden sm:inline">Update Administrator</span>
                <span class="sm:hidden">Update</span>
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>