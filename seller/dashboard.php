<?php
require_once __DIR__ . '/../includes/auth.php';

$user = require_role('seller');
$db = get_db();
$categories = $db->query('SELECT * FROM categories ORDER BY name')->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $imageUrl = trim($_POST['image_url'] ?? '');

    if ($name === '') $errors[] = 'Name is required.';
    if ($price <= 0) $errors[] = 'Price must be greater than 0.';
    if ($stock < 0) $errors[] = 'Stock cannot be negative.';

    if (empty($errors)) {
        $stmt = $db->prepare(
            'INSERT INTO products (seller_id, category_id, name, description, price, stock, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$user['id'], $categoryId, $name, $description ?: null, $price, $stock, $imageUrl ?: null]);
        header('Location: /seller/dashboard.php');
        exit;
    }
}

$stmt = $db->prepare(
    "SELECT p.*, c.name AS category_name FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE p.seller_id = ? ORDER BY p.created_at DESC"
);
$stmt->execute([$user['id']]);
$products = $stmt->fetchAll();

$pageTitle = 'My store';
require __DIR__ . '/../includes/layout_top.php';
?>
<h1 class="section-title" style="font-size:20px;">My store</h1>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="form-card" style="max-width:600px; margin:0 0 32px;">
    <h2 style="font-size:16px; margin:0 0 16px;">List a new product</h2>
    <form method="post">
        <?= csrf_field() ?>
        <div class="field">
            <label>Name</label>
            <input type="text" name="name" required>
        </div>
        <div class="field">
            <label>Description</label>
            <textarea name="description" rows="2"></textarea>
        </div>
        <div style="display:flex; gap:12px;">
            <div class="field" style="flex:1;">
                <label>Category</label>
                <select name="category_id">
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="flex:1;">
                <label>Price (Rs)</label>
                <input type="number" name="price" step="0.01" min="0" required>
            </div>
            <div class="field" style="flex:1;">
                <label>Stock</label>
                <input type="number" name="stock" min="0" required>
            </div>
        </div>
        <div class="field">
            <label>Image URL (optional)</label>
            <input type="text" name="image_url" placeholder="https://...">
        </div>
        <button type="submit" class="btn btn-accent">List product</button>
    </form>
</div>

<h2 class="section-title" style="font-size:16px;">Your listings</h2>
<div id="seller-products">
    <?php require __DIR__ . '/../partials/seller_products.php'; ?>
</div>

<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
