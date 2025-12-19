<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

$ids = $_GET['ids'] ?? '';
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build Query
if (!empty($ids)) {
    // Export specifically selected IDs
    $idArray = explode(',', $ids);
    $placeholders = implode(',', array_fill(0, count($idArray), '?'));
    $sql = "SELECT s.email, s.status, s.created_at, u.full_name 
            FROM newsletter_subscribers s 
            LEFT JOIN users u ON s.user_id = u.user_id 
            WHERE s.subscriber_id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($idArray);
} else {
    // Export based on active filters
    $sql = "SELECT s.email, s.status, s.created_at, u.full_name 
            FROM newsletter_subscribers s 
            LEFT JOIN users u ON s.user_id = u.user_id 
            WHERE (s.email LIKE :search OR u.full_name LIKE :search)";
    $params = [':search' => '%' . $searchTerm . '%'];

    if ($statusFilter) {
        $sql .= " AND s.status = :status";
        $params[':status'] = $statusFilter;
    }
    
    $sql .= " ORDER BY s.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Download Headers
$filename = "subscribers_" . date('Y-m-d_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
fputcsv($output, ['Email', 'Status', 'User Name', 'Date Subscribed']);

foreach ($subscribers as $row) {
    fputcsv($output, [
        $row['email'],
        ucfirst($row['status']),
        $row['full_name'] ?? 'Guest',
        $row['created_at']
    ]);
}
fclose($output);
exit;