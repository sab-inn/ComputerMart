<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    header('Location: /');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'buyer';
    $storeName = trim($_POST['store_name'] ?? '');

    if ($fullName === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    // The dropdown only ever renders buyer/seller, but nothing stops
    // someone from editing the submitted value by hand.
    if (!in_array($role, ['buyer', 'seller'], true)) $errors[] = 'Invalid role.';

    $db = get_db();
    if (empty($errors)) {
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with that email already exists.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare(
            'INSERT INTO users (full_name, email, password_hash, role, store_name) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$fullName, $email, $hash, $role, $role === 'seller' ? $storeName : null]);
        login_user((int)$db->lastInsertId());
        header('Location: /');
        exit;
    }
}

$pageTitle = 'Sign up';
require __DIR__ . '/includes/layout_top.php';
?>
<div class="form-card">
    <h1>Create an account</h1>
    <p class="sub">Buy computer parts, or start selling your own.</p>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>
        <div class="field">
            <label>Full name</label>
            <input type="text" name="full_name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Email</label>
            <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Password</label>
            <input type="password" name="password" required minlength="8">
            <div class="hint">At least 8 characters.</div>
        </div>
        <div class="field">
            <label>I'm signing up as a</label>
            <select name="role" id="role-select">
                <option value="buyer">Buyer</option>
                <option value="seller">Seller</option>
            </select>
        </div>
        <div class="field" id="store-name-field" style="display:none;">
            <label>Store name</label>
            <input type="text" name="store_name" value="<?= htmlspecialchars($_POST['store_name'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-accent btn-block">Create account</button>
    </form>

    <p style="text-align:center; font-size:13px; margin-top:16px;">
        Already have an account, or want to use Google?
        <a href="/login.php">Log in</a>
    </p>
</div>
<script>
    // The only bit of hand-written JS in this project: toggle one field
    // based on a dropdown.
    const roleSelect = document.getElementById('role-select');
    const storeField = document.getElementById('store-name-field');
    function syncStoreField() {
        storeField.style.display = roleSelect.value === 'seller' ? 'block' : 'none';
    }
    roleSelect.addEventListener('change', syncStoreField);
    syncStoreField();
</script>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
