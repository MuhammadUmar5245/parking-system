<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$floors = $conn->query("SELECT * FROM parking_floors WHERE status='active' ORDER BY floor_level ASC");
$floorList = [];
while ($f = $floors->fetch_assoc()) { $floorList[] = $f; }

$selectedFloor = isset($_GET['floor']) ? (int)$_GET['floor'] : ($floorList[0]['floor_id'] ?? 0);

$pageTitle = "Parking Availability";
require_once __DIR__ . '/includes/header.php';
?>
<section class="section container">
    <div class="section-title">
        <h2>Parking Availability</h2>
        <p>Live status of every parking space across all floors</p>
    </div>

    <div class="floor-tabs">
        <?php foreach ($floorList as $f): ?>
            <a href="?floor=<?php echo $f['floor_id']; ?>" class="floor-tab <?php echo $f['floor_id']==$selectedFloor?'active':''; ?>">
                <?php echo htmlspecialchars($f['floor_name']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="legend">
        <div class="legend-item"><span class="legend-dot available"></span> Available</div>
        <div class="legend-item"><span class="legend-dot reserved"></span> Reserved</div>
        <div class="legend-item"><span class="legend-dot ev"></span> ⚡ EV Charging Space</div>
    </div>

    <div class="panel">
        <?php
        $stmt = $conn->prepare("SELECT * FROM parking_spaces WHERE floor_id = ? ORDER BY space_number ASC");
        $stmt->bind_param("i", $selectedFloor);
        $stmt->execute();
        $spaces = $stmt->get_result();

        if ($spaces->num_rows === 0):
        ?>
            <div class="empty-state">
                <div class="icon">🅿️</div>
                <p>No parking spaces configured on this floor yet.</p>
            </div>
        <?php else: ?>
        <div class="parking-grid">
            <?php while ($sp = $spaces->fetch_assoc()):
                $isEV = $sp['space_type'] === 'EV';
                $statusClass = $sp['status'] === 'reserved' ? 'reserved' : ($sp['status'] === 'maintenance' ? 'reserved' : '');
            ?>
                <div class="parking-space <?php echo $statusClass; ?> <?php echo $isEV?'ev-space':''; ?>">
                    <div class="space-icon"><?php echo $isEV ? '⚡' : '🚗'; ?></div>
                    <div class="space-number"><?php echo htmlspecialchars($sp['space_number']); ?></div>
                    <div class="space-type-tag">
                        <?php echo $sp['status']==='reserved' ? 'Reserved' : ($sp['status']==='maintenance' ? 'Closed' : ($isEV?'EV Available':'Available')); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="text-center mt-2">
        <?php if (isUserLoggedIn()): ?>
            <a href="reserve.php?floor=<?php echo $selectedFloor; ?>" class="btn btn-primary">Reserve a Space on This Floor →</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary">Log In to Reserve a Space →</a>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
