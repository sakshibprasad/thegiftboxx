<?php
declare(strict_types=1);

/*
 * "Continue with Google" (OAuth 2.0 / OpenID Connect, server-side code flow).
 * Customers: optional — creates or opens their account. Admin: only for
 * people already on the team (matched by email).
 */

function google_login_ready(): bool
{
    return setting_on('google_login_enabled') && setting('google_client_id') && setting('google_client_secret');
}

function google_redirect_uri(string $area): string
{
    return $area === 'admin' ? admin_url('auth/google/callback') : site_url('auth/google/callback/');
}

function google_auth_start(string $area, string $next = ''): void
{
    if (!google_login_ready()) {
        flash('error', 'Google sign-in isn’t set up yet.');
        redirect($area === 'admin' ? '/login' : '/my-account/');
    }
    $state = random_token(16);
    $_SESSION['g_state'] = $state;
    $_SESSION['g_next'] = $next;
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => setting('google_client_id'),
        'redirect_uri' => google_redirect_uri($area),
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $state,
        'prompt' => 'select_account',
    ]));
    exit;
}

/** Finish the Google round trip. Returns ['email', 'name', 'next'] or throws with a friendly message. */
function google_auth_finish(string $area): array
{
    $expected = (string) ($_SESSION['g_state'] ?? '');
    $next = (string) ($_SESSION['g_next'] ?? '');
    unset($_SESSION['g_state'], $_SESSION['g_next']);
    if (input('error')) {
        throw new RuntimeException('Google sign-in was cancelled.');
    }
    if ($expected === '' || !hash_equals($expected, (string) input('state'))) {
        throw new RuntimeException('That sign-in link expired. Please try again.');
    }
    $res = http_request('POST', 'https://oauth2.googleapis.com/token', ['Content-Type' => 'application/x-www-form-urlencoded'], http_build_query([
        'code' => (string) input('code'),
        'client_id' => setting('google_client_id'),
        'client_secret' => setting('google_client_secret'),
        'redirect_uri' => google_redirect_uri($area),
        'grant_type' => 'authorization_code',
    ]), 20);
    $tok = json_decode((string) $res['body'], true) ?: [];
    if ($res['status'] !== 200 || empty($tok['id_token'])) {
        app_log('google', 'token exchange failed', ['status' => $res['status'], 'error' => $tok['error'] ?? '', 'desc' => $tok['error_description'] ?? '']);
        throw new RuntimeException('Google sign-in didn’t work (' . ($tok['error'] ?? 'no response') . '). Please try again or use email.');
    }
    // The ID token came straight from Google over HTTPS, so its claims can be read directly.
    $parts = explode('.', (string) $tok['id_token']);
    $claims = json_decode((string) base64_decode(strtr($parts[1] ?? '', '-_', '+/')), true) ?: [];
    $okIss = in_array($claims['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true);
    if (!$okIss || ($claims['aud'] ?? '') !== setting('google_client_id') || (int) ($claims['exp'] ?? 0) < time()
        || empty($claims['email']) || !filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
        throw new RuntimeException('Google couldn’t confirm this account’s email address.');
    }
    return ['email' => strtolower((string) $claims['email']), 'name' => mb_substr((string) ($claims['name'] ?? ''), 0, 120), 'next' => $next];
}

/** Google's multi-colour "G" (brand guidelines ask for the original colours). */
function google_icon(): string
{
    return '<svg class="g-logo" width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>';
}
