<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$totalRevenue = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE payment_status='success'")->fetch_assoc()['s'];
$totalTxns = $conn->query("SELECT COUNT(*) c FROM payments")->fetch_assoc()['c'];
$successTxns = $conn->query("SELECT COUNT(*) c FROM payments WHERE payment_status='success'")->fetch_assoc()['c'];

$payments = $conn->query("
    SELECT p.*, u.full_name, u.email, r.reservation_id, ps.space_number
    FROM payments p
    JOIN users u ON p.user_id = u.user_id
    JOIN reservations r ON p.reservation_id = r.reservation_id
    JOIN parking_spaces ps ON r.space_id = ps.space_id
    ORDER BY p.paid_at DESC
");

$pageTitle = "Payment Records";
require_once __DIR__ . '/includes/admin_header.php';
?>
<div class="page-header">
    <div><h1>Payment Records</h1><p>All transaction history (simulated payments)</p></div>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon red">💰</div><div><div class="stat-value"><?php echo CURRENCY . ' ' . number_format($totalRevenue,2); ?></div><div class="stat-label">Total Revenue</div></div></div>
    <div class="stat-card"><div class="stat-icon blue">📊</div><div><div class="stat-value"><?php echo $totalTxns; ?></div><div class="stat-label">Total Transactions</div></div></div>
    <div class="stat-card"><div class="stat-icon">✅</div><div><div class="stat-value"><?php echo $successTxns; ?></div><div class="stat-label">Successful Payments</div></div></div>
</div>

<div class="panel">
    <div class="panel-title">All Payments</div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Transaction Ref</th><th>User</th><th>Reservation</th><th>Space</th><th>Amount</th><th>Method</th><th>Card</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php while ($p = $payments->fetch_assoc()): ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($p['transaction_ref']); ?></code></td>
                    <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                    <td>#<?php echo str_pad($p['reservation_id'],6,'0',STR_PAD_LEFT); ?></td>
                    <td><?php echo htmlspecialchars($p['space_number']); ?></td>
                    <td><?php echo CURRENCY . ' ' . number_format($p['amount'],2); ?></td>
                    <td><?php echo htmlspecialchars($p['payment_method']); ?></td>
                    <td>•••• <?php echo htmlspecialchars($p['card_last4']); ?></td>
                    <td><span class="badge badge-<?php echo $p['payment_status']; ?>"><?php echo ucfirst($p['payment_status']); ?></span></td>
                    <td><?php echo date('d M Y, H:i', strtotime($p['paid_at'])); ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
