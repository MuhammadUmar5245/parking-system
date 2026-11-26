<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
requireUserLogin();

$userId = $_SESSION['user_id'];
$error = '';

// Handle new vehicle quick-add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
    $plate = sanitize($conn, $_POST['plate_number']);
    $vtype = sanitize($conn, $_POST['vehicle_type']);
    $model = sanitize($conn, $_POST['make_model']);
    $color = sanitize($conn, $_POST['color']);
    if ($plate !== '' && in_array($vtype, ['Regular','EV'])) {
        $stmt = $conn->prepare("INSERT INTO vehicles (user_id, plate_number, vehicle_type, make_model, color) VALUES (?,?,?,?,?)");
        $stmt->bind_param("issss", $userId, $plate, $vtype, $model, $color);
        $stmt->execute();
    }
}

// Handle reservation submission -> go to payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reservation'])) {
    $vehicleId = (int)$_POST['vehicle_id'];
    $spaceId = (int)$_POST['space_id'];
    $date = sanitize($conn, $_POST['reservation_date']);
    $startTime = sanitize($conn, $_POST['start_time']);
    $duration = (float)$_POST['duration'];

    $startDateTime = $date . ' ' . $startTime . ':00';
    $endDateTime = date('Y-m-d H:i:s', strtotime($startDateTime . " +{$duration} hours"));

    // Verify space availability
    $checkStmt = $conn->prepare("SELECT status, hourly_rate, space_number, space_type FROM parking_spaces WHERE space_id=?");
    $checkStmt->bind_param("i", $spaceId);
    $checkStmt->execute();
    $spaceData = $checkStmt->get_result()->fetch_assoc();

    if (!$spaceData || $spaceData['status'] !== 'available') {
        $error = "Sorry, this space is no longer available. Please choose another.";
    } elseif (strtotime($startDateTime) < time() - 300) {
        $error = "Reservation start time cannot be in the past.";
    } else {
        $rate = $spaceData['hourly_rate'];
        $totalPrice = round($rate * $duration, 2);

        $_SESSION['pending_reservation'] = [
            'vehicle_id' => $vehicleId,
            'space_id' => $spaceId,
            'space_number' => $spaceData['space_number'],
            'space_type' => $spaceData['space_type'],
            'reservation_date' => $date,
            'start_time' => $startDateTime,
            'end_time' => $endDateTime,
            'duration' => $duration,
            'total_price' => $totalPrice
        ];
        header("Location: " . BASE_URL . "payment.php");
        exit();
    }
}

// Data for the form
$floors = $conn->query("SELECT * FROM parking_floors WHERE status='active' ORDER BY floor_level ASC");
$floorList = [];
while ($f = $floors->fetch_assoc()) { $floorList[] = $f; }
$selectedFloor = isset($_GET['floor']) ? (int)$_GET['floor'] : ($floorList[0]['floor_id'] ?? 0);

$vehStmt = $conn->prepare("SELECT * FROM vehicles WHERE user_id=? ORDER BY vehicle_id DESC");
$vehStmt->bind_param("i", $userId);
$vehStmt->execute();
$vehicles = $vehStmt->get_result();
$vehicleList = [];
while ($v = $vehicles->fetch_assoc()) { $vehicleList[] = $v; }

$pageTitle = "New Reservation";
require_once __DIR__ . '/includes/header.php';
?>
<div class="dashboard-wrap">
    <aside class="sidebar">
        <div class="sidebar-title">User Menu</div>
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="availability.php">🅿️ Parking Availability</a>
        <a href="reserve.php" class="active">➕ New Reservation</a>
        <a href="my_reservations.php">📋 My Reservations</a>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <div><h1>New Parking Reservation</h1><p>Choose your vehicle, space, and time</p></div>
        </div>

        <?php if ($error): ?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="panel">
            <div class="panel-title">Step 1 — Select Your Vehicle</div>
            <?php if (empty($vehicleList)): ?>
                <p style="color:var(--text-muted); margin-bottom:16px;">You haven't added a vehicle yet. Add one below to continue.</p>
            <?php endif; ?>

            <details <?php echo empty($vehicleList) ? 'open' : ''; ?> style="margin-bottom:16px;">
                <summary style="cursor:pointer; color:var(--accent); font-weight:600;">+ Add a new vehicle</summary>
                <form method="POST" action="reserve.php?floor=<?php echo $selectedFloor; ?>" style="margin-top:14px;">
                    <div class="form-row">
                        <div class="form-group"><label>Plate Number</label><input type="text" name="plate_number" required></div>
                        <div class="form-group">
                            <label>Vehicle Type</label>
                            <select name="vehicle_type" required>
                                <option value="Regular">Regular Vehicle</option>
                                <option value="EV">Electric Vehicle (EV)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Make / Model (optional)</label><input type="text" name="make_model"></div>
                        <div class="form-group"><label>Color (optional)</label><input type="text" name="color"></div>
                    </div>
                    <button type="submit" name="add_vehicle" class="btn btn-secondary btn-sm">Save Vehicle</button>
                </form>
            </details>
        </div>

        <form method="POST" action="reserve.php" id="reservationForm">
        <div class="panel">
            <div class="panel-title">Step 2 — Choose Vehicle & Floor</div>
            <div class="form-row">
                <div class="form-group">
                    <label>Select Vehicle</label>
                    <select name="vehicle_id" required>
                        <option value="">-- Choose a vehicle --</option>
                        <?php foreach ($vehicleList as $v): ?>
                            <option value="<?php echo $v['vehicle_id']; ?>">
                                <?php echo htmlspecialchars($v['plate_number']) . ' (' . $v['vehicle_type'] . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Floor</label>
                    <select name="floor_select" id="floorSelect" onchange="window.location.href='reserve.php?floor='+this.value">
                        <?php foreach ($floorList as $f): ?>
                            <option value="<?php echo $f['floor_id']; ?>" <?php echo $f['floor_id']==$selectedFloor?'selected':''; ?>>
                                <?php echo htmlspecialchars($f['floor_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-title">Step 3 — Select an Available Space</div>
            <div class="legend">
                <div class="legend-item"><span class="legend-dot available"></span> Available</div>
                <div class="legend-item"><span class="legend-dot reserved"></span> Reserved (unavailable)</div>
                <div class="legend-item"><span class="legend-dot ev"></span> ⚡ EV Charging</div>
            </div>
            <?php
            $spStmt = $conn->prepare("SELECT * FROM parking_spaces WHERE floor_id=? ORDER BY space_number ASC");
            $spStmt->bind_param("i", $selectedFloor);
            $spStmt->execute();
            $spacesResult = $spStmt->get_result();
            ?>
            <div class="parking-grid" id="spaceGrid">
                <?php while ($sp = $spacesResult->fetch_assoc()):
                    $isEV = $sp['space_type'] === 'EV';
                    $unavailable = $sp['status'] !== 'available';
                ?>
                    <div class="parking-space <?php echo $unavailable?'reserved disabled':''; ?> <?php echo $isEV?'ev-space':''; ?>"
                         data-id="<?php echo $sp['space_id']; ?>"
                         data-rate="<?php echo $sp['hourly_rate']; ?>"
                         data-number="<?php echo htmlspecialchars($sp['space_number']); ?>"
                         <?php echo $unavailable ? '' : 'onclick="selectSpace(this)"'; ?>>
                        <div class="space-icon"><?php echo $isEV ? '⚡' : '🚗'; ?></div>
                        <div class="space-number"><?php echo htmlspecialchars($sp['space_number']); ?></div>
                        <div class="space-type-tag"><?php echo $unavailable ? 'Reserved' : ($isEV?'EV · '.CURRENCY.' '.$sp['hourly_rate'].'/hr':CURRENCY.' '.$sp['hourly_rate'].'/hr'); ?></div>
                    </div>
                <?php endwhile; ?>
            </div>
            <input type="hidden" name="space_id" id="space_id" required>
        </div>

        <div class="panel">
            <div class="panel-title">Step 4 — Date, Time & Duration</div>
            <div class="form-row">
                <div class="form-group">
                    <label>Reservation Date</label>
                    <input type="date" name="reservation_date" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="start_time" required>
                </div>
            </div>
            <div class="form-group">
                <label>Duration (hours)</label>
                <select name="duration" id="duration" onchange="updatePrice()" required>
                    <option value="1">1 hour</option>
                    <option value="2" selected>2 hours</option>
                    <option value="3">3 hours</option>
                    <option value="4">4 hours</option>
                    <option value="6">6 hours</option>
                    <option value="8">8 hours (full day)</option>
                </select>
            </div>
        </div>

        <div class="panel">
            <div class="panel-title">Reservation Summary</div>
            <div class="summary-box">
                <div class="summary-row"><span class="label">Selected Space</span><span class="value" id="sumSpace">— Select a space above —</span></div>
                <div class="summary-row"><span class="label">Rate</span><span class="value" id="sumRate">—</span></div>
                <div class="summary-row"><span class="label">Duration</span><span class="value" id="sumDuration">2 hours</span></div>
                <div class="summary-row total"><span class="label">Total Price</span><span class="value" id="sumTotal">—</span></div>
            </div>
            <button type="submit" name="confirm_reservation" class="btn btn-primary btn-block mt-2" id="submitBtn" disabled>Proceed to Payment →</button>
        </div>
        </form>
    </main>
</div>

<script>
let selectedRate = 0;
function selectSpace(el) {
    document.querySelectorAll('.parking-space').forEach(s => s.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('space_id').value = el.dataset.id;
    document.getElementById('sumSpace').textContent = el.dataset.number;
    selectedRate = parseFloat(el.dataset.rate);
    document.getElementById('sumRate').textContent = '<?php echo CURRENCY; ?> ' + selectedRate.toFixed(2) + ' / hour';
    document.getElementById('submitBtn').disabled = false;
    updatePrice();
}
function updatePrice() {
    const duration = parseFloat(document.getElementById('duration').value);
    document.getElementById('sumDuration').textContent = duration + ' hour' + (duration>1?'s':'');
    if (selectedRate > 0) {
        document.getElementById('sumTotal').textContent = '<?php echo CURRENCY; ?> ' + (selectedRate * duration).toFixed(2);
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
