<?php
// Edit these to match your environment. Keeping them in one place (rather
// than scattered across files) is the PHP equivalent of appsettings.json.

// --- Database ---
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307'); // separate from the .NET project's MariaDB on 3306
define('DB_NAME', 'computemart_php');
define('DB_USER', 'computemart');
define('DB_PASS', 'computemart_php_pw');

// --- Base URL of this app (used to build redirect URIs) ---
define('BASE_URL', 'http://localhost:8000');

// --- Google OAuth (leave blank to disable the Google login button) ---
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');
define('GOOGLE_REDIRECT_URI', BASE_URL . '/google_callback.php');

// --- eSewa ePay v2 (public UAT test credentials - safe to commit) ---
define('ESEWA_PRODUCT_CODE', 'EPAYTEST');
define('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q');
define('ESEWA_FORM_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form');
define('ESEWA_STATUS_URL', 'https://uat.esewa.com.np/api/epay/transaction/status/');
define('ESEWA_SUCCESS_URL', BASE_URL . '/payment/success.php');
define('ESEWA_FAILURE_URL', BASE_URL . '/payment/failure.php');
