<?php
require_once __DIR__ . '/includes/auth.php';

$state = $_GET['state'] ?? '';
$code = $_GET['code'] ?? '';

if ($state === '' || !hash_equals($_SESSION['oauth_state'] ?? '', $state)) {
    header('Location: /login.php');
    exit;
}
unset($_SESSION['oauth_state']);

if ($code === '') {
    header('Location: /login.php');
    exit;
}

// Exchange the authorization code for an access token (server-to-server,
// authenticated with our client secret).
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code',
    ]),
    CURLOPT_TIMEOUT => 15,
]);
$tokenResponse = json_decode((string)curl_exec($ch), true);
curl_close($ch);

$accessToken = $tokenResponse['access_token'] ?? null;
if (!$accessToken) {
    header('Location: /login.php');
    exit;
}

// Ask Google who this token belongs to. This sidesteps hand-rolling
// JWT/RS256 signature verification for the id_token ourselves - the
// access token was already obtained via an authenticated HTTPS call to
// Google, so trusting Google's own answer here is sound.
$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer {$accessToken}"],
    CURLOPT_TIMEOUT => 15,
]);
$profile = json_decode((string)curl_exec($ch), true);
curl_close($ch);

$email = $profile['email'] ?? null;
$name = $profile['name'] ?? $email;
$googleId = $profile['sub'] ?? null;

if (!$email || !$googleId) {
    header('Location: /login.php');
    exit;
}

$db = get_db();

$stmt = $db->prepare('SELECT * FROM users WHERE google_id = ?');
$stmt->execute([$googleId]);
$user = $stmt->fetch();

if (!$user) {
    // Match by email, or create a new Buyer account. (Google sign-up
    // always defaults to Buyer - signing up as a Seller needs the
    // email/password form, since we need to ask for a store name.)
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $db->prepare('UPDATE users SET google_id = ? WHERE id = ?')->execute([$googleId, $user['id']]);
    } else {
        $stmt = $db->prepare('INSERT INTO users (full_name, email, google_id, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $googleId, 'buyer']);
        $user = ['id' => $db->lastInsertId(), 'is_locked' => 0];
    }
}

if ((int)($user['is_locked'] ?? 0) === 1) {
    header('Location: /login.php');
    exit;
}

login_user((int)$user['id']);
header('Location: /');
exit;
