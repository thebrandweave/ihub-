<?php
require_once __DIR__ . '/../auth/customer_auth.php';
require_once __DIR__ . '/../config/config.php';

$user_id = $_SESSION['customer_id'];

$stmt = $pdo->prepare("
  SELECT 
    o.*,
    a.full_name   AS addr_full_name,
    a.phone       AS addr_phone,
    a.address_line1,
    a.address_line2,
    a.city,
    a.state,
    a.postal_code,
    a.country
  FROM orders o
  LEFT JOIN addresses a ON o.address_id = a.address_id
  WHERE o.user_id = ?
  ORDER BY o.order_date DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper to get a product image URL for an order item
if (!function_exists('getOrderItemImage')) {
    function getOrderItemImage(array $item): string {
        global $BASE_URL;
        if (!empty($item['primary_image'])) {
            return $BASE_URL . 'uploads/products/' . ltrim($item['primary_image'], '/');
        }
        if (!empty($item['thumbnail'])) {
            return $BASE_URL . 'uploads/products/' . ltrim($item['thumbnail'], '/');
        }
        return 'https://via.placeholder.com/80';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>My Orders — iHub Electronics</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
  body{font-family:Arial,sans-serif;color:#0f172a;background:#ffffff;}
  .profile-hero{
    background:#f1f5f9;
    padding:56px 0;
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
    border-radius:8px;
    border:1px solid #e5e7eb;
  }
  .account-sidebar{
    background:#fff;
    border-radius:8px;
    border:1px solid #e5e7eb;
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
</style>

</head>
<body>

<?php include __DIR__ . "/../components/navbar.php"; ?>

<section class="profile-hero">
  <div class="container">
    <h1 class="fw-bold">Your Orders</h1>
    <p class="text-secondary mb-0">Track, return, or reorder items you’ve purchased.</p>
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
            <li><a href="../wishlist/wishlist_action.php" onclick="event.preventDefault(); document.querySelector('[data-bs-target=#wishlistDrawer]')?.click();"><i class="bi bi-heart"></i>Your wishlist</a></li>
            <li><a href="change_password.php"><i class="bi bi-shield-lock"></i>Login &amp; security</a></li>
          </ul>
        </div>
      </aside>

      <!-- MAIN CONTENT -->
      <div class="col-lg-9">

        <?php if(empty($orders)): ?>
          <div class="profile-card text-center text-muted">
            <i class="bi bi-bag-x fs-1 mb-2"></i>
            <p class="mb-1">You haven’t placed any orders yet.</p>
            <p class="small mb-3">Browse our products and your orders will appear here.</p>
            <a href="../shop/" class="btn btn-primary btn-sm px-4">Start shopping</a>
          </div>
        <?php else: ?>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Order history</h5>
            <span class="text-muted small">Showing <?= count($orders) ?> orders</span>
          </div>

          <div class="d-flex flex-column gap-3">
            <?php foreach($orders as $order): ?>
              <?php
                // Fetch items for this order
                $itemsStmt = $pdo->prepare("
                  SELECT 
                    oi.quantity,
                    oi.price_at_time,
                    p.product_id,
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
                ");
                $itemsStmt->execute([$order['order_id']]);
                $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

                // Check which products have been reviewed by this user
                $reviewedProducts = [];
                if ($order['status'] === 'delivered' && !empty($items)) {
                    $productIds = array_column($items, 'product_id');
                    $reviewStmt = $pdo->prepare("
                        SELECT product_id 
                        FROM reviews 
                        WHERE user_id = ? AND product_id IN (" . implode(',', array_fill(0, count($productIds), '?')) . ")
                    ");
                    $reviewStmt->execute(array_merge([$user_id], $productIds));
                    $reviewedProducts = $reviewStmt->fetchAll(PDO::FETCH_COLUMN);
                }

                $itemCount = count($items);
                $firstItem = $items[0] ?? null;

                // Build a short shipping address summary
                $addressParts = [];
                if (!empty($order['addr_full_name'])) {
                    $addressParts[] = $order['addr_full_name'];
                }
                if (!empty($order['city'])) {
                    $addressParts[] = $order['city'];
                }
                if (!empty($order['state'])) {
                    $addressParts[] = $order['state'];
                }
                if (!empty($order['postal_code'])) {
                    $addressParts[] = $order['postal_code'];
                }
                $shippingSummary = !empty($order['shipping_address'])
                  ? $order['shipping_address']
                  : implode(', ', array_filter($addressParts));
              ?>
              <div class="profile-card">
                <div class="d-flex justify-content-between flex-wrap mb-2">
                  <div>
                    <div class="small text-muted">Order</div>
                    <strong>
                      <?= !empty($order['order_number']) ? htmlspecialchars($order['order_number']) : 'Order #' . $order['order_id'] ?>
                    </strong>
                  </div>
                  <div>
                    <div class="small text-muted">Placed on</div>
                    <span><?= date("d M Y", strtotime($order['order_date'])) ?></span>
                  </div>
                  <div class="text-end">
                    <div class="small text-muted">Total</div>
                    <span class="fw-bold">₹<?= $order['total_amount'] ?></span>
                  </div>
                </div>

                <?php if($firstItem): ?>
                  <div class="d-flex gap-3 align-items-center mb-2">
                    <div class="flex-shrink-0">
                      <img 
                        src="<?= htmlspecialchars(getOrderItemImage($firstItem)) ?>" 
                        alt="<?= htmlspecialchars($firstItem['name']) ?>" 
                        width="64"
                        height="64"
                        class="rounded border object-fit-cover">
                    </div>
                    <div class="flex-grow-1">
                      <div class="fw-semibold small text-truncate">
                        <?= htmlspecialchars($firstItem['name']) ?>
                      </div>
                      <div class="small text-muted">
                        Qty: <?= (int)$firstItem['quantity'] ?> • ₹<?= number_format($firstItem['price_at_time'], 2) ?>
                      </div>
                      <?php if($itemCount > 1): ?>
                        <div class="small text-muted">
                          + <?= $itemCount - 1 ?> more item<?= $itemCount > 2 ? 's' : '' ?>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endif; ?>

                <?php if(!empty($shippingSummary)): ?>
                  <div class="small text-muted mb-2">
                    Deliver to: <?= htmlspecialchars($shippingSummary) ?>
                  </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center flex-wrap mt-2">
                  <div>
                    <span class="small text-muted">Status:</span>
                    <span class="<?= $order['status'] == 'delivered' ? 'text-success' : 'text-warning' ?> fw-semibold">
                      <?= ucfirst($order['status']) ?>
                    </span>
                    <span class="ms-2 small text-muted">• Payment: <?= $order['payment_method'] ?></span>
                  </div>
                  <div class="mt-2 mt-md-0 d-flex gap-2">
                    <a href="order_details.php?id=<?= $order['order_id'] ?>"
                       class="btn btn-outline-primary btn-sm">
                       View order details
                    </a>
                    <?php if ($order['status'] === 'delivered' && !empty($items)): ?>
                      <?php 
                        $unreviewedProducts = array_filter($items, function($item) use ($reviewedProducts) {
                          return !in_array($item['product_id'], $reviewedProducts);
                        });
                      ?>
                      <?php if (!empty($unreviewedProducts)): ?>
                        <a href="review.php?order_id=<?= $order['order_id'] ?>"
                           class="btn btn-success btn-sm">
                           <i class="bi bi-star me-1"></i> Write Review
                        </a>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
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
