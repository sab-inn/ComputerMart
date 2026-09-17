<?php
require_once __DIR__ . '/includes/auth.php';

if (GOOGLE_CLIENT_ID === '') {
    header('Location: /login.php');
    exit;
}

// Random state, stored in session, checked on the way back - stops an
// attacker from linking their own Google account into a victim's session
// (the PHP equivalent of what ASP.NET Core's OAuth handler does for you
// automatically).
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = http_build_query([
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'prompt' => 'select_account',
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
exit;
