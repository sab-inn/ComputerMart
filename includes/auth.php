<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Loads the logged-in user's row fresh from the DB (not just trusting the
// session) so an admin disabling someone mid-session takes effect on
// their very next request.
function current_user(): ?array {
    static $cached = false;
    if ($cached !== false) return $cached;

    if (!isset($_SESSION['user_id'])) {
        $cached = null;
        return null;
    }

    $stmt = get_db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || (int)$user['is_locked'] === 1) {
        $_SESSION = [];
        session_destroy();
        $cached = null;
        return null;
    }

    $cached = $user;
    return $user;
}

function require_login(): array {
    $user = current_user();
    if (!$user) {
        header('Location: /login.php');
        exit;
    }
    return $user;
}

function require_role(string ...$roles): array {
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        header('Location: /access_denied.php');
        exit;
    }
    return $user;
}

function login_user(int $userId): void {
    // Regenerating the session id on privilege change (logging in) avoids
    // session fixation - reusing whatever id the browser had before login.
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logout_user(): void {
    $_SESSION = [];
    session_destroy();
}

// --- CSRF -----------------------------------------------------------
// Same idea as the .NET version's antiforgery token: a per-session secret
// that every state-changing request must echo back, so a malicious site
// can't submit a form or htmx request on the user's behalf.

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(): void {
    $sent = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if ($expected === '' || !hash_equals($expected, $sent)) {
        http_response_code(400);
        exit('Invalid or missing CSRF token.');
    }
}
