<?php
$technicians = [
    [
        'name' => 'Daniel M.',
        'profession' => 'Electrician',
        'service' => 'Electrical',
        'location' => 'Douala',
        'rating' => 4.8,
        'reviews' => 124,
        'price' => 20,
        'image' => 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=700&q=80',
        'description' => 'Electrical installations, wiring and home repairs.'
    ],
    [
        'name' => 'Samuel T.',
        'profession' => 'Plumber',
        'service' => 'Plumbing',
        'location' => 'Yaoundé',
        'rating' => 4.7,
        'reviews' => 98,
        'price' => 18,
        'image' => 'https://images.unsplash.com/photo-1621905251918-48416bd8575a?auto=format&fit=crop&w=700&q=80',
        'description' => 'Pipe installations, leak repairs and bathroom fittings.'
    ],
    [
        'name' => 'Joseph K.',
        'profession' => 'Carpenter',
        'service' => 'Carpentry',
        'location' => 'Bafoussam',
        'rating' => 4.6,
        'reviews' => 87,
        'price' => 16,
        'image' => 'https://images.unsplash.com/photo-1620626011761-996317b8d101?auto=format&fit=crop&w=700&q=80',
        'description' => 'Furniture making, door repairs and woodwork.'
    ],
    [
        'name' => 'Robert B.',
        'profession' => 'AC Technician',
        'service' => 'AC Repair',
        'location' => 'Douala',
        'rating' => 4.9,
        'reviews' => 76,
        'price' => 22,
        'image' => 'https://images.unsplash.com/photo-1631545806609-7c6a2b7a6f9a?auto=format&fit=crop&w=700&q=80',
        'description' => 'Air conditioner installation and maintenance.'
    ],
    [
        'name' => 'Patrick N.',
        'profession' => 'Painter',
        'service' => 'Painting',
        'location' => 'Yaoundé',
        'rating' => 4.5,
        'reviews' => 54,
        'price' => 15,
        'image' => 'https://images.unsplash.com/photo-1562259949-e8e7689d7828?auto=format&fit=crop&w=700&q=80',
        'description' => 'Interior painting, exterior painting and finishing.'
    ],
    [
        'name' => 'Michael A.',
        'profession' => 'Appliance Technician',
        'service' => 'Appliance Repair',
        'location' => 'Bafoussam',
        'rating' => 4.7,
        'reviews' => 63,
        'price' => 17,
        'image' => 'https://images.unsplash.com/photo-1581092921461-eab62e97a780?auto=format&fit=crop&w=700&q=80',
        'description' => 'Troubleshooting and repairs for home appliances.'
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
    <meta name="description" content="Browse sample technician profiles on TechConnect.">
    <title>Find Technicians | TechConnect</title>
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
                aria-label="Open navigation" aria-expanded="false">☰</button>

        <nav class="main-nav" id="mainNav">
            <a href="index.php">Home</a>
            <a href="index.php#services">Services</a>
            <a class="active" href="technicians.php">Find Technicians</a>
            <a href="index.php#how-it-works">How It Works</a>
            <a href="index.php#about">About</a>
            <a href="index.php#contact">Contact</a>
            <div class="mobile-auth">
                <a href="login.php">Login</a>
                <a href="register.php" class="btn btn-primary">Sign Up</a>
            </div>
        </nav>

        <div class="nav-actions">
            <a href="login.php" class="btn btn-outline">Login</a>
            <a href="register.php" class="btn btn-primary">Sign Up</a>
        </div>
    </div>
</header>

<main>
    <section class="listing-hero">
        <div class="container">
            <span class="section-kicker">FIND THE RIGHT PROFESSIONAL</span>
            <h1>Find Skilled Technicians</h1>
            <p>Browse professionals and find someone for your next job.</p>

            <form id="listingSearch" class="listing-search">
                <label>
                    <span>Service category</span>
                    <select id="listingService">
                        <option value="">All services</option>
                        <option value="Electrical">Electrical</option>
                        <option value="Plumbing">Plumbing</option>
                        <option value="Carpentry">Carpentry</option>
                        <option value="AC Repair">AC Repair</option>
                        <option value="Painting">Painting</option>
                        <option value="Appliance Repair">Appliance Repair</option>
                    </select>
                </label>

                <label>
                    <span>Location</span>
                    <select id="listingLocation">
                        <option value="">All locations</option>
                        <option value="Douala">Douala</option>
                        <option value="Yaoundé">Yaoundé</option>
                        <option value="Bafoussam">Bafoussam</option>
                    </select>
                </label>

                <label>
                    <span>Minimum rating</span>
                    <select id="listingRating">
                        <option value="0">Any rating</option>
                        <option value="4">4.0+ stars</option>
                        <option value="4.5">4.5+ stars</option>
                        <option value="4.8">4.8+ stars</option>
                    </select>
                </label>

                <button class="btn btn-primary" type="submit">
                    Search
                </button>
            </form>
        </div>
    </section>

    <section class="section listing-section">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="section-kicker">OUR PROFESSIONALS</span>
                    <h2>Available Technicians</h2>
                </div>
                <p id="listingCount" aria-live="polite">
                    <?= count($technicians) ?> sample technicians
                </p>
            </div>

            <div class="technicians-grid listing-grid" id="listingGrid">
                <?php foreach ($technicians as $technician): ?>
                    <article class="technician-card listing-card"
                        data-service="<?= e($technician['service']) ?>"
                        data-location="<?= e($technician['location']) ?>"
                        data-rating="<?= e($technician['rating']) ?>">

                        <div class="technician-image">
                            <img src="<?= e($technician['image']) ?>"
                                 alt="<?= e($technician['profession']) ?>"
                                 loading="lazy">
                            <span class="verified-badge">✓ Sample profile</span>
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

                            <p class="profile-description">
                                <?= e($technician['description']) ?>
                            </p>

                            <div class="technician-footer">
                                <span class="location">
                                    ⌖ <?= e($technician['location']) ?>, Cameroon
                                </span>
                                <a href="techniciandetails.php"
                                   class="btn btn-small btn-outline">
                                    View Profile
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="no-results" id="listingEmpty" hidden>
                <h3>No matching technicians</h3>
                <p>Try changing your service, location or rating.</p>
                <button type="button" id="listingReset"
                        class="btn btn-primary">Clear Filters</button>
            </div>
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
            <a href="index.php#services">Services</a>
            <a href="technicians.php">Find Technicians</a>
        </div>

        <div class="footer-column">
            <h3>Account</h3>
            <a href="login.php">Login</a>
            <a href="register.php">Create Account</a>
        </div>

        <div class="footer-column">
            <h3>Contact Us</h3>
            <a href="mailto:support@techconnect.com">support@techconnect.com</a>
            <p>Cameroon</p>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            &copy; <?= date('Y') ?> TechConnect. All rights reserved.
        </div>
    </div>
</footer>

</body>
</html>