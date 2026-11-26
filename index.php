<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = "Home";
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <h1>Smart Indoor Parking, Reserved in Seconds</h1>
    <p>Skip the search. Reserve your parking space — regular or EV charging — at <?php echo MALL_NAME; ?> before you even leave home, and drive straight to your spot.</p>
    <div class="hero-buttons">
        <a href="<?php echo BASE_URL; ?>availability.php" class="btn btn-primary">🔍 Check Availability</a>
        <?php if (!isUserLoggedIn()): ?>
            <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-outline" style="background:#fff;">Create Account</a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-outline" style="background:#fff;">Go to Dashboard</a>
        <?php endif; ?>
    </div>
</section>

<section class="section container">
    <div class="section-title">
        <h2>Why SmartPark?</h2>
        <p>Everything you need for a stress-free mall visit</p>
    </div>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">🅿️</div>
            <h3>Real-Time Availability</h3>
            <p>See exactly which spaces are free across every floor before you arrive.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">⚡</div>
            <h3>EV Charging Spaces</h3>
            <p>Dedicated, clearly marked electric vehicle spaces with charging points.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">💳</div>
            <h3>Easy Online Payment</h3>
            <p>Secure simulated checkout — reserve and pay in a couple of clicks.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📱</div>
            <h3>QR Gate Entry</h3>
            <p>Get a unique QR code for your reservation, scanned right at the gate.</p>
        </div>
    </div>
</section>

<section class="section" style="background:#fff;">
    <div class="container">
        <div class="section-title">
            <h2>How It Works</h2>
            <p>Four simple steps to a guaranteed parking spot</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">1️⃣</div>
                <h3>Choose Floor & Space</h3>
                <p>Pick your vehicle type and browse available spaces by floor.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">2️⃣</div>
                <h3>Pick Date & Time</h3>
                <p>Select your arrival time and how long you'll need the space.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">3️⃣</div>
                <h3>Pay Securely</h3>
                <p>Confirm your reservation with a quick simulated payment.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">4️⃣</div>
                <h3>Scan & Park</h3>
                <p>Show your QR code at the gate and drive straight to your space.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
