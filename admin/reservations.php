<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $resId = (int)$_POST['reservation_id'];
    $newStatus = sanitize($conn, $_POST['new_status']);

    $get = $conn->prepare("SELECT space_id FROM reservations WHERE reservation_id=?");
    $get->bind_param("i", $resId);
    $get->execute();
    $row = $get->get_result()->fetch_assoc();

    $conn->begin_transaction();
    try {
        $upd = $conn->prepare("UPDATE reservations SET status=? WHERE reservation_id=?");
        $upd->bind_param("si", $newStatus, $resId);
        $upd->execute();

        if (in_array($newStatus, ['cancelled','completed']) && $row) {
            $updSpace = $conn->prepare("UPDATE parking_spaces SET status='available' WHERE space_id=?");
            $updSpace->bind_param("i", $row['space_id']);
            $updSpace->execute();
        } elseif ($newStatus === 'confirmed' && $row) {
            $updSpace = $conn->prepare("UPDATE parking_spaces SET status='reserved' WHERE space_id=?");
            $updSpace->bind_param("i", $row['space_id']);
            $updSpace->execute();
        }

        $conn->commit();
        $message = "Reservation status updated.";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Failed to update reservation.";
    }
}

$statusFilter = $_GET['status'] ?? '';
$sql = "
    SELECT r.*, ps.space_number, ps.space_type, pf.floor_name, u.full_name, u.email, v.plate_number
    FROM reservations r
    JOIN parking_spaces ps ON r.space_id = ps.space_id
    JOIN parking_floors pf ON ps.floor_id = pf.floor_id
    JOIN users u ON r.user_id = u.user_id
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
";
if ($statusFilter) $sql .= " WHERE r.status='" . $conn->real_escape_string($statusFilter) . "'";
$sql .= " ORDER BY r.created_at DESC";
$reservations = $conn->query($sql);

$pageTitle = "Reservation Management";
require_once __DIR__ . '/includes/admin_header.php';
?>
<div class="page-header">
    <div><h1>Reservation Management</h1><p>View and manage all customer reservations</p></div>
</div>

<?php if ($message): ?><div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="panel">
    <div class="panel-title">
        All Reservations
        <select onchange="window.location.href='reservations.php?status='+this.value" style="width:auto; padding:6px 10px;">
            <option value="">All Statuses</option>
            <option value="pending" <?php echo $statusFilter=='pending'?'selected':''; ?>>Pending</option>
            <option value="confirmed" <?php echo $statusFilter=='confirmed'?'selected':''; ?>>Confirmed</option>
            <option value="completed" <?php echo $statusFilter=='completed'?'selected':''; ?>>Completed</option>
            <option value="cancelled" <?php echo $statusFilter=='cancelled'?'selected':''; ?>>Cancelled</option>
        </select>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>ID</th><th>User</th><th>Space</th><th>Vehicle</th><th>Date/Time</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php while ($r = $reservations->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo str_pad($r['reservation_id'],6,'0',STR_PAD_LEFT); ?></td>
                    <td><?php echo htmlspecialchars($r['full_name']); ?><br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($r['email']); ?></small></td>
                    <td><?php echo htmlspecialchars($r['space_number']); ?> <?php echo $r['space_type']==='EV'?'⚡':''; ?><br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($r['floor_name']); ?></small></td>
                    <td><?php echo htmlspecialchars($r['plate_number']); ?></td>
                    <td><?php echo date('d M Y, H:i', strtotime($r['start_time'])); ?></td>
                    <td><?php echo CURRENCY . ' ' . number_format($r['total_price'],2); ?></td>
                    <td><span class="badge badge-<?php echo $r['status']; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                    <td>
                        <form method="POST" style="display:flex; gap:6px;">
                            <input type="hidden" name="reservation_id" value="<?php echo $r['reservation_id']; ?>">
                            <select name="new_status" style="width:auto; padding:5px 8px; font-size:0.82rem;">
                                <option value="pending" <?php echo $r['status']=='pending'?'selected':''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $r['status']=='confirmed'?'selected':''; ?>>Confirmed</option>
                                <option value="completed" <?php echo $r['status']=='completed'?'selected':''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $r['status']=='cancelled'?'selected':''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-outline btn-sm">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
