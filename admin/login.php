<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (isAdminLoggedIn()) {
    header("Location: " . BASE_URL . "admin/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT admin_id, full_name, username, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_username'] = $admin['username'];
            header("Location: " . BASE_URL . "admin/dashboard.php");
            exit();
        } else {
            $error = "Invalid admin credentials.";
        }
    } else {
        $error = "Invalid admin credentials.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - <?php echo SITE_NAME; ?></title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="navbar-inner">
        <a href="<?php echo BASE_URL; ?>index.php" class="navbar-brand"><span class="icon">🅿️</span> <?php echo SITE_NAME; ?> <small style="font-size:0.7rem; opacity:0.7; font-weight:400;">Admin Portal</small></a>
        <div class="navbar-links"><a href="<?php echo BASE_URL; ?>index.php">← Back to Site</a></div>
    </div>
</nav>
<div class="auth-wrapper">
    <div class="auth-card">
        <h2>🔐 Admin Login</h2>
        <p class="subtitle">Restricted access — <?php echo MALL_NAME; ?> Management</p>

        <?php if ($error): ?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Admin Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-secondary btn-block">Log In to Admin Panel</button>
        </form>
        <div class="auth-footer" style="margin-top:14px;">
            <small>Demo login — username: <strong>admin</strong> / password: <strong>admin123</strong></small>
        </div>
    </div>
</div>
</body>
</html>
