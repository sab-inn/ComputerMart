<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_helper.php';
verify_csrf();

$admin = require_role('admin');
$userId = (int)($_POST['user_id'] ?? 0);

if ($userId !== (int)$admin['id']) { // don't let an admin lock themselves out
    $stmt = get_db()->prepare('SELECT is_locked FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) {
        $newState = $row['is_locked'] ? 0 : 1;
        get_db()->prepare('UPDATE users SET is_locked = ? WHERE id = ?')->execute([$newState, $userId]);
    }
}

$view = build_admin_view('users');
require __DIR__ . '/../partials/admin_shell.php';
