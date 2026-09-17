<?php
// Expects $view (from build_admin_view()) to be set.
function admin_tab_url(string $t): string { return "/admin/tab.php?tab={$t}"; }
?>
<div id="admin-shell">
    <div class="tabs">
        <button type="button" class="<?= $view['tab'] === 'users' ? 'active' : '' ?>" hx-get="<?= admin_tab_url('users') ?>" hx-target="#admin-shell" hx-swap="outerHTML">Users</button>
        <button type="button" class="<?= $view['tab'] === 'products' ? 'active' : '' ?>" hx-get="<?= admin_tab_url('products') ?>" hx-target="#admin-shell" hx-swap="outerHTML">All products</button>
        <button type="button" class="<?= $view['tab'] === 'byseller' ? 'active' : '' ?>" hx-get="<?= admin_tab_url('byseller') ?>" hx-target="#admin-shell" hx-swap="outerHTML">Products by seller</button>
        <button type="button" class="<?= $view['tab'] === 'orders' ? 'active' : '' ?>" hx-get="<?= admin_tab_url('orders') ?>" hx-target="#admin-shell" hx-swap="outerHTML">Order history</button>
    </div>

    <?php if ($view['tab'] === 'products'): ?>
        <?php if (empty($view['products'])): ?>
        <div class="empty-state">No products yet.</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Product</th><th>Seller</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($view['products'] as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars($p['seller_display_name']) ?></td>
                    <td class="mono"><?= htmlspecialchars($p['category_name']) ?></td>
                    <td class="mono">Rs <?= number_format((float)$p['price'], 0) ?></td>
                    <td class="mono"><?= (int)$p['stock'] ?></td>
                    <td>
                        <?php if ($p['is_active']): ?><span class="badge badge-paid">Listed</span>
                        <?php else: ?><span class="badge badge-cancelled">Removed</span><?php endif; ?>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm <?= $p['is_active'] ? 'btn-danger' : 'btn-outline' ?>"
                                hx-post="/admin/toggle_product.php"
                                hx-vals='{"product_id": <?= (int)$p['id'] ?>}'
                                hx-target="#admin-shell" hx-swap="outerHTML"
                                hx-confirm="<?= $p['is_active'] ? 'Remove this listing?' : 'Relist this product?' ?>">
                            <?= $p['is_active'] ? 'Remove' : 'Relist' ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

    <?php elseif ($view['tab'] === 'byseller'): ?>
        <?php if (empty($view['seller_groups'])): ?>
        <div class="empty-state">No sellers yet.</div>
        <?php else: ?>
            <?php foreach ($view['seller_groups'] as $group): ?>
            <h3 style="font-size:14px; margin:20px 0 8px;"><?= htmlspecialchars($group['seller_name']) ?> <span style="color:var(--muted); font-weight:400;">(<?= htmlspecialchars($group['seller_email']) ?>)</span></h3>
            <table class="data-table">
                <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($group['products'] as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td class="mono"><?= htmlspecialchars($p['category_name']) ?></td>
                        <td class="mono">Rs <?= number_format((float)$p['price'], 0) ?></td>
                        <td class="mono"><?= (int)$p['stock'] ?></td>
                        <td>
                            <?php if ($p['is_active']): ?><span class="badge badge-paid">Listed</span>
                            <?php else: ?><span class="badge badge-cancelled">Removed</span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php elseif ($view['tab'] === 'orders'): ?>
        <?php if (empty($view['orders'])): ?>
        <div class="empty-state">No orders yet.</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Order</th><th>Buyer</th><th>Items</th><th>Total</th><th>Status</th><th>Payment</th></tr></thead>
            <tbody>
                <?php foreach ($view['orders'] as $o): ?>
                <tr>
                    <td class="mono">#<?= (int)$o['id'] ?><br><span style="color:var(--muted); font-size:11px;"><?= htmlspecialchars(date('M j, Y', strtotime($o['order_date']))) ?></span></td>
                    <td><?= htmlspecialchars($o['buyer_name']) ?></td>
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

    <?php else: ?>
        <?php if (empty($view['users'])): ?>
        <div class="empty-state">No users yet.</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($view['users'] as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['full_name']) ?></td>
                    <td class="mono"><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge badge-role"><?= htmlspecialchars(ucfirst($u['role'])) ?></span></td>
                    <td class="mono"><?= htmlspecialchars(date('M j, Y', strtotime($u['created_at']))) ?></td>
                    <td>
                        <?php if ($u['is_locked']): ?><span class="badge badge-cancelled">Disabled</span>
                        <?php else: ?><span class="badge badge-paid">Active</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($u['role'] !== 'admin'): ?>
                        <button type="button" class="btn btn-sm <?= $u['is_locked'] ? 'btn-outline' : 'btn-danger' ?>"
                                hx-post="/admin/toggle_user.php"
                                hx-vals='{"user_id": <?= (int)$u['id'] ?>}'
                                hx-target="#admin-shell" hx-swap="outerHTML"
                                hx-confirm="<?= $u['is_locked'] ? 'Re-enable this account?' : htmlspecialchars("Disable this account? They won't be able to log in.") ?>">
                            <?= $u['is_locked'] ? 'Enable' : 'Disable' ?>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    <?php endif; ?>
</div>
