<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/esewa.php';

// Deliberately no require_login() here - eSewa's own server redirects the
// browser to this URL, and we verify the payment by its signature and
// transaction_uuid rather than trusting whoever happens to be logged in
// when the redirect lands.

$data = $_GET['data'] ?? '';
$callback = $data !== '' ? esewa_verify_callback($data) : null;

if (!$callback || ($callback['status'] ?? '') !== 'COMPLETE') {
    header('Location: /payment/failure.php');
    exit;
}

$db = get_db();
$stmt = $db->prepare('SELECT * FROM payments WHERE transaction_uuid = ?');
$stmt->execute([$callback['transaction_uuid']]);
$payment = $stmt->fetch();

if (!$payment) {
    header('Location: /payment/failure.php');
    exit;
}

// Idempotent: reloading this page (or eSewa retrying the redirect)
// shouldn't re-verify or double-decrement anything.
if ($payment['status'] !== 'success') {
    // Belt-and-suspenders: confirm server-to-server too, rather than
    // trusting the browser redirect alone.
    $statusCheck = esewa_check_status($payment['transaction_uuid'], (float)$payment['amount']);
    if (($statusCheck['status'] ?? '') !== 'COMPLETE') {
        header('Location: /payment/failure.php');
        exit;
    }

    $db->prepare('UPDATE payments SET status = "success", esewa_ref_id = ?, verified_at = NOW() WHERE id = ?')
       ->execute([$statusCheck['ref_id'] ?? ($callback['transaction_code'] ?? null), $payment['id']]);
    $db->prepare("UPDATE orders SET status = 'paid' WHERE id = ?")->execute([$payment['order_id']]);
}

$stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$payment['order_id']]);
$order = $stmt->fetch();

$pageTitle = 'Payment successful';
require __DIR__ . '/../includes/layout_top.php';
?>
<div style="max-width:480px; margin:60px auto; text-align:center;">
    <h1>Payment successful</h1>
    <?php if ($order): ?>
    <p style="color:var(--muted);">Order #<?= (int)$order['id'] ?> is confirmed. Total paid: Rs <?= number_format((float)$order['total_amount'], 0) ?></p>
    <?php endif; ?>
    <a href="/orders/history.php" class="btn btn-accent">View my orders</a>
</div>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
