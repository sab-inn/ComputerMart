<?php
require_once __DIR__ . '/../includes/auth.php';

// eSewa may or may not include a data param on failure, depending on how
// the buyer bailed out - only act on it if it's present.
$data = $_GET['data'] ?? '';
if ($data !== '') {
    $json = base64_decode($data, true);
    if ($json !== false) {
        $payload = json_decode($json, true);
        if (is_array($payload) && isset($payload['transaction_uuid'])) {
            $stmt = get_db()->prepare('SELECT id, status FROM payments WHERE transaction_uuid = ?');
            $stmt->execute([$payload['transaction_uuid']]);
            $payment = $stmt->fetch();
            if ($payment && $payment['status'] === 'pending') {
                get_db()->prepare("UPDATE payments SET status = 'failed' WHERE id = ?")->execute([$payment['id']]);
            }
        }
    }
}

$pageTitle = 'Payment failed';
require __DIR__ . '/../includes/layout_top.php';
?>
<div style="max-width:480px; margin:60px auto; text-align:center;">
    <h1>Payment didn't go through</h1>
    <p style="color:var(--muted);">Nothing was charged. Check your order history — the order may still be there waiting for another payment attempt.</p>
    <a href="/orders/history.php" class="btn btn-outline">View my orders</a>
    <a href="/" class="btn btn-accent" style="margin-left:8px;">Back to shop</a>
</div>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
