<?php
require_once __DIR__ . '/includes/auth.php';

$user = current_user();
$canBuy = $user && in_array($user['role'], ['buyer', 'admin'], true);

$q = trim($_GET['q'] ?? '');
$categoryId = $_GET['category_id'] ?? '';

$sql = "SELECT p.*, c.name AS category_name, COALESCE(u.store_name, u.full_name) AS seller_display_name
        FROM products p
        JOIN categories c ON c.id = p.category_id
        JOIN users u ON u.id = p.seller_id
        WHERE p.is_active = 1";
$params = [];

if ($q !== '') {
    $sql .= ' AND (p.name LIKE ? OR p.description LIKE ?)';
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}
if ($categoryId !== '') {
    $sql .= ' AND p.category_id = ?';
    $params[] = (int)$categoryId;
}
$sql .= ' ORDER BY p.created_at DESC';

$stmt = get_db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

require __DIR__ . '/partials/product_grid.php';
