<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/esewa.php';

$user = require_login();
if (!in_array($user['role'], ['buyer', 'admin'], true)) {
    header('Location: /access_denied.php');
    exit;
}

$db = get_db();

function load_cart_items(PDO $db, int $buyerId): array {
    $stmt = $db->prepare(
        "SELECT ci.id AS cart_item_id, ci.quantity, pr.id AS product_id, pr.name, pr.price,
                pr.stock, pr.is_active, pr.seller_id
         FROM cart_items ci JOIN products pr ON pr.id = ci.product_id
         WHERE ci.buyer_id = ?"
    );
    $stmt->execute([$buyerId]);
    return $stmt->fetchAll();
}

$items = load_cart_items($db, (int)$user['id']);
if (empty($items)) {
    header('Location: /');
    exit;
}

$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // Stock may have moved since the cart was last loaded - check again
    // right before committing to anything.
    $items = load_cart_items($db, (int)$user['id']);
    $ok = true;
    foreach ($items as $item) {
        if (!$item['is_active'] || $item['quantity'] > $item['stock']) {
            $errorMessage = "'{$item['name']}' no longer has enough stock. Please update your cart.";
            $ok = false;
            break;
        }
    }

    if ($ok) {
        $total = 0;
        foreach ($items as $item) { $total += $item['price'] * $item['quantity']; }

        $db->beginTransaction();
        try {
            $db->prepare("INSERT INTO orders (buyer_id, status, total_amount) VALUES (?, 'pending', ?)")
               ->execute([$user['id'], $total]);
            $orderId = (int)$db->lastInsertId();

            $insertItem = $db->prepare(
                'INSERT INTO order_items (order_id, product_id, seller_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)'
            );
            $decrementStock = $db->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');

            foreach ($items as $item) {
                $insertItem->execute([$orderId, $item['product_id'], $item['seller_id'], $item['quantity'], $item['price']]);
                $decrementStock->execute([$item['quantity'], $item['product_id']]);
            }

            $transactionUuid = 'CM-' . $orderId . '-' . bin2hex(random_bytes(6));
            $db->prepare(
                "INSERT INTO payments (order_id, amount, transaction_uuid, status) VALUES (?, ?, ?, 'pending')"
            )->execute([$orderId, $total, $transactionUuid]);

            $db->prepare('DELETE FROM cart_items WHERE buyer_id = ?')->execute([$user['id']]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }

        header('Location: /payment/redirect.php?order_id=' . $orderId);
        exit;
    }
}

$total = 0;
foreach ($items as $item) { $total += $item['price'] * $item['quantity']; }

$pageTitle = 'Checkout';
require __DIR__ . '/../includes/layout_top.php';
?>
<div style="max-width:560px; margin:0 auto;">
    <h1 class="section-title" style="font-size:20px;">Review your order</h1>

    <?php if ($errorMessage): ?>
    <div class="alert alert-error"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <div class="form-card" style="max-width:none; margin:0;">
        <?php foreach ($items as $item): ?>
        <div class="cart-line">
            <span><?= htmlspecialchars($item['name']) ?> × <?= (int)$item['quantity'] ?></span>
            <span style="font-family:var(--font-mono);">Rs <?= number_format($item['price'] * $item['quantity'], 0) ?></span>
        </div>
        <?php endforeach; ?>
        <div class="cart-total">
            <span>Total</span>
            <span style="font-family:var(--font-mono);">Rs <?= number_format($total, 0) ?></span>
        </div>

        <form method="post" style="margin-top:20px;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-accent btn-block">Pay with eSewa</button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
