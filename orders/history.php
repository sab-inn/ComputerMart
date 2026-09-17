<?php
require_once __DIR__ . '/../includes/auth.php';

$user = require_login();
$db = get_db();

if ($user['role'] === 'admin') {
    $viewLabel = 'All orders';
    $orders = $db->query(
        "SELECT o.*, u.full_name AS buyer_name FROM orders o
         JOIN users u ON u.id = o.buyer_id
         ORDER BY o.order_date DESC"
    )->fetchAll();
} elseif ($user['role'] === 'seller') {
    $viewLabel = 'Orders containing your products';
    $stmt = $db->prepare(
        "SELECT DISTINCT o.*, u.full_name AS buyer_name FROM orders o
         JOIN users u ON u.id = o.buyer_id
         JOIN order_items oi ON oi.order_id = o.id
         WHERE oi.seller_id = ?
         ORDER BY o.order_date DESC"
    );
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll();
} else {
    $viewLabel = 'Your orders';
    $stmt = $db->prepare('SELECT * FROM orders WHERE buyer_id = ? ORDER BY order_date DESC');
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll();
}

// Load items + payment status for each order shown (fine at this scale;
// a bigger catalog would want this joined in one query instead).
$itemStmt = $db->prepare(
    "SELECT oi.*, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ?" . ($user['role'] === 'seller' ? ' AND oi.seller_id = ?' : '')
);
$paymentStmt = $db->prepare('SELECT status FROM payments WHERE order_id = ?');

foreach ($orders as &$order) {
    $params = $user['role'] === 'seller' ? [$order['id'], $user['id']] : [$order['id']];
    $itemStmt->execute($params);
    $order['items'] = $itemStmt->fetchAll();

    $paymentStmt->execute([$order['id']]);
    $order['payment_status'] = $paymentStmt->fetchColumn() ?: null;
}
unset($order);

$showBuyerColumn = in_array($user['role'], ['admin', 'seller'], true);

$pageTitle = 'Orders';
require __DIR__ . '/../includes/layout_top.php';
?>
<h1 class="section-title" style="font-size:20px;"><?= htmlspecialchars($viewLabel) ?></h1>

<?php if (empty($orders)): ?>
<div class="empty-state">Nothing here yet.</div>
<?php else: ?>
<table class="data-table">
    <thead>
        <tr>
            <th>Order</th>
            <?php if ($showBuyerColumn): ?><th>Buyer</th><?php endif; ?>
            <th>Items</th>
            <th>Total</th>
            <th>Order status</th>
            <th>Payment</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
            <td class="mono">#<?= (int)$o['id'] ?><br><span style="color:var(--muted); font-size:11px;"><?= htmlspecialchars(date('M j, Y', strtotime($o['order_date']))) ?></span></td>
            <?php if ($showBuyerColumn): ?><td><?= htmlspecialchars($o['buyer_name'] ?? '') ?></td><?php endif; ?>
            <td>
                <?php foreach ($o['items'] as $item): ?>
                <div style="font-size:13px;"><?= htmlspecialchars($item['name']) ?> × <?= (int)$item['quantity'] ?></div>
                <?php endforeach; ?>
            </td>
            <td class="mono">Rs <?= number_format((float)$o['total_amount'], 0) ?></td>
            <td><span class="badge badge-<?= htmlspecialchars($o['status']) ?>"><?= htmlspecialchars(ucfirst($o['status'])) ?></span></td>
            <td>
                <?php if ($o['payment_status']): ?>
                <span class="badge badge-<?= htmlspecialchars($o['payment_status']) ?>"><?= htmlspecialchars(ucfirst($o['payment_status'])) ?></span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
