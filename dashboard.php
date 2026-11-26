<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
requireUserLogin();
requireMallSelected();

$userId = $_SESSION['user_id'];

// Stats
$totalRes = $conn->query("SELECT COUNT(*) c FROM reservations WHERE user_id=$userId")->fetch_assoc()['c'];
$activeRes = $conn->query("SELECT COUNT(*) c FROM reservations WHERE user_id=$userId AND status='confirmed' AND end_time >= NOW()")->fetch_assoc()['c'];
$vehiclesCount = $conn->query("SELECT COUNT(*) c FROM vehicles WHERE user_id=$userId")->fetch_assoc()['c'];
$totalSpent = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE user_id=$userId AND payment_status='success'")->fetch_assoc()['s'];

// Upcoming reservations
$upcoming = $conn->prepare("
    SELECT r.*, ps.space_number, ps.space_type, pf.floor_name, v.plate_number
    FROM reservations r
    JOIN parking_spaces ps ON r.space_id = ps.space_id
    JOIN parking_floors pf ON ps.floor_id = pf.floor_id
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    WHERE r.user_id = ? AND r.status IN ('confirmed','pending')
    ORDER BY r.start_time ASC LIMIT 5
");
$upcoming->bind_param("i", $userId);
$upcoming->execute();
$upcomingRes = $upcoming->get_result();

$pageTitle = "Dashboard";
require_once __DIR__ . '/includes/header.php';
?>
<div class="dashboard-wrap">
    <aside class="sidebar">
        <div class="sidebar-title">User Menu</div>
        <a href="dashboard.php" class="active">📊 Dashboard</a>
        <a href="availability.php">🅿️ Parking Availability</a>
        <a href="reserve.php">➕ New Reservation</a>
        <a href="my_reservations.php">📋 My Reservations</a>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> 👋</h1>
                <p>Here's an overview of your parking activity</p>
                <span class="mall-badge">📍 <strong><?php echo htmlspecialchars($_SESSION['mall_name']); ?></strong> <a href="select_mall.php">Change mall</a></span>
            </div>
            <a href="reserve.php" class="btn btn-primary">➕ New Reservation</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">📋</div>
                <div><div class="stat-value"><?php echo $totalRes; ?></div><div class="stat-label">Total Reservations</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div><div class="stat-value"><?php echo $activeRes; ?></div><div class="stat-label">Active / Upcoming</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">🚗</div>
                <div><div class="stat-value"><?php echo $vehiclesCount; ?></div><div class="stat-label">Registered Vehicles</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">💰</div>
                <div><div class="stat-value"><?php echo CURRENCY . ' ' . number_format($totalSpent,2); ?></div><div class="stat-label">Total Spent</div></div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-title">
                Upcoming Reservations
                <a href="my_reservations.php" style="font-size:0.85rem; color:var(--accent);">View All →</a>
            </div>
            <?php if ($upcomingRes->num_rows === 0): ?>
                <div class="empty-state">
                    <div class="icon">🅿️</div>
                    <p>No upcoming reservations yet.</p>
                    <a href="reserve.php" class="btn btn-primary mt-2">Reserve a Space</a>
                </div>
            <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Space</th><th>Floor</th><th>Vehicle</th><th>Date & Time</th><th>Price</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php while ($r = $upcomingRes->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($r['space_number']); ?></strong> <?php echo $r['space_type']==='EV'?'⚡':''; ?></td>
                            <td><?php echo htmlspecialchars($r['floor_name']); ?></td>
                            <td><?php echo htmlspecialchars($r['plate_number']); ?></td>
                            <td><?php echo date('d M Y, H:i', strtotime($r['start_time'])); ?></td>
                            <td><?php echo CURRENCY . ' ' . number_format($r['total_price'],2); ?></td>
                            <td><span class="badge badge-<?php echo $r['status']; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                            <td><a href="qr_code.php?id=<?php echo $r['reservation_id']; ?>" class="btn btn-outline btn-sm">View QR</a></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
