<?php
require_once __DIR__ . '/../config/config.php';
?>

<!-- Footer -->
<footer class="container-fluid bg-white pt-5 pb-3">
  <div class="container">
    <div class="row g-5">

      <!-- Contact -->
      <div class="col-md-3">
        <h5 class="fw-bold mb-3">Contact</h5>
        <p class="mb-2">
          <i class="bi bi-geo-alt me-2"></i>
          16122 Collins Street, Melbourne
        </p>
        <p class="mb-2">
          <i class="bi bi-telephone me-2"></i>
          (603) 555-0123
        </p>
        <p class="mb-3">
          <i class="bi bi-envelope me-2"></i>
          support@ucham.com
        </p>

        <div class="d-flex gap-2 mt-3">
          <a href="#" class="btn btn-outline-secondary rounded-circle p-2"><i class="bi bi-facebook"></i></a>
          <a href="#" class="btn btn-outline-secondary rounded-circle p-2"><i class="bi bi-twitter"></i></a>
          <a href="#" class="btn btn-outline-secondary rounded-circle p-2"><i class="bi bi-instagram"></i></a>
          <a href="#" class="btn btn-outline-secondary rounded-circle p-2"><i class="bi bi-youtube"></i></a>
        </div>
      </div>

      <!-- Shop -->
      <div class="col-md-2">
        <h5 class="fw-bold mb-3">Shop</h5>
        <ul class="list-unstyled text-secondary">
          <li class="mb-2"><a href="<?= $BASE_URL ?>shop/" class="text-decoration-none text-secondary">All Products</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>categories/" class="text-decoration-none text-secondary">Categories</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>shop/?filter=popular" class="text-decoration-none text-secondary">Popular</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>" class="text-decoration-none text-secondary">Home</a></li>
        </ul>
      </div>

      <!-- Info -->
      <div class="col-md-2">
        <h5 class="fw-bold mb-3">Information</h5>
        <ul class="list-unstyled text-secondary">
          <li class="mb-2"><a href="<?= $BASE_URL ?>auth/signup.php" class="text-decoration-none text-secondary">Register</a></li>
          <li class="mb-2"><a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" class="text-decoration-none text-secondary">Login</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>cart/" class="text-decoration-none text-secondary">My Cart</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>account/orders.php" class="text-decoration-none text-secondary">My Orders</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>wishlist/" class="text-decoration-none text-secondary">Wishlist</a></li>
        </ul>
      </div>

      <!-- About -->
      <div class="col-md-2">
        <h5 class="fw-bold mb-3">About</h5>
        <ul class="list-unstyled text-secondary">
          <li class="mb-2"><a href="<?= $BASE_URL ?>about/" class="text-decoration-none text-secondary">About Us</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>contact/" class="text-decoration-none text-secondary">Contact</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>#" class="text-decoration-none text-secondary">FAQs</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>#" class="text-decoration-none text-secondary">Privacy Policy</a></li>
        </ul>
      </div>

      <!-- Services -->
      <div class="col-md-3">
        <h5 class="fw-bold mb-3">Services</h5>
        <ul class="list-unstyled text-secondary">
          <li class="mb-2"><a href="<?= $BASE_URL ?>account/orders.php" class="text-decoration-none text-secondary">Order History</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>contact/" class="text-decoration-none text-secondary">Customer Support</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>terms/" class="text-decoration-none text-secondary">Terms & Conditions</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>returns/" class="text-decoration-none text-secondary">Returns</a></li>
          <li class="mb-2"><a href="<?= $BASE_URL ?>shipping/" class="text-decoration-none text-secondary">Shipping</a></li>
        </ul>
      </div>

    </div>

    <!-- Bottom Bar -->
    <hr class="my-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3">
      <p class="mb-0 text-secondary">
        Copyright © <span class="fw-bold text-primary">iHub Electronics</span> — All rights reserved.
      </p>

      <div class="d-flex gap-3 align-items-center">
        <img src="" height="26">
      </div>
    </div>

    <!-- Scroll to top -->
    <a href="<?= $BASE_URL ?>" class="btn btn-primary rounded-circle position-fixed"
       style="right:20px; bottom:20px; width:48px; height:48px; display:flex;align-items:center;justify-content:center;">
      <i class="bi bi-arrow-up text-white"></i>
    </a>
  </div>
</footer>
