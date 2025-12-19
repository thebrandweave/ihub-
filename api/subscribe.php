<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }

    try {
        // Check if exists
        $stmt = $pdo->prepare("SELECT status FROM newsletter_subscribers WHERE email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['status'] === 'subscribed') {
                echo json_encode(['success' => false, 'message' => 'You are already subscribed!']);
            } else {
                $pdo->prepare("UPDATE newsletter_subscribers SET status = 'subscribed' WHERE email = ?")->execute([$email]);
                echo json_encode(['success' => true, 'message' => 'Welcome back! Subscribed successfully.']);
            }
        } else {
            // New subscription
            $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
            $stmt->execute([$email]);
            echo json_encode(['success' => true, 'message' => 'Thanks for subscribing to iHub!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
}