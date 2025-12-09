<?php
session_start();
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$user_id = $_SESSION['customer_id'] ?? null;

if (!$user_id) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? null;

switch ($action) {

    /* ===================== ADD ===================== */
    case 'add':

        // prevent duplicate
        $check = $pdo->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check->execute([$user_id, $id]);

        if ($check->rowCount() == 0) {
            $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $id]);
        }

        echo json_encode(['status' => 'added']);
        exit;


    /* ===================== REMOVE ===================== */
    case 'remove':

        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $id]);

        echo json_encode(['status' => 'removed']);
        exit;


    /* ===================== CLEAR ===================== */
    case 'clear':

        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ?");
        $stmt->execute([$user_id]);

        echo json_encode(['status' => 'cleared']);
        exit;


    /* ===================== COUNT ===================== */
    case 'count':

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $stmt->execute([$user_id]);

        echo json_encode(['count' => $stmt->fetchColumn()]);
        exit;


    /* ===================== GET ITEMS ===================== */
    case 'get':

        $stmt = $pdo->prepare("
            SELECT w.product_id AS id, p.name, p.price,
                   (SELECT image_url FROM product_images WHERE product_id = p.product_id LIMIT 1) as image
            FROM wishlist w
            JOIN products p ON w.product_id = p.product_id
            WHERE w.user_id = ?
        ");
        $stmt->execute([$user_id]);

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['items' => $items]);
        exit;


    /* ===================== MOVE ALL TO CART ===================== */
    case 'move_all':

        $items = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $items->execute([$user_id]);

        foreach ($items->fetchAll(PDO::FETCH_ASSOC) as $row) {

            // If item exists increase qty
            $check = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
            $check->execute([$user_id, $row['product_id']]);

            if ($check->rowCount() > 0) {
                $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?")
                    ->execute([$user_id, $row['product_id']]);
            } else {
                $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)")
                    ->execute([$user_id, $row['product_id']]);
            }
        }

        // Clear wishlist
        $pdo->prepare("DELETE FROM wishlist WHERE user_id = ?")->execute([$user_id]);

        echo json_encode(['status' => 'moved_all']);
        exit;
}

echo json_encode(['status' => 'invalid']);
