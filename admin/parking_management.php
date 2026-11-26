<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$message = '';

// Add space
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_space'])) {
    $floorId = (int)$_POST['floor_id'];
    $spaceNumber = sanitize($conn, $_POST['space_number']);
    $spaceType = sanitize($conn, $_POST['space_type']);
    $hasCharger = $spaceType === 'EV' ? 1 : 0;
    $rate = (float)$_POST['hourly_rate'];

    $check = $conn->prepare("SELECT space_id FROM parking_spaces WHERE floor_id=? AND space_number=?");
    $check->bind_param("is", $floorId, $spaceNumber);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $message = "A space with that number already exists on this floor.";
    } else {
        $stmt = $conn->prepare("INSERT INTO parking_spaces (floor_id, space_number, space_type, has_charger, hourly_rate, status) VALUES (?,?,?,?,?,'available')");
        $stmt->bind_param("issid", $floorId, $spaceNumber, $spaceType, $hasCharger, $rate);
        $stmt->execute();
        $message = "Parking space added successfully.";
    }
}

// Edit space
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_space'])) {
    $spaceId = (int)$_POST['space_id'];
    $spaceNumber = sanitize($conn, $_POST['space_number']);
    $spaceType = sanitize($conn, $_POST['space_type']);
    $hasCharger = $spaceType === 'EV' ? 1 : 0;
    $rate = (float)$_POST['hourly_rate'];
    $status = sanitize($conn, $_POST['status']);

    $stmt = $conn->prepare("UPDATE parking_spaces SET space_number=?, space_type=?, has_charger=?, hourly_rate=?, status=? WHERE space_id=?");
    $stmt->bind_param("ssidsi", $spaceNumber, $spaceType, $hasCharger, $rate, $status, $spaceId);
    $stmt->execute();
    $message = "Parking space updated successfully.";
}

// Delete space
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_space'])) {
    $spaceId = (int)$_POST['space_id'];
    $inUse = $conn->prepare("SELECT COUNT(*) c FROM reservations WHERE space_id=? AND status='confirmed'");
    $inUse->bind_param("i", $spaceId);
    $inUse->execute();
    $count = $inUse->get_result()->fetch_assoc()['c'];
    if ($count > 0) {
        $message = "Cannot delete: this space has active reservations.";
    } else {
        $stmt = $conn->prepare("DELETE FROM parking_spaces WHERE space_id=?");
        $stmt->bind_param("i", $spaceId);
        $stmt->execute();
        $message = "Parking space deleted.";
    }
}

$floors = $conn->query("SELECT * FROM parking_floors ORDER BY floor_level ASC");
$floorList = [];
while ($f = $floors->fetch_assoc()) { $floorList[] = $f; }
$filterFloor = isset($_GET['floor']) ? (int)$_GET['floor'] : 0;

$sql = "SELECT ps.*, pf.floor_name FROM parking_spaces ps JOIN parking_floors pf ON ps.floor_id=pf.floor_id";
if ($filterFloor > 0) $sql .= " WHERE ps.floor_id=$filterFloor";
$sql .= " ORDER BY pf.floor_level ASC, ps.space_number ASC";
$spaces = $conn->query($sql);

$pageTitle = "Parking Management";
require_once __DIR__ . '/includes/admin_header.php';
?>
<div class="page-header">
    <div><h1>Parking Management</h1><p>Add, edit, and remove parking spaces</p></div>
</div>

<?php if ($message): ?><div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="panel">
    <div class="panel-title">+ Add New Parking Space</div>
    <form method="POST" action="parking_management.php">
        <div class="form-row" style="grid-template-columns: 1fr 1fr 1fr 1fr 1fr;">
            <div class="form-group">
                <label>Floor</label>
                <select name="floor_id" required>
                    <?php foreach ($floorList as $f): ?>
                        <option value="<?php echo $f['floor_id']; ?>"><?php echo htmlspecialchars($f['floor_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Space Number</label><input type="text" name="space_number" placeholder="e.g. G-11" required></div>
            <div class="form-group">
                <label>Type</label>
                <select name="space_type" required>
                    <option value="Regular">Regular</option>
                    <option value="EV">EV Charging</option>
                </select>
            </div>
            <div class="form-group"><label>Rate (<?php echo CURRENCY; ?>/hr)</label><input type="number" step="0.01" name="hourly_rate" value="1.00" required></div>
            <div class="form-group" style="display:flex; align-items:flex-end;">
                <button type="submit" name="add_space" class="btn btn-primary btn-block">Add Space</button>
            </div>
        </div>
    </form>
</div>

<div class="panel">
    <div class="panel-title">
        All Parking Spaces
        <select onchange="window.location.href='parking_management.php?floor='+this.value" style="width:auto; padding:6px 10px;">
            <option value="0">All Floors</option>
            <?php foreach ($floorList as $f): ?>
                <option value="<?php echo $f['floor_id']; ?>" <?php echo $filterFloor==$f['floor_id']?'selected':''; ?>><?php echo htmlspecialchars($f['floor_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Space #</th><th>Floor</th><th>Type</th><th>Rate</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php while ($sp = $spaces->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($sp['space_number']); ?></strong></td>
                    <td><?php echo htmlspecialchars($sp['floor_name']); ?></td>
                    <td><span class="badge badge-<?php echo $sp['space_type']==='EV'?'ev':'regular'; ?>"><?php echo $sp['space_type']==='EV'?'⚡ EV':'Regular'; ?></span></td>
                    <td><?php echo CURRENCY . ' ' . number_format($sp['hourly_rate'],2); ?>/hr</td>
                    <td><span class="badge badge-<?php echo $sp['status']; ?>"><?php echo ucfirst($sp['status']); ?></span></td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <button type="button" class="btn btn-outline btn-sm" onclick="openEdit(<?php echo htmlspecialchars(json_encode($sp)); ?>)">Edit</button>
                            <form method="POST" onsubmit="return confirm('Delete this space?');" style="display:inline;">
                                <input type="hidden" name="space_id" value="<?php echo $sp['space_id']; ?>">
                                <button type="submit" name="delete_space" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
    <div class="panel" style="max-width:440px; width:90%;">
        <div class="panel-title">Edit Parking Space</div>
        <form method="POST" action="parking_management.php">
            <input type="hidden" name="space_id" id="edit_space_id">
            <div class="form-group"><label>Space Number</label><input type="text" name="space_number" id="edit_space_number" required></div>
            <div class="form-group">
                <label>Type</label>
                <select name="space_type" id="edit_space_type">
                    <option value="Regular">Regular</option>
                    <option value="EV">EV Charging</option>
                </select>
            </div>
            <div class="form-group"><label>Rate (<?php echo CURRENCY; ?>/hr)</label><input type="number" step="0.01" name="hourly_rate" id="edit_hourly_rate" required></div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="edit_status">
                    <option value="available">Available</option>
                    <option value="reserved">Reserved</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="submit" name="edit_space" class="btn btn-primary btn-block">Save Changes</button>
                <button type="button" class="btn btn-outline btn-block" onclick="closeEdit()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(sp) {
    document.getElementById('edit_space_id').value = sp.space_id;
    document.getElementById('edit_space_number').value = sp.space_number;
    document.getElementById('edit_space_type').value = sp.space_type;
    document.getElementById('edit_hourly_rate').value = sp.hourly_rate;
    document.getElementById('edit_status').value = sp.status;
    document.getElementById('editModal').style.display = 'flex';
}
function closeEdit() { document.getElementById('editModal').style.display = 'none'; }
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
