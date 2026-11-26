<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
requireUserLogin();

$userId = $_SESSION['user_id'];
$message = '';

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $cancelId = (int)$_POST['cancel_id'];

    $verify = $conn->prepare("SELECT space_id, status, start_time FROM reservations WHERE reservation_id=? AND user_id=?");
    $verify->bind_param("ii", $cancelId, $userId);
    $verify->execute();
    $resv = $verify->get_result()->fetch_assoc();

    if ($resv && $resv['status'] === 'confirmed') {
        $conn->begin_transaction();
        try {
            $upd = $conn->prepare("UPDATE reservations SET status='cancelled' WHERE reservation_id=?");
            $upd->bind_param("i", $cancelId);
            $upd->execute();

            $updSpace = $conn->prepare("UPDATE parking_spaces SET status='available' WHERE space_id=?");
            $updSpace->bind_param("i", $resv['space_id']);
            $updSpace->execute();

            $conn->commit();
            $message = "Reservation cancelled successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Failed to cancel reservation.";
        }
    }
}

$stmt = $conn->prepare("
    SELECT r.*, ps.space_number, ps.space_type, pf.floor_name, v.plate_number
    FROM reservations r
    JOIN parking_spaces ps ON r.space_id = ps.space_id
    JOIN parking_floors pf ON ps.floor_id = pf.floor_id
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$reservations = $stmt->get_result();

$pageTitle = "My Reservations";
require_once __DIR__ . '/includes/header.php';
?>
<div class="dashboard-wrap">
    <aside class="sidebar">
        <div class="sidebar-title">User Menu</div>
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="availability.php">🅿️ Parking Availability</a>
        <a href="reserve.php">➕ New Reservation</a>
        <a href="my_reservations.php" class="active">📋 My Reservations</a>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <div><h1>My Reservations</h1><p>Your full reservation history</p></div>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

        <div class="panel">
            <?php if ($reservations->num_rows === 0): ?>
                <div class="empty-state">
                    <div class="icon">🅿️</div>
                    <p>You haven't made any reservations yet.</p>
                    <a href="reserve.php" class="btn btn-primary mt-2">Make Your First Reservation</a>
                </div>
            <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>ID</th><th>Space</th><th>Floor</th><th>Vehicle</th><th>Date & Time</th><th>Duration</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php while ($r = $reservations->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo str_pad($r['reservation_id'],6,'0',STR_PAD_LEFT); ?></td>
                            <td><strong><?php echo htmlspecialchars($r['space_number']); ?></strong> <?php echo $r['space_type']==='EV'?'⚡':''; ?></td>
                            <td><?php echo htmlspecialchars($r['floor_name']); ?></td>
                            <td><?php echo htmlspecialchars($r['plate_number']); ?></td>
                            <td><?php echo date('d M Y, H:i', strtotime($r['start_time'])); ?></td>
                            <td><?php echo $r['duration_hours']; ?>h</td>
                            <td><?php echo CURRENCY . ' ' . number_format($r['total_price'],2); ?></td>
                            <td><span class="badge badge-<?php echo $r['status']; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                            <td>
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                    <a href="qr_code.php?id=<?php echo $r['reservation_id']; ?>" class="btn btn-outline btn-sm">QR</a>
                                    <?php if ($r['status'] === 'confirmed'): ?>
                                    <form method="POST" onsubmit="return confirm('Cancel this reservation?');" style="display:inline;">
                                        <input type="hidden" name="cancel_id" value="<?php echo $r['reservation_id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
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
