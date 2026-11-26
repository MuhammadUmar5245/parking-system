<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
requireUserLogin();

$reservationId = (int)($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT r.*, ps.space_number, ps.space_type, pf.floor_name, v.plate_number, v.vehicle_type,
           p.transaction_ref, p.amount, p.payment_method, p.paid_at
    FROM reservations r
    JOIN parking_spaces ps ON r.space_id = ps.space_id
    JOIN parking_floors pf ON ps.floor_id = pf.floor_id
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    LEFT JOIN payments p ON p.reservation_id = r.reservation_id
    WHERE r.reservation_id = ? AND r.user_id = ?
");
$stmt->bind_param("ii", $reservationId, $userId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

$pageTitle = "Reservation Confirmed";
require_once __DIR__ . '/includes/header.php';
?>
<section class="section container">
    <div class="text-center mb-2">
        <div style="font-size:3.5rem;">✅</div>
        <h2 style="color:var(--primary); font-size:1.8rem; margin:10px 0 4px;">Reservation Confirmed!</h2>
        <p style="color:var(--text-muted);">Your payment was successful and your parking space is reserved.</p>
    </div>

    <div class="panel" style="max-width:640px; margin:0 auto;">
        <div class="panel-title">Reservation Details</div>
        <div class="summary-box">
            <div class="summary-row"><span class="label">Reservation ID</span><span class="value">#<?php echo str_pad($res['reservation_id'],6,'0',STR_PAD_LEFT); ?></span></div>
            <div class="summary-row"><span class="label">Parking Space</span><span class="value"><?php echo htmlspecialchars($res['space_number']); ?> <?php echo $res['space_type']==='EV'?'⚡ EV Charging':''; ?></span></div>
            <div class="summary-row"><span class="label">Floor</span><span class="value"><?php echo htmlspecialchars($res['floor_name']); ?></span></div>
            <div class="summary-row"><span class="label">Vehicle</span><span class="value"><?php echo htmlspecialchars($res['plate_number']); ?> (<?php echo $res['vehicle_type']; ?>)</span></div>
            <div class="summary-row"><span class="label">Date & Time</span><span class="value"><?php echo date('d M Y, H:i', strtotime($res['start_time'])); ?></span></div>
            <div class="summary-row"><span class="label">Duration</span><span class="value"><?php echo $res['duration_hours']; ?> hour(s)</span></div>
            <div class="summary-row"><span class="label">Transaction Ref</span><span class="value"><?php echo htmlspecialchars($res['transaction_ref']); ?></span></div>
            <div class="summary-row total"><span class="label">Amount Paid</span><span class="value"><?php echo CURRENCY . ' ' . number_format($res['amount'],2); ?></span></div>
        </div>

        <div class="text-center mt-2">
            <a href="qr_code.php?id=<?php echo $reservationId; ?>" class="btn btn-primary">📱 View My QR Code</a>
            <a href="my_reservations.php" class="btn btn-outline">View All Reservations</a>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
