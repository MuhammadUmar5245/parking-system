<?php
$pageTitle = "Dashboard";
require_once __DIR__ . '/includes/admin_header.php';

$totalSpaces = $conn->query("SELECT COUNT(*) c FROM parking_spaces")->fetch_assoc()['c'];
$availableSpaces = $conn->query("SELECT COUNT(*) c FROM parking_spaces WHERE status='available'")->fetch_assoc()['c'];
$reservedSpaces = $conn->query("SELECT COUNT(*) c FROM parking_spaces WHERE status='reserved'")->fetch_assoc()['c'];
$evSpaces = $conn->query("SELECT COUNT(*) c FROM parking_spaces WHERE space_type='EV'")->fetch_assoc()['c'];
$totalUsers = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$totalReservations = $conn->query("SELECT COUNT(*) c FROM reservations")->fetch_assoc()['c'];
$todayReservations = $conn->query("SELECT COUNT(*) c FROM reservations WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['c'];
$totalRevenue = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE payment_status='success'")->fetch_assoc()['s'];

$recent = $conn->query("
    SELECT r.reservation_id, r.status, r.total_price, r.created_at, ps.space_number, u.full_name
    FROM reservations r
    JOIN parking_spaces ps ON r.space_id = ps.space_id
    JOIN users u ON r.user_id = u.user_id
    ORDER BY r.created_at DESC LIMIT 8
");
?>
<div class="page-header">
    <div><h1>Admin Dashboard</h1><p>Overview of <?php echo MALL_NAME; ?> parking operations</p></div>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon">🅿️</div><div><div class="stat-value"><?php echo $totalSpaces; ?></div><div class="stat-label">Total Spaces</div></div></div>
    <div class="stat-card"><div class="stat-icon"><span style="color:#22c55e;">●</span></div><div><div class="stat-value"><?php echo $availableSpaces; ?></div><div class="stat-label">Available Now</div></div></div>
    <div class="stat-card"><div class="stat-icon red">●</div><div><div class="stat-value"><?php echo $reservedSpaces; ?></div><div class="stat-label">Currently Reserved</div></div></div>
    <div class="stat-card"><div class="stat-icon purple">⚡</div><div><div class="stat-value"><?php echo $evSpaces; ?></div><div class="stat-label">EV Charging Spaces</div></div></div>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon blue">👥</div><div><div class="stat-value"><?php echo $totalUsers; ?></div><div class="stat-label">Registered Users</div></div></div>
    <div class="stat-card"><div class="stat-icon blue">📋</div><div><div class="stat-value"><?php echo $totalReservations; ?></div><div class="stat-label">Total Reservations</div></div></div>
    <div class="stat-card"><div class="stat-icon">🆕</div><div><div class="stat-value"><?php echo $todayReservations; ?></div><div class="stat-label">Reservations Today</div></div></div>
    <div class="stat-card"><div class="stat-icon red">💰</div><div><div class="stat-value"><?php echo CURRENCY . ' ' . number_format($totalRevenue,2); ?></div><div class="stat-label">Total Revenue</div></div></div>
</div>

<div class="panel">
    <div class="panel-title">Recent Reservations <a href="reservations.php" style="font-size:0.85rem; color:var(--accent);">View All →</a></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>ID</th><th>User</th><th>Space</th><th>Price</th><th>Status</th><th>Created</th></tr></thead>
            <tbody>
            <?php while ($r = $recent->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo str_pad($r['reservation_id'],6,'0',STR_PAD_LEFT); ?></td>
                    <td><?php echo htmlspecialchars($r['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['space_number']); ?></td>
                    <td><?php echo CURRENCY . ' ' . number_format($r['total_price'],2); ?></td>
                    <td><span class="badge badge-<?php echo $r['status']; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                    <td><?php echo date('d M, H:i', strtotime($r['created_at'])); ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
