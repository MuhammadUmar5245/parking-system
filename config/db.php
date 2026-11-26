<?php
/**
 * Database Connection
 * Smart Indoor Parking Reservation System
 * XAMPP Default: host=localhost, user=root, password=""
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'parking_system');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error .
        "<br><br>Please make sure:<br>1. XAMPP MySQL is running<br>2. You imported database/parking_system.sql<br>3. config/db.php credentials match your MySQL setup");
}

$conn->set_charset("utf8mb4");
