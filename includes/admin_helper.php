<?php
require_once __DIR__ . '/db.php';

function build_admin_view(string $tab): array {
    $db = get_db();
    $data = ['tab' => $tab];

    switch ($tab) {
        case 'products':
            $data['products'] = $db->query(
                "SELECT p.*, c.name AS category_name, COALESCE(u.store_name, u.full_name) AS seller_display_name
                 FROM products p JOIN categories c ON c.id = p.category_id JOIN users u ON u.id = p.seller_id
                 ORDER BY p.created_at DESC"
            )->fetchAll();
            break;

        case 'byseller':
            $rows = $db->query(
                "SELECT p.*, c.name AS category_name, u.id AS seller_id_col,
                        COALESCE(u.store_name, u.full_name) AS seller_display_name, u.email AS seller_email
                 FROM products p JOIN categories c ON c.id = p.category_id JOIN users u ON u.id = p.seller_id
                 ORDER BY seller_display_name, p.created_at DESC"
            )->fetchAll();
            $groups = [];
            foreach ($rows as $row) {
                $key = $row['seller_id_col'];
                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'seller_name' => $row['seller_display_name'],
                        'seller_email' => $row['seller_email'],
                        'products' => [],
                    ];
                }
                $groups[$key]['products'][] = $row;
            }
            $data['seller_groups'] = array_values($groups);
            break;

        case 'orders':
            $orders = $db->query(
                "SELECT o.*, u.full_name AS buyer_name FROM orders o JOIN users u ON u.id = o.buyer_id
                 ORDER BY o.order_date DESC"
            )->fetchAll();
            $itemStmt = $db->prepare('SELECT oi.*, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?');
            $paymentStmt = $db->prepare('SELECT status FROM payments WHERE order_id = ?');
            foreach ($orders as &$o) {
                $itemStmt->execute([$o['id']]);
                $o['items'] = $itemStmt->fetchAll();
                $paymentStmt->execute([$o['id']]);
                $o['payment_status'] = $paymentStmt->fetchColumn() ?: null;
            }
            unset($o);
            $data['orders'] = $orders;
            break;

        default:
            $data['tab'] = 'users';
            $data['users'] = $db->query('SELECT * FROM users ORDER BY created_at')->fetchAll();
            break;
    }

    return $data;
}
