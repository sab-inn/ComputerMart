<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Access denied';
require __DIR__ . '/includes/layout_top.php';
?>
<div class="form-card">
    <h1>Access denied</h1>
    <p class="sub">Your account doesn't have permission to view that page.</p>
    <a href="/" class="btn btn-accent btn-block">Back to computemart</a>
</div>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
