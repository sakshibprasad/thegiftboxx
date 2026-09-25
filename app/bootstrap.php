<?php
declare(strict_types=1);

define('APP_DIR', __DIR__);
define('APP_ROOT', dirname(__DIR__));

date_default_timezone_set('Asia/Kolkata');
mb_internal_encoding('UTF-8');

$configFile = APP_DIR . '/config.php';
$GLOBALS['__config'] = is_file($configFile) ? require $configFile : null;

if (($GLOBALS['__config']['debug'] ?? false) === true) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}
ini_set('log_errors', '1');
@mkdir(APP_ROOT . '/storage/logs', 0775, true);
ini_set('error_log', APP_ROOT . '/storage/logs/php-error.log');

foreach ([
    'helpers', 'db', 'crypto', 'settings', 'sanitize', 'auth', 'images',
    'catalog', 'cart', 'mailer', 'orders', 'seo', 'tracking', 'cron', 'icons', 'csv_import',
] as $lib) {
    require APP_DIR . "/lib/{$lib}.php";
}
foreach (['http', 'payu', 'cashfree', 'shiprocket', 'meta_capi', 'woo_import'] as $int) {
    require APP_DIR . "/integrations/{$int}.php";
}

function app_installed(): bool
{
    return is_array($GLOBALS['__config']);
}

function start_session(string $name): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name($name);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
