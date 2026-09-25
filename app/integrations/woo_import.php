<?php
declare(strict_types=1);

/**
 * One-time migration from the old WooCommerce store using its REST API.
 * Runs in small steps (one API page per request) so shared hosting never times out.
 * Re-running is safe: records are matched by their WooCommerce ID.
 */
const WOO_STEPS = ['categories', 'brands', 'products', 'links', 'reviews', 'customers', 'orders', 'done'];

function woo_state(): array
{
    return setting_json('woo_import_state', ['step' => 'categories', 'page' => 1, 'log' => [], 'counts' => []]);
}

function woo_save_state(array $s): void
{
    $s['log'] = array_slice($s['log'], -200);
    setting_set('woo_import_state', json_encode($s, JSON_UNESCAPED_UNICODE));
}

function woo_reset(): void
{
    setting_set('woo_import_state', '');
}

function woo_get(string $path, array $query = []): array
{
    $base = rtrim((string) setting('woo_url'), '/');
    $query += ['consumer_key' => setting('woo_key'), 'consumer_secret' => setting('woo_secret')];
    $url = $base . '/wp-json/wc/v3/' . ltrim($path, '/') . '?' . http_build_query($query);
    for ($attempt = 1; $attempt <= 4; $attempt++) {
        $res = http_request('GET', $url, ['Accept: application/json'], null, 60);
        if ($res['status'] === 429 || $res['status'] >= 500 || $res['status'] === 0) {
            sleep($attempt * 3); // Hostinger rate limit / temporary error: back off and retry.
            continue;
        }
        break;
    }
    if ($res['status'] === 404 && str_starts_with($path, 'products/brands')) {
        return [];
    }
    if ($res['status'] !== 200 || !is_array($res['json'])) {
        $msg = $res['json']['message'] ?? ($res['error'] ?: 'HTTP ' . $res['status']);
        throw new RuntimeException("WooCommerce API ({$path}): {$msg}");
    }
    return $res['json'];
}

function woo_map(string $table, $wooId): ?int
{
    if (!$wooId) {
        return null;
    }
    $id = val("SELECT id FROM {$table} WHERE woo_id = ?", [(int) $wooId]);
    return $id ? (int) $id : null;
}

function woo_date(?string $d): ?string
{
    return $d ? date('Y-m-d H:i:s', strtotime($d)) : null;
}

function woo_meta(array $item, string $key): ?string
{
    foreach ($item['meta_data'] ?? [] as $m) {
        if (($m['key'] ?? '') === $key && is_string($m['value']) && $m['value'] !== '' && !str_contains($m['value'], '%')) {
            return $m['value'];
        }
    }
    return null;
}

function woo_num($v): ?float
{
    return $v === '' || $v === null ? null : (float) $v;
}

/** Run the next step. Returns the updated state. */
function woo_import_step(): array
{
    $s = woo_state();
    if ($s['step'] === 'done') {
        return $s;
    }
    $page = (int) $s['page'];
    $log = function (string $m) use (&$s) {
        $s['log'][] = date('H:i:s') . ' ' . $m;
    };
    $next = function () use (&$s) {
        $i = array_search($s['step'], WOO_STEPS, true);
        $s['step'] = WOO_STEPS[$i + 1];
        $s['page'] = 1;
    };
    $count = function (string $k, int $n) use (&$s) {
        $s['counts'][$k] = ($s['counts'][$k] ?? 0) + $n;
    };

    try {
        switch ($s['step']) {
            case 'categories':
                $rows = woo_get('products/categories', ['per_page' => 100, 'page' => $page]);
                foreach ($rows as $c) {
                    if ($c['slug'] === 'uncategorized') {
                        continue;
                    }
                    $data = ['name' => html_entity_decode($c['name'], ENT_QUOTES), 'description' => clean_html($c['description']),
                        'sort_order' => (int) ($c['menu_order'] ?? 0)];
                    // Categories created earlier (installer / CSV) have no WooCommerce ID yet: match them by URL or name.
                    $existing = woo_map('categories', $c['id'])
                        ?: (int) val('SELECT id FROM categories WHERE woo_id IS NULL AND (slug = ? OR name = ?)', [$c['slug'], $data['name']]);
                    if ($existing) {
                        update('categories', ['woo_id' => $c['id'], 'slug' => $c['slug']], 'id = ?', [$existing]);
                    }
                    if ($id = woo_map('categories', $c['id'])) {
                        update('categories', $data, 'id = ?', [$id]);
                    } else {
                        if (!empty($c['image']['src']) && ($img = save_remote_image($c['image']['src']))) {
                            $data['image'] = $img;
                        }
                        insert('categories', $data + ['slug' => unique_slug('categories', $c['slug']), 'woo_id' => $c['id']]);
                    }
                }
                foreach ($rows as $c) {
                    if ($c['parent'] && ($id = woo_map('categories', $c['id']))) {
                        update('categories', ['parent_id' => woo_map('categories', $c['parent'])], 'id = ?', [$id]);
                    }
                }
                $count('categories', count($rows));
                $log('Categories page ' . $page . ': ' . count($rows) . ' imported');
                count($rows) < 100 ? $next() : $s['page']++;
                break;

            case 'brands':
                $rows = woo_get('products/brands', ['per_page' => 100, 'page' => $page]);
                foreach ($rows as $b) {
                    $bName = html_entity_decode($b['name'], ENT_QUOTES);
                    if (!woo_map('brands', $b['id']) && ($bid = val('SELECT id FROM brands WHERE woo_id IS NULL AND name = ?', [$bName]))) {
                        update('brands', ['woo_id' => $b['id']], 'id = ?', [$bid]);
                    }
                    if (!woo_map('brands', $b['id'])) {
                        insert('brands', ['name' => html_entity_decode($b['name'], ENT_QUOTES), 'slug' => unique_slug('brands', $b['slug']), 'woo_id' => $b['id']]);
                    }
                }
                $count('brands', count($rows));
                $log('Brands: ' . count($rows) . ' imported');
                count($rows) < 100 ? $next() : $s['page']++;
                break;

            case 'products':
                $rows = woo_get('products', ['per_page' => 5, 'page' => $page, 'status' => 'any']);
                foreach ($rows as $wp) {
                    woo_import_product($wp);
                    $log('Product: ' . html_entity_decode($wp['name'], ENT_QUOTES));
                }
                $count('products', count($rows));
                count($rows) < 5 ? $next() : $s['page']++;
                break;

            case 'links':
                foreach (all('SELECT id, upsell_ids, cross_sell_ids FROM products WHERE woo_id IS NOT NULL') as $p) {
                    $mapIds = fn($csv) => implode(',', array_filter(array_map(fn($w) => woo_map('products', (int) $w), array_filter(explode(',', (string) $csv)))));
                    update('products', ['upsell_ids' => $mapIds($p['upsell_ids']), 'cross_sell_ids' => $mapIds($p['cross_sell_ids'])], 'id = ?', [$p['id']]);
                }
                $log('Linked upsells / cross-sells');
                $next();
                break;

            case 'reviews':
                $rows = woo_get('products/reviews', ['per_page' => 100, 'page' => $page, 'status' => 'all']);
                foreach ($rows as $r) {
                    $pid = woo_map('products', $r['product_id']);
                    if (!$pid || woo_map('reviews', $r['id'])) {
                        continue;
                    }
                    insert('reviews', ['product_id' => $pid, 'name' => $r['reviewer'], 'email' => $r['reviewer_email'],
                        'rating' => max(1, (int) $r['rating']), 'body' => trim(strip_tags($r['review'])),
                        'status' => $r['status'] === 'approved' ? 'approved' : 'pending', 'woo_id' => $r['id'],
                        'created_at' => woo_date($r['date_created']) ?? now()]);
                    product_rating_refresh($pid);
                }
                $count('reviews', count($rows));
                $log('Reviews page ' . $page . ': ' . count($rows));
                count($rows) < 100 ? $next() : $s['page']++;
                break;

            case 'customers':
                $rows = woo_get('customers', ['per_page' => 50, 'page' => $page, 'role' => 'all']);
                foreach ($rows as $c) {
                    $email = strtolower((string) $c['email']);
                    if (!$email || one('SELECT id FROM users WHERE email = ?', [$email])) {
                        continue;
                    }
                    $b = $c['billing'] ?? [];
                    insert('users', [
                        'email' => $email,
                        'password_hash' => null,
                        'name' => trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')) ?: ($c['username'] ?? ''),
                        'phone' => $b['phone'] ?? '',
                        'role' => 'customer',
                        'address_json' => json_encode(['address1' => $b['address_1'] ?? '', 'address2' => $b['address_2'] ?? '',
                            'city' => $b['city'] ?? '', 'state' => $b['state'] ?? '', 'pincode' => $b['postcode'] ?? '']),
                        'woo_id' => $c['id'],
                        'created_at' => woo_date($c['date_created']) ?? now(),
                    ]);
                }
                $count('customers', count($rows));
                $log('Customers page ' . $page . ': ' . count($rows));
                count($rows) < 50 ? $next() : $s['page']++;
                break;

            case 'orders':
                $rows = woo_get('orders', ['per_page' => 25, 'page' => $page, 'status' => 'any']);
                foreach ($rows as $o) {
                    woo_import_order($o);
                }
                $count('orders', count($rows));
                $log('Orders page ' . $page . ': ' . count($rows));
                count($rows) < 25 ? $next() : $s['page']++;
                break;
        }
    } catch (Throwable $e) {
        $log('⚠️ ' . $e->getMessage());
        $s['error'] = $e->getMessage();
        woo_save_state($s);
        return $s;
    }
    unset($s['error']);
    if ($s['step'] === 'done') {
        $log('✅ Migration finished.');
        foreach (all('SELECT id FROM products') as $p) {
            product_refresh_cache((int) $p['id']);
        }
    }
    woo_save_state($s);
    return $s;
}

function woo_state_map(string $s): string
{
    return ['outofstock' => 'outofstock', 'onbackorder' => 'onbackorder'][$s] ?? 'instock';
}

function woo_import_product(array $wp): void
{
    $type = $wp['type'] === 'variable' ? 'variable' : 'simple';
    $attributes = [];
    foreach ($wp['attributes'] ?? [] as $a) {
        $attributes[] = ['name' => $a['name'], 'options' => array_values($a['options']), 'variation' => (bool) ($a['variation'] ?? false), 'visible' => (bool) ($a['visible'] ?? true)];
    }
    $defaults = [];
    foreach ($wp['default_attributes'] ?? [] as $d) {
        $defaults[$d['name']] = $d['option'];
    }
    $brandWoo = $wp['brands'][0]['id'] ?? null;
    $data = [
        'type' => $type,
        'name' => html_entity_decode($wp['name'], ENT_QUOTES),
        'status' => $wp['status'] === 'publish' ? 'published' : 'draft',
        'featured' => $wp['featured'] ? 1 : 0,
        'sku' => $wp['sku'] ?: null,
        'gtin' => ($wp['global_unique_id'] ?? '') ?: null,
        'brand_id' => $brandWoo ? woo_map('brands', $brandWoo) : null,
        'short_description' => clean_html($wp['short_description']),
        'description' => clean_html($wp['description']),
        'regular_price' => woo_num($wp['regular_price']),
        'sale_price' => woo_num($wp['sale_price']),
        'sale_from' => woo_date($wp['date_on_sale_from'] ?? null),
        'sale_to' => woo_date($wp['date_on_sale_to'] ?? null),
        'manage_stock' => $wp['manage_stock'] === true ? 1 : 0,
        'stock_qty' => $wp['stock_quantity'],
        'stock_status' => woo_state_map($wp['stock_status']),
        'weight' => woo_num($wp['weight']),
        'length' => woo_num($wp['dimensions']['length'] ?? null),
        'width' => woo_num($wp['dimensions']['width'] ?? null),
        'height' => woo_num($wp['dimensions']['height'] ?? null),
        'tax_status' => $wp['tax_status'] ?: 'taxable',
        'tags' => implode(', ', array_map(fn($t) => html_entity_decode($t['name'], ENT_QUOTES), $wp['tags'] ?? [])),
        'attributes_json' => json_encode($attributes, JSON_UNESCAPED_UNICODE),
        'default_attributes_json' => json_encode($defaults, JSON_UNESCAPED_UNICODE),
        'upsell_ids' => implode(',', $wp['upsell_ids'] ?? []),
        'cross_sell_ids' => implode(',', $wp['cross_sell_ids'] ?? []),
        'seo_title' => woo_meta($wp, 'rank_math_title'),
        'seo_description' => woo_meta($wp, 'rank_math_description'),
        'focus_keyword' => woo_meta($wp, 'rank_math_focus_keyword'),
        'sort_order' => (int) ($wp['menu_order'] ?? 0),
        'rating_avg' => (float) ($wp['average_rating'] ?? 0),
        'rating_count' => (int) ($wp['rating_count'] ?? 0),
        'updated_at' => now(),
    ];
    $pid = woo_map('products', $wp['id']);
    $isNew = !$pid;
    if ($pid) {
        update('products', $data, 'id = ?', [$pid]);
    } else {
        $pid = insert('products', $data + ['slug' => unique_slug('products', $wp['slug'] ?: $wp['name']), 'woo_id' => $wp['id'],
                'created_at' => woo_date($wp['date_created']) ?? now()]);
    }
    q('DELETE FROM product_categories WHERE product_id = ?', [$pid]);
    foreach ($wp['categories'] ?? [] as $c) {
        if ($cid = woo_map('categories', $c['id'])) {
            insert('product_categories', ['product_id' => $pid, 'category_id' => $cid]);
        }
    }
    if ($isNew || !val('SELECT COUNT(*) FROM product_images WHERE product_id = ? AND variation_id IS NULL', [$pid])) {
        foreach (array_values($wp['images'] ?? []) as $i => $img) {
            if ($path = save_remote_image($img['src'])) {
                insert('product_images', ['product_id' => $pid, 'variation_id' => null, 'path' => $path, 'alt' => $img['alt'] ?: $data['name'], 'sort_order' => $i]);
            }
        }
    }
    if ($type === 'variable') {
        foreach (woo_get("products/{$wp['id']}/variations", ['per_page' => 100]) as $wv) {
            $attrs = [];
            foreach ($wv['attributes'] as $a) {
                $attrs[$a['name']] = $a['option'];
            }
            $vdata = [
                'product_id' => $pid,
                'attributes_json' => json_encode($attrs, JSON_UNESCAPED_UNICODE),
                'sku' => $wv['sku'] ?: null,
                'gtin' => ($wv['global_unique_id'] ?? '') ?: null,
                'description' => trim(strip_tags((string) $wv['description'])),
                'regular_price' => woo_num($wv['regular_price']),
                'sale_price' => woo_num($wv['sale_price']),
                'sale_from' => woo_date($wv['date_on_sale_from'] ?? null),
                'sale_to' => woo_date($wv['date_on_sale_to'] ?? null),
                'manage_stock' => $wv['manage_stock'] === true ? 1 : 0,
                'stock_qty' => $wv['stock_quantity'],
                'stock_status' => woo_state_map($wv['stock_status']),
                'weight' => woo_num($wv['weight']),
                'length' => woo_num($wv['dimensions']['length'] ?? null),
                'width' => woo_num($wv['dimensions']['width'] ?? null),
                'height' => woo_num($wv['dimensions']['height'] ?? null),
                'enabled' => $wv['status'] === 'publish' ? 1 : 0,
                'sort_order' => (int) ($wv['menu_order'] ?? 0),
            ];
            $vid = woo_map('variations', $wv['id']);
            if ($vid) {
                update('variations', $vdata, 'id = ?', [$vid]);
            } else {
                $vid = insert('variations', $vdata + ['woo_id' => $wv['id']]);
                if (!empty($wv['image']['src']) && ($path = save_remote_image($wv['image']['src']))) {
                    insert('product_images', ['product_id' => $pid, 'variation_id' => $vid, 'path' => $path, 'alt' => $data['name'], 'sort_order' => 0]);
                }
            }
        }
    }
    product_refresh_cache($pid);
}

function woo_import_order(array $o): void
{
    if ($o['status'] === 'checkout-draft' || woo_map('orders', $o['id'])) {
        return;
    }
    $status = ['pending' => 'pending_payment', 'on-hold' => 'on_hold'][$o['status']] ?? $o['status'];
    if (!isset(order_statuses()[$status])) {
        $status = 'on_hold';
    }
    $addr = fn(array $a) => [
        'name' => trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')), 'phone' => $a['phone'] ?? '',
        'email' => $a['email'] ?? '', 'address1' => $a['address_1'] ?? '', 'address2' => $a['address_2'] ?? '',
        'city' => $a['city'] ?? '', 'state' => $a['state'] ?? '', 'pincode' => $a['postcode'] ?? '', 'country' => 'India',
    ];
    $billing = $addr($o['billing'] ?? []);
    $shipping = $addr($o['shipping'] ?? []);
    if (!$shipping['address1']) {
        $shipping = $billing;
    }
    $email = strtolower((string) ($o['billing']['email'] ?? ''));
    $userId = $email ? val('SELECT id FROM users WHERE email = ?', [$email]) : null;
    $subtotal = array_sum(array_map(fn($l) => (float) $l['subtotal'], $o['line_items'] ?? []));
    $number = 'WC-' . $o['number'];
    if (one('SELECT id FROM orders WHERE number = ?', [$number])) {
        return;
    }
    $id = insert('orders', [
        'number' => $number,
        'access_key' => random_token(16),
        'user_id' => $userId ?: null,
        'email' => $email ?: 'unknown@' . parse_url(site_url(), PHP_URL_HOST),
        'phone' => $billing['phone'],
        'status' => $status,
        'payment_method' => 'woocommerce',
        'payment_status' => $o['date_paid'] ? 'paid' : 'unpaid',
        'payment_ref' => $o['transaction_id'] ?: null,
        'subtotal' => $subtotal,
        'discount' => (float) $o['discount_total'],
        'shipping' => (float) $o['shipping_total'],
        'fee' => array_sum(array_map(fn($f) => (float) $f['total'], $o['fee_lines'] ?? [])),
        'total' => (float) $o['total'],
        'coupon_code' => $o['coupon_lines'][0]['code'] ?? null,
        'billing_json' => json_encode($billing, JSON_UNESCAPED_UNICODE),
        'shipping_json' => json_encode($shipping, JSON_UNESCAPED_UNICODE),
        'customer_note' => $o['customer_note'] ?: null,
        'stock_reduced' => 1,
        'tracked' => 1,
        'woo_id' => $o['id'],
        'created_at' => woo_date($o['date_created']) ?? now(),
        'updated_at' => woo_date($o['date_modified']) ?? now(),
    ]);
    foreach ($o['line_items'] ?? [] as $l) {
        $variationLabel = implode(' · ', array_map(fn($m) => (string) $m['display_value'],
            array_filter($l['meta_data'] ?? [], fn($m) => is_string($m['display_value'] ?? null) && !str_starts_with((string) $m['key'], '_'))));
        insert('order_items', [
            'order_id' => $id,
            'product_id' => woo_map('products', $l['product_id']),
            'variation_id' => woo_map('variations', $l['variation_id']),
            'name' => $l['name'],
            'variation_label' => $variationLabel ?: null,
            'sku' => $l['sku'] ?: null,
            'qty' => (int) $l['quantity'],
            'price' => (float) $l['price'],
            'total' => (float) $l['total'],
        ]);
    }
    order_note($id, 'Imported from WooCommerce (order #' . $o['number'] . ', paid via ' . ($o['payment_method_title'] ?: 'n/a') . ').');
}
