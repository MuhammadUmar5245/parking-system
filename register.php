<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isUserLoggedIn()) {
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($conn, $_POST['full_name'] ?? '');
    $email    = sanitize($conn, $_POST['email'] ?? '');
    $username = sanitize($conn, $_POST['username'] ?? '');
    $phone    = sanitize($conn, $_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || $email === '' || $username === '' || $phone === '' || $password === '') {
        $errors[] = "All fields are required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $check = $conn->query("SELECT user_id FROM users WHERE email='$email' OR username='$username'");
        if ($check && $check->num_rows > 0) {
            $errors[] = "An account with this email or username already exists.";
        }
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, username, password, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $fullName, $email, $username, $hashedPassword, $phone);
        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
        $stmt->close();
    }
}

$pageTitle = "Register";
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
    <div class="auth-card">
        <h2>Create Your Account</h2>
        <p class="subtitle">Join <?php echo SITE_NAME; ?> to start reserving parking spaces</p>

        <?php if ($success): ?>
            <div class="alert alert-success">✅ Registration successful! You can now <a href="login.php" style="color:#15803d; text-decoration:underline;">log in</a>.</div>
        <?php else: ?>
            <?php foreach ($errors as $err): ?>
                <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($err); ?></div>
            <?php endforeach; ?>

            <form method="POST" action="register.php">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" placeholder="+968 9xxx xxxx" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            </form>
        <?php endif; ?>

        <div class="auth-footer">Already have an account? <a href="login.php">Log in</a></div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
