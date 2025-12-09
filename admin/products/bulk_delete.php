<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

$data = json_decode(file_get_contents("php://input"), true);

if(empty($data['ids'])) exit;

$ids = array_map('intval', $data['ids']);
$in  = implode(',', array_fill(0, count($ids), '?'));

$pdo->prepare("DELETE FROM product_images WHERE product_id IN ($in)")->execute($ids);
$pdo->prepare("DELETE FROM products WHERE product_id IN ($in)")->execute($ids);

echo json_encode(['success'=>true]);
