<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Database Connection
require_once __DIR__ . '/../config/config.php'; 
require_once __DIR__ . '/../auth/customer_auth.php';

try {
    /**
     * FETCH SETTINGS FROM site_settings TABLE
     */
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    /**
     * HANDLE FORM SUBMISSION
     */
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_contact'])) {
        $name    = htmlspecialchars(strip_tags(trim($_POST['name'])));
        $email   = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $phone   = htmlspecialchars(strip_tags(trim($_POST['phone'])));
        $message = htmlspecialchars(strip_tags(trim($_POST['message'])));

        if (!empty($name) && !empty($email) && !empty($message)) {
            // Start Transaction to ensure both records are saved
            $pdo->beginTransaction();

            // 1. Insert into contact_messages
            $insertMsg = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)");
            $insertMsg->execute([$name, $email, $phone, $message]);

            // 2. Insert into notifications for all Admins
            $adminStmt = $pdo->query("SELECT user_id FROM users WHERE role = 'admin'");
            $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);

            if ($admins) {
                $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, target_url) VALUES (?, 'system_alert', ?, ?, ?)");
                
                $notifTitle = "New Inquiry: " . $name;
                $notifBody  = "You have received a new contact form submission. Email: " . $email;
                $targetUrl  = "../messages/index.php"; // Points to admin/messages/index.php

                foreach ($admins as $admin) {
                    $notifStmt->execute([$admin['user_id'], $notifTitle, $notifBody, $targetUrl]);
                }
            }

            $pdo->commit();
            $_SESSION['contact_success'] = "Thank you! Your message has been sent and our team has been notified.";
        } else {
            $_SESSION['contact_error'] = "Please fill in all required fields.";
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Contact Page Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Contact Us — iHub Electronics</title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $BASE_URL ?>favicon.png">
  <link rel="shortcut icon" href="<?= $BASE_URL ?>favicon.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    :root { --text-color: #1a1a1a; --text-muted: #666666; --accent-color: #e3000e; --input-border: #e5e5e5; }
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: var(--text-color); }
    .page-title { text-align: center; margin: 60px 0 40px; }
    .page-title h1 { font-weight: 800; font-size: 2.8rem; }
    .info-box { margin-bottom: 2rem; }
    .info-title { font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 1rem; }
    .info-text { color: var(--text-muted); line-height: 1.8; }
    .form-control { border-radius: 0; border: 1px solid var(--input-border); padding: 12px 15px; margin-bottom: 20px; }
    .form-control:focus { box-shadow: none; border-color: var(--text-color); }
    .btn-submit { background-color: #000; color: #fff; border: none; padding: 14px 30px; text-transform: uppercase; font-weight: 700; border-radius: 0; transition: 0.3s; width: 100%; }
    .btn-submit:hover { background-color: var(--accent-color); }
    .map-container { width: 100%; height: 450px; margin-top: 80px; filter: grayscale(100%); transition: 0.5s; background: #eee; }
    .map-container:hover { filter: grayscale(0%); }
    @media (min-width: 768px) { .btn-submit { width: auto; } .contact-layout { padding: 0 50px; } }
  </style>
</head>
<body>

<?php include __DIR__ . "/../components/navbar.php"; ?>

<div class="container">
    <div class="page-title">
        <h1>Contact Us</h1>
        <p>Have a question? We're here to help.</p>
    </div>

    <?php if(isset($_SESSION['contact_success'])): ?>
        <div class="alert alert-success border-0 rounded-0 shadow-sm mb-5">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $_SESSION['contact_success']; unset($_SESSION['contact_success']); ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['contact_error'])): ?>
        <div class="alert alert-danger border-0 rounded-0 shadow-sm mb-5">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $_SESSION['contact_error']; unset($_SESSION['contact_error']); ?>
        </div>
    <?php endif; ?>
</div>

<div class="container mb-5">
    <div class="row contact-layout g-5">
        <div class="col-md-5">
            <div class="info-box">
                <h6 class="info-title">Our Store</h6>
                <p class="info-text"><?php echo nl2br($settings['contact_address'] ?? ''); ?></p>
                <div class="mt-2">
                    <a href="<?php echo $settings['google_maps_link'] ?? '#'; ?>" target="_blank" class="fw-bold small text-dark text-decoration-none">GET DIRECTIONS <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
            <div class="info-box">
                <h6 class="info-title">Contact Details</h6>
                <p class="info-text">
                    <a href="tel:<?php echo $settings['contact_phone'] ?? ''; ?>" class="text-decoration-none text-muted"><?php echo $settings['contact_phone'] ?? ''; ?></a><br>
                    <a href="mailto:<?php echo $settings['contact_email'] ?? ''; ?>" class="text-decoration-none text-muted"><?php echo $settings['contact_email'] ?? ''; ?></a>
                </p>
            </div>
            <div class="info-box">
                <h6 class="info-title">Opening Hours</h6>
                <p class="info-text">
                    <?php echo $settings['hours_weekday'] ?? ''; ?><br>
                    <?php echo $settings['hours_saturday'] ?? ''; ?><br>
                    <?php echo $settings['hours_sunday'] ?? ''; ?>
                </p>
            </div>
        </div>

        <div class="col-md-7">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Your Full Name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="Your Email Address" required>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-bold small">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" placeholder="Your Phone Number (Optional)">
                </div>
                <div class="mb-0">
                    <label class="form-label fw-bold small">Message</label>
                    <textarea name="message" rows="5" class="form-control" placeholder="How can we help you?" required></textarea>
                </div>
                <button type="submit" name="submit_contact" class="btn btn-submit">Send Message</button>
            </form>
        </div>
    </div>
</div>

<section class="map-container">
    <?php if(!empty($settings['google_maps_embed'])): ?>
        <iframe src="<?php echo $settings['google_maps_embed']; ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    <?php else: ?>
        <div class="d-flex align-items-center justify-content-center h-100 text-muted">
            <p><i class="bi bi-map me-2"></i>Map not available. Please configure 'google_maps_embed' in site_settings.</p>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . "/../components/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 