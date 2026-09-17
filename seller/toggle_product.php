<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/seller_helper.php';
verify_csrf();

$user = require_role('seller');
$productId = (int)($_POST['product_id'] ?? 0);

$stmt = get_db()->prepare('SELECT id, is_active FROM products WHERE id = ? AND seller_id = ?');
$stmt->execute([$productId, $user['id']]);
$product = $stmt->fetch();

if ($product) {
    $newState = $product['is_active'] ? 0 : 1;
    get_db()->prepare('UPDATE products SET is_active = ? WHERE id = ?')->execute([$newState, $productId]);
}

render_seller_products_partial((int)$user['id']);
