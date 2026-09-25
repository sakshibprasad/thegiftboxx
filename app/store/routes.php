<?php
declare(strict_types=1);

require_once APP_DIR . '/store/content.php';

/** Storefront front controller. */
function store_dispatch(string $method, string $path): void
{
    // Normalise: add trailing slash to "page-like" URLs (keeps old WordPress URLs identical).
    if ($method === 'GET' && $path !== '/' && !str_ends_with($path, '/') && !preg_match('/\.[a-z0-9]{2,5}$/i', $path)
        && !str_starts_with($path, '/payment/') && !str_starts_with($path, '/cron')) {
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        redirect($path . '/' . ($qs ? '?' . $qs : ''), 301);
    }

    if (setting_on('maintenance_mode') && !maintenance_bypass($path)) {
        http_response_code(503);
        header('Retry-After: 3600');
        echo render('store/maintenance');
        return;
    }

    $routes = [
        ['GET', '#^/$#', 'page_home'],
        ['GET', '#^/shop/$#', 'page_shop'],
        ['GET', '#^/product-category/([a-z0-9-]+)/$#', 'page_category'],
        ['GET', '#^/product/([a-z0-9-]+)/$#', 'page_product'],
        ['POST', '#^/product/([a-z0-9-]+)/review$#', 'action_review'],
        ['GET', '#^/cart/$#', 'page_cart'],
        ['POST', '#^/cart/add$#', 'action_cart_add'],
        ['POST', '#^/cart/update$#', 'action_cart_update'],
        ['POST', '#^/cart/coupon$#', 'action_cart_coupon'],
        ['GET', '#^/checkout/$#', 'page_checkout'],
        ['POST', '#^/checkout/$#', 'action_checkout'],
        ['POST', '#^/checkout/capture$#', 'action_checkout_capture'],
        ['GET', '#^/pay/([A-Za-z0-9-]+)/$#', 'page_pay'],
        ['POST', '#^/payment/payu/return$#', 'action_payu_return'],
        ['GET', '#^/payment/cashfree/return$#', 'action_cashfree_return'],
        ['POST', '#^/payment/cashfree/webhook$#', 'action_cashfree_webhook'],
        ['GET', '#^/order/([A-Za-z0-9-]+)/$#', 'page_order'],
        ['GET', '#^/restore-cart/([a-f0-9]+)/$#', 'action_restore_cart'],
        ['GET', '#^/my-account/$#', 'page_account'],
        ['POST', '#^/my-account/login$#', 'action_login'],
        ['POST', '#^/my-account/register$#', 'action_register'],
        ['POST', '#^/my-account/details$#', 'action_account_details'],
        ['GET', '#^/my-account/logout/$#', 'action_logout'],
        ['GET', '#^/my-account/forgot-password/$#', 'page_forgot'],
        ['POST', '#^/my-account/forgot-password/$#', 'action_forgot'],
        ['GET', '#^/my-account/reset-password/$#', 'page_reset'],
        ['POST', '#^/my-account/reset-password/$#', 'action_reset'],
        ['GET', '#^/track-order/$#', 'page_track'],
        ['POST', '#^/track-order/$#', 'action_track'],
        ['GET', '#^/wishlist/$#', 'page_wishlist'],
        ['POST', '#^/wishlist/toggle$#', 'action_wishlist_toggle'],
        ['GET', '#^/contact/$#', 'page_contact'],
        ['POST', '#^/contact/$#', 'action_enquiry'],
        ['GET', '#^/corporate-gifting/$#', 'page_corporate'],
        ['POST', '#^/corporate-gifting/$#', 'action_enquiry'],
        ['GET', '#^/custom-box/$#', 'page_custom_box'],
        ['POST', '#^/custom-box/$#', 'action_enquiry'],
        ['GET', '#^/blog/$#', 'page_blog'],
        ['GET', '#^/blog/([a-z0-9-]+)/$#', 'page_post'],
        ['GET', '#^/search/$#', 'page_search'],
        ['GET', '#^/pincode-check/$#', 'action_pincode'],
        ['GET', '#^/sitemap\.xml$#', 'file_sitemap'],
        ['GET', '#^/sitemap_index\.xml$#', 'file_sitemap_redirect'],
        ['GET', '#^/product-sitemap\.xml$#', 'file_sitemap_redirect'],
        ['GET', '#^/robots\.txt$#', 'file_robots'],
        ['GET', '#^/llms\.txt$#', 'file_llms'],
        ['GET', '#^/feeds/(google|meta|pinterest)\.xml$#', 'file_feed'],
        ['GET', '#^/cron/run$#', 'action_cron'],
        ['GET', '#^/([a-z0-9_-]+)/$#', 'page_cms'],
    ];
    foreach ($routes as [$m, $pattern, $handler]) {
        if ($m === $method && preg_match($pattern, $path, $match)) {
            array_shift($match);
            $handler(...$match);
            return;
        }
    }
    page_not_found();
}

/** During maintenance: payment callbacks keep working, and the owner can preview with the secret link from admin. */
function maintenance_bypass(string $path): bool
{
    if (preg_match('#^/(payment/|cron/|robots\.txt|assets/|uploads/)#', $path)) {
        return true;
    }
    $key = preview_key();
    if (isset($_GET['preview_key']) && hash_equals($key, (string) $_GET['preview_key'])) {
        setcookie('tgb_preview', $key, ['expires' => time() + 7 * 86400, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
        return true;
    }
    return isset($_COOKIE['tgb_preview']) && hash_equals($key, (string) $_COOKIE['tgb_preview']);
}

function preview_key(): string
{
    return substr(hash_hmac('sha256', 'site-preview', (string) config('app_key')), 0, 24);
}

function store_view(string $template, array $data = [], array $meta = []): void
{
    $content = render('store/' . $template, $data);
    echo render('store/layout', ['content' => $content, 'meta' => $meta, 'bodyClass' => $data['bodyClass'] ?? '', 'bodyStyle' => $data['bodyStyle'] ?? '']);
}

function page_not_found(): void
{
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $r = one('SELECT * FROM redirects WHERE from_path = ? OR from_path = ?', [$path, rtrim((string) $path, '/')]);
    if ($r) {
        q('UPDATE redirects SET hits = hits + 1 WHERE id = ?', [$r['id']]);
        redirect($r['to_url'], 301);
    }
    http_response_code(404);
    store_view('404', ['popular' => products_query(['limit' => 4, 'sort' => 'popular'])],
        ['title' => 'Page not found', 'noindex' => true]);
}

/* ---------------- Catalogue ---------------- */

function page_home(): void
{
    $home = home_content();
    $featured = products_query(['featured' => true, 'limit' => 8]);
    if (count($featured) < 4) {
        $featured = products_query(['limit' => 8, 'sort' => 'manual']);
    }
    $cats = array_values(array_filter(categories_all(), fn($c) => !$c['parent_id'] && $c['product_count'] > 0));
    $posts = setting_on('blog_enabled')
        ? all("SELECT * FROM posts WHERE status = 'published' AND published_at <= ? ORDER BY published_at DESC LIMIT 3", [now()]) : [];
    $faq = $home['faq'];
    store_view('home', compact('home', 'featured', 'cats', 'posts') + ['bodyClass' => 'is-home'], [
        'title' => setting('seo_home_title'),
        'raw_title' => true,
        'description' => setting('seo_home_description'),
        'jsonld' => [[
            '@context' => 'https://schema.org', '@type' => 'FAQPage',
            'mainEntity' => array_map(fn($f) => ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']]], $faq),
        ], ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => setting('store_name'), 'url' => site_url(),
            'potentialAction' => ['@type' => 'SearchAction', 'target' => site_url('search/?q={search_term_string}'), 'query-input' => 'required name=search_term_string']]],
    ]);
}

function listing_params(): array
{
    $sort = (string) input('sort', 'manual');
    $page = max(1, (int) input('page', 1));
    return [$sort, $page, 12];
}

function page_shop(): void
{
    [$sort, $page, $per] = listing_params();
    $opts = ['sort' => $sort, 'limit' => $per, 'offset' => ($page - 1) * $per];
    $products = products_query($opts);
    $total = products_count($opts);
    store_view('shop', [
        'title' => 'All gift boxes',
        'intro' => 'Every box is hand-packed in real wood and ready to gift. Filter by occasion or browse everything below.',
        'products' => $products, 'total' => $total, 'page' => $page, 'per' => $per, 'sort' => $sort,
        'cats' => categories_all(), 'current' => null,
    ], [
        'title' => 'Shop Premium Gift Boxes & Hampers Online',
        'description' => 'Browse our full range of premium gift boxes and hampers in wooden packaging. Birthday, Valentine’s, wellness, wedding and corporate gifts with delivery across India.',
        'canonical' => site_url('shop/') . ($page > 1 ? '?page=' . $page : ''),
        'jsonld' => [breadcrumb_jsonld([['Home', '/'], ['Shop', '/shop/']])],
    ]);
}

function page_category(string $slug): void
{
    $cat = category_by_slug($slug);
    if (!$cat) {
        page_not_found();
        return;
    }
    [$sort, $page, $per] = listing_params();
    $opts = ['category' => $slug, 'sort' => $sort, 'limit' => $per, 'offset' => ($page - 1) * $per];
    store_view('shop', [
        'title' => $cat['name'], 'intro' => $cat['description'], 'category' => $cat,
        'products' => products_query($opts), 'total' => products_count($opts), 'page' => $page, 'per' => $per,
        'sort' => $sort, 'cats' => categories_all(), 'current' => $slug,
    ], [
        'title' => $cat['seo_title'] ?: $cat['name'] . ' – Premium Gift Hampers',
        'description' => $cat['seo_description'] ?: ($cat['description'] ?: 'Shop ' . $cat['name'] . ' at ' . setting('store_name') . '. Curated gift hampers in wooden boxes, delivered across India.'),
        'image' => $cat['image'] ? image_url($cat['image'], 'lg') : null,
        'canonical' => site_url(category_url($cat)) . ($page > 1 ? '?page=' . $page : ''),
        'jsonld' => [breadcrumb_jsonld([['Home', '/'], ['Shop', '/shop/'], [$cat['name'], category_url($cat)]])],
    ]);
}

function page_search(): void
{
    $term = mb_substr((string) input('q'), 0, 80);
    $products = $term !== '' ? products_query(['search' => $term, 'limit' => 48]) : [];
    store_view('shop', [
        'title' => $term !== '' ? 'Results for “' . $term . '”' : 'Search',
        'intro' => $term !== '' ? count($products) . ' gift box' . (count($products) === 1 ? '' : 'es') . ' found.' : '',
        'products' => $products, 'total' => count($products), 'page' => 1, 'per' => 48, 'sort' => 'manual',
        'cats' => categories_all(), 'current' => null, 'search' => $term,
    ], ['title' => 'Search', 'noindex' => true]);
}

function page_product(string $slug): void
{
    $row = one("SELECT * FROM products WHERE slug = ? AND status = 'published'", [$slug]);
    if (!$row) {
        page_not_found();
        return;
    }
    q('UPDATE products SET views = views + 1 WHERE id = ?', [$row['id']]);
    $p = product_full($row);
    $reviews = all("SELECT * FROM reviews WHERE product_id = ? AND status = 'approved' ORDER BY created_at DESC LIMIT 50", [$p['id']]);
    $related = [];
    if ($p['upsell_ids']) {
        $related = products_query(['ids' => explode(',', $p['upsell_ids']), 'limit' => 4]);
    }
    if (count($related) < 4 && $p['categories']) {
        $more = products_query(['category' => $p['categories'][0]['slug'], 'exclude' => $p['id'], 'limit' => 8]);
        foreach ($more as $m) {
            if (count($related) < 4 && !in_array($m['id'], array_column($related, 'id'))) {
                $related[] = $m;
            }
        }
    }
    $theme = product_box_theme($p);
    $boxTypes = [];
    foreach (box_types() as $bt) {
        $boxTypes[$bt['id']] = ['vars' => box_type_css_vars($bt), 'name' => $bt['name']];
    }
    $style = '';
    if ($theme['default'] && isset($boxTypes[$theme['default']])) {
        $style = $boxTypes[$theme['default']]['vars'];
    }
    if ($p['page_bg_color']) {
        $style = wood_vars($p['page_bg_color'], $p['page_text_color'] ?: (color_is_dark($p['page_bg_color']) ? '#F7EFE6' : '#1D1714'), setting('theme_accent')) . '--wood-image:none;--wood-grain:0;';
    }
    $firstVar = null;
    foreach ($p['variations'] as $v) {
        if ($v['enabled'] && ($p['default_attributes'] ? $v['attributes'] == $p['default_attributes'] : true)) {
            $firstVar = $v;
            break;
        }
    }
    track('view_item', ['currency' => 'INR', 'value' => $p['pricing']['min'], 'items' => [track_item($p, $firstVar, 1, (float) $p['pricing']['min'])]]);
    $crumbs = [['Home', '/'], ['Shop', '/shop/']];
    if ($p['categories']) {
        $crumbs[] = [$p['categories'][0]['name'], category_url($p['categories'][0])];
    }
    $crumbs[] = [$p['name'], product_url($p)];
    store_view('product', [
        'p' => $p, 'reviews' => $reviews, 'related' => $related, 'theme' => $theme, 'boxTypes' => $boxTypes,
        'crumbs' => $crumbs, 'inWishlist' => in_array((int) $p['id'], wishlist_ids(), true),
        'bodyClass' => 'is-product' . ($style ? ' has-wood' : ''), 'bodyStyle' => $style,
        'customColor' => (bool) $p['page_bg_color'],
    ], [
        'title' => $p['seo_title'] ?: $p['name'],
        'description' => $p['seo_description'] ?: str_limit((string) ($p['short_description'] ?: $p['description']), 158),
        'image' => $p['images'] ? image_url($p['images'][0]['path'], 'lg') : null,
        'type' => 'product',
        'price' => $p['pricing']['min'],
        'canonical' => site_url(product_url($p)),
        'jsonld' => [product_jsonld($p, $reviews), breadcrumb_jsonld($crumbs)],
    ]);
}

function action_review(string $slug): void
{
    csrf_check();
    $p = one("SELECT * FROM products WHERE slug = ? AND status = 'published'", [$slug]);
    if (!$p || !setting_on('reviews_enabled')) {
        page_not_found();
        return;
    }
    $name = mb_substr((string) input('name'), 0, 80);
    $email = (string) input('email');
    $rating = (int) input('rating');
    $body = mb_substr((string) input('body'), 0, 3000);
    if (input('website') !== '' || !$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || $rating < 1 || $rating > 5 || mb_strlen($body) < 5) {
        flash('error', 'Please fill in your name, email, a rating and a few words.');
        redirect(product_url($p) . '#reviews');
    }
    insert('reviews', ['product_id' => $p['id'], 'user_id' => customer()['id'] ?? null, 'name' => $name, 'email' => strtolower($email),
        'rating' => $rating, 'body' => $body, 'status' => 'pending', 'created_at' => now()]);
    flash('success', 'Thank you! Your review will appear once we’ve had a look at it.');
    redirect(product_url($p) . '#reviews');
}

/* ---------------- Cart ---------------- */

function wants_json(): bool
{
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

function action_cart_add(): void
{
    csrf_check();
    $pid = (int) input('product_id');
    $vid = (int) input('variation_id') ?: null;
    $qty = max(1, (int) input('qty', 1));
    $p = one("SELECT * FROM products WHERE id = ? AND status = 'published'", [$pid]);
    $error = null;
    $v = null;
    if (!$p) {
        $error = 'This product is no longer available.';
    } elseif ($p['type'] === 'variable') {
        $v = $vid ? one('SELECT * FROM variations WHERE id = ? AND product_id = ? AND enabled = 1', [$vid, $pid]) : null;
        if (!$v) {
            $error = 'Please choose an option first.';
        }
    }
    $src = $v ?? $p;
    if (!$error && $src['stock_status'] === 'outofstock') {
        $error = 'Sorry, this is out of stock right now.';
    }
    if ($error) {
        if (wants_json()) {
            json_out(['ok' => false, 'error' => $error], 422);
        }
        flash('error', $error);
        back('/shop/');
    }
    cart_add($pid, $v ? (int) $v['id'] : null, $qty);
    $price = effective_price($src)['price'];
    $event = ['currency' => 'INR', 'value' => $price * $qty, 'items' => [track_item($p, $v, $qty, (float) $price)]];
    if (wants_json()) {
        json_out(['ok' => true, 'count' => cart_count(), 'event' => $event, 'drawer' => render('store/partials/cart-drawer', ['lines' => cart_lines()])]);
    }
    track_next('add_to_cart', $event);
    flash('success', $p['name'] . ' added to your cart.');
    redirect('/cart/');
}

function action_cart_update(): void
{
    csrf_check();
    foreach ((array) ($_POST['qty'] ?? []) as $key => $qty) {
        cart_set_qty((string) $key, (int) $qty);
    }
    if ($remove = input('remove')) {
        cart_set_qty((string) $remove, 0);
    }
    if (wants_json()) {
        json_out(['ok' => true, 'count' => cart_count(), 'drawer' => render('store/partials/cart-drawer', ['lines' => cart_lines()])]);
    }
    redirect('/cart/');
}

function action_cart_coupon(): void
{
    csrf_check();
    $code = strtoupper(trim((string) input('coupon')));
    if (input('remove_coupon')) {
        unset($_SESSION['coupon']);
        flash('success', 'Coupon removed.');
        back('/cart/');
    }
    $totals = cart_totals(cart_lines(), '');
    $check = coupon_check($code, $totals['subtotal']);
    if ($check['ok']) {
        $_SESSION['coupon'] = $code;
        flash('success', 'Coupon applied. You saved ' . money($check['discount']) . '.');
    } else {
        flash('error', $check['error']);
    }
    back('/cart/');
}

function page_cart(): void
{
    $lines = cart_lines();
    $totals = cart_totals($lines);
    $cross = [];
    $ids = [];
    foreach ($lines as $l) {
        $ids = array_merge($ids, array_filter(explode(',', (string) $l['product']['cross_sell_ids'])));
    }
    if ($ids) {
        $cross = products_query(['ids' => array_unique($ids), 'limit' => 4]);
    }
    store_view('cart', compact('lines', 'totals', 'cross'), ['title' => 'Your cart', 'noindex' => true]);
}

/* ---------------- Checkout & payment ---------------- */

function checkout_fields(): array
{
    return ['email', 'phone', 'name', 'address1', 'address2', 'city', 'state', 'pincode',
        'ship_different', 'ship_name', 'ship_phone', 'ship_address1', 'ship_address2', 'ship_city', 'ship_state', 'ship_pincode',
        'gift_message', 'delivery_date', 'customer_note', 'payment_method', 'create_account', 'password'];
}

function page_checkout(): void
{
    $lines = cart_lines();
    if (setting_on('checkout_disabled')) {
        flash('error', (string) setting('checkout_disabled_text'));
        redirect('/cart/');
    }
    if (!$lines) {
        redirect('/cart/');
    }
    $totals = cart_totals($lines);
    $c = customer();
    $saved = $c ? json_arr($c['address_json']) : [];
    $prefill = [
        'email' => $c['email'] ?? '', 'name' => $c['name'] ?? '', 'phone' => $c['phone'] ?? '',
        'address1' => $saved['address1'] ?? '', 'address2' => $saved['address2'] ?? '', 'city' => $saved['city'] ?? '',
        'state' => $saved['state'] ?? 'Maharashtra', 'pincode' => $saved['pincode'] ?? '',
    ];
    track('begin_checkout', ['currency' => 'INR', 'value' => $totals['total'],
        'items' => array_map(fn($l) => track_item($l['product'], $l['variation'], $l['qty'], (float) $l['price']), $lines)]);
    store_view('checkout', ['lines' => $lines, 'totals' => $totals, 'methods' => payment_methods($totals['total']),
        'prefill' => $prefill, 'bodyClass' => 'is-checkout'], ['title' => 'Checkout', 'noindex' => true]);
}

function action_checkout_capture(): void
{
    csrf_check();
    abandoned_capture((string) input('email'), (string) input('name'), (string) input('phone'));
    json_out(['ok' => true]);
}

function action_checkout(): void
{
    csrf_check();
    remember_input();
    $lines = cart_lines();
    if (!$lines || setting_on('checkout_disabled')) {
        redirect('/cart/');
    }
    $f = [];
    foreach (checkout_fields() as $k) {
        $f[$k] = mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $k === 'gift_message' || $k === 'customer_note' ? 600 : 190);
    }
    $errors = [];
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!preg_match('/^(\+?91)?[6-9]\d{9}$/', preg_replace('/[\s-]/', '', $f['phone']))) $errors[] = 'Enter a valid 10-digit mobile number.';
    foreach (['name' => 'full name', 'address1' => 'address', 'city' => 'city', 'state' => 'state'] as $k => $label) {
        if ($f[$k] === '') $errors[] = "Enter your {$label}.";
    }
    if (!preg_match('/^\d{6}$/', $f['pincode'])) $errors[] = 'Enter a 6-digit pincode.';
    if ($f['ship_different']) {
        if ($f['ship_name'] === '' || $f['ship_address1'] === '' || $f['ship_city'] === '' || !preg_match('/^\d{6}$/', $f['ship_pincode'])) {
            $errors[] = 'Please complete the delivery address.';
        }
    }
    if ($f['delivery_date'] !== '') {
        $min = date('Y-m-d', strtotime('+' . (int) setting('delivery_min_days') . ' days'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['delivery_date']) || $f['delivery_date'] < $min) {
            $errors[] = 'The earliest delivery date we can promise is ' . nice_date($min) . '.';
        }
    }
    foreach ($lines as $l) {
        if (!$l['in_stock']) $errors[] = $l['name'] . ' is out of stock or not available in that quantity.';
    }
    $totals = cart_totals($lines, null, $f['payment_method']);
    $methods = payment_methods($totals['total']);
    if (!isset($methods[$f['payment_method']])) $errors[] = 'Choose a payment method.';
    if ($f['create_account'] && !customer()) {
        if (mb_strlen($f['password']) < 8) $errors[] = 'Choose a password with at least 8 characters.';
        elseif (one('SELECT id FROM users WHERE email = ?', [strtolower($f['email'])])) $errors[] = 'An account already exists for this email. Log in or untick “create an account”.';
    }
    if ($errors) {
        foreach ($errors as $err) flash('error', $err);
        redirect('/checkout/');
    }
    if ($f['create_account'] && !customer()) {
        $uid = insert('users', ['email' => strtolower($f['email']), 'password_hash' => password_hash($f['password'], PASSWORD_DEFAULT),
            'name' => $f['name'], 'phone' => $f['phone'], 'role' => 'customer', 'created_at' => now()]);
        customer_login(['id' => $uid]);
        wishlist_merge($uid);
    }
    if ($c = customer()) {
        update('users', ['phone' => $f['phone'], 'address_json' => json_encode(['address1' => $f['address1'], 'address2' => $f['address2'],
            'city' => $f['city'], 'state' => $f['state'], 'pincode' => $f['pincode']])], 'id = ?', [$c['id']]);
    }
    $order = order_create($lines, $totals, $f, $f['payment_method']);
    clear_old();
    $_SESSION['last_order'] = $order['id'];
    if ($f['payment_method'] === 'cod') {
        $order = order_confirm($order, 'cod');
        cart_clear();
        redirect('/order/' . $order['number'] . '/?key=' . $order['access_key']);
    }
    redirect('/pay/' . $order['number'] . '/?key=' . $order['access_key']);
}

function order_from_request(string $number): ?array
{
    $o = one('SELECT * FROM orders WHERE number = ?', [$number]);
    if (!$o) {
        return null;
    }
    $key = (string) input('key');
    $c = customer();
    if (($key && hash_equals($o['access_key'], $key)) || ($c && (int) $o['user_id'] === (int) $c['id'])) {
        return $o;
    }
    return null;
}

function page_pay(string $number): void
{
    $order = order_from_request($number);
    if (!$order) {
        page_not_found();
        return;
    }
    if (!in_array($order['status'], ['pending_payment', 'failed'], true)) {
        redirect('/order/' . $order['number'] . '/?key=' . $order['access_key']);
    }
    $data = ['order' => $order, 'error' => null];
    try {
        if ($order['payment_method'] === 'payu') {
            $data['payu'] = payu_form($order);
        } elseif ($order['payment_method'] === 'cashfree') {
            $data['cashfree'] = ['session' => cashfree_create($order), 'mode' => setting('cashfree_mode') === 'production' ? 'production' : 'sandbox'];
        }
    } catch (Throwable $e) {
        $data['error'] = $e->getMessage();
    }
    store_view('pay', $data + ['bodyClass' => 'is-checkout'], ['title' => 'Complete your payment', 'noindex' => true]);
}

function action_payu_return(): void
{
    $r = payu_verify_response($_POST);
    $order = $r['order_id'] ? order_find($r['order_id']) : null;
    if (!$order || !$r['ok'] || $order['gateway_order_id'] !== $r['txnid']) {
        app_log('payu', 'invalid return', ['post' => array_diff_key($_POST, ['hash' => 1])]);
        http_response_code(400);
        exit('We could not verify this payment. If money was deducted, please contact us with your order number.');
    }
    if ($r['status'] === 'success' && abs($r['amount'] - (float) $order['total']) < 0.01) {
        $server = payu_server_verify($r['txnid']);
        if ($server === null || $server === 'success') {
            $order = order_confirm($order, 'paid', $r['mihpayid']);
            cart_clear();
        } else {
            order_note((int) $order['id'], "PayU server verification returned '{$server}'. Please check the PayU dashboard.");
            update('orders', ['status' => 'on_hold', 'updated_at' => now()], 'id = ?', [$order['id']]);
        }
    } else {
        order_fail($order, $r['error'] ?: $r['status']);
        flash('error', 'Your payment didn’t go through. No money was taken — you can try again below.');
        redirect('/pay/' . $order['number'] . '/?key=' . $order['access_key']);
    }
    redirect('/order/' . $order['number'] . '/?key=' . $order['access_key']);
}

function action_cashfree_return(): void
{
    $cfId = (string) input('order_id');
    $order = $cfId ? one('SELECT * FROM orders WHERE gateway_order_id = ?', [$cfId]) : null;
    if (!$order) {
        page_not_found();
        return;
    }
    $order = cashfree_sync($order);
    if ($order['status'] === 'processing' || $order['payment_status'] === 'paid') {
        cart_clear();
        redirect('/order/' . $order['number'] . '/?key=' . $order['access_key']);
    }
    flash('error', 'Your payment wasn’t completed. You can try again below.');
    redirect('/pay/' . $order['number'] . '/?key=' . $order['access_key']);
}

function action_cashfree_webhook(): void
{
    $raw = (string) file_get_contents('php://input');
    if (!cashfree_webhook_valid($raw, $_SERVER['HTTP_X_WEBHOOK_TIMESTAMP'] ?? '', $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '')) {
        http_response_code(401);
        exit('invalid signature');
    }
    $data = json_decode($raw, true);
    $cfId = $data['data']['order']['order_id'] ?? '';
    if ($cfId && ($order = one('SELECT * FROM orders WHERE gateway_order_id = ?', [$cfId]))) {
        cashfree_sync($order);
    }
    json_out(['ok' => true]);
}

function page_order(string $number): void
{
    $order = order_from_request($number);
    if (!$order) {
        page_not_found();
        return;
    }
    $items = order_items((int) $order['id']);
    $justPlaced = ($_SESSION['last_order'] ?? null) === (int) $order['id'];
    if ($justPlaced && !$order['tracked'] && in_array($order['status'], ['processing', 'on_hold'], true)) {
        track('purchase', ['transaction_id' => $order['number'], 'value' => (float) $order['total'], 'currency' => 'INR',
            'shipping' => (float) $order['shipping'], 'coupon' => $order['coupon_code'],
            'items' => array_map(fn($i) => ['item_id' => 'TGB-' . $i['product_id'] . ($i['variation_id'] ? '-' . $i['variation_id'] : ''),
                'item_name' => $i['name'], 'item_variant' => $i['variation_label'], 'price' => (float) $i['price'], 'quantity' => (int) $i['qty']], $items)]);
        update('orders', ['tracked' => 1], 'id = ?', [$order['id']]);
    }
    store_view('order', ['order' => $order, 'items' => $items, 'justPlaced' => $justPlaced], ['title' => 'Order ' . $order['number'], 'noindex' => true]);
}

function action_restore_cart(string $token): void
{
    $ac = one('SELECT * FROM abandoned_carts WHERE token = ?', [$token]);
    if ($ac && $ac['status'] !== 'recovered') {
        $_SESSION['cart'] = json_arr($ac['cart_json']);
        $_SESSION['abandoned_token'] = $token;
        if ($code = setting('abandoned_coupon')) {
            $_SESSION['coupon'] = $code;
        }
    }
    redirect('/checkout/');
}

function action_pincode(): void
{
    $pin = (string) input('code');
    if (!preg_match('/^\d{6}$/', $pin)) {
        json_out(['ok' => false, 'message' => 'Enter a 6-digit pincode.']);
    }
    if (!setting_on('shiprocket_enabled') || !setting('shiprocket_password')) {
        json_out(['ok' => true, 'message' => 'We deliver across India. ' . setting('shipping_eta')]);
    }
    try {
        $r = shiprocket_serviceability($pin, 1.0);
        if ($r['ok'] && $r['days']) {
            $r['message'] = 'Delivers to ' . $pin . ' in about ' . ($r['days'] + 2) . ' days.';
        }
        json_out($r);
    } catch (Throwable $e) {
        json_out(['ok' => true, 'message' => 'We deliver across India. ' . setting('shipping_eta')]);
    }
}

/* ---------------- Account ---------------- */

function page_account(): void
{
    $c = customer();
    if (!$c) {
        store_view('account/login', ['next' => (string) input('next')], ['title' => 'My account', 'noindex' => true]);
        return;
    }
    $orders = all('SELECT * FROM orders WHERE user_id = ? OR email = ? ORDER BY created_at DESC LIMIT 50', [$c['id'], $c['email']]);
    store_view('account/dashboard', ['c' => $c, 'orders' => $orders, 'address' => json_arr($c['address_json'])], ['title' => 'My account', 'noindex' => true]);
}

function safe_next(string $next, string $fallback = '/my-account/'): string
{
    return preg_match('#^/[a-z0-9/_-]*$#i', $next) ? $next : $fallback;
}

function action_login(): void
{
    csrf_check();
    if (login_throttled('customer')) {
        flash('error', 'Too many attempts. Please wait 15 minutes and try again.');
        redirect('/my-account/');
    }
    $user = attempt_login((string) input('email'), (string) input('password'), ['customer', 'admin', 'manager'], 'customer');
    if (!$user) {
        $exists = one('SELECT password_hash FROM users WHERE email = ?', [strtolower((string) input('email'))]);
        flash('error', $exists && !$exists['password_hash']
            ? 'Welcome back! Our store has moved to a new system — please use “Forgot password” once to set a new password.'
            : 'That email and password don’t match.');
        redirect('/my-account/');
    }
    customer_login($user);
    wishlist_merge((int) $user['id']);
    redirect(safe_next((string) input('next')));
}

function action_register(): void
{
    csrf_check();
    $email = strtolower((string) input('email'));
    $name = mb_substr((string) input('name'), 0, 120);
    $pass = (string) input('password');
    if (input('website') !== '') {
        redirect('/my-account/');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($pass) < 8 || !$name) {
        flash('error', 'Enter your name, a valid email and a password of at least 8 characters.');
        redirect('/my-account/?tab=register');
    }
    if (one('SELECT id FROM users WHERE email = ?', [$email])) {
        flash('error', 'An account with this email already exists. Try logging in or resetting your password.');
        redirect('/my-account/');
    }
    $id = insert('users', ['email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'name' => $name,
        'role' => 'customer', 'created_at' => now()]);
    session_regenerate_id(true);
    customer_login(['id' => $id]);
    wishlist_merge($id);
    flash('success', 'Welcome to ' . setting('store_name') . ', ' . explode(' ', $name)[0] . '!');
    redirect('/my-account/');
}

function action_account_details(): void
{
    csrf_check();
    $c = customer() ?? redirect('/my-account/');
    $data = ['name' => mb_substr((string) input('name'), 0, 120), 'phone' => mb_substr((string) input('phone'), 0, 20),
        'address_json' => json_encode(['address1' => input('address1'), 'address2' => input('address2'), 'city' => input('city'),
            'state' => input('state'), 'pincode' => input('pincode')])];
    $newPass = (string) input('new_password');
    if ($newPass !== '') {
        if (mb_strlen($newPass) < 8) {
            flash('error', 'New password must be at least 8 characters.');
            redirect('/my-account/#details');
        }
        $data['password_hash'] = password_hash($newPass, PASSWORD_DEFAULT);
    }
    update('users', $data, 'id = ?', [$c['id']]);
    flash('success', 'Your details have been saved.');
    redirect('/my-account/#details');
}

function action_logout(): void
{
    customer_logout();
    redirect('/');
}

function page_forgot(): void
{
    store_view('account/forgot', [], ['title' => 'Reset your password', 'noindex' => true]);
}

function action_forgot(): void
{
    csrf_check();
    if (login_throttled('reset')) {
        flash('error', 'Too many requests. Please try again in 15 minutes.');
        redirect('/my-account/forgot-password/');
    }
    login_failed('reset');
    $user = one('SELECT * FROM users WHERE email = ?', [strtolower((string) input('email'))]);
    if ($user) {
        $token = random_token(24);
        update('users', ['reset_token' => hash('sha256', $token), 'reset_expires' => date('Y-m-d H:i:s', time() + 3600)], 'id = ?', [$user['id']]);
        $link = site_url('my-account/reset-password/?email=' . urlencode($user['email']) . '&token=' . $token);
        send_mail($user['email'], 'Set your password for ' . setting('store_name'),
            email_layout('Reset your password', '<p>Hi ' . e(explode(' ', $user['name'])[0] ?: 'there') . ',</p><p>Click the button below to choose a new password. The link works for one hour.</p><p>If you didn’t ask for this, you can ignore this email.</p>', ['Choose a new password', $link]));
    }
    flash('success', 'If an account exists for that email, we’ve sent a link to reset the password. Check your inbox (and spam folder).');
    redirect('/my-account/forgot-password/');
}

function reset_user(): ?array
{
    $u = one('SELECT * FROM users WHERE email = ?', [strtolower((string) input('email'))]);
    $token = (string) input('token');
    if (!$u || !$u['reset_token'] || !$token || $u['reset_expires'] < now() || !hash_equals($u['reset_token'], hash('sha256', $token))) {
        return null;
    }
    return $u;
}

function page_reset(): void
{
    $u = reset_user();
    store_view('account/reset', ['valid' => (bool) $u, 'email' => (string) input('email'), 'token' => (string) input('token')],
        ['title' => 'Choose a new password', 'noindex' => true]);
}

function action_reset(): void
{
    csrf_check();
    $u = reset_user();
    $pass = (string) input('password');
    if (!$u) {
        flash('error', 'This reset link has expired. Please request a new one.');
        redirect('/my-account/forgot-password/');
    }
    if (mb_strlen($pass) < 8) {
        flash('error', 'Password must be at least 8 characters.');
        redirect('/my-account/reset-password/?email=' . urlencode($u['email']) . '&token=' . urlencode((string) input('token')));
    }
    update('users', ['password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'reset_token' => null, 'reset_expires' => null], 'id = ?', [$u['id']]);
    session_regenerate_id(true);
    customer_login($u);
    flash('success', 'Your new password is saved and you’re logged in.');
    redirect('/my-account/');
}

/* ---------------- Track order ---------------- */

function page_track(?array $order = null, ?array $tracking = null): void
{
    store_view('track', ['order' => $order, 'tracking' => $tracking, 'items' => $order ? order_items((int) $order['id']) : []], [
        'title' => 'Track Your Order',
        'description' => 'Check the status of your Gift Boxx order and follow your parcel with the courier.',
    ]);
}

function action_track(): void
{
    csrf_check();
    if (login_throttled('track')) {
        flash('error', 'Too many attempts. Please wait a few minutes or call us.');
        redirect('/track-order/');
    }
    $number = strtoupper(trim((string) input('number')));
    $contact = strtolower(trim((string) input('contact')));
    $digits = substr(preg_replace('/\D/', '', $contact), -10);
    $order = $number !== '' ? one('SELECT * FROM orders WHERE UPPER(number) = ? OR number = ?', [$number, ltrim($number, '#')]) : null;
    $matches = $order && ($contact === strtolower($order['email']) || ($digits !== '' && strlen($digits) === 10 && str_ends_with(preg_replace('/\D/', '', $order['phone']), $digits)));
    if (!$matches) {
        login_failed('track');
        flash('error', 'We couldn’t find an order with those details. Check the order number in your confirmation email.');
        redirect('/track-order/');
    }
    $tracking = null;
    if ($order['awb'] && setting_on('shiprocket_enabled') && setting('shiprocket_password')) {
        try {
            $tracking = shiprocket_track($order['awb']);
        } catch (Throwable $e) {
            $tracking = null;
        }
    }
    page_track($order, $tracking);
}

/* ---------------- Wishlist ---------------- */

function page_wishlist(): void
{
    $ids = wishlist_ids();
    $products = $ids ? products_query(['ids' => $ids, 'limit' => 100]) : [];
    store_view('wishlist', ['products' => $products], ['title' => 'Your wishlist', 'noindex' => true]);
}

function action_wishlist_toggle(): void
{
    csrf_check();
    $pid = (int) input('product_id');
    $added = one("SELECT id FROM products WHERE id = ? AND status = 'published'", [$pid]) ? wishlist_toggle($pid) : false;
    if (wants_json()) {
        json_out(['ok' => true, 'added' => $added, 'count' => count(wishlist_ids())]);
    }
    back('/wishlist/');
}

/* ---------------- Enquiries (contact, corporate, custom box) ---------------- */

function page_contact(): void
{
    store_view('enquiry', ['type' => 'contact'], [
        'title' => 'Contact Us',
        'description' => 'Questions, custom boxes or bulk orders? Call ' . setting('store_phone') . ' or send us a message. We usually reply within a few hours.',
    ]);
}

function page_corporate(): void
{
    store_view('enquiry', ['type' => 'corporate'], [
        'title' => 'Corporate Gifting & Bulk Gift Hampers',
        'description' => 'Premium corporate gift boxes in wood for Diwali, employee onboarding and client gifting. Custom branding, bulk pricing and pan-India delivery.',
    ]);
}

function page_custom_box(): void
{
    store_view('enquiry', ['type' => 'custom_box'], [
        'title' => 'Design Your Own Gift Box',
        'description' => 'Tell us who the gift is for, your budget and your ideas. We’ll put together a custom wooden gift box and get back to you within a day.',
    ]);
}

function action_enquiry(): void
{
    csrf_check();
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $type = str_contains((string) $path, 'corporate') ? 'corporate' : (str_contains((string) $path, 'custom-box') ? 'custom_box' : 'contact');
    remember_input();
    if (input('website') !== '') { // honeypot
        redirect($path);
    }
    $f = fn($k, $len = 190) => mb_substr((string) input($k), 0, $len);
    $data = ['type' => $type, 'name' => $f('name'), 'email' => strtolower($f('email')), 'phone' => $f('phone', 40),
        'company' => $f('company'), 'occasion' => $f('occasion'), 'quantity' => $f('quantity', 60), 'budget' => $f('budget', 60),
        'needed_by' => $f('needed_by', 40), 'message' => $f('message', 5000), 'status' => 'new', 'created_at' => now()];
    if (!$data['name'] || !filter_var($data['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($data['message']) < 5) {
        flash('error', 'Please add your name, email and a short message.');
        redirect($path);
    }
    if (login_throttled('enquiry')) {
        flash('error', 'You’ve sent a few messages already. Please wait a little or call us.');
        redirect($path);
    }
    login_failed('enquiry'); // counts toward the per-IP limit
    insert('enquiries', $data);
    $labels = ['contact' => 'Contact message', 'corporate' => 'Corporate gifting enquiry', 'custom_box' => 'Custom box idea'];
    $rows = '';
    foreach (['name', 'email', 'phone', 'company', 'occasion', 'quantity', 'budget', 'needed_by'] as $k) {
        if ($data[$k] !== '') {
            $rows .= '<tr><td style="padding:4px 12px 4px 0;color:#8a7b6e">' . e(ucwords(str_replace('_', ' ', $k))) . '</td><td>' . e($data[$k]) . '</td></tr>';
        }
    }
    if ($admin = setting('store_email')) {
        send_mail($admin, $labels[$type] . ' from ' . $data['name'],
            email_layout($labels[$type], '<table>' . $rows . '</table><p style="white-space:pre-line">' . e($data['message']) . '</p>', ['Open in admin', admin_url('enquiries')]), $data['email']);
    }
    clear_old();
    flash('success', $type === 'contact'
        ? 'Thanks, ' . explode(' ', $data['name'])[0] . '! We’ve got your message and will reply soon.'
        : 'Thanks, ' . explode(' ', $data['name'])[0] . '! We’ll look at your idea and get back to you within one working day.');
    redirect($path . '?sent=1');
}

/* ---------------- Blog ---------------- */

function page_blog(): void
{
    if (!setting_on('blog_enabled')) {
        page_not_found();
        return;
    }
    $page = max(1, (int) input('page', 1));
    $posts = all("SELECT * FROM posts WHERE status = 'published' AND published_at <= ? ORDER BY published_at DESC LIMIT 12 OFFSET " . (($page - 1) * 12), [now()]);
    $total = (int) val("SELECT COUNT(*) FROM posts WHERE status = 'published' AND published_at <= ?", [now()]);
    store_view('blog', compact('posts', 'page', 'total'), [
        'title' => 'Gifting Ideas & Guides – The Gift Boxx Journal',
        'raw_title' => true,
        'description' => 'Gift ideas for every occasion, tips on choosing the right hamper, and stories from our studio.',
        'jsonld' => [breadcrumb_jsonld([['Home', '/'], ['Blog', '/blog/']])],
    ]);
}

function page_post(string $slug): void
{
    $post = one("SELECT * FROM posts WHERE slug = ? AND status = 'published' AND published_at <= ?", [$slug, now()]);
    $preview = admin_preview_allowed() ? one('SELECT * FROM posts WHERE slug = ?', [$slug]) : null;
    $post = $post ?? $preview;
    if (!$post || (!setting_on('blog_enabled') && !$preview)) {
        page_not_found();
        return;
    }
    $more = all("SELECT * FROM posts WHERE status = 'published' AND id <> ? AND published_at <= ? ORDER BY published_at DESC LIMIT 3", [$post['id'], now()]);
    $products = products_query(['featured' => true, 'limit' => 3]);
    store_view('post', compact('post', 'more', 'products'), [
        'title' => $post['seo_title'] ?: $post['title'],
        'description' => $post['seo_description'] ?: ($post['excerpt'] ?: str_limit((string) $post['content'], 158)),
        'image' => $post['cover_image'] ? image_url($post['cover_image'], 'lg') : null,
        'type' => 'article',
        'noindex' => $post['status'] !== 'published',
        'jsonld' => [[
            '@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $post['title'],
            'datePublished' => date('c', strtotime((string) ($post['published_at'] ?: $post['created_at']))),
            'dateModified' => date('c', strtotime($post['updated_at'])),
            'author' => ['@type' => 'Person', 'name' => $post['author'] ?: setting('store_name')],
            'publisher' => ['@type' => 'Organization', 'name' => setting('store_name'), 'logo' => ['@type' => 'ImageObject', 'url' => site_url('assets/img/logo-dark.svg')]],
            'image' => $post['cover_image'] ? image_url($post['cover_image'], 'lg') : null,
            'mainEntityOfPage' => site_url('blog/' . $post['slug'] . '/'),
        ], breadcrumb_jsonld([['Home', '/'], ['Blog', '/blog/'], [$post['title'], '/blog/' . $post['slug'] . '/']])],
    ]);
}

/** Admins can preview draft posts/pages by adding ?preview=<token> (token shown in admin). */
function admin_preview_allowed(): bool
{
    $t = (string) input('preview');
    return $t !== '' && hash_equals(hash_hmac('sha256', 'preview', (string) config('app_key')), $t);
}

/* ---------------- CMS pages ---------------- */

function page_cms(string $slug): void
{
    $page = one("SELECT * FROM pages WHERE slug = ? AND status = 'published'", [$slug]);
    if (!$page) {
        page_not_found();
        return;
    }
    store_view('page', ['page' => $page, 'isAbout' => $slug === 'about'], [
        'title' => $page['seo_title'] ?: $page['title'],
        'description' => $page['seo_description'] ?: str_limit((string) $page['content'], 158),
        'jsonld' => [breadcrumb_jsonld([['Home', '/'], [$page['title'], '/' . $slug . '/']])],
    ]);
}

/* ---------------- Machine-readable files ---------------- */

function file_sitemap(): void
{
    header('Content-Type: application/xml; charset=utf-8');
    echo sitemap_xml();
}

function file_sitemap_redirect(): void
{
    redirect('/sitemap.xml', 301);
}

function file_robots(): void
{
    header('Content-Type: text/plain; charset=utf-8');
    echo robots_txt();
}

function file_llms(): void
{
    header('Content-Type: text/plain; charset=utf-8');
    echo llms_txt();
}

function file_feed(string $which): void
{
    header('Content-Type: application/xml; charset=utf-8');
    header('Cache-Control: public, max-age=3600');
    echo product_feed_xml();
}

function action_cron(): void
{
    $token = (string) input('token');
    if (!$token || !hash_equals(cron_token(), $token)) {
        http_response_code(403);
        exit('forbidden');
    }
    header('Content-Type: text/plain');
    echo run_cron();
}
