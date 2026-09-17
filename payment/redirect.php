<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/esewa.php';

$user = require_login();
if (!in_array($user['role'], ['buyer', 'admin'], true)) {
    header('Location: /access_denied.php');
    exit;
}

$orderId = (int)($_GET['order_id'] ?? 0);

$stmt = get_db()->prepare(
    'SELECT p.* FROM payments p JOIN orders o ON o.id = p.order_id
     WHERE p.order_id = ? AND o.buyer_id = ?'
);
$stmt->execute([$orderId, $user['id']]);
$payment = $stmt->fetch();

$fields = $payment ? esewa_build_payment_form((float)$payment['amount'], $payment['transaction_uuid']) : null;

$pageTitle = 'Redirecting to eSewa';
require __DIR__ . '/../includes/layout_top.php';
?>
<div style="max-width:420px; margin:80px auto; text-align:center;">
    <p>Sending you to eSewa to complete payment…</p>
    <?php if ($fields): ?>
    <form id="esewa-form" method="POST" action="<?= htmlspecialchars($fields['form_url']) ?>">
        <input type="hidden" name="amount" value="<?= htmlspecialchars($fields['amount']) ?>">
        <input type="hidden" name="tax_amount" value="<?= htmlspecialchars($fields['tax_amount']) ?>">
        <input type="hidden" name="total_amount" value="<?= htmlspecialchars($fields['total_amount']) ?>">
        <input type="hidden" name="transaction_uuid" value="<?= htmlspecialchars($fields['transaction_uuid']) ?>">
        <input type="hidden" name="product_code" value="<?= htmlspecialchars($fields['product_code']) ?>">
        <input type="hidden" name="product_service_charge" value="<?= htmlspecialchars($fields['product_service_charge']) ?>">
        <input type="hidden" name="product_delivery_charge" value="<?= htmlspecialchars($fields['product_delivery_charge']) ?>">
        <input type="hidden" name="success_url" value="<?= htmlspecialchars($fields['success_url']) ?>">
        <input type="hidden" name="failure_url" value="<?= htmlspecialchars($fields['failure_url']) ?>">
        <input type="hidden" name="signed_field_names" value="<?= htmlspecialchars($fields['signed_field_names']) ?>">
        <input type="hidden" name="signature" value="<?= htmlspecialchars($fields['signature']) ?>">
    </form>
    <script>document.getElementById('esewa-form').submit();</script>
    <?php else: ?>
    <p class="alert alert-error">Couldn't find that order.</p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
