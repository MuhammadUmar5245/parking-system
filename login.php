<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isUserLoggedIn()) {
    header("Location: " . BASE_URL . "select_mall.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT user_id, full_name, username, password, status FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if ($user['status'] !== 'active') {
            $error = "Your account has been disabled. Please contact support.";
        } elseif (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['username'] = $user['username'];
            unset($_SESSION['mall_key'], $_SESSION['mall_name']); // ask for mall on every login
            header("Location: " . BASE_URL . "select_mall.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }
    $stmt->close();
}

$pageTitle = "Login";
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
    <div class="auth-card">
        <h2>Welcome Back</h2>
        <p class="subtitle">Log in to manage your parking reservations</p>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Log In</button>
        </form>

        <div class="auth-footer">Don't have an account? <a href="register.php">Register here</a></div>
        <div class="auth-footer" style="margin-top:6px;">
            <small>Demo login — username: <strong>user</strong> / password: <strong>user123</strong></small>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
