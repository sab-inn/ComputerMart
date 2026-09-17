<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart_helper.php';
verify_csrf();

$user = require_login();
$db = get_db();

$cartItemId = (int)($_POST['cart_item_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 0);

$stmt = $db->prepare(
    'SELECT ci.*, pr.stock FROM cart_items ci JOIN products pr ON pr.id = ci.product_id
     WHERE ci.id = ? AND ci.buyer_id = ?'
);
$stmt->execute([$cartItemId, $user['id']]);
$item = $stmt->fetch();

if ($item) {
    if ($quantity <= 0) {
        $db->prepare('DELETE FROM cart_items WHERE id = ?')->execute([$cartItemId]);
    } else {
        $quantity = min($quantity, (int)$item['stock']);
        $db->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?')->execute([$quantity, $cartItemId]);
    }
}

render_cart_partial((int)$user['id']);
