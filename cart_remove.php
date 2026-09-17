<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart_helper.php';
verify_csrf();

$user = require_login();
$cartItemId = (int)($_POST['cart_item_id'] ?? 0);

$stmt = get_db()->prepare('DELETE FROM cart_items WHERE id = ? AND buyer_id = ?');
$stmt->execute([$cartItemId, $user['id']]);

render_cart_partial((int)$user['id']);
