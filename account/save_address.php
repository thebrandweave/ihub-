<?php
require_once __DIR__ . '/../auth/customer_auth.php';
require_once __DIR__ . '/../config/config.php';

$user_id = $_SESSION['customer_id'];

$full_name = $_POST['full_name'];
$phone = $_POST['phone'];
$address1 = $_POST['address_line1'];
$address2 = $_POST['address_line2'] ?? null;
$city = $_POST['city'];
$state = $_POST['state'];
$postal_code = $_POST['postal_code'];
$is_default = isset($_POST['is_default']) ? 1 : 0;

// If new address is default, remove all previous defaults
if ($is_default) {
    $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?")
        ->execute([$user_id]);
}

$stmt = $pdo->prepare("
INSERT INTO addresses
(user_id, full_name, phone, address_line1, address_line2, city, state, postal_code, is_default)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $user_id,
    $full_name,
    $phone,
    $address1,
    $address2,
    $city,
    $state,
    $postal_code,
    $is_default
]);

header("Location: addresses.php");
exit;
