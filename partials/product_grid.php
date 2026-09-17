<?php
// Expects $products (array of rows incl. category_name, seller_display_name)
// and $canBuy (bool) to already be set by whichever page includes this.
if (empty($products)):
?>
<div class="empty-state">No products match that search.</div>
<?php else: ?>
<div class="product-grid">
    <?php foreach ($products as $p): ?>
    <div class="product-card">
        <div class="thumb">
            <?php if (!empty($p['image_url'])): ?>
                <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
            <?php else: ?>
                <span><?= htmlspecialchars($p['category_name']) ?></span>
            <?php endif; ?>
        </div>
        <span class="tag"><?= htmlspecialchars($p['category_name']) ?></span>
        <h3><?= htmlspecialchars($p['name']) ?></h3>
        <div class="seller"><?= htmlspecialchars($p['seller_display_name']) ?></div>
        <div class="price-row">
            <span class="price">Rs <?= number_format((float)$p['price'], 0) ?></span>
            <?php if ((int)$p['stock'] <= 0): ?>
                <span class="stock out">Out of stock</span>
            <?php elseif ((int)$p['stock'] <= 5): ?>
                <span class="stock low"><?= (int)$p['stock'] ?> left</span>
            <?php else: ?>
                <span class="stock"><?= (int)$p['stock'] ?> in stock</span>
            <?php endif; ?>
        </div>
        <?php if ($canBuy): ?>
        <button type="button" class="btn btn-outline btn-block btn-sm"
                hx-post="/cart_add.php"
                hx-vals='{"product_id": <?= (int)$p['id'] ?>}'
                hx-target="#cart-panel" hx-swap="innerHTML"
                <?= ((int)$p['stock'] <= 0) ? 'disabled' : '' ?>>
            Add to cart
        </button>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
