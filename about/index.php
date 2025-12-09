<?php
require_once __DIR__ . '/../auth/customer_auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>About Us — iHub Electronics</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    /* Theme Variables */
    :root {
        --text-color: #1a1a1a;
        --text-muted: #555;
        --accent-color: #e3000e;
        --bg-light: #f9f9f9;
    }

    body {
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        color: var(--text-color);
        background-color: #ffffff;
        line-height: 1.6;
    }

    /* Typography */
    h1, h2, h3, h4 {
        font-weight: 800;
        letter-spacing: -0.5px;
        color: var(--text-color);
    }

    p {
        color: var(--text-muted);
        font-size: 1.05rem;
        margin-bottom: 1.5rem;
    }

    /* Page Header */
    .page-header {
        padding: 4rem 0 2rem;
        text-align: center;
        max-width: 700px;
        margin: 0 auto;
    }

    .page-header h1 {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    /* Hero Image */
    .hero-banner {
        width: 100%;
        height: 450px;
        object-fit: cover;
        margin-bottom: 5rem;
        filter: brightness(0.95);
    }

    /* Content Sections */
    .content-section {
        margin-bottom: 5rem;
    }

    .text-block {
        padding: 2rem 0;
    }

    /* Value Grid (Icons) */
    .value-item {
        text-align: center;
        padding: 1rem;
    }
    
    .value-icon {
        font-size: 2rem;
        margin-bottom: 1rem;
        color: var(--text-color);
    }

    .value-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .value-desc {
        font-size: 0.95rem;
        color: #777;
    }

    /* Quote Section */
    .quote-section {
        background-color: var(--bg-light);
        padding: 5rem 0;
        text-align: center;
        margin-bottom: 5rem;
    }

    .quote-text {
        font-size: 1.8rem;
        font-weight: 300;
        font-style: italic;
        max-width: 800px;
        margin: 0 auto;
        color: var(--text-color);
    }

    @media (max-width: 768px) {
        .hero-banner { height: 300px; }
        .page-header h1 { font-size: 2.2rem; }
    }
  </style>
</head>
<body>

<?php include __DIR__ . "/../components/navbar.php"; ?>

<div class="container">
    
    <div class="page-header">
        <h1>About Us</h1>
        <p>
            We are iHub Electronics. We bridge the gap between cutting-edge technology and everyday adventure.
        </p>
    </div>

    <img src="https://images.unsplash.com/photo-1550009158-9ebf69173e03?q=80&w=2101&auto=format&fit=crop" 
         alt="Electronics Workshop" class="hero-banner">

    <section class="content-section">
        <div class="row align-items-center g-5">
            <div class="col-md-6 order-md-2">
                <img src="https://images.unsplash.com/photo-1519389950473-47ba0277781c?q=80&w=2070&auto=format&fit=crop" 
                     class="w-100" alt="Our Team working">
            </div>
            <div class="col-md-6 order-md-1">
                <div class="text-block pe-md-4">
                    <h3>Our Story</h3>
                    <p>
                        Founded in 2023, iHub Electronics began with a simple mission: to make high-quality tech gear accessible, reliable, and stylish. What started as a small garage project has grown into a community-driven platform for tech enthusiasts.
                    </p>
                    <p>
                        We believe that technology shouldn't just be functional—it should be an experience. Whether you are setting up a smart home, gearing up for a camping trip, or upgrading your workspace, our curated selection is designed to elevate your lifestyle.
                    </p>
                    <p>
                        Today, we serve thousands of customers across the country, but our core values remain the same: Integrity, Innovation, and Customer Obsession.
                    </p>
                </div>
            </div>
        </div>
    </section>

</div>

<section class="quote-section">
    <div class="container">
        <blockquote class="quote-text">
            "We don't just sell electronics. We provide the tools that power your passions, your work, and your life."
        </blockquote>
        <div class="mt-4 fw-bold text-uppercase small text-muted">— The Founders</div>
    </div>
</section>

<div class="container">
    
    <section class="content-section">
        <div class="row g-4 justify-content-center">
            <div class="col-md-4">
                <div class="value-item">
                    <div class="value-icon"><i class="bi bi-box-seam"></i></div>
                    <h5 class="value-title">Curated Selection</h5>
                    <p class="value-desc">We don't sell everything. We sell the best. Every product is tested by our team.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="value-item">
                    <div class="value-icon"><i class="bi bi-globe-americas"></i></div>
                    <h5 class="value-title">Sustainable Tech</h5>
                    <p class="value-desc">We partner with brands that prioritize eco-friendly packaging and energy efficiency.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="value-item">
                    <div class="value-icon"><i class="bi bi-people"></i></div>
                    <h5 class="value-title">Community First</h5>
                    <p class="value-desc">24/7 support and a community forum for tech lovers to share tips and setups.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="content-section mb-5">
        <div class="row g-3">
            <div class="col-md-6">
                <img src="https://images.unsplash.com/photo-1498049381960-a0d918c75dd9?q=80&w=2070&auto=format&fit=crop" 
                     class="w-100 h-100 object-fit-cover" style="min-height:300px;" alt="Lifestyle 1">
            </div>
            <div class="col-md-6">
                <img src="https://images.unsplash.com/photo-1526738549149-8e07eca6c147?q=80&w=2125&auto=format&fit=crop" 
                     class="w-100 h-100 object-fit-cover" style="min-height:300px;" alt="Lifestyle 2">
            </div>
        </div>
    </section>

</div>

<?php include __DIR__ . "/../components/newsletter.php"; ?>

<?php include __DIR__ . "/../components/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>