<?php
require_once __DIR__ . '/../auth/customer_auth.php';
require_once __DIR__ . '/../config/config.php';

$user_id = $_SESSION['customer_id'];
$address_id = $_POST['address_id'] ?? null;
$total = $_POST['total'] ?? 0;

if (!$address_id || $total <= 0) {
    die("Invalid order data");
}

try {
    // Begin transaction for data consistency
    $pdo->beginTransaction();

    // Get address
    $addr = $pdo->prepare("SELECT * FROM addresses WHERE address_id=? AND user_id=?");
    $addr->execute([$address_id, $user_id]);
    $address = $addr->fetch(PDO::FETCH_ASSOC);

    if (!$address) {
        throw new Exception("Invalid address");
    }

    // Format address string
    $shipping_address = $address['full_name'] . ", " .
                        $address['phone'] . ", " .
                        $address['address_line1'] . ", " .
                        ($address['address_line2'] ? $address['address_line2'] . ", " : "") .
                        $address['city'] . ", " .
                        $address['state'] . " - " .
                        $address['postal_code'] . ", " .
                        $address['country'];

    // Generate unique order number
    $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    // Check if order number exists (very rare, but good practice)
    $checkOrderNum = $pdo->prepare("SELECT order_id FROM orders WHERE order_number = ?");
    $checkOrderNum->execute([$order_number]);
    
    // If exists, regenerate (extremely rare case)
    while ($checkOrderNum->fetch()) {
        $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $checkOrderNum->execute([$order_number]);
    }

    // 1️⃣ Insert order
    $pdo->prepare("
        INSERT INTO orders (user_id, order_number, address_id, total_amount, shipping_address, payment_method)
        VALUES (?, ?, ?, ?, ?, 'COD')
    ")->execute([$user_id, $order_number, $address_id, $total, $shipping_address]);

    $order_id = $pdo->lastInsertId();

    // 2️⃣ Get cart items
    $cart = $pdo->prepare("SELECT * FROM cart WHERE user_id=?");
    $cart->execute([$user_id]);
    $cartItems = $cart->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        throw new Exception("Cart is empty");
    }

    // 3️⃣ Insert order items
    $firstProductName = null;
    foreach ($cartItems as $item) {
        // Get product price and check stock
        $prod = $pdo->prepare("SELECT price, stock FROM products WHERE product_id=?");
        $prod->execute([$item['product_id']]);
        $product = $prod->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Product not found: " . $item['product_id']);
        }

        if ($product['stock'] < $item['quantity']) {
            throw new Exception("Insufficient stock for product ID: " . $item['product_id']);
        }

        if ($firstProductName === null) {
            $firstProductName = $product['name'] ?? null;
        }

        // Insert order item
        $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price_at_time)
            VALUES (?, ?, ?, ?)
        ")->execute([
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $product['price']
        ]);

        // Decrease stock
        $pdo->prepare("
            UPDATE products SET stock = stock - ?
            WHERE product_id=?
        ")->execute([$item['quantity'], $item['product_id']]);
    }

    // 4️⃣ Clear cart
    $pdo->prepare("DELETE FROM cart WHERE user_id=?")->execute([$user_id]);

    // 5️⃣ Create admin notifications about the new order
    $admins = $pdo->query("SELECT user_id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
    if ($admins) {
        $title = 'New order placed';
        $primaryText = $firstProductName ? $firstProductName : 'order';
        $message = "Customer placed a new {$primaryText} order #{$order_number} (ID {$order_id}) for ₹{$total}.";
        $targetUrl = $BASE_URL . "admin/orders/view.php?id=" . $order_id;

        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, target_url)
            VALUES (?, 'order_update', ?, ?, ?)
        ");

        foreach ($admins as $adminId) {
            $notifStmt->execute([$adminId, $title, $message, $targetUrl]);
        }
    }

    // Commit transaction
    $pdo->commit();

    header("Location: ".$BASE_URL."account/orders.php?success=1");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Order placement error: " . $e->getMessage());
    die("Error placing order: " . $e->getMessage());
}
