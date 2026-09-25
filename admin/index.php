<?php
declare(strict_types=1);

// Locate the private "app" folder. If your hosting layout is unusual, create
// admin/app-path.php containing: <?php return '/full/path/to/app';
$bootstrap = null;
if (is_file(__DIR__ . '/app-path.php')) {
    $bootstrap = rtrim((string) require __DIR__ . '/app-path.php', '/') . '/bootstrap.php';
} else {
    $dir = __DIR__;
    for ($i = 0; $i < 5; $i++) {
        foreach ([$dir . '/app/bootstrap.php', $dir . '/domains/app/bootstrap.php'] as $candidate) {
            if (is_file($candidate)) {
                $bootstrap = $candidate;
                break 2;
            }
        }
        $dir = dirname($dir);
    }
}
if (!$bootstrap || !is_file($bootstrap)) {
    http_response_code(500);
    exit('App folder not found. See docs/DEPLOYMENT.md (step "Admin subdomain").');
}
require $bootstrap;
$GLOBALS['__docroot'] = __DIR__;

if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file) && !str_ends_with($file, '.php')) {
        return false;
    }
}

// If the admin folder is opened through the main domain (e.g. thegiftboxx.com/admin/), send people to the subdomain.
if (app_installed() && PHP_SAPI !== 'cli-server') {
    $adminHost = parse_url((string) config('admin_url'), PHP_URL_HOST);
    if ($adminHost && strcasecmp($adminHost, (string) ($_SERVER['HTTP_HOST'] ?? '')) !== 0) {
        $rest = preg_replace('#^/admin#', '', (string) ($_SERVER['REQUEST_URI'] ?? '/'));
        header('Location: ' . admin_url(ltrim($rest, '/')), true, 301);
        exit;
    }
}

start_session('tgb_admin');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('X-Robots-Tag: noindex, nofollow');

require APP_DIR . '/admin/routes.php';

$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
try {
    admin_dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', rtrim($path, '/') ?: '/');
} catch (Throwable $e) {
    app_log('admin-error', $e->getMessage(), ['file' => $e->getFile() . ':' . $e->getLine(), 'url' => $path]);
    http_response_code(500);
    $msg = config('debug') ? (string) $e : $e->getMessage();
    if (function_exists('admin_user') && app_installed() && admin_user()) {
        echo admin_page('Something went wrong', '<div class="card pad"><h2>Something went wrong</h2><pre class="code">' . e($msg) . '</pre><p class="muted">Details were saved in storage/logs/app.log.</p><a class="btn" href="javascript:history.back()">Go back</a></div>');
    } else {
        echo '<pre>' . e($msg) . '</pre>';
    }
}
