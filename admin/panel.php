<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_helper.php';

require_role('admin');
$tab = $_GET['tab'] ?? 'users';
$view = build_admin_view($tab);

$pageTitle = 'Admin';
require __DIR__ . '/../includes/layout_top.php';
?>
<h1 class="section-title" style="font-size:20px;">Admin panel</h1>
<?php require __DIR__ . '/../partials/admin_shell.php'; ?>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
