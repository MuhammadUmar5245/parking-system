<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $userId = (int)$_POST['user_id'];
    $newStatus = sanitize($conn, $_POST['new_status']);
    $stmt = $conn->prepare("UPDATE users SET status=? WHERE user_id=?");
    $stmt->bind_param("si", $newStatus, $userId);
    $stmt->execute();
    $message = "User status updated.";
}

$users = $conn->query("
    SELECT u.*,
    (SELECT COUNT(*) FROM reservations WHERE user_id=u.user_id) as total_reservations,
    (SELECT COUNT(*) FROM vehicles WHERE user_id=u.user_id) as total_vehicles
    FROM users u ORDER BY u.created_at DESC
");

$pageTitle = "User Management";
require_once __DIR__ . '/includes/admin_header.php';
?>
<div class="page-header">
    <div><h1>User Management</h1><p>View and manage registered customers</p></div>
</div>

<?php if ($message): ?><div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="panel">
    <div class="panel-title">All Users</div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Name</th><th>Email</th><th>Username</th><th>Phone</th><th>Vehicles</th><th>Reservations</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td><?php echo htmlspecialchars($u['phone']); ?></td>
                    <td><?php echo $u['total_vehicles']; ?></td>
                    <td><?php echo $u['total_reservations']; ?></td>
                    <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                    <td><span class="badge badge-<?php echo $u['status']==='active'?'available':'reserved'; ?>"><?php echo ucfirst($u['status']); ?></span></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('<?php echo $u['status']==='active'?'Disable':'Enable'; ?> this user account?');">
                            <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                            <input type="hidden" name="new_status" value="<?php echo $u['status']==='active'?'disabled':'active'; ?>">
                            <button type="submit" name="toggle_status" class="btn btn-outline btn-sm"><?php echo $u['status']==='active'?'Disable':'Enable'; ?></button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
