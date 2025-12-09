<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

$id = $_GET['id'];

$pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$id]);
$pdo->prepare("DELETE FROM orders WHERE order_id = ?")->execute([$id]);

header("Location: index.php");
