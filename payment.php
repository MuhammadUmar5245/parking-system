<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
requireUserLogin();

if (!isset($_SESSION['pending_reservation'])) {
    header("Location: " . BASE_URL . "reserve.php");
    exit();
}

$pending = $_SESSION['pending_reservation'];
$userId = $_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardName = sanitize($conn, $_POST['card_name'] ?? '');
    $cardNumber = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    $expiry = sanitize($conn, $_POST['expiry'] ?? '');
    $cvv = preg_replace('/\D/', '', $_POST['cvv'] ?? '');
    $method = sanitize($conn, $_POST['payment_method'] ?? 'Credit Card');

    if (strlen($cardNumber) < 12 || strlen($cvv) < 3 || $cardName === '' || $expiry === '') {
        $error = "Please fill in valid (simulated) card details.";
    } else {
        // Re-check space availability at time of payment (race condition safety)
        $conn->begin_transaction();
        try {
            $checkStmt = $conn->prepare("SELECT status FROM parking_spaces WHERE space_id=? FOR UPDATE");
            $checkStmt->bind_param("i", $pending['space_id']);
            $checkStmt->execute();
            $spaceStatus = $checkStmt->get_result()->fetch_assoc();

            if (!$spaceStatus || $spaceStatus['status'] !== 'available') {
                throw new Exception("This space was just reserved by someone else. Please choose another.");
            }

            // Insert reservation
            $insRes = $conn->prepare("INSERT INTO reservations (user_id, vehicle_id, space_id, reservation_date, start_time, end_time, duration_hours, total_price, status) VALUES (?,?,?,?,?,?,?,?,'confirmed')");
            $insRes->bind_param("iiisssdd", $userId, $pending['vehicle_id'], $pending['space_id'], $pending['reservation_date'], $pending['start_time'], $pending['end_time'], $pending['duration'], $pending['total_price']);
            $insRes->execute();
            $reservationId = $conn->insert_id;

            // Mark space as reserved
            $updSpace = $conn->prepare("UPDATE parking_spaces SET status='reserved' WHERE space_id=?");
            $updSpace->bind_param("i", $pending['space_id']);
            $updSpace->execute();

            // Insert payment (simulated - always success here since it's a demo gateway)
            $txnRef = generateTransactionRef();
            $last4 = substr($cardNumber, -4);
            $insPay = $conn->prepare("INSERT INTO payments (reservation_id, user_id, amount, payment_method, card_last4, payment_status, transaction_ref) VALUES (?,?,?,?,?,'success',?)");
            $insPay->bind_param("iidsss", $reservationId, $userId, $pending['total_price'], $method, $last4, $txnRef);
            $insPay->execute();

            // Generate QR code
            $qrValue = generateQRValue($reservationId);
            $insQR = $conn->prepare("INSERT INTO qrcodes (reservation_id, qr_code_value) VALUES (?, ?)");
            $insQR->bind_param("is", $reservationId, $qrValue);
            $insQR->execute();

            $conn->commit();
            unset($_SESSION['pending_reservation']);
            header("Location: " . BASE_URL . "confirmation.php?id=" . $reservationId);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

$pageTitle = "Payment";
require_once __DIR__ . '/includes/header.php';
?>
<section class="section container">
    <div class="section-title">
        <h2>Complete Your Payment</h2>
        <p>This is a simulated payment gateway — no real charges will be made</p>
    </div>

    <?php if ($error): ?><div class="alert alert-error" style="max-width:600px; margin:0 auto 20px;">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div style="display:grid; grid-template-columns: 1.2fr 1fr; gap:24px; max-width:900px; margin:0 auto;" class="payment-grid">
        <div class="panel">
            <div class="panel-title">💳 Card Details (Simulated)</div>
            <form method="POST" action="payment.php">
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method">
                        <option value="Credit Card">Credit Card</option>
                        <option value="Debit Card">Debit Card</option>
                        <option value="Wallet">Wallet</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Cardholder Name</label>
                    <input type="text" name="card_name" placeholder="As shown on card" required>
                </div>
                <div class="form-group">
                    <label>Card Number</label>
                    <input type="text" name="card_number" placeholder="4111 1111 1111 1111" maxlength="19" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Expiry (MM/YY)</label>
                        <input type="text" name="expiry" placeholder="12/28" maxlength="5" required>
                    </div>
                    <div class="form-group">
                        <label>CVV</label>
                        <input type="text" name="cvv" placeholder="123" maxlength="4" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Pay <?php echo CURRENCY . ' ' . number_format($pending['total_price'],2); ?></button>
                <p style="text-align:center; margin-top:12px; font-size:0.8rem; color:var(--text-muted);">🔒 Simulated secure checkout — for demo purposes only</p>
            </form>
        </div>

        <div class="panel">
            <div class="panel-title">Order Summary</div>
            <div class="summary-box">
                <div class="summary-row"><span class="label">Parking Space</span><span class="value"><?php echo htmlspecialchars($pending['space_number']); ?> <?php echo $pending['space_type']==='EV'?'⚡':''; ?></span></div>
                <div class="summary-row"><span class="label">Space Type</span><span class="value"><?php echo $pending['space_type']==='EV'?'EV Charging':'Regular'; ?></span></div>
                <div class="summary-row"><span class="label">Date</span><span class="value"><?php echo date('d M Y', strtotime($pending['reservation_date'])); ?></span></div>
                <div class="summary-row"><span class="label">Start Time</span><span class="value"><?php echo date('H:i', strtotime($pending['start_time'])); ?></span></div>
                <div class="summary-row"><span class="label">Duration</span><span class="value"><?php echo $pending['duration']; ?> hour(s)</span></div>
                <div class="summary-row total"><span class="label">Total</span><span class="value"><?php echo CURRENCY . ' ' . number_format($pending['total_price'],2); ?></span></div>
            </div>
        </div>
    </div>
</section>
<style>
@media (max-width: 800px) { .payment-grid { grid-template-columns: 1fr !important; } }
</style>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
