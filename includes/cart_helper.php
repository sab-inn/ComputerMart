<?php
require_once __DIR__ . '/db.php';

function render_cart_partial(int $buyerId): void {
    $stmt = get_db()->prepare(
        "SELECT ci.id, ci.quantity, pr.id AS product_id, pr.name, pr.price
         FROM cart_items ci JOIN products pr ON pr.id = ci.product_id
         WHERE ci.buyer_id = ?"
    );
    $stmt->execute([$buyerId]);
    $cartItems = $stmt->fetchAll();
    require __DIR__ . '/../partials/cart.php';
}
