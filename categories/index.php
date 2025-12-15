<?php
require_once __DIR__ . '/../auth/customer_auth.php';
require_once __DIR__ . '/../config/config.php';

// HARD STOP if BASE_URL is missing
if (!isset($BASE_URL)) {
  die("BASE_URL is not defined. Check config.php");
}

// Fetch all categories
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <base href="<?= $BASE_URL ?>">  <!-- ✅ THIS LINE FIXES EVERYTHING -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Categories — iHub Electronics</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    body{font-family:Arial, sans-serif; color:#0f172a;}
    .cat-hero{
      background:#f1f5f9; padding:70px 0; text-align:center;
    }
    .cat-card{
      padding:20px;
      text-align:center;
      transition:0.2s;
    }
    .cat-card:hover{
      transform:translateY(-5px);
    }
    .cat-card img{
      width:100px; height:100px; object-fit:cover; border-radius:50%;
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/../components/navbar.php'; ?>

<section class="cat-hero">
  <div class="container">
    <h1 class="fw-bold display-5">Shop by Category</h1>
    <p class="text-secondary mt-2">Browse all our product categories</p>
  </div>
</section>

<section class="py-5 container">
  <div class="row g-4">

    <?php foreach ($categories as $cat): ?>
      <div class="col-6 col-md-3">
        <a href="<?= $BASE_URL ?>shop/?category=<?= $cat['category_id'] ?>" class="text-decoration-none text-dark">
          <div class="cat-card">

            <?php if (!empty($cat['image_url'])): ?>
              <img src="<?= $BASE_URL ?>uploads/categories/<?= htmlspecialchars($cat['image_url']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>">
            <?php else: ?>
              <img src="https://via.placeholder.com/150" alt="<?= htmlspecialchars($cat['name']) ?>">
            <?php endif; ?>

            <h6 class="fw-bold mt-3"><?= htmlspecialchars($cat['name']) ?></h6>
            <!-- <p class="text-secondary small"><?= htmlspecialchars($cat['description'] ?? '') ?></p> -->

          </div>
        </a>
      </div>
    <?php endforeach; ?>

    <?php if (empty($categories)): ?>
      <div class="col-12 text-center text-secondary py-5">
        <h5>No categories found.</h5>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php include __DIR__ . '/../components/newsletter.php'; ?>
<?php include __DIR__ . '/../components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
