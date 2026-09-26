<?php
declare(strict_types=1);

function admin_settings(string $group = ''): void
{
    require_admin(true);
    $schema = settings_schema();
    if ($group === '') {
        admin_view('settings-index', ['schema' => $schema], 'Settings', 'settings');
        return;
    }
    if (!isset($schema[$group])) {
        redirect('/settings');
    }
    admin_view('settings', [
        'group' => $group, 'schema' => $schema, 'def' => $schema[$group],
        'cronUrl' => site_url('cron/run?token=' . cron_token()),
        'previewUrl' => site_url('?preview_key=' . preview_key_admin()),
    ], $schema[$group]['label'], $group === 'appearance' || $group === 'payments' ? $group : 'settings');
}

function preview_key_admin(): string
{
    return substr(hash_hmac('sha256', 'site-preview', (string) config('app_key')), 0, 24);
}

function admin_settings_save(string $group): void
{
    require_admin(true);
    $schema = settings_schema();
    if (!isset($schema[$group])) {
        redirect('/settings');
    }
    $fields = $schema[$group]['fields'];
    // Setup wizards save only the fields they show.
    if (!empty($_POST['_only'])) {
        $only = array_filter(explode(',', (string) $_POST['_only']));
        $fields = array_intersect_key($fields, array_flip($only));
    }
    history_log('update', 'settings', implode(',', array_keys($fields)), $schema[$group]['label'] . ' settings changed',
        history_snapshot('settings', implode(',', array_keys($fields))));
    foreach ($fields as $key => $f) {
        $val = $_POST[$key] ?? null;
        switch ($f['type']) {
            case 'bool':
                setting_set($key, $val ? '1' : '0');
                break;
            case 'secret':
                if (!empty($_POST[$key . '__clear'])) {
                    setting_set($key, '', true);
                } elseif (is_string($val) && trim($val) !== '') {
                    setting_set($key, trim($val), true);
                }
                break;
            case 'color':
                if (is_string($val) && preg_match('/^#[0-9a-f]{6}$/i', $val)) {
                    setting_set($key, strtoupper($val));
                }
                break;
            case 'number':
                setting_set($key, (string) max(0, (float) $val));
                break;
            case 'select':
                if (isset(field_options($f)[$val])) {
                    setting_set($key, (string) $val);
                }
                break;
            case 'code':
                setting_set($key, (string) $val);
                break;
            case 'image':
                setting_set($key, preg_match('#^[\w/.-]*$#', (string) $val) ? (string) $val : '');
                break;
            default:
                setting_set($key, setting_extract($key, (string) $val));
        }
    }
    if ($group === 'shipping') {
        setting_set('shiprocket_token_cache', '', true);
    }
    if ($group === 'appearance' && input('reset_theme')) {
        foreach ($schema['appearance']['fields'] as $key => $f) {
            setting_set($key, (string) ($f['default'] ?? ''));
        }
        flash('success', 'Appearance reset to the original Gift Boxx look.');
        redirect('/settings/appearance');
    }
    if (!empty($_POST['_only'])) {
        json_out(['ok' => true]);
    }
    flash('success', $schema[$group]['label'] . ' settings saved.');
    redirect('/settings/' . $group);
}

function admin_settings_test(string $what): void
{
    require_admin(true);
    try {
        switch ($what) {
            case 'email':
                $to = admin_user()['email'];
                $ok = send_mail($to, 'Test email from your store', email_layout('It works!', '<p>Your store can send emails. Order confirmations and reminders will arrive like this one.</p>'));
                $ok ? json_out(['ok' => true, 'message' => "Sent! Check {$to} (and the spam folder)."]) : throw new RuntimeException('Sending failed — check the SMTP details. The exact error is in storage/logs/app.log.');
            case 'shiprocket':
                shiprocket_token(true);
                $r = shiprocket_serviceability('110001', 1.0);
                json_out(['ok' => true, 'message' => 'Connected to Shiprocket. Test pincode 110001: ' . ($r['ok'] ? 'serviceable' : 'not serviceable') . '.']);
            case 'cashfree':
                $res = http_request('GET', cashfree_base() . '/orders/tgb_connection_test', cashfree_headers());
                if ($res['status'] === 401) {
                    throw new RuntimeException('Cashfree rejected the App ID / Secret for ' . setting('cashfree_mode') . ' mode.');
                }
                json_out(['ok' => true, 'message' => 'Cashfree keys accepted (' . setting('cashfree_mode') . ').']);
            case 'payu':
                $status = payu_server_verify('tgb-connection-test');
                json_out(['ok' => true, 'message' => $status === null ? 'PayU responded. Keys look fine — do a ₹1 test order to be sure.' : 'PayU responded: ' . $status]);
        }
    } catch (Throwable $e) {
        json_out(['ok' => false, 'message' => $e->getMessage()]);
    }
}

/* ---------- Sales channels & connection status ---------- */

function admin_channels(): void
{
    $products = [];
    foreach (all("SELECT * FROM products WHERE status = 'published' ORDER BY name") as $row) {
        $p = product_full($row);
        $issues = [];
        $warn = [];
        if (!$p['images']) $issues[] = 'No photo';
        if ($p['pricing']['min'] === null) $issues[] = 'No price';
        if (mb_strlen(strip_tags((string) $p['description'])) < 50) $warn[] = 'Description is short (Google prefers 150+ characters)';
        if (mb_strlen($p['name']) > 150) $issues[] = 'Title longer than 150 characters';
        if (!$p['gtin']) $warn[] = 'No GTIN — fine for handmade boxes (sent as identifier_exists = no)';
        if (!$p['brand']) $warn[] = 'No brand set — “' . setting('store_name') . '” will be used';
        if (!$p['weight']) $warn[] = 'No weight';
        if ($p['stock_status'] === 'outofstock') $warn[] = 'Out of stock';
        $products[] = ['p' => $p, 'issues' => $issues, 'warn' => $warn, 'synced' => (bool) $p['google_sync']];
    }
    $ok = fn($v) => $v !== '' && $v !== null && $v !== false;
    $connectors = [
        ['Google Merchant Center', 'Free listings & Shopping ads', $ok(setting('gmc_id')), 'Add the Google feed URL in Merchant Center → Products → Feeds.', 'https://merchants.google.com/', '/settings/integrations'],
        ['Google Analytics 4', 'Traffic & ecommerce reports', (bool) preg_match('/^G-[A-Z0-9]+$/', (string) setting('ga4_id')), 'Tracks page views, add-to-cart, checkout and purchases.', 'https://analytics.google.com/', '/settings/integrations'],
        ['Google Ads', 'Purchase conversions', $ok(setting('gads_id')) && $ok(setting('gads_label')), 'Sends a conversion with order value on every purchase.', 'https://ads.google.com/', '/settings/integrations'],
        ['Google Search Console', 'Search visibility', $ok(setting('gsc_verification')), 'Submit ' . site_url('sitemap.xml') . ' after verifying.', 'https://search.google.com/search-console', '/settings/integrations'],
        ['Meta Pixel', 'Facebook & Instagram ads', (bool) preg_match('/^\d+$/', (string) setting('meta_pixel_id')), 'Browser events: ViewContent, AddToCart, InitiateCheckout, Purchase.', 'https://business.facebook.com/events_manager', '/settings/integrations'],
        ['Meta Conversions API', 'Server-side purchases', $ok(setting('meta_pixel_id')) && $ok(setting('meta_capi_token')), 'Purchases are also sent from the server, so ad-blockers don’t hide them.', 'https://business.facebook.com/events_manager', '/settings/integrations'],
        ['Meta catalog', 'Instagram & Facebook Shop', $ok(setting('meta_pixel_id')), 'Add the Meta feed URL in Commerce Manager → Catalogue → Data sources.', 'https://business.facebook.com/commerce', '/settings/integrations'],
        ['Pinterest', 'Tag & catalog', (bool) preg_match('/^\d+$/', (string) setting('pinterest_tag_id')), 'Add the Pinterest feed URL in Pinterest Business → Catalogues.', 'https://www.pinterest.com/business/catalogs/', '/settings/integrations'],
        ['Bing Webmaster', 'Bing & ChatGPT search', $ok(setting('bing_verification')), 'ChatGPT search relies on Bing’s index — worth doing.', 'https://www.bing.com/webmasters', '/settings/integrations'],
        ['Shiprocket', 'Shipping & tracking', setting_on('shiprocket_enabled') && $ok(setting('shiprocket_password')), 'Creates shipments and courier AWBs from the order screen.', 'https://app.shiprocket.in/', '/settings/shipping'],
        ['PayU', 'Payments', setting_on('payu_enabled') && $ok(setting('payu_key')), 'Mode: ' . ucfirst((string) setting('payu_mode')), 'https://onboarding.payu.in/', '/settings/payments'],
        ['Cashfree', 'Payments', setting_on('cashfree_enabled') && $ok(setting('cashfree_app_id')), 'Mode: ' . ucfirst((string) setting('cashfree_mode')), 'https://merchant.cashfree.com/', '/settings/payments'],
    ];
    $feeds = [
        ['Google Merchant Center', site_url('feeds/google.xml')],
        ['Meta (Facebook & Instagram)', site_url('feeds/meta.xml')],
        ['Pinterest', site_url('feeds/pinterest.xml')],
    ];
    admin_view('channels', compact('products', 'connectors', 'feeds'), 'Sales channels', 'channels', ['wide' => true]);
}

function admin_channel_test(string $which): void
{
    // Lightweight check that the public site renders the tags.
    $res = http_request('GET', site_url(), [], null, 20);
    $html = $res['body'];
    if ($which === 'ga4') {
        $id = (string) setting('ga4_id');
        json_out(['ok' => $id && str_contains($html, $id), 'message' => $id && str_contains($html, $id) ? "GA4 tag {$id} found on your homepage." : 'GA4 tag not found on the homepage (HTTP ' . $res['status'] . ').']);
    }
    $id = (string) setting('meta_pixel_id');
    json_out(['ok' => $id && str_contains($html, $id), 'message' => $id && str_contains($html, $id) ? "Meta Pixel {$id} found on your homepage." : 'Meta Pixel not found on the homepage (HTTP ' . $res['status'] . ').']);
}

/* ---------- Import ---------- */

function admin_import(): void
{
    require_admin(true);
    admin_view('import', ['missingImages' => count(images_missing()), 'state' => woo_state(), 'wooUrl' => setting('woo_url', ''), 'hasKeys' => (bool) setting('woo_key', ''),
        'seedAvailable' => is_file(APP_DIR . '/seed/woocommerce-products.csv')], 'Import', 'import');
}

function admin_import_repair_images(): void
{
    require_admin(true);
    $s = repair_missing_images();
    if (!$s['missing']) {
        flash('success', 'All photos are in place — nothing to restore.');
    } elseif (!$s['failed']) {
        flash('success', "Restored {$s['restored']} photos.");
    } else {
        flash($s['restored'] ? 'success' : 'error', "Restored {$s['restored']} of {$s['missing']} photos. " . count($s['failed'])
            . ' could not be found on the old website — upload those again on the product page.');
    }
    redirect('/import');
}

function admin_import_csv(): void
{
    require_admin(true);
    @set_time_limit(600);
    try {
        if (input('seed')) {
            $stats = import_seed_products((bool) input('images'));
            flash('success', "Imported {$stats['products']} products, {$stats['variations']} options and {$stats['images']} photos.");
            redirect('/import');
        } else {
            $f = $_FILES['csv'] ?? null;
            if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Choose the CSV file exported from WooCommerce → Products → Export.');
            }
            $file = $f['tmp_name'];
        }
        $stats = import_woo_csv($file, (bool) input('images'));
        flash('success', "Imported {$stats['products']} products, {$stats['variations']} options and {$stats['images']} photos.");
        foreach (array_slice($stats['errors'], 0, 5) as $err) {
            flash('error', $err);
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('/import');
}

function admin_import_woo_start(): void
{
    require_admin(true);
    $url = rtrim(trim((string) input('woo_url')), '/');
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        field_error_redirect('Enter your old store address, e.g. https://thegiftboxx.com', '/import');
    }
    setting_set('woo_url', $url);
    if (input('woo_key')) {
        setting_set('woo_key', trim((string) input('woo_key')), true);
    }
    if (input('woo_secret')) {
        setting_set('woo_secret', trim((string) input('woo_secret')), true);
    }
    woo_reset();
    flash('success', 'Keys saved. Migration started — keep this page open.');
    redirect('/import?run=1');
}

function admin_import_woo_step(): void
{
    require_admin(true);
    @set_time_limit(180);
    json_out(woo_import_step());
}

function admin_import_woo_reset(): void
{
    require_admin(true);
    woo_reset();
    redirect('/import');
}

/* ---------- Team & profile ---------- */

function admin_team(): void
{
    require_admin(true);
    admin_view('team', ['rows' => all("SELECT * FROM users WHERE role IN ('admin','manager') ORDER BY name")], 'Team', 'settings');
}

function admin_team_save(): void
{
    require_admin(true);
    $email = strtolower(trim((string) input('email')));
    $role = array_key_exists((string) input('role'), admin_roles()) ? (string) input('role') : 'manager';
    $pass = (string) input('password');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($pass) < 10) {
        field_error_redirect('Enter a valid email and a password of at least 10 characters.', '/team');
    }
    $data = ['name' => trim((string) input('name')) ?: $email, 'role' => $role, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT)];
    if ($u = one('SELECT id FROM users WHERE email = ?', [$email])) {
        update('users', $data, 'id = ?', [$u['id']]);
    } else {
        insert('users', $data + ['email' => $email, 'created_at' => now()]);
    }
    flash('success', "{$email} can now sign in to the admin.");
    redirect('/team');
}

function admin_team_delete(string $id): void
{
    require_admin(true);
    if ((int) $id === (int) admin_user()['id']) {
        field_error_redirect('You can’t remove yourself.', '/team');
    }
    update('users', ['role' => 'customer'], 'id = ?', [(int) $id]);
    flash('success', 'Admin access removed.');
    redirect('/team');
}

function admin_profile(): void
{
    admin_view('profile', ['u' => admin_user()], 'Your profile', '');
}

function admin_profile_save(): void
{
    $u = admin_user();
    if (!password_verify((string) input('current'), (string) $u['password_hash'])) {
        field_error_redirect('Your current password is not correct.', '/profile');
    }
    $data = ['name' => trim((string) input('name')) ?: $u['name']];
    if (input('new') !== '') {
        if (mb_strlen((string) input('new')) < 10) {
            field_error_redirect('New password must be at least 10 characters.', '/profile');
        }
        $data['password_hash'] = password_hash((string) input('new'), PASSWORD_DEFAULT);
    }
    update('users', $data, 'id = ?', [$u['id']]);
    flash('success', 'Profile updated.');
    redirect('/profile');
}
