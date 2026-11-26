-- ============================================================
-- Smart Indoor Parking Reservation System
-- Database: parking_system
-- For: XAMPP (MySQL/MariaDB) - phpMyAdmin compatible
-- ============================================================

CREATE DATABASE IF NOT EXISTS parking_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE parking_system;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active','disabled') DEFAULT 'active'
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: admins
-- ============================================================
CREATE TABLE admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: vehicles
-- ============================================================
CREATE TABLE vehicles (
    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plate_number VARCHAR(20) NOT NULL,
    vehicle_type ENUM('Regular','EV') NOT NULL,
    make_model VARCHAR(100),
    color VARCHAR(30),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: parking_floors
-- ============================================================
CREATE TABLE parking_floors (
    floor_id INT AUTO_INCREMENT PRIMARY KEY,
    floor_name VARCHAR(50) NOT NULL,
    floor_level INT NOT NULL,
    description VARCHAR(255),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: parking_spaces
-- ============================================================
CREATE TABLE parking_spaces (
    space_id INT AUTO_INCREMENT PRIMARY KEY,
    floor_id INT NOT NULL,
    space_number VARCHAR(20) NOT NULL,
    space_type ENUM('Regular','EV') NOT NULL DEFAULT 'Regular',
    has_charger TINYINT(1) DEFAULT 0,
    hourly_rate DECIMAL(10,2) NOT NULL DEFAULT 1.500,
    status ENUM('available','reserved','maintenance') DEFAULT 'available',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (floor_id) REFERENCES parking_floors(floor_id) ON DELETE CASCADE,
    UNIQUE KEY unique_space_per_floor (floor_id, space_number)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: reservations
-- ============================================================
CREATE TABLE reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    space_id INT NOT NULL,
    reservation_date DATE NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    duration_hours DECIMAL(5,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending','confirmed','cancelled','completed') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE,
    FOREIGN KEY (space_id) REFERENCES parking_spaces(space_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: payments
-- ============================================================
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Credit Card','Debit Card','Wallet') DEFAULT 'Credit Card',
    card_last4 VARCHAR(4),
    payment_status ENUM('pending','success','failed','refunded') DEFAULT 'pending',
    transaction_ref VARCHAR(50) UNIQUE,
    paid_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: qrcodes
-- ============================================================
CREATE TABLE qrcodes (
    qrcode_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL UNIQUE,
    qr_code_value VARCHAR(100) NOT NULL UNIQUE,
    qr_image_path VARCHAR(255),
    is_scanned TINYINT(1) DEFAULT 0,
    scanned_at DATETIME NULL,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default Admin (username: admin / password: admin123)
INSERT INTO admins (full_name, username, password, email) VALUES
('System Administrator', 'admin', '$2b$10$2fBYqJdwRWj8Ce6f4vU6fOuFvNVrV1YqBHEDebLFRh.tdOtkuAt3G', 'admin@mallparking.om');

-- Default Test User (username: user / password: user123)
INSERT INTO users (full_name, email, username, password, phone) VALUES
('Test User', 'user@example.com', 'user', '$2b$10$daLvOggyBCjQWz54.pQFDOx./mrHda2WnAQcZUjGB0lPjSo7DhNhq', '+968 9123 4567');

-- Parking Floors
INSERT INTO parking_floors (floor_name, floor_level, description) VALUES
('Ground Floor', 0, 'Main entrance level parking'),
('Level 1', 1, 'First floor parking'),
('Level 2', 2, 'Second floor parking - EV focused');

-- Parking Spaces - Ground Floor (mix)
INSERT INTO parking_spaces (floor_id, space_number, space_type, has_charger, hourly_rate, status) VALUES
(1,'G-01','Regular',0,1.000,'available'),
(1,'G-02','Regular',0,1.000,'available'),
(1,'G-03','Regular',0,1.000,'reserved'),
(1,'G-04','EV',1,1.500,'available'),
(1,'G-05','EV',1,1.500,'available'),
(1,'G-06','Regular',0,1.000,'available'),
(1,'G-07','Regular',0,1.000,'available'),
(1,'G-08','EV',1,1.500,'reserved'),
(1,'G-09','Regular',0,1.000,'available'),
(1,'G-10','Regular',0,1.000,'available');

-- Parking Spaces - Level 1
INSERT INTO parking_spaces (floor_id, space_number, space_type, has_charger, hourly_rate, status) VALUES
(2,'L1-01','Regular',0,1.000,'available'),
(2,'L1-02','Regular',0,1.000,'available'),
(2,'L1-03','Regular',0,1.000,'available'),
(2,'L1-04','Regular',0,1.000,'reserved'),
(2,'L1-05','EV',1,1.500,'available'),
(2,'L1-06','EV',1,1.500,'available'),
(2,'L1-07','Regular',0,1.000,'available'),
(2,'L1-08','Regular',0,1.000,'available'),
(2,'L1-09','Regular',0,1.000,'available'),
(2,'L1-10','EV',1,1.500,'available');

-- Parking Spaces - Level 2 (EV Focused)
INSERT INTO parking_spaces (floor_id, space_number, space_type, has_charger, hourly_rate, status) VALUES
(3,'L2-01','EV',1,1.500,'available'),
(3,'L2-02','EV',1,1.500,'available'),
(3,'L2-03','EV',1,1.500,'available'),
(3,'L2-04','EV',1,1.500,'available'),
(3,'L2-05','EV',1,1.500,'reserved'),
(3,'L2-06','Regular',0,1.000,'available'),
(3,'L2-07','Regular',0,1.000,'available'),
(3,'L2-08','Regular',0,1.000,'available'),
(3,'L2-09','Regular',0,1.000,'available'),
(3,'L2-10','EV',1,1.500,'available');
