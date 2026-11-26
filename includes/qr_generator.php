<?php
/**
 * QR Code Generator Helper
 * Uses bundled phpqrcode library (offline, no external API calls)
 */
require_once __DIR__ . '/qrlib/phpqrcode.php';

/**
 * Generates a QR code PNG image for the given value and saves it to disk.
 * Returns the relative path (from project root) to the saved image.
 */
function generateQRImage($value, $reservationId) {
    $dir = __DIR__ . '/../assets/qrcodes/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = 'qr_' . $reservationId . '.png';
    $filepath = $dir . $filename;

    if (!file_exists($filepath)) {
        QRcode::png($value, $filepath, QR_ECLEVEL_M, 6, 2);
    }

    return 'assets/qrcodes/' . $filename;
}
