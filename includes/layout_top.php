<?php
require_once __DIR__ . '/auth.php';

$__user = current_user();
$__cartCount = 0;
if ($__user && in_array($__user['role'], ['buyer', 'admin'], true)) {
    $stmt = get_db()->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE buyer_id = ?');
    $stmt->execute([$__user['id']]);
    $__cartCount = (int)$stmt->fetchColumn();
}
$__title = isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - computemart' : 'computemart';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <title><?= $__title ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/htmx/2.0.10/htmx.min.js"></script>
</head>
<body>
    <header class="site-header">
        <div class="bar">
            <a class="brand" href="/">compute<span>mart</span></a>
            <div class="nav-links" style="margin-left:auto;">
                <?php if ($__user): ?>
                    <?php if ($__user['role'] === 'seller'): ?>
                        <a href="/seller/dashboard.php">My store</a>
                    <?php endif; ?>
                    <?php if ($__user['role'] === 'admin'): ?>
                        <a href="/admin/panel.php">Admin</a>
                    <?php endif; ?>
                    <a href="/orders/history.php">Orders</a>
                    <?php if (in_array($__user['role'], ['buyer', 'admin'], true)): ?>
                        <a href="/" class="cart-toggle">Cart<span class="cart-count" id="cart-count-badge"><?= $__cartCount ?></span></a>
                    <?php endif; ?>
                    <form method="post" action="/logout.php" style="margin:0;">
                        <?= csrf_field() ?>
                        <button type="submit" class="linklike">Log out</button>
                    </form>
                <?php else: ?>
                    <a href="/login.php">Log in</a>
                    <a href="/register.php" class="btn btn-accent btn-sm">Sign up</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="container page">
