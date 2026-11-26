<?php
if (!isset($conn)) { require_once __DIR__ . '/../config/config.php'; }
require_once __DIR__ . '/auth.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🅿️</text></svg>">
</head>
<body>
<nav class="navbar">
    <div class="navbar-inner">
        <a href="<?php echo BASE_URL; ?>index.php" class="navbar-brand">
            <span class="icon">🅿️</span> <?php echo SITE_NAME; ?>
        </a>
        <div class="navbar-links">
            <a href="<?php echo BASE_URL; ?>index.php" class="<?php echo $currentPage=='index.php'?'active':''; ?>">Home</a>
            <a href="<?php echo BASE_URL; ?>about.php" class="<?php echo $currentPage=='about.php'?'active':''; ?>">About</a>
            <a href="<?php echo BASE_URL; ?>availability.php" class="<?php echo $currentPage=='availability.php'?'active':''; ?>">Availability</a>
            <?php if (isUserLoggedIn()): ?>
                <a href="<?php echo BASE_URL; ?>dashboard.php" class="<?php echo $currentPage=='dashboard.php'?'active':''; ?>">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>my_reservations.php" class="<?php echo $currentPage=='my_reservations.php'?'active':''; ?>">My Reservations</a>
                <span class="nav-user">👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="<?php echo BASE_URL; ?>logout.php" class="btn-nav-accent">Logout</a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>login.php" class="<?php echo $currentPage=='login.php'?'active':''; ?>">Login</a>
                <a href="<?php echo BASE_URL; ?>register.php" class="btn-nav-accent">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
