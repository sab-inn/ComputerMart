<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_helper.php';
verify_csrf();

require_role('admin');
$productId = (int)($_POST['product_id'] ?? 0);

$stmt = get_db()->prepare('SELECT is_active FROM products WHERE id = ?');
$stmt->execute([$productId]);
$row = $stmt->fetch();
if ($row) {
    $newState = $row['is_active'] ? 0 : 1;
    get_db()->prepare('UPDATE products SET is_active = ? WHERE id = ?')->execute([$newState, $productId]);
}

$view = build_admin_view('products');
require __DIR__ . '/../partials/admin_shell.php';
