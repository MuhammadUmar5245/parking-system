<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdminLogin();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? $pageTitle . ' - Admin - ' . SITE_NAME : 'Admin - ' . SITE_NAME; ?></title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="navbar-inner">
        <a href="dashboard.php" class="navbar-brand"><span class="icon">🅿️</span> <?php echo SITE_NAME; ?> <small style="font-size:0.7rem; opacity:0.7; font-weight:400;">Admin</small></a>
        <div class="navbar-links">
            <span class="nav-user">👤 <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
            <a href="<?php echo BASE_URL; ?>index.php">View Site</a>
            <a href="logout.php" class="btn-nav-accent">Logout</a>
        </div>
    </div>
</nav>
<div class="dashboard-wrap">
    <aside class="sidebar">
        <div class="sidebar-title">Admin Panel</div>
        <a href="dashboard.php" class="<?php echo $currentPage=='dashboard.php'?'active':''; ?>">📊 Dashboard</a>
        <a href="parking_management.php" class="<?php echo $currentPage=='parking_management.php'?'active':''; ?>">🅿️ Parking Management</a>
        <a href="floors.php" class="<?php echo $currentPage=='floors.php'?'active':''; ?>">🏢 Manage Floors</a>
        <a href="reservations.php" class="<?php echo $currentPage=='reservations.php'?'active':''; ?>">📋 Reservations</a>
        <a href="users.php" class="<?php echo $currentPage=='users.php'?'active':''; ?>">👥 Users</a>
        <a href="payments.php" class="<?php echo $currentPage=='payments.php'?'active':''; ?>">💳 Payments</a>
        <a href="qrcodes.php" class="<?php echo $currentPage=='qrcodes.php'?'active':''; ?>">📱 QR Codes</a>
    </aside>
    <main class="main-content">
