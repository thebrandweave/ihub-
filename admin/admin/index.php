<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";
include __DIR__ . "/../includes/header.php";

// --- START: Search Logic (Status removed) ---

// Get search term from GET parameters
$searchTerm = $_GET['search'] ?? '';

// Build the base SQL query
$sql = "SELECT * FROM users WHERE role = 'admin'";
$conditions = [];
$params = [];

// 1. Add Search Term Condition
if ($searchTerm) {
    // Search by full_name or email
    $conditions[] = "(full_name LIKE :search_term OR email LIKE :search_term)";
    $params[':search_term'] = '%' . $searchTerm . '%';
}

// Combine conditions (Only search condition remains)
if (count($conditions) > 0) {
    // Append the conditions to the base query
    $sql .= " AND " . implode(' AND ', $conditions);
}

$sql .= " ORDER BY full_name ASC"; // Add ordering for consistency

// Prepare and execute the statement
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- END: Search Logic ---

// Success/Error messages
$msg = $_GET['msg'] ?? null;
$error = $_GET['error'] ?? null;
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-0">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Admin Management</h1>
            <p class="text-sm text-gray-500 mt-1">Manage administrator accounts and permissions</p>
        </div>
        <a href="add.php" class="inline-flex items-center justify-center px-3 md:px-4 py-2 md:py-2.5 bg-gradient-to-r from-red-500 to-pink-600 text-white text-xs md:text-sm font-medium rounded-lg hover:from-red-600 hover:to-pink-700 transition-all duration-200 shadow-sm">
            <svg class="w-4 h-4 md:w-5 md:h-5 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span class="hidden sm:inline">Add New Admin</span>
            <span class="sm:hidden">Add Admin</span>
        </a>
    </div>
</div>

<?php if ($msg): ?>
<div class="mb-6 bg-green-50 border-l-4 border-green-500 rounded-lg p-4 shadow-sm">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($msg) ?></p>
    </div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-lg p-4 shadow-sm">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6 mb-4 md:mb-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">Total Admins</p>
                <p class="text-2xl font-bold text-gray-800"><?= count($admins) ?></p>
            </div>
            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">Active Sessions</p>
                <p class="text-2xl font-bold text-gray-800">1</p>
            </div>
            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">Account Security</p>
                <p class="text-2xl font-bold text-gray-800">100%</p>
            </div>
            <div class="w-12 h-12 bg-gradient-to-br from-red-400 to-pink-500 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h3 class="text-lg font-semibold text-gray-800">Administrator Accounts</h3>
            
            <form method="GET" action="index.php" class="flex items-center space-x-3">
                
                <div class="relative">
                    <input type="text" name="search" placeholder="Search admins..." value="<?= htmlspecialchars($searchTerm) ?>" class="pl-10 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                
                <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600 transition-colors">Search</button>
            </form>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Admin</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Role</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($admins) > 0): ?>
                    <?php foreach ($admins as $a): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-400 to-pink-600 flex items-center justify-center text-white font-semibold">
                                    <?= strtoupper(substr($a['full_name'], 0, 1)) ?>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($a['full_name']) ?></div>
                                    <div class="text-xs text-gray-500">ID: #<?= $a['user_id'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-800"><?= htmlspecialchars($a['email']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                                Administrator
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-3">
                                <a href="edit.php?id=<?= $a['user_id'] ?>" class="text-red-600 hover:text-red-800 font-medium transition-colors" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <a href="delete.php?id=<?= $a['user_id'] ?>" class="text-red-600 hover:text-red-800 font-medium transition-colors" title="Delete" onclick="return confirm('Are you sure you want to delete this admin? This action cannot be undone.')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="bg-white">
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                            No administrators found matching the criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Showing <span class="font-semibold text-gray-800"><?= count($admins) ?></span> administrator(s)
            </div>
            <div class="flex items-center space-x-2">
                <button class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50" disabled>
                    Previous
                </button>
                <button class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50" disabled>
                    Next
                </button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>