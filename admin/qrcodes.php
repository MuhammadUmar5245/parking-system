<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$message = '';
$scanResult = null;

// Simulate gate scan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan_code'])) {
    $code = sanitize($conn, $_POST['qr_code_value']);

    $stmt = $conn->prepare("
        SELECT q.*, r.status as reservation_status, r.start_time, r.end_time, r.reservation_id,
               ps.space_number, u.full_name, v.plate_number
        FROM qrcodes q
        JOIN reservations r ON q.reservation_id = r.reservation_id
        JOIN parking_spaces ps ON r.space_id = ps.space_id
        JOIN users u ON r.user_id = u.user_id
        JOIN vehicles v ON r.vehicle_id = v.vehicle_id
        WHERE q.qr_code_value = ?
    ");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $qr = $stmt->get_result()->fetch_assoc();

    if (!$qr) {
        $scanResult = ['valid' => false, 'message' => 'QR code not found. Invalid or fraudulent code.'];
    } elseif ($qr['reservation_status'] === 'cancelled') {
        $scanResult = ['valid' => false, 'message' => 'This reservation has been cancelled.'];
    } elseif ($qr['is_scanned']) {
        $scanResult = ['valid' => false, 'message' => 'This QR code has already been used on ' . date('d M Y H:i', strtotime($qr['scanned_at'])) . '.'];
    } else {
        $upd = $conn->prepare("UPDATE qrcodes SET is_scanned=1, scanned_at=NOW() WHERE qrcode_id=?");
        $upd->bind_param("i", $qr['qrcode_id']);
        $upd->execute();
        $scanResult = [
            'valid' => true,
            'message' => 'Access granted. Welcome to ' . MALL_NAME . '.',
            'data' => $qr
        ];
    }
}

$qrcodes = $conn->query("
    SELECT q.*, r.status as reservation_status, ps.space_number, u.full_name
    FROM qrcodes q
    JOIN reservations r ON q.reservation_id = r.reservation_id
    JOIN parking_spaces ps ON r.space_id = ps.space_id
    JOIN users u ON r.user_id = u.user_id
    ORDER BY q.generated_at DESC
");

$pageTitle = "QR Code Management";
require_once __DIR__ . '/includes/admin_header.php';
?>
<div class="page-header">
    <div><h1>QR Code Management</h1><p>Simulate gate scans and monitor reservation QR codes</p></div>
</div>

<div class="panel">
    <div class="panel-title">🚪 Gate Scan Simulator</div>
    <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:16px;">
        Paste or type a reservation QR code value below to simulate scanning it at the entrance gate.
    </p>
    <form method="POST" style="display:flex; gap:10px;">
        <input type="text" name="qr_code_value" placeholder="e.g. PARK-20260820-000001-A1B2C3" style="flex:1; padding:11px 14px; border:1.5px solid var(--border); border-radius:8px;" required>
        <button type="submit" name="scan_code" class="btn btn-primary">Scan Code</button>
    </form>

    <?php if ($scanResult): ?>
        <div class="alert <?php echo $scanResult['valid'] ? 'alert-success' : 'alert-error'; ?>" style="margin-top:16px;">
            <?php echo $scanResult['valid'] ? '✅' : '❌'; ?> <?php echo htmlspecialchars($scanResult['message']); ?>
        </div>
        <?php if ($scanResult['valid']): ?>
            <div class="summary-box mt-2">
                <div class="summary-row"><span class="label">Visitor</span><span class="value"><?php echo htmlspecialchars($scanResult['data']['full_name']); ?></span></div>
                <div class="summary-row"><span class="label">Vehicle</span><span class="value"><?php echo htmlspecialchars($scanResult['data']['plate_number']); ?></span></div>
                <div class="summary-row"><span class="label">Space</span><span class="value"><?php echo htmlspecialchars($scanResult['data']['space_number']); ?></span></div>
                <div class="summary-row"><span class="label">Valid Until</span><span class="value"><?php echo date('d M Y H:i', strtotime($scanResult['data']['end_time'])); ?></span></div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-title">All QR Codes</div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>QR Value</th><th>User</th><th>Space</th><th>Reservation Status</th><th>Scanned?</th><th>Generated</th></tr></thead>
            <tbody>
            <?php while ($q = $qrcodes->fetch_assoc()): ?>
                <tr>
                    <td><code style="font-size:0.78rem;"><?php echo htmlspecialchars($q['qr_code_value']); ?></code></td>
                    <td><?php echo htmlspecialchars($q['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($q['space_number']); ?></td>
                    <td><span class="badge badge-<?php echo $q['reservation_status']; ?>"><?php echo ucfirst($q['reservation_status']); ?></span></td>
                    <td><?php echo $q['is_scanned'] ? '✅ Scanned' : '⏳ Not yet'; ?></td>
                    <td><?php echo date('d M Y, H:i', strtotime($q['generated_at'])); ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
