<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
    $stmt->execute([$name, $email, $password]);

    header("Location: index.php?msg=Admin added successfully");
    exit;
}

include __DIR__ . "/../includes/header.php";
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Add New Admin</h1>
            <p class="text-sm text-gray-500 mt-1">Create a new administrator account</p>
        </div>
    </div>
</div>

<!-- Full Width Form Card -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <!-- Card Header -->
    <div class="px-4 md:px-8 py-4 md:py-6 border-b border-gray-100 bg-gradient-to-r from-red-50 to-pink-50">
        <h3 class="text-lg md:text-xl font-semibold text-gray-800 flex items-center">
            <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-br from-red-500 to-pink-600 rounded-lg flex items-center justify-center mr-2 md:mr-3 flex-shrink-0">
                <svg class="w-4 h-4 md:w-6 md:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
            </div>
            <span class="text-sm md:text-base">Administrator Details</span>
        </h3>
        <p class="text-xs md:text-sm text-gray-600 mt-1 md:ml-13">Fill in the information below to create a new admin account</p>
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
                        <input type="text" name="name" id="name" 
                               class="block w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" 
                               placeholder="Enter full name" required>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Enter the administrator's full legal name</p>
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
                        <input type="email" name="email" id="email" 
                               class="block w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" 
                               placeholder="admin@example.com" required>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">This email will be used for login and notifications</p>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                        Password <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <input type="password" name="password" id="password" 
                               class="block w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" 
                               placeholder="Enter secure password" required>
                    </div>
                    <div class="mt-3 space-y-2">
                        <p class="text-xs font-medium text-gray-700">Password Requirements:</p>
                        <ul class="text-xs text-gray-600 space-y-1 ml-4">
                            <li class="flex items-center">
                                <svg class="w-3 h-3 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                At least 8 characters long
                            </li>
                            <li class="flex items-center">
                                <svg class="w-3 h-3 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Contains uppercase and lowercase letters
                            </li>
                            <li class="flex items-center">
                                <svg class="w-3 h-3 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Includes numbers and special characters
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <h4 class="text-lg font-semibold text-gray-800 pb-3 border-b border-gray-200">Permissions & Information</h4>
                
                <!-- Role Info Card -->
                <div class="bg-gradient-to-br from-red-50 to-pink-50 border border-red-200 rounded-xl p-6">
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-bold text-gray-800 mb-2">Administrator Privileges</p>
                            <p class="text-xs text-gray-700 leading-relaxed">This account will be granted full administrative access with the following capabilities:</p>
                        </div>
                    </div>
                    
                    <div class="mt-4 space-y-2 ml-16">
                        <div class="flex items-center text-xs text-gray-700">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Full access to dashboard and analytics
                        </div>
                        <div class="flex items-center text-xs text-gray-700">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Manage users and customer accounts
                        </div>
                        <div class="flex items-center text-xs text-gray-700">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Create, edit, and delete products
                        </div>
                        <div class="flex items-center text-xs text-gray-700">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Process and manage orders
                        </div>
                        <div class="flex items-center text-xs text-gray-700">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            View reports and financial data
                        </div>
                        <div class="flex items-center text-xs text-gray-700">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Manage other administrator accounts
                        </div>
                    </div>
                </div>

                <!-- Security Notice -->
                <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-red-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-red-800 mb-1">Security Best Practices</p>
                            <p class="text-xs text-red-700 leading-relaxed">Ensure you're creating this admin account for a trusted individual. Admin accounts have extensive system access and should be protected with strong passwords and regular security audits.</p>
                        </div>
                    </div>
                </div>

                <!-- Warning Notice -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-yellow-800 mb-1">Important Notice</p>
                            <p class="text-xs text-yellow-700 leading-relaxed">The new admin will be able to access all system features immediately after account creation. Make sure all information is accurate before proceeding.</p>
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
                Cancel
            </a>
            <button type="submit" class="w-full sm:w-auto px-6 md:px-8 py-2.5 md:py-3 text-xs md:text-sm font-medium text-white bg-gradient-to-r from-red-500 to-pink-600 rounded-lg hover:from-red-600 hover:to-pink-700 transition-all duration-200 shadow-lg hover:shadow-xl flex items-center justify-center">
                <svg class="w-4 h-4 md:w-5 md:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span class="hidden sm:inline">Create Admin Account</span>
                <span class="sm:hidden">Create Admin</span>
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>