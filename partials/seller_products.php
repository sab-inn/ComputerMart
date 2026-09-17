<?php
// Expects $products (seller's own rows, with category_name joined).
?>
<?php if (empty($products)): ?>
<div class="empty-state">You haven't listed anything yet.</div>
<?php else: ?>
<table class="data-table">
    <thead>
        <tr><th>Product</th><th>Category</th><th>Price &amp; stock</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td class="mono"><?= htmlspecialchars($p['category_name']) ?></td>
            <td>
                <form hx-post="/seller/update_product.php" hx-target="#seller-products" hx-swap="innerHTML"
                      style="display:flex; gap:6px; align-items:center;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                    Rs
                    <input type="number" name="price" value="<?= htmlspecialchars((string)$p['price']) ?>" step="0.01" min="0"
                           style="width:90px; padding:4px 6px; border:1px solid var(--border); border-radius:4px; font-family:var(--font-mono);">
                    ×
                    <input type="number" name="stock" value="<?= (int)$p['stock'] ?>" min="0"
                           style="width:55px; padding:4px 6px; border:1px solid var(--border); border-radius:4px; font-family:var(--font-mono);">
                    <button type="submit" class="btn btn-outline btn-sm">Save</button>
                </form>
            </td>
            <td>
                <?php if ($p['is_active']): ?>
                    <span class="badge badge-paid">Listed</span>
                <?php else: ?>
                    <span class="badge badge-cancelled">Removed</span>
                <?php endif; ?>
            </td>
            <td>
                <button type="button" class="btn btn-sm <?= $p['is_active'] ? 'btn-danger' : 'btn-outline' ?>"
                        hx-post="/seller/toggle_product.php"
                        hx-vals='{"product_id": <?= (int)$p['id'] ?>}'
                        hx-target="#seller-products" hx-swap="innerHTML"
                        hx-confirm="<?= $p['is_active'] ? 'Remove this listing?' : 'Relist this product?' ?>">
                    <?= $p['is_active'] ? 'Remove' : 'Relist' ?>
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
