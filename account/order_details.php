<?php
require_once __DIR__ . '/../auth/customer_auth.php';
require_once __DIR__ . '/../config/config.php';

// Ensure customer is logged in
if (empty($customer_logged_in) || empty($customer_id)) {
    header("Location: ../auth/customer_login.php");
    exit;
}

$user_id = $customer_id;
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    header("Location: orders.php?error=invalid_order");
    exit;
}

// Fetch order details - verify it belongs to the user
$orderStmt = $pdo->prepare("
    SELECT 
        o.*,
        a.full_name AS addr_full_name,
        a.phone AS addr_phone,
        a.address_line1,
        a.address_line2,
        a.city,
        a.state,
        a.postal_code,
        a.country
    FROM orders o
    LEFT JOIN addresses a ON o.address_id = a.address_id
    WHERE o.order_id = ? AND o.user_id = ?
");
$orderStmt->execute([$order_id, $user_id]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: orders.php?error=order_not_found");
    exit;
}

// Fetch order items
$itemsStmt = $pdo->prepare("
    SELECT 
        oi.order_item_id,
        oi.product_id,
        oi.quantity,
        oi.price_at_time,
        p.name,
        p.thumbnail,
        (
            SELECT image_url 
            FROM product_images 
            WHERE product_id = p.product_id AND is_primary = 1 
            LIMIT 1
        ) AS primary_image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
    ORDER BY oi.order_item_id ASC
");
$itemsStmt->execute([$order_id]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to get product image
function getOrderItemImage(array $item): string {
    global $BASE_URL;
    if (!empty($item['primary_image'])) {
        return $BASE_URL . 'uploads/products/' . ltrim($item['primary_image'], '/');
    }
    if (!empty($item['thumbnail'])) {
        return $BASE_URL . 'uploads/products/' . ltrim($item['thumbnail'], '/');
    }
    return 'https://via.placeholder.com/150';
}

// Check for reviews
$reviewedProducts = [];
if ($order['status'] === 'delivered' && !empty($items)) {
    $productIds = array_column($items, 'product_id');
    $reviewStmt = $pdo->prepare("
        SELECT product_id 
        FROM reviews 
        WHERE user_id = ? 
        AND product_id IN (" . implode(',', array_fill(0, count($productIds), '?')) . ")
    ");
    $reviewStmt->execute(array_merge([$user_id], $productIds));
    $reviewedProducts = $reviewStmt->fetchAll(PDO::FETCH_COLUMN);
}

// Status badge helper
function getStatusBadge($status) {
    $badges = [
        'pending' => 'bg-warning text-dark',
        'processing' => 'bg-info text-white',
        'shipped' => 'bg-primary text-white',
        'delivered' => 'bg-success text-white',
        'cancelled' => 'bg-danger text-white'
    ];
    return $badges[$status] ?? 'bg-secondary text-white';
}

// Payment status badge helper
function getPaymentStatusBadge($status) {
    $badges = [
        'unpaid' => 'bg-warning text-dark',
        'paid' => 'bg-success text-white',
        'refunded' => 'bg-info text-white'
    ];
    return $badges[$status] ?? 'bg-secondary text-white';
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Order Details — iHub Electronics</title>
<link rel="icon" type="image/png" sizes="32x32" href="<?= $BASE_URL ?>favicon.png">
<link rel="shortcut icon" href="<?= $BASE_URL ?>favicon.png">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body{font-family:Arial,sans-serif;color:#0f172a;background:#f8f9fa;}
  .profile-hero{
    background:#fff;
    padding:20px 0;
    text-align:left;
    border-bottom:1px solid #e5e7eb;
  }
  .profile-hero h1{
    font-size:1.9rem;
    margin-bottom:0.25rem;
  }
  .profile-card{
    background:#fff;
    padding:20px 24px;
    border-radius:4px;
    border:1px solid #d1d5db;
    margin-bottom:1rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  }
  .account-sidebar{
    background:#fff;
    border-radius:4px;
    border:1px solid #d1d5db;
    padding:18px 0;
  }
  .account-sidebar-title{
    font-size:1rem;
    font-weight:700;
    padding:0 18px 8px;
    border-bottom:1px solid #e5e7eb;
  }
  .account-nav{
    list-style:none;
    padding:8px 0 0;
    margin:0;
  }
  .account-nav li a{
    display:flex;
    align-items:center;
    padding:8px 18px;
    font-size:0.9rem;
    color:#111827;
    text-decoration:none;
  }
  .account-nav li a:hover{
    background:#f3f4f6;
  }
  .account-nav li a.active{
    font-weight:600;
    background:#e5f1ff;
    border-left:3px solid #0d6efd;
  }
  .account-nav i{
    margin-right:8px;
    font-size:1rem;
  }
  
  /* Amazon-style shipping progress */
  .shipping-progress {
    position: relative;
    padding: 20px 0;
  }
  .progress-steps {
    display: flex;
    justify-content: space-between;
    position: relative;
    margin-bottom: 20px;
  }
  .progress-steps::before {
    content: '';
    position: absolute;
    top: 15px;
    left: 0;
    right: 0;
    height: 2px;
    background: #d1d5db;
    z-index: 1;
  }
  .progress-bar {
    position: absolute;
    top: 15px;
    left: 0;
    height: 2px;
    background: #ff9900;
    z-index: 2;
    transition: width 0.3s ease;
  }
  .progress-step {
    position: relative;
    z-index: 3;
    flex: 1;
    text-align: center;
  }
  .step-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #d1d5db;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    font-size: 14px;
  }
  .step-icon.completed {
    background: #ff9900;
    border-color: #ff9900;
    color: #fff;
  }
  .step-icon.active {
    background: #fff;
    border-color: #ff9900;
    color: #ff9900;
    box-shadow: 0 0 0 3px rgba(255, 153, 0, 0.2);
  }
  .step-label {
    font-size: 12px;
    color: #6b7280;
    font-weight: 500;
  }
  .step-label.completed,
  .step-label.active {
    color: #ff9900;
    font-weight: 600;
  }
  .step-date {
    font-size: 11px;
    color: #9ca3af;
    margin-top: 2px;
  }
</style>
</head>
<body>

<?php include __DIR__ . "/../components/navbar.php"; ?>

<section class="profile-hero">
  <div class="container">
    <h1 class="fw-bold">Order Details</h1>
    <p class="text-secondary mb-0">View complete information about your order</p>
  </div>
</section>

<section class="py-4">
  <div class="container">
    <div class="row g-4">
      <!-- SIDEBAR -->
      <aside class="col-lg-3">
        <div class="account-sidebar">
          <div class="account-sidebar-title">Your shortcuts</div>
          <ul class="account-nav">
            <li><a href="index.php"><i class="bi bi-person-circle"></i>Account overview</a></li>
            <li><a href="orders.php" class="active"><i class="bi bi-box-seam"></i>Your orders</a></li>
            <li><a href="addresses.php"><i class="bi bi-geo-alt"></i>Your addresses</a></li>
            <li><a href="change_password.php"><i class="bi bi-shield-lock"></i>Login &amp; security</a></li>
          </ul>
        </div>
      </aside>

      <!-- MAIN CONTENT -->
      <div class="col-lg-9">
        <!-- Back Button -->
        <div class="mb-3">
          <a href="orders.php" class="text-decoration-none text-dark">
            <i class="bi bi-arrow-left me-1"></i> Back to Orders
          </a>
        </div>

        <!-- Order Header -->
        <div class="profile-card mb-3">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h4 class="mb-1 fw-bold">Order <?= !empty($order['order_number']) ? htmlspecialchars($order['order_number']) : '#' . $order['order_id'] ?></h4>
              <p class="text-muted small mb-0">Placed on <?= date("F d, Y", strtotime($order['order_date'])) ?></p>
            </div>
            <div class="text-end">
              <span class="badge <?= getStatusBadge($order['status']) ?> px-3 py-2 fw-normal">
                <?= ucfirst($order['status']) ?>
              </span>
            </div>
          </div>
        </div>

        <!-- Shipping Progress Tracker -->
        <div class="profile-card mb-3">
          <h6 class="fw-bold mb-3">Track Your Package</h6>
          <div class="shipping-progress">
            <?php
            // Define steps and their status
            $steps = [
                'pending' => ['order_placed' => true, 'processing' => false, 'shipped' => false, 'delivered' => false],
                'processing' => ['order_placed' => true, 'processing' => true, 'shipped' => false, 'delivered' => false],
                'shipped' => ['order_placed' => true, 'processing' => true, 'shipped' => true, 'delivered' => false],
                'delivered' => ['order_placed' => true, 'processing' => true, 'shipped' => true, 'delivered' => true],
                'cancelled' => ['order_placed' => true, 'processing' => false, 'shipped' => false, 'delivered' => false]
            ];
            $currentSteps = $steps[$order['status']] ?? $steps['pending'];
            $currentStepIndex = 0;
            if ($order['status'] === 'processing') $currentStepIndex = 1;
            elseif ($order['status'] === 'shipped') $currentStepIndex = 2;
            elseif ($order['status'] === 'delivered') $currentStepIndex = 3;
            elseif ($order['status'] === 'cancelled') $currentStepIndex = 0;
            
            $stepConfig = [
                ['icon' => 'bi-check-circle', 'label' => 'Order Placed', 'date' => date("M d, Y", strtotime($order['order_date']))],
                ['icon' => 'bi-box-seam', 'label' => 'Processing', 'date' => $order['status'] === 'processing' ? date("M d, Y") : ''],
                ['icon' => 'bi-truck', 'label' => 'Shipped', 'date' => $order['status'] === 'shipped' ? date("M d, Y") : ''],
                ['icon' => 'bi-check-circle-fill', 'label' => 'Delivered', 'date' => !empty($order['delivered_at']) ? date("M d, Y", strtotime($order['delivered_at'])) : '']
            ];
            $progressWidth = ($currentStepIndex / 3) * 100;
            ?>
            <div class="progress-steps">
              <div class="progress-bar" style="width: <?= $progressWidth ?>%"></div>
              <?php foreach ($stepConfig as $index => $step): ?>
                <?php
                  $isCompleted = $index <= $currentStepIndex && $order['status'] !== 'cancelled';
                  $isActive = $index === $currentStepIndex && $order['status'] !== 'cancelled';
                ?>
                <div class="progress-step">
                  <div class="step-icon <?= $isCompleted ? 'completed' : ($isActive ? 'active' : '') ?>">
                    <i class="bi <?= $step['icon'] ?>"></i>
                  </div>
                  <div class="step-label <?= $isCompleted || $isActive ? 'active' : '' ?>">
                    <?= $step['label'] ?>
                  </div>
                  <?php if (!empty($step['date'])): ?>
                    <div class="step-date"><?= $step['date'] ?></div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
            <?php if ($order['status'] === 'cancelled'): ?>
              <div class="alert alert-danger mb-0 py-2">
                <i class="bi bi-x-circle me-2"></i>This order has been cancelled.
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Order Summary & Shipping Address -->
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="profile-card h-100">
              <h6 class="fw-bold mb-3">Shipping Address</h6>
              <div class="small">
            <?php if (!empty($order['shipping_address'])): ?>
              <p class="mb-0"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
            <?php else: ?>
              <?php if (!empty($order['addr_full_name'])): ?>
                <p class="fw-semibold mb-1"><?= htmlspecialchars($order['addr_full_name']) ?></p>
              <?php endif; ?>
              <?php if (!empty($order['address_line1'])): ?>
                <p class="mb-1"><?= htmlspecialchars($order['address_line1']) ?></p>
              <?php endif; ?>
              <?php if (!empty($order['address_line2'])): ?>
                <p class="mb-1"><?= htmlspecialchars($order['address_line2']) ?></p>
              <?php endif; ?>
              <p class="mb-1">
                <?php 
                  $addrParts = array_filter([
                    $order['city'],
                    $order['state'],
                    $order['postal_code']
                  ]);
                  echo htmlspecialchars(implode(', ', $addrParts));
                ?>
              </p>
              <?php if (!empty($order['country'])): ?>
                <p class="mb-1"><?= htmlspecialchars($order['country']) ?></p>
              <?php endif; ?>
              <?php if (!empty($order['addr_phone'])): ?>
                <p class="mb-0"><strong>Phone:</strong> <?= htmlspecialchars($order['addr_phone']) ?></p>
              <?php endif; ?>
              <?php endif; ?>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="profile-card h-100">
              <h6 class="fw-bold mb-3">Order Summary</h6>
              <div class="mb-3">
                <div class="d-flex justify-content-between small mb-2">
                  <span class="text-muted">Items (<?= count($items) ?>):</span>
                  <span>₹<?= number_format($order['total_amount'], 2) ?></span>
                </div>
                <div class="d-flex justify-content-between small mb-2">
                  <span class="text-muted">Shipping & Handling:</span>
                  <span class="text-success">FREE</span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between fw-bold">
                  <span>Order Total:</span>
                  <span class="text-danger">₹<?= number_format($order['total_amount'], 2) ?></span>
                </div>
              </div>
              <div class="border-top pt-3">
                <div class="small mb-1"><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></div>
                <div class="small">
                  <strong>Payment Status:</strong> 
                  <span class="badge <?= getPaymentStatusBadge($order['payment_status']) ?> ms-1">
                    <?= ucfirst($order['payment_status']) ?>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Order Items Card -->
        <div class="profile-card">
          <h6 class="fw-bold mb-3">Ordered Items</h6>
          <div class="d-flex flex-column gap-3">
            <?php foreach ($items as $item): ?>
              <div class="d-flex gap-3 pb-3 border-bottom">
                <div class="flex-shrink-0">
                  <img src="<?= htmlspecialchars(getOrderItemImage($item)) ?>" 
                       alt="<?= htmlspecialchars($item['name']) ?>" 
                       class="rounded border"
                       style="width: 100px; height: 100px; object-fit: cover;">
                </div>
                <div class="flex-grow-1">
                  <h6 class="mb-1">
                    <a href="<?= $BASE_URL ?>shop/product_details.php?id=<?= $item['product_id'] ?>" 
                       class="text-decoration-none text-dark fw-semibold">
                      <?= htmlspecialchars($item['name']) ?>
                    </a>
                  </h6>
                  <div class="small text-muted mb-2">Quantity: <?= $item['quantity'] ?></div>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Price: ₹<?= number_format($item['price_at_time'], 2) ?></span>
                    <span class="fw-bold">₹<?= number_format($item['price_at_time'] * $item['quantity'], 2) ?></span>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Actions Card -->
        <?php if ($order['status'] === 'delivered' && !empty($items)): ?>
          <div class="profile-card">
            <h6 class="fw-bold mb-3">Rate & Review Your Purchase</h6>
            <?php 
              $unreviewedProducts = array_filter($items, function($item) use ($reviewedProducts) {
                return !in_array($item['product_id'], $reviewedProducts);
              });
            ?>
            <?php if (!empty($unreviewedProducts)): ?>
              <a href="review.php?order_id=<?= $order['order_id'] ?>" class="btn btn-warning btn-sm me-2">
                <i class="bi bi-star me-1"></i> Write a Product Review
              </a>
            <?php endif; ?>
            <?php if (!empty($reviewedProducts)): ?>
              <a href="review.php?order_id=<?= $order['order_id'] ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil me-1"></i> Edit Your Review
              </a>
            <?php endif; ?>
            <?php if (!empty($order['delivered_at'])): ?>
              <div class="mt-3 pt-3 border-top">
                <p class="small mb-0 text-success">
                  <i class="bi bi-check-circle me-1"></i>
                  Delivered on <?= date("F d, Y", strtotime($order['delivered_at'])) ?>
                </p>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . "/../components/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

