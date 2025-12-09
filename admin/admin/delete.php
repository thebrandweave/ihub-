<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

$id = $_GET['id'] ?? null;

if ($id) {
    // prevent deleting the currently logged-in admin
    if ($_SESSION['admin_id'] == $id) {
        header("Location: index.php?error=You cannot delete yourself!");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'admin'");
    $stmt->execute([$id]);
}

header("Location: index.php?msg=Admin deleted successfully");
exit;
?>
