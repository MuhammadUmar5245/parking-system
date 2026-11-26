# SmartPark Oman — Smart Indoor Parking Reservation System

A complete PHP + MySQL web application for reserving indoor mall parking spaces
(Regular and EV charging) with QR-code gate verification, built for XAMPP.

---

## ✅ Features

**User side**
- Register / Login
- Live parking availability by floor (🟩 Available / 🟥 Reserved / ⚡ EV)
- Select vehicle type (Regular / EV), add vehicles
- Choose a specific space, date, time, and duration
- Real-time price calculation
- Simulated online payment (no real transactions — safe for demo/academic use)
- Auto-generated unique QR code per reservation (offline PHP QR library, no internet needed)
- Reservation history, cancellation, QR re-download

**Admin side**
- Separate Admin Login
- Dashboard with live stats (occupancy, revenue, users, reservations)
- Add / edit / delete parking spaces
- Manage parking floors
- Manage reservations (change status — this automatically frees/reserves the space)
- View users (enable/disable accounts)
- View payment records
- QR code list + a **gate scan simulator** to test entry verification

---

## 🖥️ Requirements

- **XAMPP** (Apache + MySQL/MariaDB + PHP 7.4 or 8.x)
- A modern web browser

---

## 🚀 Installation (XAMPP)

1. **Copy the project folder**
   Extract this zip and place the `parking-system` folder inside your XAMPP `htdocs` directory, e.g.:
   ```
   C:\xampp\htdocs\parking-system\
   ```

2. **Start XAMPP**
   Open the XAMPP Control Panel and start **Apache** and **MySQL**.

3. **Create the database**
   - Open `http://localhost/phpmyadmin`
   - Click **New** to create a database (or just import — the SQL file creates it automatically)
   - Click the **Import** tab
   - Choose the file: `database/parking_system.sql`
   - Click **Go**

   This creates the `parking_system` database with all tables and seed data
   (3 floors, 30 parking spaces, 1 admin account, 1 demo user account).

4. **Check the database config**
   Open `config/db.php` — the defaults match a standard XAMPP install:
   ```php
   DB_HOST = localhost
   DB_USER = root
   DB_PASS = (empty)
   DB_NAME = parking_system
   ```
   If your MySQL root user has a password, update `DB_PASS` accordingly.

5. **Open the site**
   Visit: `http://localhost/parking-system/`

---

## 🔑 Default Login Credentials

| Role  | Username | Password  |
|-------|----------|-----------|
| Admin | `admin`  | `admin123`|
| User  | `user`   | `user123` |

Admin panel: `http://localhost/parking-system/admin/login.php`

---

## 📁 Project Structure

```
parking-system/
├── index.php                 Home page
├── about.php                 About page
├── register.php / login.php  User auth
├── logout.php
├── dashboard.php              User dashboard
├── availability.php           Public parking availability view
├── reserve.php                 Reservation flow (vehicle, floor, space, time)
├── payment.php                  Simulated payment + finalizes reservation
├── confirmation.php              Post-payment confirmation
├── qr_code.php                    QR code display for a reservation
├── my_reservations.php             Reservation history + cancel
│
├── admin/
│   ├── login.php / logout.php
│   ├── dashboard.php               Admin stats dashboard
│   ├── parking_management.php      Add/edit/delete spaces
│   ├── floors.php                  Manage floors
│   ├── reservations.php            Manage all reservations
│   ├── users.php                   Manage users
│   ├── payments.php                Payment records
│   ├── qrcodes.php                 QR list + gate scan simulator
│   └── includes/                   Admin header/footer
│
├── config/
│   ├── db.php                      DB connection
│   └── config.php                  Site constants (currency, mall name, etc.)
│
├── includes/
│   ├── auth.php                    Session/auth helpers
│   ├── header.php / footer.php     Public site layout
│   ├── qr_generator.php            QR image generator wrapper
│   └── qrlib/phpqrcode.php         Bundled offline QR code library (LGPL)
│
├── assets/
│   ├── css/style.css               Full design system
│   └── qrcodes/                    Generated QR PNG images (auto-created)
│
└── database/
    └── parking_system.sql          Full schema + seed data
```

---

## 🗄️ Database Schema

Eight related tables with foreign keys:
`users`, `admins`, `vehicles`, `parking_floors`, `parking_spaces`,
`reservations`, `payments`, `qrcodes`.

When a reservation is paid for, the system automatically:
1. Creates the `reservations` row (status = `confirmed`)
2. Sets the related `parking_spaces.status` to `reserved`
3. Creates a `payments` row (simulated, status = `success`)
4. Generates a unique QR code in `qrcodes`

When a reservation is cancelled (by user or admin), the space automatically
reverts to `available`.

---

## 💳 About the Simulated Payment

This project does **not** connect to a real payment gateway. The payment
form validates that the fields look like card details, then records a
`success` transaction with a generated reference number — exactly as
requested in the specification ("payment can be simulated within the
project"). No real card data is transmitted or stored anywhere.

---

## 📱 QR Codes

QR codes are generated **entirely offline** using a bundled, self-contained
PHP QR code library (`includes/qrlib/phpqrcode.php`) — no internet connection
or external API is required at runtime. Images are saved to
`assets/qrcodes/` and reused if already generated.

Use **Admin → QR Codes → Gate Scan Simulator** to simulate scanning a
customer's QR code at the entrance gate. It validates the code, checks the
reservation hasn't been cancelled, and prevents the same code from being
used twice.

---

## 🎨 Design Notes

- Fully responsive (desktop, tablet, mobile)
- Clear color coding: 🟩 green = available, 🟥 red = reserved, ⚡ purple/EV tag = EV charging
- Consistent sidebar-based admin panel
- Built with plain CSS (no frameworks) for full control and easy customization —
  see `assets/css/style.css`, all colors defined as CSS variables at the top

---

## ⚠️ Notes for Submission / Demo Use

- This is a self-contained demo/academic project. Change default admin/user
  passwords before any real-world deployment.
- `config/config.php` sets timezone to `Asia/Muscat` and currency to `OMR`
  (Omani Rial) to match the Oman context specified in the requirements.
- If you place the project in a different folder name than `parking-system`,
  update the `BASE_URL` constant in `config/config.php` accordingly.
