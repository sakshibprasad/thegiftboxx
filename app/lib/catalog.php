<?php
declare(strict_types=1);

/** Price that applies right now, honouring scheduled sales. */
function effective_price(array $row): array
{
    $regular = $row['regular_price'] !== null && $row['regular_price'] !== '' ? (float) $row['regular_price'] : null;
    $sale = $row['sale_price'] !== null && $row['sale_price'] !== '' ? (float) $row['sale_price'] : null;
    $now = now();
    $saleActive = $sale !== null
        && ($regular === null || $sale < $regular)
        && (empty($row['sale_from']) || $row['sale_from'] <= $now)
        && (empty($row['sale_to']) || $row['sale_to'] >= $now);
    return [
        'price' => $saleActive ? $sale : $regular,
        'regular' => $regular,
        'on_sale' => $saleActive,
    ];
}

function product_url(array $p): string
{
    return '/product/' . $p['slug'] . '/';
}

function category_url(array $c): string
{
    return '/product-category/' . $c['slug'] . '/';
}

function stock_label(string $status): string
{
    return ['instock' => 'In stock', 'outofstock' => 'Out of stock', 'onbackorder' => 'Made to order'][$status] ?? $status;
}

function variation_label(array $attrs): string
{
    return implode(' · ', array_map(fn($k, $v) => $v, array_keys($attrs), $attrs));
}

function categories_all(): array
{
    static $cache = null;
    return $cache ??= all('SELECT c.*, (SELECT COUNT(*) FROM product_categories pc JOIN products p ON p.id = pc.product_id
        WHERE pc.category_id = c.id AND p.status = \'published\') AS product_count FROM categories c ORDER BY c.sort_order, c.name');
}

function category_by_slug(string $slug): ?array
{
    return one('SELECT * FROM categories WHERE slug = ?', [$slug]);
}

function brands_all(): array
{
    return all('SELECT * FROM brands ORDER BY name');
}

/**
 * List published products for the storefront.
 * $o: category (slug), search, featured (bool), ids (array), sort, limit, offset, include_drafts
 */
function products_query(array $o = []): array
{
    [$where, $params] = products_where($o);
    $sort = [
        'latest' => 'p.created_at DESC',
        'price_asc' => 'p.price_min ASC',
        'price_desc' => 'p.price_max DESC',
        'popular' => 'p.views DESC',
        'rating' => 'p.rating_avg DESC, p.rating_count DESC',
        'manual' => 'p.sort_order ASC, p.created_at DESC',
    ][$o['sort'] ?? 'manual'] ?? 'p.sort_order ASC, p.created_at DESC';
    $limit = max(1, min(200, (int) ($o['limit'] ?? 24)));
    $offset = max(0, (int) ($o['offset'] ?? 0));
    $rows = all("SELECT p.* FROM products p WHERE {$where} ORDER BY {$sort} LIMIT {$limit} OFFSET {$offset}", $params);
    return attach_primary_images($rows);
}

function products_count(array $o = []): int
{
    [$where, $params] = products_where($o);
    return (int) val("SELECT COUNT(*) FROM products p WHERE {$where}", $params);
}

function products_where(array $o): array
{
    $where = empty($o['include_drafts']) ? ["p.status = 'published'"] : ['1=1'];
    $params = [];
    if (!empty($o['category'])) {
        $where[] = 'p.id IN (SELECT pc.product_id FROM product_categories pc JOIN categories c ON c.id = pc.category_id WHERE c.slug = ? OR c.parent_id = (SELECT id FROM categories WHERE slug = ?))';
        $params[] = $o['category'];
        $params[] = $o['category'];
    }
    if (!empty($o['search'])) {
        $where[] = '(p.name LIKE ? OR p.tags LIKE ? OR p.short_description LIKE ? OR p.sku LIKE ?)';
        $like = '%' . $o['search'] . '%';
        array_push($params, $like, $like, $like, $like);
    }
    if (!empty($o['featured'])) {
        $where[] = 'p.featured = 1';
    }
    if (isset($o['ids'])) {
        $ids = array_map('intval', (array) $o['ids']) ?: [0];
        $where[] = 'p.id IN (' . in_list($ids) . ')';
        $params = array_merge($params, $ids);
    }
    if (!empty($o['exclude'])) {
        $where[] = 'p.id <> ?';
        $params[] = (int) $o['exclude'];
    }
    return [implode(' AND ', $where), $params];
}

function attach_primary_images(array $rows): array
{
    if (!$rows) {
        return [];
    }
    $ids = array_column($rows, 'id');
    $imgs = [];
    foreach (all('SELECT product_id, path, alt FROM product_images WHERE variation_id IS NULL AND product_id IN (' . in_list($ids) . ') ORDER BY sort_order, id', $ids) as $img) {
        $imgs[$img['product_id']][] = $img;
    }
    foreach ($rows as &$r) {
        $r['image'] = $imgs[$r['id']][0]['path'] ?? null;
        $r['image_alt'] = $imgs[$r['id']][0]['alt'] ?? $r['name'];
        $r['image_hover'] = $imgs[$r['id']][1]['path'] ?? null;
    }
    return $rows;
}

/** Full product with images, variations, categories and brand. */
function product_full(array $p): array
{
    $p['images'] = all('SELECT * FROM product_images WHERE product_id = ? AND variation_id IS NULL ORDER BY sort_order, id', [$p['id']]);
    $p['categories'] = all('SELECT c.* FROM categories c JOIN product_categories pc ON pc.category_id = c.id WHERE pc.product_id = ? ORDER BY c.sort_order, c.name', [$p['id']]);
    $p['brand'] = $p['brand_id'] ? one('SELECT * FROM brands WHERE id = ?', [$p['brand_id']]) : null;
    $p['attributes'] = json_arr($p['attributes_json']);
    $p['default_attributes'] = json_arr($p['default_attributes_json']);
    $p['variations'] = [];
    if ($p['type'] === 'variable') {
        $vImages = [];
        foreach (all('SELECT * FROM product_images WHERE product_id = ? AND variation_id IS NOT NULL ORDER BY sort_order, id', [$p['id']]) as $img) {
            $vImages[$img['variation_id']][] = $img;
        }
        foreach (all('SELECT * FROM variations WHERE product_id = ? ORDER BY sort_order, id', [$p['id']]) as $v) {
            $v['attributes'] = json_arr($v['attributes_json']);
            $v['images'] = $vImages[$v['id']] ?? [];
            $v['pricing'] = effective_price($v);
            $p['variations'][] = $v;
        }
    }
    $p['pricing'] = product_price_range($p);
    return $p;
}

function product_price_range(array $p): array
{
    if ($p['type'] === 'variable') {
        $prices = [];
        $regulars = [];
        $onSale = false;
        $vars = $p['variations'] ?? all('SELECT * FROM variations WHERE product_id = ? AND enabled = 1', [$p['id']]);
        foreach ($vars as $v) {
            if (isset($v['enabled']) && !$v['enabled']) {
                continue;
            }
            $ep = $v['pricing'] ?? effective_price($v);
            if ($ep['price'] !== null) {
                $prices[] = $ep['price'];
                $regulars[] = $ep['regular'] ?? $ep['price'];
                $onSale = $onSale || $ep['on_sale'];
            }
        }
        if (!$prices) {
            return ['min' => null, 'max' => null, 'on_sale' => false, 'regular_min' => null];
        }
        return ['min' => min($prices), 'max' => max($prices), 'on_sale' => $onSale, 'regular_min' => min($regulars)];
    }
    $ep = effective_price($p);
    return ['min' => $ep['price'], 'max' => $ep['price'], 'on_sale' => $ep['on_sale'], 'regular_min' => $ep['regular']];
}

/** HTML for a price or price range. */
function price_html(array $range): string
{
    if ($range['min'] === null) {
        return '<span class="price">Price on request</span>';
    }
    if ($range['min'] != $range['max']) {
        return '<span class="price">' . money($range['min']) . ' – ' . money($range['max']) . '</span>';
    }
    $html = '<span class="price">';
    if ($range['on_sale'] && $range['regular_min'] > $range['min']) {
        $html .= '<del>' . money($range['regular_min']) . '</del> ';
    }
    return $html . '<ins>' . money($range['min']) . '</ins></span>';
}

/** Recalculate cached min/max prices + stock status after an edit or order. */
function product_refresh_cache(int $productId): void
{
    $p = one('SELECT * FROM products WHERE id = ?', [$productId]);
    if (!$p) {
        return;
    }
    $range = product_price_range($p);
    $data = ['price_min' => $range['min'] ?? 0, 'price_max' => $range['max'] ?? 0];
    if ($p['type'] === 'variable') {
        $inStock = (int) val("SELECT COUNT(*) FROM variations WHERE product_id = ? AND enabled = 1 AND stock_status <> 'outofstock'", [$productId]);
        $data['stock_status'] = $inStock ? 'instock' : 'outofstock';
    }
    update('products', $data, 'id = ?', [$productId]);
}

function product_rating_refresh(int $productId): void
{
    $r = one("SELECT AVG(rating) AS a, COUNT(*) AS c FROM reviews WHERE product_id = ? AND status = 'approved'", [$productId]);
    update('products', ['rating_avg' => round((float) ($r['a'] ?? 0), 2), 'rating_count' => (int) ($r['c'] ?? 0)], 'id = ?', [$productId]);
}

function stars_html(float $rating): string
{
    $out = '<span class="stars" aria-label="Rated ' . e(number_format($rating, 1)) . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) {
        $out .= '<span class="star ' . ($rating >= $i - 0.25 ? 'on' : ($rating >= $i - 0.75 ? 'half' : '')) . '">★</span>';
    }
    return $out . '</span>';
}

function unique_slug(string $table, string $slug, ?int $ignoreId = null): string
{
    $base = slugify($slug);
    $candidate = $base;
    $i = 2;
    while (true) {
        $row = one("SELECT id FROM {$table} WHERE slug = ?", [$candidate]);
        if (!$row || ($ignoreId && (int) $row['id'] === $ignoreId)) {
            return $candidate;
        }
        $candidate = $base . '-' . $i++;
    }
}

/* ---------- Wishlist ---------- */

function wishlist_ids(): array
{
    if ($c = customer()) {
        return array_map('intval', array_column(all('SELECT product_id FROM wishlist WHERE user_id = ?', [$c['id']]), 'product_id'));
    }
    return array_map('intval', $_SESSION['wishlist'] ?? []);
}

function wishlist_toggle(int $productId): bool
{
    $ids = wishlist_ids();
    $has = in_array($productId, $ids, true);
    if ($c = customer()) {
        if ($has) {
            q('DELETE FROM wishlist WHERE user_id = ? AND product_id = ?', [$c['id'], $productId]);
        } else {
            insert('wishlist', ['user_id' => $c['id'], 'product_id' => $productId, 'created_at' => now()]);
        }
    } else {
        $_SESSION['wishlist'] = $has ? array_values(array_diff($ids, [$productId])) : array_merge($ids, [$productId]);
    }
    return !$has;
}

/** Move a guest's wishlist into their account after login. */
function wishlist_merge(int $userId): void
{
    foreach ($_SESSION['wishlist'] ?? [] as $pid) {
        if (!one('SELECT product_id FROM wishlist WHERE user_id = ? AND product_id = ?', [$userId, (int) $pid])) {
            insert('wishlist', ['user_id' => $userId, 'product_id' => (int) $pid, 'created_at' => now()]);
        }
    }
    unset($_SESSION['wishlist']);
}

/* ---------- Box types (wood) ---------- */

function box_types(): array
{
    static $cache = null;
    return $cache ??= all('SELECT * FROM box_types ORDER BY sort_order, name');
}

/** Find the box type matching a variation value such as "Pinewood" or "Premium Teak Wood". */
function box_type_match(string $value): ?array
{
    $value = strtolower($value);
    foreach (box_types() as $bt) {
        $words = array_filter(array_map('trim', explode(',', strtolower($bt['name'] . ',' . $bt['keywords']))));
        foreach ($words as $w) {
            if ($w !== '' && str_contains(str_replace(' ', '', $value), str_replace(' ', '', $w))) {
                return $bt;
            }
        }
    }
    return null;
}

/** Map of variation id => box type id, plus the product-level default. */
function product_box_theme(array $p): array
{
    $map = [];
    foreach ($p['variations'] as $v) {
        foreach ($v['attributes'] as $val) {
            if ($bt = box_type_match((string) $val)) {
                $map[$v['id']] = (int) $bt['id'];
                break;
            }
        }
    }
    $default = $p['box_type_id'] ? (int) $p['box_type_id'] : null;
    if (!$default && $p['variations']) {
        $defAttrs = $p['default_attributes'];
        foreach ($p['variations'] as $v) {
            if ($defAttrs && $v['attributes'] == $defAttrs && isset($map[$v['id']])) {
                $default = $map[$v['id']];
            }
        }
        $default ??= $map ? reset($map) : null;
    }
    return ['map' => $map, 'default' => $default];
}

function box_type_css_vars(array $bt): string
{
    return wood_vars((string) $bt['bg_color'], (string) $bt['text_color'], (string) $bt['accent_color'])
        . ($bt['bg_image'] ? '--wood-image:url(' . image_url($bt['bg_image'], 'lg') . ');' : '--wood-image:none;')
        . '--wood-grain:' . ($bt['grain'] ? '1' : '0') . ';';
}

/** Colour variables for a product-page theme. Works for light and dark woods. */
function wood_vars(string $bg, string $text, string $accent): string
{
    $dark = color_is_dark($bg);
    return sprintf('--wood-bg:%s;--wood-text:%s;--wood-accent:%s;--wood-btn-bg:%s;--wood-btn-fg:%s;--wood-logo:%s;',
        $bg, $text, $accent, $dark ? $text : '#241913', $dark ? $bg : '#FFFFFF', $dark ? 'invert(1) brightness(1.6)' : 'none');
}

function color_is_dark(string $hex): bool
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) {
        return false;
    }
    [$r, $g, $b] = array_map('hexdec', str_split($hex, 2));
    return (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255 < 0.5;
}
