<?php
/**
 * Global site configuration
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('SITE_NAME', 'SmartPark Oman');
define('MALL_NAME', 'Muscat Grand Mall');
define('CURRENCY', 'OMR');
define('BASE_URL', '/parking-system/'); // adjust if placed in a different subfolder

date_default_timezone_set('Asia/Muscat');

require_once __DIR__ . '/db.php';
