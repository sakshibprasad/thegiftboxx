<?php
declare(strict_types=1);

// Find the private "app" folder (it sits next to public_html on Hostinger).
$dir = __DIR__;
$bootstrap = null;
for ($i = 0; $i < 4; $i++) {
    if (is_file($dir . '/app/bootstrap.php')) {
        $bootstrap = $dir . '/app/bootstrap.php';
        break;
    }
    $dir = dirname($dir);
}
if (!$bootstrap) {
    http_response_code(500);
    exit('App folder not found. See docs/DEPLOYMENT.md.');
}
require $bootstrap;
$GLOBALS['__docroot'] = __DIR__;

if (!app_installed()) {
    http_response_code(503);
    exit('The store is being set up. Please check back shortly.');
}

// Built-in PHP server (local testing): let it serve real files directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file) && !str_ends_with($file, '.php')) {
        return false;
    }
}

start_session('tgb_store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

require APP_DIR . '/store/routes.php';

$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
try {
    store_dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path ?: '/');
} catch (Throwable $e) {
    app_log('error', $e->getMessage(), ['file' => $e->getFile() . ':' . $e->getLine(), 'url' => $path]);
    http_response_code(500);
    if (config('debug')) {
        echo '<pre>' . e((string) $e) . '</pre>';
    } else {
        echo '<!doctype html><meta charset="utf-8"><title>Something went wrong</title><div style="font:16px system-ui;max-width:520px;margin:15vh auto;text-align:center"><h1 style="font-weight:600">Something went wrong</h1><p>Please refresh the page. If it keeps happening, call us on ' . e(setting('store_phone')) . '.</p><p><a href="/">Back to the homepage</a></p></div>';
    }
}
