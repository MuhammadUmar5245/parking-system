<?php
/**
 * Authentication helper functions
 */

function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireUserLogin() {
    if (!isUserLoggedIn()) {
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header("Location: " . BASE_URL . "admin/login.php");
        exit();
    }
}

function sanitize($conn, $value) {
    return $conn->real_escape_string(trim($value));
}

function generateTransactionRef() {
    return 'TXN' . strtoupper(bin2hex(random_bytes(6)));
}

function generateQRValue($reservationId) {
    return 'PARK-' . date('Ymd') . '-' . str_pad($reservationId, 6, '0', STR_PAD_LEFT) . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * Malls available for selection after login
 */
function getMalls() {
    return [
        'oman_mall'    => ['name' => 'Mall of Oman',   'location' => 'Bausher, Muscat', 'icon' => '🏬'],
        'muscat_mall'  => ['name' => 'Muscat Mall',    'location' => 'Al Mabelah, Muscat', 'icon' => '🛍️'],
        'avenues_mall' => ['name' => 'Avenues Mall',   'location' => 'Al Ghubra, Muscat', 'icon' => '🏢'],
    ];
}

function hasSelectedMall() {
    return isset($_SESSION['mall_key']) && array_key_exists($_SESSION['mall_key'], getMalls());
}

function requireMallSelected() {
    if (!hasSelectedMall()) {
        header("Location: " . BASE_URL . "select_mall.php");
        exit();
    }
}
