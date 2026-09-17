<?php
require_once __DIR__ . '/includes/auth.php';

$user = current_user();
$canBuy = $user && in_array($user['role'], ['buyer', 'admin'], true);
$isSeller = $user && $user['role'] === 'seller';

$db = get_db();
$categories = $db->query('SELECT * FROM categories ORDER BY name')->fetchAll();

$products = $db->query(
    "SELECT p.*, c.name AS category_name, COALESCE(u.store_name, u.full_name) AS seller_display_name
     FROM products p
     JOIN categories c ON c.id = p.category_id
     JOIN users u ON u.id = p.seller_id
     WHERE p.is_active = 1
     ORDER BY p.created_at DESC"
)->fetchAll();

$cartItems = [];
if ($canBuy) {
    $stmt = $db->prepare(
        "SELECT ci.id, ci.quantity, pr.id AS product_id, pr.name, pr.price
         FROM cart_items ci JOIN products pr ON pr.id = ci.product_id
         WHERE ci.buyer_id = ?"
    );
    $stmt->execute([$user['id']]);
    $cartItems = $stmt->fetchAll();
}

$pageTitle = 'Browse';
require __DIR__ . '/includes/layout_top.php';
?>

<?php if ($isSeller): ?>
<p style="color:var(--muted); margin: 0 0 16px; font-size:14px;">
    Seller accounts browse but don't buy — head to your
    <a href="/seller/dashboard.php">store dashboard</a> to manage listings.
</p>
<?php endif; ?>

<div class="<?= $isSeller ? '' : 'split' ?>">
    <div>
        <div style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;">
            <div class="search-box" style="max-width:420px;">
                <input type="search" name="q" placeholder="Search CPUs, RAM, GPUs..."
                       hx-get="/search.php" hx-trigger="keyup changed delay:300ms"
                       hx-target="#product-grid" hx-include="[name='category_id']">
            </div>
            <select name="category_id"
                    style="width:auto; padding:9px 10px; border:1px solid var(--border); border-radius:6px; background:var(--surface); font-size:14px;"
                    hx-get="/search.php" hx-trigger="change" hx-target="#product-grid" hx-include="[name='q']">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="product-grid">
            <?php require __DIR__ . '/partials/product_grid.php'; ?>
        </div>
    </div>

    <?php if (!$isSeller): ?>
    <aside>
        <?php if ($canBuy): ?>
        <div id="cart-panel" class="cart-panel">
            <?php require __DIR__ . '/partials/cart.php'; ?>
        </div>
        <?php else: ?>
        <div class="cart-panel">
            <p style="margin:0 0 10px; font-size:14px;">Want to buy something?</p>
            <a href="/login.php" class="btn btn-accent btn-block btn-sm">Log in to add to cart</a>
        </div>
        <?php endif; ?>
    </aside>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
