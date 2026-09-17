<?php
require_once __DIR__ . '/db.php';

function render_seller_products_partial(int $sellerId): void {
    $stmt = get_db()->prepare(
        "SELECT p.*, c.name AS category_name FROM products p
         JOIN categories c ON c.id = p.category_id
         WHERE p.seller_id = ? ORDER BY p.created_at DESC"
    );
    $stmt->execute([$sellerId]);
    $products = $stmt->fetchAll();
    require __DIR__ . '/../partials/seller_products.php';
}
