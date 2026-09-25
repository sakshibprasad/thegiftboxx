<?php
declare(strict_types=1);

const LOGIN_MAX_ATTEMPTS = 8;
const LOGIN_WINDOW_MINUTES = 15;

function login_throttled(string $scope): bool
{
    $since = date('Y-m-d H:i:s', time() - LOGIN_WINDOW_MINUTES * 60);
    $count = (int) val('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND scope = ? AND created_at > ?',
        [client_ip(), $scope, $since]);
    return $count >= LOGIN_MAX_ATTEMPTS;
}

function login_failed(string $scope): void
{
    insert('login_attempts', ['ip' => client_ip(), 'scope' => $scope, 'created_at' => now()]);
    q('DELETE FROM login_attempts WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
}

function attempt_login(string $email, string $password, array $roles, string $scope): ?array
{
    if (login_throttled($scope)) {
        return null;
    }
    $user = one('SELECT * FROM users WHERE email = ?', [strtolower(trim($email))]);
    if (!$user || !$user['password_hash'] || !in_array($user['role'], $roles, true)
        || !password_verify($password, $user['password_hash'])) {
        login_failed($scope);
        return null;
    }
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$user['id']]);
    }
    update('users', ['last_login' => now()], 'id = ?', [$user['id']]);
    session_regenerate_id(true);
    return $user;
}

/* ---------- Customers (storefront) ---------- */

function customer(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $id = $_SESSION['customer_id'] ?? null;
    $cache = $id ? one('SELECT * FROM users WHERE id = ?', [$id]) : null;
    return $cache;
}

function customer_login(array $user): void
{
    $_SESSION['customer_id'] = (int) $user['id'];
}

function customer_logout(): void
{
    unset($_SESSION['customer_id']);
    session_regenerate_id(true);
}

/* ---------- Admin ---------- */

function admin_roles(): array
{
    return ['admin' => 'Administrator (full access)', 'manager' => 'Shop manager (no settings or users)'];
}

function admin_user(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $id = $_SESSION['admin_id'] ?? null;
    $cache = $id ? one("SELECT * FROM users WHERE id = ? AND role IN ('admin','manager')", [$id]) : null;
    return $cache;
}

function require_admin(bool $fullAdmin = false): array
{
    $u = admin_user();
    if (!$u) {
        redirect('/login?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
    }
    if ($fullAdmin && $u['role'] !== 'admin') {
        http_response_code(403);
        echo render('admin/layout', ['title' => 'Not allowed', 'content' => '<div class="empty"><h2>Only administrators can open this page.</h2></div>']);
        exit;
    }
    return $u;
}
