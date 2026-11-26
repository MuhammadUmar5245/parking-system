<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = "About";
require_once __DIR__ . '/includes/header.php';
?>
<section class="section container">
    <div class="section-title">
        <h2>About This Project</h2>
        <p>Smart Indoor Parking Reservation System</p>
    </div>

    <div class="panel" style="max-width:820px; margin:0 auto;">
        <p style="margin-bottom:16px;">
            <strong><?php echo SITE_NAME; ?></strong> is a smart indoor parking reservation platform designed for
            shopping malls in Oman, supporting both conventional and electric vehicles. The system allows
            shoppers to browse real-time parking availability across multiple floors, reserve a specific
            space in advance, pay online, and receive a unique QR code used for gate entry verification.
        </p>
        <p style="margin-bottom:16px;">
            The platform distinguishes between <strong>Regular Parking</strong> and dedicated
            <strong>EV Parking with Charging</strong>, helping electric vehicle owners quickly find a
            compatible space instead of circling the parking structure.
        </p>
        <p style="margin-bottom:16px;">
            An integrated <strong>Admin Dashboard</strong> allows mall management to configure parking floors
            and spaces, monitor live occupancy, manage reservations and payments, and oversee QR code
            verification at entrance gates.
        </p>
        <h3 style="color:var(--primary); margin:24px 0 12px;">Technology Stack</h3>
        <ul style="line-height:2; padding-left:20px; list-style:disc;">
            <li>Frontend: HTML5, CSS3, JavaScript</li>
            <li>Backend: PHP (procedural, mysqli)</li>
            <li>Database: MySQL / MariaDB (XAMPP)</li>
            <li>QR Codes: Server-side QR generation</li>
        </ul>
        <p style="margin-top:20px; color:var(--text-muted); font-size:0.88rem;">
            Note: This is a demonstration / academic project. All payments processed on this platform are
            simulated and no real financial transactions take place.
        </p>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
