<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_floor'])) {
    $name = sanitize($conn, $_POST['floor_name']);
    $level = (int)$_POST['floor_level'];
    $desc = sanitize($conn, $_POST['description']);
    $stmt = $conn->prepare("INSERT INTO parking_floors (floor_name, floor_level, description) VALUES (?,?,?)");
    $stmt->bind_param("sis", $name, $level, $desc);
    $stmt->execute();
    $message = "Floor added successfully.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_floor'])) {
    $floorId = (int)$_POST['floor_id'];
    $newStatus = sanitize($conn, $_POST['new_status']);
    $stmt = $conn->prepare("UPDATE parking_floors SET status=? WHERE floor_id=?");
    $stmt->bind_param("si", $newStatus, $floorId);
    $stmt->execute();
    $message = "Floor status updated.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_floor'])) {
    $floorId = (int)$_POST['floor_id'];
    $check = $conn->prepare("SELECT COUNT(*) c FROM parking_spaces WHERE floor_id=?");
    $check->bind_param("i", $floorId);
    $check->execute();
    $count = $check->get_result()->fetch_assoc()['c'];
    if ($count > 0) {
        $message = "Cannot delete: this floor has parking spaces assigned. Remove them first.";
    } else {
        $stmt = $conn->prepare("DELETE FROM parking_floors WHERE floor_id=?");
        $stmt->bind_param("i", $floorId);
        $stmt->execute();
        $message = "Floor deleted.";
    }
}

$floors = $conn->query("
    SELECT pf.*,
    (SELECT COUNT(*) FROM parking_spaces WHERE floor_id=pf.floor_id) as total_spaces,
    (SELECT COUNT(*) FROM parking_spaces WHERE floor_id=pf.floor_id AND status='available') as available_spaces,
    (SELECT COUNT(*) FROM parking_spaces WHERE floor_id=pf.floor_id AND space_type='EV') as ev_spaces
    FROM parking_floors pf ORDER BY floor_level ASC
");

$pageTitle = "Manage Floors";
require_once __DIR__ . '/includes/admin_header.php';
?>
<div class="page-header">
    <div><h1>Manage Parking Floors</h1><p>Configure floors within the parking structure</p></div>
</div>

<?php if ($message): ?><div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="panel">
    <div class="panel-title">+ Add New Floor</div>
    <form method="POST" action="floors.php">
        <div class="form-row" style="grid-template-columns: 2fr 1fr 2fr 1fr;">
            <div class="form-group"><label>Floor Name</label><input type="text" name="floor_name" placeholder="e.g. Level 3" required></div>
            <div class="form-group"><label>Level (order)</label><input type="number" name="floor_level" placeholder="3" required></div>
            <div class="form-group"><label>Description</label><input type="text" name="description" placeholder="Optional description"></div>
            <div class="form-group" style="display:flex; align-items:flex-end;"><button type="submit" name="add_floor" class="btn btn-primary btn-block">Add Floor</button></div>
        </div>
    </form>
</div>

<div class="panel">
    <div class="panel-title">All Floors</div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Floor</th><th>Level</th><th>Total Spaces</th><th>Available</th><th>EV Spaces</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php while ($f = $floors->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($f['floor_name']); ?></strong><br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($f['description']); ?></small></td>
                    <td><?php echo $f['floor_level']; ?></td>
                    <td><?php echo $f['total_spaces']; ?></td>
                    <td><?php echo $f['available_spaces']; ?></td>
                    <td><?php echo $f['ev_spaces']; ?> ⚡</td>
                    <td><span class="badge badge-<?php echo $f['status']==='active'?'available':'maintenance'; ?>"><?php echo ucfirst($f['status']); ?></span></td>
                    <td>
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="floor_id" value="<?php echo $f['floor_id']; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $f['status']==='active'?'inactive':'active'; ?>">
                                <button type="submit" name="toggle_floor" class="btn btn-outline btn-sm"><?php echo $f['status']==='active'?'Deactivate':'Activate'; ?></button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Delete this floor?');" style="display:inline;">
                                <input type="hidden" name="floor_id" value="<?php echo $f['floor_id']; ?>">
                                <button type="submit" name="delete_floor" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
