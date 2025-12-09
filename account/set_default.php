<?php
require_once __DIR__ . '/../auth/customer_auth.php';
require_once __DIR__ . '/../config/config.php';

$user_id = $_SESSION['customer_id'];
$id = $_GET['id'];

$pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
$pdo->prepare("UPDATE addresses SET is_default = 1 WHERE address_id = ? AND user_id = ?")->execute([$id, $user_id]);

header("Location: addresses.php");
exit;
    