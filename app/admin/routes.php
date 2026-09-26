<?php
declare(strict_types=1);

require_once APP_DIR . '/store/content.php';
require_once APP_DIR . '/admin/installer.php';
require_once APP_DIR . '/admin/products.php';
require_once APP_DIR . '/admin/orders.php';
require_once APP_DIR . '/admin/content.php';
require_once APP_DIR . '/admin/settings.php';
require_once APP_DIR . '/admin/wizards.php';
require_once APP_DIR . '/admin/system.php';

function admin_dispatch(string $method, string $path): void
{
    if (!app_installed()) {
        $method === 'POST' && $path === '/install' ? install_run() : install_form();
        return;
    }
    $routes = [
        ['GET', '#^/login$#', 'admin_login_form', false],
        ['POST', '#^/login$#', 'admin_login', false],
        ['GET', '#^/auth/google$#', 'admin_google_start', false],
        ['GET', '#^/auth/google/callback$#', 'admin_google_callback', false],
        ['GET', '#^/logout$#', 'admin_logout', false],
        ['GET', '#^/manifest\.webmanifest$#', 'admin_manifest', false],
        ['GET', '#^/$#', 'admin_dashboard'],
        ['GET', '#^/search$#', 'admin_search'],

        ['GET', '#^/products$#', 'admin_products'],
        ['GET', '#^/products/new$#', 'admin_product_edit'],
        ['GET', '#^/products/(\d+)$#', 'admin_product_edit'],
        ['POST', '#^/products/save$#', 'admin_product_save'],
        ['POST', '#^/products/(\d+)/delete$#', 'admin_product_delete'],
        ['POST', '#^/products/(\d+)/duplicate$#', 'admin_product_duplicate'],
        ['POST', '#^/products/bulk$#', 'admin_products_bulk'],
        ['POST', '#^/upload$#', 'admin_upload'],
        ['GET', '#^/categories$#', 'admin_categories'],
        ['POST', '#^/categories/save$#', 'admin_category_save'],
        ['POST', '#^/categories/(\d+)/delete$#', 'admin_category_delete'],
        ['GET', '#^/box-types$#', 'admin_box_types'],
        ['POST', '#^/box-types/save$#', 'admin_box_type_save'],
        ['POST', '#^/box-types/(\d+)/delete$#', 'admin_box_type_delete'],
        ['GET', '#^/brands$#', 'admin_brands'],
        ['POST', '#^/brands/save$#', 'admin_brand_save'],
        ['POST', '#^/brands/(\d+)/delete$#', 'admin_brand_delete'],
        ['GET', '#^/reviews$#', 'admin_reviews'],
        ['POST', '#^/reviews/(\d+)/(approve|pending|delete)$#', 'admin_review_action'],

        ['GET', '#^/orders$#', 'admin_orders'],
        ['GET', '#^/orders/export$#', 'admin_orders_export'],
        ['GET', '#^/orders/(\d+)$#', 'admin_order'],
        ['GET', '#^/orders/(\d+)/print$#', 'admin_order_print'],
        ['POST', '#^/orders/(\d+)/status$#', 'admin_order_status'],
        ['POST', '#^/orders/(\d+)/note$#', 'admin_order_note'],
        ['POST', '#^/orders/(\d+)/tracking$#', 'admin_order_tracking'],
        ['POST', '#^/orders/(\d+)/shiprocket$#', 'admin_order_shiprocket'],
        ['POST', '#^/orders/(\d+)/resend$#', 'admin_order_resend'],
        ['GET', '#^/customers$#', 'admin_customers'],
        ['GET', '#^/customers/(\d+)$#', 'admin_customer'],
        ['GET', '#^/abandoned$#', 'admin_abandoned'],
        ['GET', '#^/coupons$#', 'admin_coupons'],
        ['POST', '#^/coupons/save$#', 'admin_coupon_save'],
        ['POST', '#^/coupons/(\d+)/delete$#', 'admin_coupon_delete'],
        ['GET', '#^/enquiries$#', 'admin_enquiries'],
        ['GET', '#^/enquiries/(\d+)$#', 'admin_enquiry'],
        ['POST', '#^/enquiries/(\d+)$#', 'admin_enquiry_save'],

        ['GET', '#^/homepage$#', 'admin_homepage'],
        ['POST', '#^/homepage$#', 'admin_homepage_save'],
        ['GET', '#^/pages$#', 'admin_pages'],
        ['GET', '#^/pages/new$#', 'admin_page_edit'],
        ['GET', '#^/pages/(\d+)$#', 'admin_page_edit'],
        ['POST', '#^/pages/save$#', 'admin_page_save'],
        ['POST', '#^/pages/(\d+)/delete$#', 'admin_page_delete'],
        ['GET', '#^/blog$#', 'admin_blog'],
        ['GET', '#^/blog/new$#', 'admin_post_edit'],
        ['GET', '#^/blog/(\d+)$#', 'admin_post_edit'],
        ['POST', '#^/blog/save$#', 'admin_post_save'],
        ['POST', '#^/blog/(\d+)/delete$#', 'admin_post_delete'],
        ['GET', '#^/redirects$#', 'admin_redirects'],
        ['POST', '#^/redirects/save$#', 'admin_redirect_save'],
        ['POST', '#^/redirects/(\d+)/delete$#', 'admin_redirect_delete'],

        ['GET', '#^/setup$#', 'admin_setup'],
        ['GET', '#^/settings$#', 'admin_settings'],
        ['GET', '#^/settings/([a-z]+)$#', 'admin_settings'],
        ['POST', '#^/settings/([a-z]+)$#', 'admin_settings_save'],
        ['POST', '#^/settings-test/(email|shiprocket|cashfree|payu)$#', 'admin_settings_test'],
        ['GET', '#^/channels$#', 'admin_channels'],
        ['POST', '#^/channels/test/(ga4|meta)$#', 'admin_channel_test'],
        ['GET', '#^/import$#', 'admin_import'],
        ['POST', '#^/import/csv$#', 'admin_import_csv'],
        ['POST', '#^/import/repair-images$#', 'admin_import_repair_images'],
        ['POST', '#^/import/woo/start$#', 'admin_import_woo_start'],
        ['POST', '#^/import/woo/step$#', 'admin_import_woo_step'],
        ['POST', '#^/import/woo/reset$#', 'admin_import_woo_reset'],
        ['GET', '#^/activity$#', 'admin_activity'],
        ['POST', '#^/activity/(\d+)/revert$#', 'admin_activity_revert'],
        ['POST', '#^/activity/(\d+)/restore$#', 'admin_activity_restore'],
        ['GET', '#^/updates$#', 'admin_updates'],
        ['POST', '#^/updates$#', 'admin_updates_install'],
        ['GET', '#^/team$#', 'admin_team'],
        ['POST', '#^/team/save$#', 'admin_team_save'],
        ['POST', '#^/team/(\d+)/delete$#', 'admin_team_delete'],
        ['GET', '#^/profile$#', 'admin_profile'],
        ['POST', '#^/profile$#', 'admin_profile_save'],
    ];
    foreach ($routes as $r) {
        [$m, $pattern, $handler] = $r;
        $auth = $r[3] ?? true;
        if ($m === $method && preg_match($pattern, $path, $match)) {
            if ($auth) {
                require_admin();
                if ($method === 'POST') {
                    csrf_check();
                }
            }
            array_shift($match);
            $handler(...$match);
            return;
        }
    }
    require_admin();
    http_response_code(404);
    echo admin_page('Not found', '<div class="empty">' . icon('alert') . '<h2>Page not found</h2><a class="btn" href="/">Back to dashboard</a></div>');
}

/* ---------- Layout helpers ---------- */

function admin_page(string $title, string $content, array $opts = []): string
{
    return render('admin/layout', ['title' => $title, 'content' => $content] + $opts);
}

function admin_view(string $template, array $data, string $title, string $active = '', array $opts = []): void
{
    echo admin_page($title, render('admin/' . $template, $data), ['active' => $active] + $opts);
}

function admin_nav(): array
{
    $u = admin_user();
    $pendingOrders = (int) val("SELECT COUNT(*) FROM orders WHERE status IN ('processing','packed')");
    $newEnquiries = (int) val("SELECT COUNT(*) FROM enquiries WHERE status = 'new'");
    $pendingReviews = (int) val("SELECT COUNT(*) FROM reviews WHERE status = 'pending'");
    $nav = [
        ['Overview', [
            ['/', 'Dashboard', 'home', 'dashboard'],
            ['/orders', 'Orders', 'orders', 'orders', $pendingOrders],
            ['/customers', 'Customers', 'users', 'customers'],
            ['/enquiries', 'Enquiries', 'chat', 'enquiries', $newEnquiries],
        ]],
        ['Catalogue', [
            ['/products', 'Products', 'box', 'products'],
            ['/categories', 'Categories', 'tag', 'categories'],
            ['/box-types', 'Box types', 'wood', 'box-types'],
            ['/reviews', 'Reviews', 'star', 'reviews', $pendingReviews],
            ['/coupons', 'Coupons', 'coupon', 'coupons'],
        ]],
        ['Website', [
            ['/homepage', 'Homepage', 'sparkle', 'homepage'],
            ['/pages', 'Pages', 'page', 'pages'],
            ['/blog', 'Blog', 'blog', 'blog'],
            ['/abandoned', 'Abandoned carts', 'cart', 'abandoned'],
            ['/channels', 'Sales channels', 'plug', 'channels'],
        ]],
    ];
    if ($u && $u['role'] === 'admin') {
        $nav[] = ['Settings', [
            ['/setup', 'Guided setup', 'wand', 'setup'],
            ['/settings/appearance', 'Appearance', 'palette', 'appearance'],
            ['/settings/payments', 'Payments', 'card', 'payments'],
            ['/settings', 'All settings', 'settings', 'settings'],
            ['/import', 'Import', 'import', 'import'],
            ['/activity', 'History', 'refresh', 'activity'],
            ['/updates', 'Updates', 'sparkle', 'updates'],
        ]];
    }
    return $nav;
}

function admin_manifest(): void
{
    header('Content-Type: application/manifest+json');
    echo json_encode([
        'name' => setting('store_name') . ' Admin',
        'short_name' => 'Gift Boxx Admin',
        'start_url' => '/',
        'display' => 'standalone',
        'background_color' => '#F5F5F7',
        'theme_color' => '#F5F5F7',
        'icons' => [
            ['src' => '/assets/icon.png', 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => '/assets/apple-touch-icon.png', 'sizes' => '180x180', 'type' => 'image/png'],
        ],
    ], JSON_UNESCAPED_SLASHES);
}

/** Small admin helpers used by the views. */
function status_badge(string $status): string
{
    return '<span class="badge b-' . e($status) . '">' . e(order_status_label($status)) . '</span>';
}

function field_error_redirect(string $msg, string $to): never
{
    flash('error', $msg);
    redirect($to);
}

function admin_paginate(int $total, int $per, int $page): array
{
    return ['total' => $total, 'per' => $per, 'page' => $page, 'pages' => max(1, (int) ceil($total / $per))];
}

/* ---------- Auth ---------- */

function admin_login_form(): void
{
    if (admin_user()) {
        redirect('/');
    }
    echo render('admin/login', ['next' => (string) input('next')]);
}

function admin_login(): void
{
    csrf_check();
    if (login_throttled('admin')) {
        flash('error', 'Too many attempts. Please wait 15 minutes.');
        redirect('/login');
    }
    $u = attempt_login((string) input('email'), (string) input('password'), ['admin', 'manager'], 'admin');
    if (!$u) {
        flash('error', 'Wrong email or password.');
        redirect('/login');
    }
    $_SESSION['admin_id'] = (int) $u['id'];
    $next = (string) input('next');
    redirect(preg_match('#^/[a-z0-9/_-]*$#i', $next) ? $next : '/');
}

function admin_google_start(): void
{
    if (!setting_on('google_admin_login')) {
        redirect('/login');
    }
    google_auth_start('admin', (string) input('next'));
}

/** Google sign-in for the admin: only people already added under Team. */
function admin_google_callback(): void
{
    try {
        $g = google_auth_finish('admin');
        $u = setting_on('google_admin_login') ? one("SELECT * FROM users WHERE email = ? AND role IN ('admin', 'manager')", [$g['email']]) : null;
        if (!$u) {
            throw new RuntimeException($g['email'] . ' isn’t on your team. Sign in with your password, or ask an administrator to add this email under Team.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirect('/login');
    }
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $u['id'];
    redirect(preg_match('#^/[a-z0-9/_-]*$#i', $g['next']) ? $g['next'] : '/');
}

function admin_logout(): void
{
    unset($_SESSION['admin_id']);
    session_regenerate_id(true);
    redirect('/login');
}

/* ---------- Dashboard ---------- */

function admin_dashboard(): void
{
    $paid = "status NOT IN ('pending_payment','failed','cancelled','refunded')";
    $range = (int) input('days', 30);
    $range = in_array($range, [7, 30, 90, 365], true) ? $range : 30;
    $since = date('Y-m-d 00:00:00', strtotime('-' . ($range - 1) . ' days'));
    $prevSince = date('Y-m-d 00:00:00', strtotime('-' . ($range * 2 - 1) . ' days'));
    $revenue = (float) val("SELECT COALESCE(SUM(total),0) FROM orders WHERE {$paid} AND created_at >= ?", [$since]);
    $prevRevenue = (float) val("SELECT COALESCE(SUM(total),0) FROM orders WHERE {$paid} AND created_at >= ? AND created_at < ?", [$prevSince, $since]);
    $orders = (int) val("SELECT COUNT(*) FROM orders WHERE {$paid} AND created_at >= ?", [$since]);
    $prevOrders = (int) val("SELECT COUNT(*) FROM orders WHERE {$paid} AND created_at >= ? AND created_at < ?", [$prevSince, $since]);
    $daily = [];
    for ($i = $range - 1; $i >= 0; $i--) {
        $daily[date('Y-m-d', strtotime("-{$i} days"))] = 0.0;
    }
    foreach (all("SELECT created_at, total FROM orders WHERE {$paid} AND created_at >= ?", [$since]) as $o) {
        $d = substr($o['created_at'], 0, 10);
        if (isset($daily[$d])) {
            $daily[$d] += (float) $o['total'];
        }
    }
    $data = [
        'range' => $range,
        'revenue' => $revenue,
        'revenueChange' => $prevRevenue > 0 ? ($revenue - $prevRevenue) / $prevRevenue * 100 : null,
        'orders' => $orders,
        'ordersChange' => $prevOrders > 0 ? ($orders - $prevOrders) / $prevOrders * 100 : null,
        'aov' => $orders ? $revenue / $orders : 0,
        'toShip' => (int) val("SELECT COUNT(*) FROM orders WHERE status IN ('processing','packed')"),
        'daily' => $daily,
        'recent' => all('SELECT * FROM orders ORDER BY created_at DESC LIMIT 6'),
        'top' => all("SELECT oi.product_id, oi.name, SUM(oi.qty) AS qty, SUM(oi.total) AS revenue FROM order_items oi JOIN orders o ON o.id = oi.order_id
            WHERE o.{$paid} AND o.created_at >= ? GROUP BY oi.product_id, oi.name ORDER BY revenue DESC LIMIT 5", [$since]),
        'lowStock' => all("SELECT p.id, p.name, NULL AS vlabel, p.stock_qty FROM products p WHERE p.manage_stock = 1 AND p.stock_qty <= 3 AND p.status = 'published'
            UNION ALL SELECT p.id, p.name, v.attributes_json AS vlabel, v.stock_qty FROM variations v JOIN products p ON p.id = v.product_id WHERE v.manage_stock = 1 AND v.stock_qty <= 3 AND v.enabled = 1 LIMIT 8"),
        'enquiries' => all("SELECT * FROM enquiries WHERE status = 'new' ORDER BY created_at DESC LIMIT 4"),
        'abandoned' => (int) val("SELECT COUNT(*) FROM abandoned_carts WHERE status IN ('open','reminded') AND created_at >= ?", [$since]),
        'recovered' => (int) val("SELECT COUNT(*) FROM abandoned_carts WHERE status = 'recovered' AND created_at >= ?", [$since]),
        'setup' => admin_setup_checklist(),
    ];
    admin_view('dashboard', $data, 'Dashboard', 'dashboard');
}

/** "Finish setting up" checklist shown on the dashboard until everything is done. */
function admin_setup_checklist(): array
{
    return [
        ['Add your products', (int) val('SELECT COUNT(*) FROM products') > 0, '/import'],
        ['Connect a payment gateway (PayU or Cashfree)', (bool) payment_methods(), '/setup#payu'],
        ['Set up order emails (SMTP)', (bool) setting('smtp_pass'), '/setup#email'],
        ['Connect Shiprocket', setting_on('shiprocket_enabled') && (bool) setting('shiprocket_password'), '/setup#shiprocket'],
        ['Add Google Analytics', (bool) setting('ga4_id'), '/setup#ga4'],
        ['Verify Google Search Console', (bool) setting('gsc_verification'), '/setup#gsc'],
        ['Add Meta Pixel', (bool) setting('meta_pixel_id'), '/setup#meta'],
        ['Schedule the cron job (abandoned-cart emails)', (bool) setting('cron_last_run', ''), '/setup#cron'],
    ];
}

/** ⌘K quick search across orders, products and customers. */
function admin_search(): void
{
    $term = trim((string) input('q'));
    $out = [];
    if (mb_strlen($term) >= 2) {
        $like = '%' . $term . '%';
        foreach (all('SELECT id, number, email, total, status FROM orders WHERE number LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY id DESC LIMIT 5', [$like, $like, $like]) as $o) {
            $out[] = ['type' => 'Order', 'title' => $o['number'] . ' · ' . money($o['total']), 'sub' => $o['email'] . ' · ' . order_status_label($o['status']), 'url' => '/orders/' . $o['id']];
        }
        foreach (all('SELECT id, name, status FROM products WHERE name LIKE ? OR sku LIKE ? LIMIT 5', [$like, $like]) as $p) {
            $out[] = ['type' => 'Product', 'title' => $p['name'], 'sub' => ucfirst($p['status']), 'url' => '/products/' . $p['id']];
        }
        foreach (all("SELECT id, name, email FROM users WHERE role = 'customer' AND (name LIKE ? OR email LIKE ? OR phone LIKE ?) LIMIT 5", [$like, $like, $like]) as $c) {
            $out[] = ['type' => 'Customer', 'title' => $c['name'] ?: $c['email'], 'sub' => $c['email'], 'url' => '/customers/' . $c['id']];
        }
    }
    json_out(['results' => $out]);
}

/** Reusable single-image picker (uploads via /upload, stores the path in a hidden input). */
function image_field(string $name, ?string $value, string $label = 'Choose image'): string
{
    return '<div class="single-img" data-image-field><img class="preview" src="' . e(image_url($value, 'sm')) . '" alt=""' . ($value ? '' : ' hidden') . '>'
        . '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '">'
        . '<label class="btn secondary sm">' . icon('image') . ' ' . e($label) . '<input type="file" accept="image/*" hidden></label>'
        . '<button type="button" class="btn danger sm" data-remove' . ($value ? '' : ' hidden') . '>Remove</button></div>';
}

/** Secret link parameter that lets admins preview drafts on the storefront. */
function preview_token(): string
{
    return hash_hmac('sha256', 'preview', (string) config('app_key'));
}
