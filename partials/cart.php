<?php
// Expects $cartItems (array of rows with product name/price joined) set
// by whichever page includes this.
$total = 0;
foreach ($cartItems as $ci) { $total += $ci['price'] * $ci['quantity']; }
$totalQty = array_sum(array_column($cartItems, 'quantity'));
?>
<span id="cart-count-badge" hx-swap-oob="true"><?= $totalQty ?></span>
<h2 style="font-size:15px; margin:0 0 12px; font-weight:500;">Your cart</h2>
<?php if (empty($cartItems)): ?>
<div class="empty-state">Cart's empty. Add something from the left.</div>
<?php else: ?>
    <?php foreach ($cartItems as $ci): ?>
    <div class="cart-line">
        <div>
            <div><?= htmlspecialchars($ci['name']) ?></div>
            <div class="qty-stepper" style="margin-top:4px;">
                <button type="button" hx-post="/cart_update.php"
                        hx-vals='{"cart_item_id": <?= (int)$ci['id'] ?>, "quantity": <?= (int)$ci['quantity'] - 1 ?>}'
                        hx-target="#cart-panel">-</button>
                <input type="text" readonly value="<?= (int)$ci['quantity'] ?>">
                <button type="button" hx-post="/cart_update.php"
                        hx-vals='{"cart_item_id": <?= (int)$ci['id'] ?>, "quantity": <?= (int)$ci['quantity'] + 1 ?>}'
                        hx-target="#cart-panel">+</button>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-family:var(--font-mono); font-size:13px;">Rs <?= number_format($ci['price'] * $ci['quantity'], 0) ?></div>
            <button type="button" style="background:none;border:none;color:var(--muted);cursor:pointer;padding:2px 0;font-size:12px;"
                    hx-post="/cart_remove.php" hx-vals='{"cart_item_id": <?= (int)$ci['id'] ?>}'
                    hx-target="#cart-panel">Remove</button>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="cart-total">
        <span>Total</span>
        <span style="font-family:var(--font-mono);">Rs <?= number_format($total, 0) ?></span>
    </div>
    <a href="/cart/checkout.php" class="btn btn-accent btn-block" style="margin-top:14px;">Checkout</a>
<?php endif; ?>
