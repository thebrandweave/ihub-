<?php
require_once __DIR__ . '/../auth/customer_auth.php';
require_once __DIR__ . '/../config/config.php';

$user_id = $_SESSION['customer_id'];
$id = $_GET['id'];

$stmt = $pdo->prepare("DELETE FROM addresses WHERE address_id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);

header("Location: addresses.php");
exit;
