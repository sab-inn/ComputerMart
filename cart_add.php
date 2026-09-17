<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart_helper.php';
verify_csrf();

$user = current_user();
if (!$user || !in_array($user['role'], ['buyer', 'admin'], true)) {
    http_response_code(403);
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$db = get_db();

$stmt = $db->prepare('SELECT * FROM products WHERE id = ? AND is_active = 1');
$stmt->execute([$productId]);
$product = $stmt->fetch();

if ($product) {
    $stmt = $db->prepare('SELECT * FROM cart_items WHERE buyer_id = ? AND product_id = ?');
    $stmt->execute([$user['id'], $productId]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ((int)$existing['quantity'] < (int)$product['stock']) {
            $db->prepare('UPDATE cart_items SET quantity = quantity + 1 WHERE id = ?')->execute([$existing['id']]);
        }
    } else {
        $db->prepare('INSERT INTO cart_items (buyer_id, product_id, quantity) VALUES (?, ?, 1)')
           ->execute([$user['id'], $productId]);
    }
}

render_cart_partial((int)$user['id']);
