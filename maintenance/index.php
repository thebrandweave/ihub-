<?php
// Define a constant to tell config.php we are already on the maintenance page
define('ON_MAINTENANCE_PAGE', true);

require_once __DIR__ . '/../config/config.php';

// Fetch custom messages for the UI
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('maintenance_title', 'maintenance_message')");
$m_text = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$title = $m_text['maintenance_title'] ?? "We'll be back shortly!";
$message = $m_text['maintenance_message'] ?? "Our site is currently undergoing scheduled maintenance.";
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance | iHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-6">
    <div class="max-w-md w-full text-center">
        <div class="mb-8 flex justify-center">
            <div class="w-24 h-24 bg-indigo-600 text-white rounded-[2.5rem] flex items-center justify-center shadow-2xl shadow-indigo-200 rotate-3 animate-pulse">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 00-1 1v1a2 2 0 11-4 0v-1a1 1 0 00-1-1H7a1 1 0 01-1-1v-3a1 1 0 011-1h1a2 2 0 100-4H7a1 1 0 01-1-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"></path></svg>
            </div>
        </div>
        
        <h1 class="text-4xl font-black text-slate-900 mb-4"><?= htmlspecialchars($title) ?></h1>
        <p class="text-slate-500 mb-10 leading-relaxed text-lg"><?= htmlspecialchars($message) ?></p>
        
        <div class="inline-flex items-center gap-3 px-6 py-3 bg-white rounded-full border border-slate-200 shadow-sm">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
            </span>
            <span class="text-sm font-bold text-slate-700 uppercase tracking-widest">Optimizing Servers</span>
        </div>
    </div>
</body>
</html>