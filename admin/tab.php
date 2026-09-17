<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_helper.php';

require_role('admin');
$tab = $_GET['tab'] ?? 'users';
$view = build_admin_view($tab);
require __DIR__ . '/../partials/admin_shell.php';
