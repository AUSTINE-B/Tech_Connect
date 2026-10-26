
<?php
// Sample technician data. The backend developer can connect this to MySQL later.
$technician = [
    'name' => 'Daniel M.',
    'profession' => 'Professional Electrician',
    'location' => 'Douala, Cameroon',
    'rating' => 4.8,
    'reviews' => 124,
    'experience' => '6 years',
    'jobs' => 180,
    'price' => 20,
    'about' => 'I provide reliable electrical installation, maintenance and repair services for homes and small businesses. I focus on safe workmanship, clear communication and quality results.',
    'email' => 'daniel@example.com',
    'phone' => '+237 612 34 56 78',
    'skills' => [
        'Electrical installation',
        'Wiring and rewiring',
        'Lighting installation',
        'Fault diagnosis',
        'Electrical maintenance',
        'Safety inspections'
    ],
    'image' => 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=800&q=85'
];

$portfolio = [
    [
        'title' => 'Home Electrical Installation',
        'category' => 'Installation',
        'image' => 'https://images.unsplash.com/photo-1621905252507-b35492cc74b4?auto=format&fit=crop&w=700&q=80'
    ],
    [
        'title' => 'Lighting Project',
        'category' => 'Lighting',
        'image' => 'https://images.unsplash.com/photo-1558008258-3256797b43f3?auto=format&fit=crop&w=700&q=80'
    ],
    [
        'title' => 'Electrical Maintenance',
        'category' => 'Maintenance',
        'image' => 'https://images.unsplash.com/photo-1621905251918-48416bd8575a?auto=format&fit=crop&w=700&q=80'
    ]
];

$reviews = [
    [
        'name' => 'Sarah M.',
        'rating' => 5,
        'date' => 'September 2026',
        'comment' => 'Daniel was professional and arrived on time. He explained the problem clearly and completed the work neatly.'
    ],
    [
        'name' => 'Paul N.',
        'rating' => 5,
        'date' => 'August 2026',
        'comment' => 'Very helpful and respectful. The electrical repair was completed successfully.'
    ],
    [
        'name' => 'Grace T.',
        'rating' => 4,
        'date' => 'July 2026',
        'comment' => 'Good service and communication. I would consider hiring him again.'
    ]
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
    <meta name="description" content="View a sample TechConnect technician profile, skills, portfolio and reviews.">
    <title><?= e($technician['name']) ?> | TechConnect</title>
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
    <div class="profile-breadcrumb">
        <div class="container">
            <a href="index.php">Home</a> /
            <a href="technicians.php">Technicians</a> /
            <span><?= e($technician['name']) ?></span>
        </div>
    </div>

    <!-- PROFILE HEADER -->
    <section class="profile-top-section">
        <div class="container profile-header">
            <img class="profile-avatar"
                 src="<?= e($technician['image']) ?>"
                 alt="<?= e($technician['profession']) ?>">

            <div class="profile-intro">
                <div class="profile-name-line">
                    <h1><?= e($technician['name']) ?></h1>
                    <span class="profile-verified">✓ Sample Profile</span>
                </div>

                <p class="profile-profession">
                    <?= e($technician['profession']) ?>
                </p>

                <div class="profile-meta">
                    <span>⌖ <?= e($technician['location']) ?></span>
                    <span><strong class="gold-star">★</strong>
                        <?= e($technician['rating']) ?>
                        (<?= e($technician['reviews']) ?> reviews)
                    </span>
                    <span>✓ <?= e($technician['experience']) ?> experience</span>
                </div>
            </div>

            <div class="profile-header-price">
                <span>Starting from</span>
                <strong>$<?= e($technician['price']) ?><small>/hour</small></strong>
                <a href="#booking" class="btn btn-primary">Book Technician</a>
            </div>
        </div>
    </section>

    <section class="section profile-content-section">
        <div class="container profile-layout">

            <!-- MAIN PROFILE CONTENT -->
            <div class="profile-main">

                <section class="profile-panel">
                    <h2>About the Technician</h2>
                    <p><?= e($technician['about']) ?></p>

                    <div class="profile-stat-grid">
                        <div class="profile-stat">
                            <strong><?= e($technician['experience']) ?></strong>
                            <span>Experience</span>
                        </div>
                        <div class="profile-stat">
                            <strong><?= e($technician['jobs']) ?>+</strong>
                            <span>Jobs completed</span>
                        </div>
                        <div class="profile-stat">
                            <strong><?= e($technician['rating']) ?>/5</strong>
                            <span>Average rating</span>
                        </div>
                    </div>
                </section>

                <!-- SKILLS -->
                <section class="profile-panel">
                    <h2>Skills and Expertise</h2>
                    <div class="skills-list">
                        <?php foreach ($technician['skills'] as $skill): ?>
                            <span class="skill-tag">✓ <?= e($skill) ?></span>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- PORTFOLIO -->
                <section class="profile-panel" id="portfolio">
                    <div class="profile-section-heading">
                        <h2>Work Portfolio</h2>
                        <span class="muted-label">Sample projects</span>
                    </div>

                    <div class="portfolio-grid">
                        <?php foreach ($portfolio as $project): ?>
                            <article class="portfolio-card">
                                <img src="<?= e($project['image']) ?>"
                                     alt="<?= e($project['title']) ?>"
                                     loading="lazy">
                                <div>
                                    <span><?= e($project['category']) ?></span>
                                    <h3><?= e($project['title']) ?></h3>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- REVIEWS -->
                <section class="profile-panel" id="reviews">
                    <div class="profile-section-heading">
                        <h2>Customer Reviews</h2>
                        <span class="review-summary">
                            ★ <?= e($technician['rating']) ?>/5
                        </span>
                    </div>

                    <?php foreach ($reviews as $review): ?>
                        <article class="review-card">
                            <div class="review-avatar">
                                <?= e(substr($review['name'], 0, 1)) ?>
                            </div>
                            <div class="review-content">
                                <div class="review-title">
                                    <strong><?= e($review['name']) ?></strong>
                                    <span><?= e($review['date']) ?></span>
                                </div>
                                <div class="review-stars">
                                    <?= str_repeat('★', (int) $review['rating']) ?>
                                    <span><?= e($review['rating']) ?>/5</span>
                                </div>
                                <p><?= e($review['comment']) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <p class="demo-note">
                        These reviews are demonstration content, not verified customer testimonials.
                    </p>
                </section>
            </div>

            <!-- BOOKING SIDEBAR -->
            <aside class="booking-sidebar" id="booking">
                <div class="booking-card">
                    <span class="section-kicker">HIRE A PROFESSIONAL</span>
                    <h2>Book This Technician</h2>

                    <p class="booking-description">
                        Request a service appointment with
                        <?= e($technician['name']) ?>.
                    </p>

                    <div class="booking-price-row">
                        <span>Hourly rate</span>
                        <strong>$<?= e($technician['price']) ?>/hr</strong>
                    </div>

                    <form id="bookingForm">
                        <label for="bookingService">Service needed</label>
                        <select id="bookingService" name="service" required>
                            <option value="">Select a service</option>
                            <option>Electrical installation</option>
                            <option>Electrical repair</option>
                            <option>Lighting installation</option>
                            <option>Electrical maintenance</option>
                        </select>

                        <label for="bookingDate">Preferred date</label>
                        <input type="date" id="bookingDate"
                               name="date" required>

                        <label for="bookingTime">Preferred time</label>
                        <select id="bookingTime" name="time" required>
                            <option value="">Choose a time</option>
                            <option>08:00 AM</option>
                            <option>10:00 AM</option>
                            <option>12:00 PM</option>
                            <option>02:00 PM</option>
                            <option>04:00 PM</option>
                        </select>

                        <label for="bookingAddress">Service location</label>
                        <input type="text" id="bookingAddress"
                               name="address"
                               placeholder="Enter your area or address"
                               required>

                        <label for="bookingNotes">Additional details</label>
                        <textarea id="bookingNotes" name="notes"
                                  rows="3"
                                  placeholder="Describe the work you need (optional)"></textarea>

                        <button type="submit" class="btn btn-primary booking-submit">
                            Request Booking →
                        </button>

                        <p id="bookingMessage" class="booking-message"
                           aria-live="polite"></p>
                    </form>

                    <p class="booking-disclaimer">
                        Demo only: your request will not be sent to a technician.
                    </p>
                </div>

                <div class="contact-card">
                    <h3>Contact Information</h3>
                    <p>✉ <?= e($technician['email']) ?></p>
                    <p>☎ <?= e($technician['phone']) ?></p>
                    <small>Sample contact details</small>
                </div>
            </aside>

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
            <a href="technicians.php">Find Technicians</a>
            <a href="index.php#services">Services</a>
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