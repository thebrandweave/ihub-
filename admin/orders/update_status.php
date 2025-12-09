<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['order_id'];
    $status = $_POST['status'];

    // Fetch current status so we only log real changes
    $currentStmt = $pdo->prepare("SELECT status FROM orders WHERE order_id = ?");
    $currentStmt->execute([$id]);
    $current = $currentStmt->fetchColumn();

    if ($current !== $status) {
        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->execute([$status, $id]);

        // Log into order_status_history for timeline tracking
        $historyStmt = $pdo->prepare("
            INSERT INTO order_status_history (order_id, status)
            VALUES (?, ?)
        ");
        $historyStmt->execute([$id, $status]);

        // Get order details for customer notification
        $orderStmt = $pdo->prepare("SELECT user_id, order_number FROM orders WHERE order_id = ?");
        $orderStmt->execute([$id]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

        if ($order && $order['user_id']) {
            // Create customer notification about order status update
            $orderNumber = $order['order_number'];
            
            $statusMessages = [
                'pending' => "Your order #{$orderNumber} is being processed. We'll update you soon!",
                'processing' => "Your order #{$orderNumber} is being prepared and will be shipped shortly.",
                'shipped' => "Great news! Your order #{$orderNumber} has been shipped and is on its way to you.",
                'delivered' => "Your order #{$orderNumber} has been delivered successfully. Thank you for shopping with us!",
                'cancelled' => "Your order #{$orderNumber} has been cancelled. If you have any questions, please contact us."
            ];

            $title = 'Order ' . ucfirst($status);
            $message = $statusMessages[$status] ?? "Your order #{$orderNumber} status has been updated";
            $targetUrl = $BASE_URL . "account/orders.php";

            $notifStmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, target_url)
                VALUES (?, 'order_update', ?, ?, ?)
            ");
            $notifStmt->execute([$order['user_id'], $title, $message, $targetUrl]);
        }
    }

    header("Location: index.php");
}
