<?php
$services = [
    ['name' => 'Electrical', 'icon' => '⚡', 'count' => '120+'],
    ['name' => 'Plumbing', 'icon' => '💧', 'count' => '98+'],
    ['name' => 'Carpentry', 'icon' => '🔨', 'count' => '76+'],
    ['name' => 'AC Repair', 'icon' => '❄️', 'count' => '54+'],
    ['name' => 'Appliance Repair', 'icon' => '🔧', 'count' => '68+'],
    ['name' => 'Painting', 'icon' => '🖌️', 'count' => '42+'],
];

$technicians = [
    [
        'name' => 'Daniel M.',
        'profession' => 'Electrician',
        'service' => 'Electrical',
        'rating' => '4.8',
        'reviews' => 124,
        'price' => 20,
        'location' => 'Douala, Cameroon',
        'image' => 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=700&q=80'
    ],
    [
        'name' => 'Samuel T.',
        'profession' => 'Plumber',
        'service' => 'Plumbing',
        'rating' => '4.7',
        'reviews' => 98,
        'price' => 18,
        'location' => 'Yaoundé, Cameroon',
        'image' => 'https://images.unsplash.com/photo-1621905251918-48416bd8575a?auto=format&fit=crop&w=700&q=80'
    ],
    [
        'name' => 'Joseph K.',
        'profession' => 'Carpenter',
        'service' => 'Carpentry',
        'rating' => '4.6',
        'reviews' => 87,
        'price' => 16,
        'location' => 'Bafoussam, Cameroon',
        'image' => 'https://images.unsplash.com/photo-1620626011761-996317b8d101?auto=format&fit=crop&w=700&q=80'
    ],
    [
        'name' => 'Robert B.',
        'profession' => 'AC Technician',
        'service' => 'AC Repair',
        'rating' => '4.9',
        'reviews' => 76,
        'price' => 22,
        'location' => 'Douala, Cameroon',
        'image' => 'https://images.unsplash.com/photo-1631545806609-7c6a2b7a6f9a?auto=format&fit=crop&w=700&q=80'
    ],
];

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Find skilled technicians for home repairs, maintenance and installation with TechConnect.">
    <title>TechConnect | Find Skilled Technicians</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>

<header class="site-header">
    <div class="container nav-container">
        <a href="index.php" class="logo">
            <span class="logo-icon">✣</span>
            <span>
                <strong>TechConnect</strong>
                <small>Connect. Fix. Done.</small>
            </span>
        </a>

        <button class="menu-toggle" id="menuToggle"
                aria-label="Open navigation" aria-expanded="false">
            ☰
        </button>

        <nav class="main-nav" id="mainNav">
            <a class="active" href="index.php">Home</a>
            <a href="#services">Services</a>
            <a href="#technicians">Find Technicians</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
            <div class="mobile-auth">
                <a href="login.php">Login</a>
                <a href="register.php" class="btn btn-primary">Sign Up</a>
            </div>
        </nav>

        <div class="nav-actions">
            <a href="#search" class="search-icon" aria-label="Go to search">⌕</a>
            <a href="login.php" class="btn btn-outline">Login</a>
            <a href="register.php" class="btn btn-primary">Sign Up</a>
        </div>
    </div>
</header>

<main>
    <!-- HERO -->
    <section class="hero" id="search">
        <div class="container hero-grid">
            <div class="hero-content">
                <span class="eyebrow">Your Trusted Service Marketplace</span>
                <h1>Find Skilled Technicians.<br>
                    <span>Get Things Done.</span>
                </h1>
                <p>
                    Connect with verified and experienced professionals
                    for your home and business repairs, maintenance and
                    installation services.
                </p>

                <form class="search-form" id="technicianSearch">
                    <label class="search-field">
                        <span>⌕</span>
                        <select id="serviceSelect" aria-label="Choose a service">
                            <option value="">What service do you need?</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?= e($service['name']) ?>">
                                    <?= e($service['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="search-field">
                        <span>⌖</span>
                        <select id="locationSelect" aria-label="Choose a location">
                            <option value="">Select a location</option>
                            <option value="Douala">Douala</option>
                            <option value="Yaoundé">Yaoundé</option>
                            <option value="Bafoussam">Bafoussam</option>
                        </select>
                    </label>

                    <button type="submit" class="btn btn-primary search-button">
                        ⌕ Find a Technician
                    </button>
                </form>

                <div class="trust-points">
                    <span>✓ Verified Technicians</span>
                    <span>✓ Secure Booking</span>
                    <span>✓ Transparent Pricing</span>
                    <span>✓ 24/7 Support</span>
                </div>
            </div>

            <div class="hero-visual">
                <img
                    src="https://images.unsplash.com/photo-1621905252507-b35492cc74b4?auto=format&fit=crop&w=1100&q=85"
                    alt="Technician working on an electrical installation"
                >
                <div class="trust-card">
                    <span class="trust-shield">✓</span>
                    <span><strong>Trusted Service</strong>
                        <small>Professionals you can rely on</small></span>
                </div>
            </div>
        </div>
    </section>

    <!-- SERVICES -->
    <section class="section services-section" id="services">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="section-kicker">WHAT WE OFFER</span>
                    <h2>Popular Services</h2>
                </div>
                <a href="services.php" class="text-link">View All Services →</a>
            </div>

            <div class="services-grid">
                <?php foreach ($services as $service): ?>
                    <button class="service-card"
                            type="button"
                            data-service="<?= e($service['name']) ?>">
                        <span class="service-icon"><?= e($service['icon']) ?></span>
                        <span class="service-info">
                            <strong><?= e($service['name']) ?></strong>
                            <small><?= e($service['count']) ?> technicians</small>
                        </span>
                        <span class="service-arrow">→</span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- TECHNICIANS -->
    <section class="section technicians-section" id="technicians">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="section-kicker">MEET THE PROFESSIONALS</span>
                    <h2>Featured Technicians</h2>
                </div>
                <a href="technicians.php" class="text-link">
                    View All Technicians →
                </a>
            </div>

            <p id="searchMessage" class="search-message" aria-live="polite"></p>

            <div class="technicians-grid" id="technicianGrid">
                <?php foreach ($technicians as $technician): ?>
                    <article class="technician-card"
                             data-service="<?= e($technician['service']) ?>"
                             data-location="<?= e($technician['location']) ?>">

                        <div class="technician-image">
                            <img src="<?= e($technician['image']) ?>"
                                 alt="<?= e($technician['profession']) ?>"
                                 loading="lazy">
                            <span class="verified-badge">✓ Verified</span>
                        </div>

                        <div class="technician-details">
                            <div class="technician-title">
                                <div>
                                    <h3><?= e($technician['name']) ?></h3>
                                    <p><?= e($technician['profession']) ?></p>
                                </div>
                                <strong class="price">
                                    $<?= e($technician['price']) ?><small>/hr</small>
                                </strong>
                            </div>

                            <div class="rating">
                                <span>★</span>
                                <?= e($technician['rating']) ?>
                                <small>(<?= e($technician['reviews']) ?> reviews)</small>
                            </div>

                            <div class="technician-footer">
                                <span class="location">
                                    ⌖ <?= e($technician['location']) ?>
                                </span>
                                <a href="technician-details.php"
                                   class="btn btn-small btn-outline">
                                    View Profile
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div id="noResults" class="no-results" hidden>
                <h3>No technicians found</h3>
                <p>Try another service or location.</p>
                <button type="button" id="resetSearch" class="btn btn-primary">
                    Clear Filters
                </button>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS -->
    <section class="section how-section" id="how-it-works">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="section-kicker">SIMPLE AND CONVENIENT</span>
                    <h2>How It Works</h2>
                </div>
                <p class="heading-note">Get your job done in four easy steps.</p>
            </div>

            <div class="steps-grid">
                <article class="step">
                    <span class="step-icon">⌕</span>
                    <div><h3>1. Search for a Service</h3>
                        <p>Tell us what you need and where you need it.</p></div>
                </article>
                <article class="step">
                    <span class="step-icon">♙</span>
                    <div><h3>2. Choose a Technician</h3>
                        <p>Compare profiles, ratings and prices.</p></div>
                </article>
                <article class="step">
                    <span class="step-icon">▦</span>
                    <div><h3>3. Book the Job</h3>
                        <p>Choose a date and time that works for you.</p></div>
                </article>
                <article class="step">
                    <span class="step-icon">✓</span>
                    <div><h3>4. Get It Done</h3>
                        <p>Let the professional take care of the job.</p></div>
                </article>
            </div>
        </div>
    </section>

    <!-- CALL TO ACTION -->
    <section class="cta-section" id="about">
        <div class="container cta-content">
            <div>
                <span class="section-kicker">GROW WITH TECHCONNECT</span>
                <h2>Are you a skilled technician?</h2>
                <p>Showcase your skills and connect with customers who need your services.</p>
            </div>
            <a href="register.php?role=technician" class="btn btn-white">
                Join as a Technician →
            </a>
        </div>
    </section>
</main>

<footer class="site-footer" id="contact">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a href="index.php" class="logo footer-logo">
                <span class="logo-icon">✣</span>
                <span><strong>TechConnect</strong>
                    <small>Connect. Fix. Done.</small></span>
            </a>
            <p>Your trusted platform for finding skilled service professionals.</p>
        </div>

        <div class="footer-column">
            <h3>Quick Links</h3>
            <a href="index.php">Home</a>
            <a href="#services">Services</a>
            <a href="technicians.php">Find Technicians</a>
            <a href="#how-it-works">How It Works</a>
        </div>

        <div class="footer-column">
            <h3>Company</h3>
            <a href="#about">About Us</a>
            <a href="#contact">Contact</a>
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Service</a>
        </div>

        <div class="footer-column">
            <h3>Contact Us</h3>
            <a href="tel:+237612345678">+237 612 34 56 78</a>
            <a href="mailto:support@techconnect.com">support@techconnect.com</a>
            <p>Cameroon</p>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            © <?= date('Y') ?> TechConnect. All rights reserved.
        </div>
    </div>
</footer>

</body>
</html>