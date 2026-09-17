<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/seller_helper.php';
verify_csrf();

$user = require_role('seller');
$productId = (int)($_POST['product_id'] ?? 0);
$price = (float)($_POST['price'] ?? 0);
$stock = (int)($_POST['stock'] ?? 0);

$stmt = get_db()->prepare('SELECT id FROM products WHERE id = ? AND seller_id = ?');
$stmt->execute([$productId, $user['id']]);
if ($stmt->fetch()) {
    $upd = get_db()->prepare('UPDATE products SET price = ?, stock = ? WHERE id = ?');
    $upd->execute([$price, $stock, $productId]);
}

render_seller_products_partial((int)$user['id']);
