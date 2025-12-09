<?php
require_once __DIR__ . '/../auth/customer_auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Contact Us — iHub Electronics</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    /* Theme Variables matching the Shop page */
    :root {
        --text-color: #1a1a1a;
        --text-muted: #666666;
        --accent-color: #e3000e;
        --input-bg: #ffffff;
        --input-border: #e5e5e5;
    }

    body {
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        color: var(--text-color);
        background-color: #ffffff;
    }

    /* Page Header */
    .page-title {
        text-align: center;
        margin-top: 60px;
        margin-bottom: 60px;
    }
    
    .page-title h1 {
        font-weight: 800;
        font-size: 2.8rem;
        margin-bottom: 0.5rem;
    }
    
    .page-title p {
        color: var(--text-muted);
        font-size: 1.1rem;
    }

    /* Info Column Styling */
    .info-box {
        margin-bottom: 2rem;
    }

    .info-title {
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
        margin-bottom: 1rem;
        color: var(--text-color);
    }

    .info-text {
        color: var(--text-muted);
        line-height: 1.8;
        font-size: 1rem;
        margin-bottom: 0;
    }
    
    .info-text a {
        color: var(--text-muted);
        text-decoration: none;
        border-bottom: 1px solid transparent;
        transition: border-color 0.3s;
    }
    
    .info-text a:hover {
        color: var(--accent-color);
        border-bottom-color: var(--accent-color);
    }

    /* Form Styling */
    .form-control {
        border-radius: 0; /* Square edges */
        border: 1px solid var(--input-border);
        padding: 12px 15px;
        margin-bottom: 20px;
        font-size: 0.95rem;
    }

    .form-control:focus {
        box-shadow: none;
        border-color: var(--text-color);
    }

    .form-label {
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 8px;
        display: block;
    }

    .btn-submit {
        background-color: #000;
        color: #fff;
        border: none;
        padding: 14px 30px;
        text-transform: uppercase;
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 1px;
        border-radius: 0;
        transition: background 0.3s;
        width: 100%;
    }

    .btn-submit:hover {
        background-color: var(--accent-color);
        color: #fff;
    }

    /* Map Section */
    .map-container {
        width: 100%;
        height: 450px;
        margin-top: 80px;
        filter: grayscale(100%); /* Cool tech vibe */
        transition: filter 0.5s;
    }
    
    .map-container:hover {
        filter: grayscale(0%);
    }

    @media (min-width: 768px) {
        .btn-submit { width: auto; }
        .contact-layout { padding: 0 50px; }
    }
  </style>
</head>
<body>

<?php include __DIR__ . "/../components/navbar.php"; ?>

<div class="container">
    <div class="page-title">
        <h1>Contact Us</h1>
        <p>Have a question? We're here to help.</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row contact-layout g-5">
        
        <div class="col-md-5 order-2 order-md-1">
            
            <div class="info-box">
                <h6 class="info-title">Our Store</h6>
                <p class="info-text">
                    123 Electronics Avenue,<br>
                    Tech Park, Bangalore - 560001<br>
                    India
                </p>
                <div class="mt-3">
                    <a href="https://maps.google.com" target="_blank" class="fw-bold small text-decoration-none text-dark">GET DIRECTIONS <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="info-box">
                <h6 class="info-title">Contact Details</h6>
                <p class="info-text">
                    <a href="tel:+919876543210">+91 98765 43210</a><br>
                    <a href="mailto:support@aceno.com">support@ihub-electronics.com</a>
                </p>
            </div>

            <div class="info-box">
                <h6 class="info-title">Opening Hours</h6>
                <p class="info-text">
                    Monday - Friday: 10am - 8pm<br>
                    Saturday: 11am - 7pm<br>
                    Sunday: 11am - 5pm
                </p>
            </div>

        </div>

        <div class="col-md-7 order-1 order-md-2">
            <form action="send_message.php" method="POST">
                
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Your name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="Your email" required>
                    </div>
                </div>

                <div class="mb-0">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" placeholder="Your phone number">
                </div>

                <div class="mb-0">
                    <label class="form-label">Message</label>
                    <textarea name="message" rows="6" class="form-control" placeholder="How can we help?" required></textarea>
                </div>

                <button type="submit" class="btn btn-submit">Send Message</button>
            </form>
        </div>

    </div>
</div>

<section class="map-container">
    <iframe 
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d248849.886539092!2d77.49085516999615!3d12.953959988118836!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bae1670c9b44e6d%3A0xf8dfc3e8517e4fe0!2sBengaluru%2C%20Karnataka!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin" 
        width="100%" 
        height="100%" 
        style="border:0;" 
        allowfullscreen="" 
        loading="lazy" 
        referrerpolicy="no-referrer-when-downgrade">
    </iframe>
</section>

<?php include __DIR__ . "/../components/newsletter.php"; ?>

<?php include __DIR__ . "/../components/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>