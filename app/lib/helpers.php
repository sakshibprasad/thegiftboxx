<?php
declare(strict_types=1);

function config(string $key, $default = null)
{
    $value = $GLOBALS['__config'] ?? [];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function site_url(string $path = ''): string
{
    return rtrim((string) config('site_url', ''), '/') . '/' . ltrim($path, '/');
}

function admin_url(string $path = ''): string
{
    return rtrim((string) config('admin_url', ''), '/') . '/' . ltrim($path, '/');
}

/** Relative URL on whichever app is currently running. */
function url(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = ($GLOBALS['__docroot'] ?? '') . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? (string) filemtime($file) : '1';
    return '/assets/' . ltrim($path, '/') . '?v=' . $v;
}

function redirect(string $to, int $code = 302): never
{
    // Admin forms are sent with fetch(); answering with JSON lets the page use
    // location.replace(), so saving never adds an extra step to the Back button.
    if (($_SERVER['HTTP_X_ADMIN_FETCH'] ?? '') === '1') {
        header('Content-Type: application/json');
        echo json_encode(['redirect' => $to]);
        exit;
    }
    header('Location: ' . $to, true, $code);
    exit;
}

function back(string $fallback = '/'): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref && parse_url($ref, PHP_URL_HOST) === $host) {
        redirect($ref);
    }
    redirect($fallback);
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function money($amount, bool $decimals = false): string
{
    $amount = (float) $amount;
    $dec = $decimals || fmod($amount, 1.0) !== 0.0 ? 2 : 0;
    return '₹' . inr_format($amount, $dec);
}

/** Indian digit grouping: 1,23,456 */
function inr_format(float $amount, int $dec = 0): string
{
    $neg = $amount < 0;
    $amount = abs($amount);
    $parts = explode('.', number_format($amount, $dec, '.', ''));
    $int = $parts[0];
    if (strlen($int) > 3) {
        $last3 = substr($int, -3);
        $rest = substr($int, 0, -3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        $int = $rest . ',' . $last3;
    }
    return ($neg ? '-' : '') . $int . (isset($parts[1]) ? '.' . $parts[1] : '');
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = str_replace(['’', "'", '&'], ['', '', 'and'], $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string) $text, '-') ?: 'item';
}

function random_token(int $bytes = 24): string
{
    return bin2hex(random_bytes($bytes));
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, $default = '')
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = random_token(16);
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Your session expired. Please go back, refresh the page and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function old(string $key, $default = '')
{
    return $_SESSION['_old'][$key] ?? $default;
}

function remember_input(): void
{
    $_SESSION['_old'] = array_map(fn($v) => is_string($v) ? $v : '', $_POST);
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

/** Render a PHP template from app/views and return the HTML. */
function render(string $template, array $data = []): string
{
    $file = APP_DIR . '/views/' . $template . '.php';
    if (!is_file($file)) {
        throw new RuntimeException("View not found: {$template}");
    }
    extract($data, EXTR_SKIP);
    ob_start();
    include $file;
    return (string) ob_get_clean();
}

function json_out($data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function str_limit(string $text, int $len = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
    return mb_strlen($text) > $len ? rtrim(mb_substr($text, 0, $len - 1)) . '…' : $text;
}

function json_arr(?string $json): array
{
    if (!$json) {
        return [];
    }
    $d = json_decode($json, true);
    return is_array($d) ? $d : [];
}

function nice_date(?string $dt, string $fmt = 'j M Y'): string
{
    return $dt ? date($fmt, strtotime($dt)) : '';
}

function time_ago(?string $dt): string
{
    if (!$dt) {
        return '';
    }
    $s = time() - strtotime($dt);
    if ($s < 60) return 'just now';
    if ($s < 3600) return floor($s / 60) . ' min ago';
    if ($s < 86400) return floor($s / 3600) . ' h ago';
    if ($s < 604800) return floor($s / 86400) . ' d ago';
    return nice_date($dt);
}

function app_log(string $channel, string $message, array $context = []): void
{
    $line = '[' . now() . "] {$channel}: {$message}";
    if ($context) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
    }
    @file_put_contents(APP_ROOT . '/storage/logs/app.log', $line . PHP_EOL, FILE_APPEND);
}

function indian_states(): array
{
    return ['Andaman and Nicobar Islands', 'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chandigarh',
        'Chhattisgarh', 'Dadra and Nagar Haveli and Daman and Diu', 'Delhi', 'Goa', 'Gujarat', 'Haryana',
        'Himachal Pradesh', 'Jammu and Kashmir', 'Jharkhand', 'Karnataka', 'Kerala', 'Ladakh', 'Lakshadweep',
        'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Puducherry',
        'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand',
        'West Bengal'];
}
