<?php
// ihub/admin/products/toggle_featured.php

require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

// Set header to return JSON
header('Content-Type: application/json');

// Get the raw POST data (JSON)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$product_id = $data['product_id'] ?? null;

// Basic validation
if (!$product_id || !is_numeric($product_id)) {
    echo json_encode([
        'success' => false, 
        'error' => 'Invalid Product ID provided.'
    ]);
    exit;
}

try {
    // 1. Check if the product is already in the featured_products table
    $checkStmt = $pdo->prepare("SELECT 1 FROM featured_products WHERE product_id = ?");
    $checkStmt->execute([$product_id]);
    $isAlreadyFeatured = $checkStmt->fetch();

    if ($isAlreadyFeatured) {
        // 2. If it exists, remove it (Un-feature)
        $deleteStmt = $pdo->prepare("DELETE FROM featured_products WHERE product_id = ?");
        $deleteStmt->execute([$product_id]);
        
        echo json_encode([
            'success' => true, 
            'action' => 'removed',
            'message' => 'Product removed from featured list.'
        ]);
    } else {
        // 3. If it doesn't exist, add it (Feature)
        $insertStmt = $pdo->prepare("INSERT INTO featured_products (product_id) VALUES (?)");
        $insertStmt->execute([$product_id]);
        
        echo json_encode([
            'success' => true, 
            'action' => 'added',
            'message' => 'Product added to featured list.'
        ]);
    }

} catch (PDOException $e) {
    // Return the database error
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
exit;