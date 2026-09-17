<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    header('Location: /');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = get_db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && (int)$user['is_locked'] === 1) {
        $error = 'This account has been disabled by an admin.';
    } elseif ($user && $user['password_hash'] && password_verify($password, $user['password_hash'])) {
        login_user((int)$user['id']);
        header('Location: /');
        exit;
    } else {
        $error = 'Incorrect email or password.';
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/layout_top.php';
?>
<div class="form-card">
    <h1>Welcome back</h1>
    <p class="sub">Log in to your computemart account.</p>

    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>
        <div class="field">
            <label>Email</label>
            <input type="email" name="email" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-accent btn-block">Log in</button>
    </form>

    <?php if (GOOGLE_CLIENT_ID !== ''): ?>
    <div class="divider-text">or</div>
    <a href="/google_login.php" class="btn btn-outline btn-block">Continue with Google</a>
    <?php endif; ?>

    <p style="text-align:center; font-size:13px; margin-top:16px;">
        New here? <a href="/register.php">Create an account</a>
    </p>
</div>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>

<script>
    console.log("Login info: ")
    console.log("Admin: admin@computemart.local / Admin@12345")
    console.log("Seller: seller@computemart.local / Seller@12345")
    </script>

