<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
requireUserLogin();

$malls = getMalls();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mallKey = $_POST['mall'] ?? '';
    if (isset($malls[$mallKey])) {
        $_SESSION['mall_key']  = $mallKey;
        $_SESSION['mall_name'] = $malls[$mallKey]['name'];
        header("Location: " . BASE_URL . "dashboard.php");
        exit();
    }
    $error = "Please select a valid mall.";
}

$current = $_SESSION['mall_key'] ?? '';
$pageTitle = "Select Mall";
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
    <div class="mall-select-card">
        <h2>Which mall are you visiting?</h2>
        <p class="subtitle">Select a mall to continue to your parking dashboard</p>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="select_mall.php">
            <div class="mall-grid">
                <?php foreach ($malls as $key => $mall): ?>
                    <label class="mall-option">
                        <input type="radio" name="mall" value="<?php echo $key; ?>" <?php echo $current === $key ? 'checked' : ''; ?> required>
                        <div class="mall-tile">
                            <div class="mall-icon"><?php echo $mall['icon']; ?></div>
                            <div class="mall-name"><?php echo htmlspecialchars($mall['name']); ?></div>
                            <div class="mall-location">📍 <?php echo htmlspecialchars($mall['location']); ?></div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Continue →</button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
