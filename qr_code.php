<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/qr_generator.php';
requireUserLogin();

$reservationId = (int)($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT r.*, ps.space_number, ps.space_type, pf.floor_name, v.plate_number,
           q.qr_code_value, q.is_scanned, q.scanned_at
    FROM reservations r
    JOIN parking_spaces ps ON r.space_id = ps.space_id
    JOIN parking_floors pf ON ps.floor_id = pf.floor_id
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    LEFT JOIN qrcodes q ON q.reservation_id = r.reservation_id
    WHERE r.reservation_id = ? AND r.user_id = ?
");
$stmt->bind_param("ii", $reservationId, $userId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res || !$res['qr_code_value']) {
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

$qrImagePath = generateQRImage($res['qr_code_value'], $reservationId);

$pageTitle = "QR Code";
require_once __DIR__ . '/includes/header.php';
?>
<section class="section container">
    <div class="section-title">
        <h2>Your Entry QR Code</h2>
        <p>Show this at the parking entrance gate to verify your reservation</p>
    </div>

    <div class="qr-box">
        <?php if ($res['is_scanned']): ?>
            <div class="alert alert-info" style="margin-bottom:20px;">✅ Already scanned at gate on <?php echo date('d M Y, H:i', strtotime($res['scanned_at'])); ?></div>
        <?php elseif ($res['status'] === 'cancelled'): ?>
            <div class="alert alert-error" style="margin-bottom:20px;">This reservation was cancelled and is no longer valid.</div>
        <?php endif; ?>

        <img src="<?php echo BASE_URL . $qrImagePath; ?>" alt="QR Code" width="240" height="240">

        <div class="qr-code-value"><?php echo htmlspecialchars($res['qr_code_value']); ?></div>

        <div style="text-align:left; margin-top:20px; font-size:0.9rem;">
            <div class="summary-row"><span class="label">Space</span><span class="value"><?php echo htmlspecialchars($res['space_number']); ?> <?php echo $res['space_type']==='EV'?'⚡':''; ?></span></div>
            <div class="summary-row"><span class="label">Floor</span><span class="value"><?php echo htmlspecialchars($res['floor_name']); ?></span></div>
            <div class="summary-row"><span class="label">Vehicle</span><span class="value"><?php echo htmlspecialchars($res['plate_number']); ?></span></div>
            <div class="summary-row"><span class="label">Valid</span><span class="value"><?php echo date('d M Y H:i', strtotime($res['start_time'])); ?> – <?php echo date('H:i', strtotime($res['end_time'])); ?></span></div>
            <div class="summary-row"><span class="label">Status</span><span class="value"><span class="badge badge-<?php echo $res['status']; ?>"><?php echo ucfirst($res['status']); ?></span></span></div>
        </div>

        <a href="my_reservations.php" class="btn btn-outline btn-block mt-2">← Back to My Reservations</a>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
