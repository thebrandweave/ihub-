<?php
require_once __DIR__ . '/../auth/customer_auth.php';
require_once __DIR__ . '/../config/config.php';

$user_id = $_SESSION['customer_id'];

// Get cart items
$stmt = $pdo->prepare("
   SELECT c.product_id, c.quantity, p.name, p.price,
   (SELECT image_url FROM product_images WHERE product_id=p.product_id LIMIT 1) AS image
   FROM cart c
   JOIN products p ON c.product_id = p.product_id
   WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$cart_items) {
    header("Location: ".$BASE_URL);
    exit;
}

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Get addresses
$addresses = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ?");
$addresses->execute([$user_id]);
$addresses = $addresses->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Checkout - iHub Electronics</title>
<link rel="icon" type="image/png" sizes="32x32" href="<?= $BASE_URL ?>favicon.png">
<link rel="shortcut icon" href="<?= $BASE_URL ?>favicon.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{font-family:Arial;background:#f8fafc;}
.checkout-card{
  background:#fff;
  border-radius:12px;
  padding:25px;
  box-shadow:0 0 12px rgba(0,0,0,0.06);
}
</style>
</head>
<body>

<?php include __DIR__ . "/../components/navbar.php"; ?>

<section class="container py-5">
<div class="row g-4">

<!-- LEFT -->
<div class="col-md-7">
<div class="checkout-card">

<h4 class="fw-bold mb-3">Select Delivery Address</h4>

<form action="place_order.php" method="POST">

<?php if($addresses): ?>
  <?php foreach($addresses as $a): ?>
    <div class="border p-3 mb-3 rounded">
      <label>
        <input type="radio" name="address_id" required value="<?= $a['address_id'] ?>">
        <strong><?= $a['full_name'] ?></strong><br>
        <?= $a['address_line1'] ?>, <?= $a['city'] ?><br>
        <?= $a['state'] ?> - <?= $a['postal_code'] ?><br>
        📞 <?= $a['phone'] ?>
      </label>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <p class="text-danger">No address found. Add one in your profile.</p>
  <a href="<?= $BASE_URL ?>account/addresses.php" class="btn btn-outline-primary">Add Address</a>
<?php endif; ?>

</div>
</div>

<!-- RIGHT -->
<div class="col-md-5">

<div class="checkout-card mb-3">

<h5 class="fw-bold mb-3">Order Summary</h5>

<?php foreach($cart_items as $item): ?>
  <div class="d-flex mb-2">
    <img src="<?= !empty($item['image']) && strpos($item['image'], 'http') !== 0 ? $BASE_URL . 'uploads/products/' . $item['image'] : ($item['image'] ?? 'https://via.placeholder.com/60') ?>" width="60" class="rounded me-2">
    <div>
      <div class="fw-bold"><?= $item['name'] ?></div>
      <small>Qty: <?= $item['quantity'] ?></small><br>
      <small>₹<?= number_format($item['price']) ?></small>
    </div>
  </div>
<?php endforeach; ?>

<hr>

<h5 class="d-flex justify-content-between">
  <span>Total</span>
  <span>₹<?= number_format($total,2) ?></span>
</h5>

<p class="text-success small">Cash on Delivery</p>

<input type="hidden" name="total" value="<?= $total ?>">

<button class="btn btn-primary w-100 mt-3">
  ✅ Place Order
</button>

</form>

</div>

</div>
</div>
</section>

<?php include __DIR__ . "/../components/footer.php"; ?>
</body>
</html>
