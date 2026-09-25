<?php
declare(strict_types=1);

function admin_products(): void
{
    $status = (string) input('status');
    $cat = (int) input('category');
    $term = trim((string) input('q'));
    $page = max(1, (int) input('page', 1));
    $where = ['1=1'];
    $params = [];
    if (in_array($status, ['published', 'draft'], true)) {
        $where[] = 'p.status = ?';
        $params[] = $status;
    } elseif ($status === 'outofstock') {
        $where[] = "p.stock_status = 'outofstock'";
    } elseif ($status === 'featured') {
        $where[] = 'p.featured = 1';
    }
    if ($cat) {
        $where[] = 'p.id IN (SELECT product_id FROM product_categories WHERE category_id = ?)';
        $params[] = $cat;
    }
    if ($term !== '') {
        $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.tags LIKE ?)';
        array_push($params, "%{$term}%", "%{$term}%", "%{$term}%");
    }
    $w = implode(' AND ', $where);
    $total = (int) val("SELECT COUNT(*) FROM products p WHERE {$w}", $params);
    $per = 30;
    $rows = attach_primary_images(all("SELECT p.* FROM products p WHERE {$w} ORDER BY p.sort_order, p.created_at DESC LIMIT {$per} OFFSET " . (($page - 1) * $per), $params));
    $catsByProduct = [];
    if ($rows) {
        $ids = array_column($rows, 'id');
        foreach (all('SELECT pc.product_id, c.name FROM product_categories pc JOIN categories c ON c.id = pc.category_id WHERE pc.product_id IN (' . in_list($ids) . ')', $ids) as $r) {
            $catsByProduct[$r['product_id']][] = $r['name'];
        }
    }
    $counts = [
        'all' => (int) val('SELECT COUNT(*) FROM products'),
        'published' => (int) val("SELECT COUNT(*) FROM products WHERE status = 'published'"),
        'draft' => (int) val("SELECT COUNT(*) FROM products WHERE status = 'draft'"),
        'outofstock' => (int) val("SELECT COUNT(*) FROM products WHERE stock_status = 'outofstock'"),
    ];
    admin_view('products', compact('rows', 'catsByProduct', 'status', 'cat', 'term', 'counts') + ['pg' => admin_paginate($total, $per, $page), 'cats' => categories_all()], 'Products', 'products');
}

function admin_product_edit(?string $id = null): void
{
    if ($id) {
        $row = one('SELECT * FROM products WHERE id = ?', [(int) $id]);
        if (!$row) {
            field_error_redirect('That product no longer exists.', '/products');
        }
        $p = product_full($row);
        $p['category_ids'] = array_map('intval', array_column($p['categories'], 'id'));
    } else {
        $p = [
            'id' => null, 'type' => 'simple', 'name' => '', 'slug' => '', 'status' => 'draft', 'featured' => 0, 'sku' => '', 'gtin' => '',
            'brand_id' => null, 'box_type_id' => null, 'page_bg_color' => null, 'page_text_color' => null,
            'short_description' => '', 'description' => '', 'regular_price' => '', 'sale_price' => '', 'sale_from' => null, 'sale_to' => null,
            'manage_stock' => 0, 'stock_qty' => null, 'stock_status' => 'instock', 'weight' => '', 'length' => '', 'width' => '', 'height' => '',
            'tax_status' => 'taxable', 'tags' => '', 'attributes' => [], 'default_attributes' => [], 'upsell_ids' => '', 'cross_sell_ids' => '',
            'seo_title' => '', 'seo_description' => '', 'focus_keyword' => '', 'google_sync' => 1, 'images' => [], 'variations' => [], 'category_ids' => [],
            'views' => 0, 'rating_avg' => 0, 'rating_count' => 0,
        ];
    }
    $allProducts = all('SELECT id, name FROM products ORDER BY name');
    admin_view('product-edit', [
        'p' => $p, 'cats' => categories_all(), 'brands' => brands_all(), 'boxTypes' => box_types(), 'allProducts' => $allProducts,
        'sales' => $id ? (int) val("SELECT COALESCE(SUM(oi.qty),0) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE oi.product_id = ? AND o.status NOT IN ('pending_payment','failed','cancelled','refunded')", [(int) $id]) : 0,
    ], $id ? $p['name'] : 'New product', 'products', ['wide' => true]);
}

function num_or_null($v): ?float
{
    $v = is_string($v) ? str_replace([',', '₹', ' '], '', $v) : $v;
    return $v === '' || $v === null ? null : (float) $v;
}

function date_or_null($v): ?string
{
    return $v ? date('Y-m-d H:i:s', strtotime((string) $v)) : null;
}

function admin_product_save(): void
{
    $id = (int) input('id') ?: null;
    $name = trim((string) input('name'));
    if ($name === '') {
        field_error_redirect('Give the product a name.', $id ? "/products/{$id}" : '/products/new');
    }
    $type = input('type') === 'variable' ? 'variable' : 'simple';
    $slug = unique_slug('products', (string) (input('slug') ?: $name), $id);

    // Options such as "Box Type: Pinewood, Teakwood"
    $attributes = [];
    foreach ((array) ($_POST['attr_name'] ?? []) as $i => $attrName) {
        $attrName = trim((string) $attrName);
        $opts = array_values(array_filter(array_map('trim', explode(',', (string) ($_POST['attr_options'][$i] ?? ''))), 'strlen'));
        if ($attrName !== '' && $opts) {
            $attributes[] = ['name' => $attrName, 'options' => $opts, 'variation' => !empty($_POST['attr_variation'][$i]) || $type === 'variable' && empty($_POST['attr_info'][$i]), 'visible' => true];
        }
    }
    if ($type === 'simple') {
        $attributes = array_values(array_filter($attributes, fn($a) => !$a['variation']));
    }
    $defaults = [];
    foreach ((array) ($_POST['default_attr'] ?? []) as $k => $v) {
        if ($v !== '') {
            $defaults[$k] = $v;
        }
    }
    $data = [
        'type' => $type,
        'name' => $name,
        'slug' => $slug,
        'status' => input('status') === 'published' ? 'published' : 'draft',
        'featured' => input('featured') ? 1 : 0,
        'sku' => input('sku') ?: null,
        'gtin' => input('gtin') ?: null,
        'brand_id' => (int) input('brand_id') ?: null,
        'box_type_id' => (int) input('box_type_id') ?: null,
        'page_bg_color' => input('use_custom_color') && preg_match('/^#[0-9a-f]{6}$/i', (string) input('page_bg_color')) ? input('page_bg_color') : null,
        'page_text_color' => input('use_custom_color') && preg_match('/^#[0-9a-f]{6}$/i', (string) input('page_text_color')) ? input('page_text_color') : null,
        'short_description' => clean_html((string) input('short_description')),
        'description' => clean_html((string) input('description')),
        'regular_price' => num_or_null(input('regular_price')),
        'sale_price' => num_or_null(input('sale_price')),
        'sale_from' => date_or_null(input('sale_from')),
        'sale_to' => date_or_null(input('sale_to')),
        'manage_stock' => input('manage_stock') ? 1 : 0,
        'stock_qty' => input('manage_stock') ? (int) input('stock_qty') : null,
        'stock_status' => in_array(input('stock_status'), ['instock', 'outofstock', 'onbackorder'], true) ? input('stock_status') : 'instock',
        'weight' => num_or_null(input('weight')),
        'length' => num_or_null(input('length')),
        'width' => num_or_null(input('width')),
        'height' => num_or_null(input('height')),
        'tax_status' => input('tax_status') === 'none' ? 'none' : 'taxable',
        'tags' => implode(', ', array_filter(array_map('trim', explode(',', (string) input('tags'))))),
        'attributes_json' => json_encode($attributes, JSON_UNESCAPED_UNICODE),
        'default_attributes_json' => json_encode($defaults, JSON_UNESCAPED_UNICODE),
        'upsell_ids' => implode(',', array_map('intval', (array) ($_POST['upsell_ids'] ?? []))),
        'cross_sell_ids' => implode(',', array_map('intval', (array) ($_POST['cross_sell_ids'] ?? []))),
        'seo_title' => input('seo_title') ?: null,
        'seo_description' => input('seo_description') ?: null,
        'focus_keyword' => input('focus_keyword') ?: null,
        'google_sync' => input('google_sync') ? 1 : 0,
        'updated_at' => now(),
    ];
    if ($data['manage_stock'] && $type === 'simple') {
        $data['stock_status'] = $data['stock_qty'] > 0 ? 'instock' : 'outofstock';
    }
    transaction(function () use (&$id, $data, $type) {
        if ($id) {
            update('products', $data, 'id = ?', [$id]);
        } else {
            $id = insert('products', $data + ['created_at' => now()]);
        }
        // Categories
        q('DELETE FROM product_categories WHERE product_id = ?', [$id]);
        foreach (array_unique(array_map('intval', (array) ($_POST['categories'] ?? []))) as $cid) {
            insert('product_categories', ['product_id' => $id, 'category_id' => $cid]);
        }
        // Gallery images (already uploaded via /upload, sent back in order)
        $keep = [];
        foreach ((array) ($_POST['images'] ?? []) as $i => $path) {
            $path = (string) $path;
            if (!preg_match('#^[\w/.-]+$#', $path)) {
                continue;
            }
            $alt = trim((string) ($_POST['image_alt'][$i] ?? ''));
            $existing = one('SELECT id FROM product_images WHERE product_id = ? AND variation_id IS NULL AND path = ?', [$id, $path]);
            if ($existing) {
                update('product_images', ['sort_order' => $i, 'alt' => $alt ?: null], 'id = ?', [$existing['id']]);
                $keep[] = (int) $existing['id'];
            } else {
                $keep[] = insert('product_images', ['product_id' => $id, 'variation_id' => null, 'path' => $path, 'alt' => $alt ?: null, 'sort_order' => $i]);
            }
        }
        q('DELETE FROM product_images WHERE product_id = ? AND variation_id IS NULL' . ($keep ? ' AND id NOT IN (' . in_list($keep) . ')' : ''), array_merge([$id], $keep));

        // Variations
        if ($type === 'variable') {
            $keepVars = [];
            foreach ((array) ($_POST['var'] ?? []) as $v) {
                $attrs = [];
                foreach ((array) ($v['attrs'] ?? []) as $k => $val) {
                    $attrs[(string) $k] = (string) $val;
                }
                $vdata = [
                    'product_id' => $id,
                    'attributes_json' => json_encode($attrs, JSON_UNESCAPED_UNICODE),
                    'sku' => ($v['sku'] ?? '') ?: null,
                    'gtin' => ($v['gtin'] ?? '') ?: null,
                    'description' => trim((string) ($v['description'] ?? '')),
                    'regular_price' => num_or_null($v['regular_price'] ?? null),
                    'sale_price' => num_or_null($v['sale_price'] ?? null),
                    'sale_from' => date_or_null($v['sale_from'] ?? null),
                    'sale_to' => date_or_null($v['sale_to'] ?? null),
                    'manage_stock' => !empty($v['manage_stock']) ? 1 : 0,
                    'stock_qty' => !empty($v['manage_stock']) ? (int) ($v['stock_qty'] ?? 0) : null,
                    'stock_status' => in_array($v['stock_status'] ?? '', ['instock', 'outofstock', 'onbackorder'], true) ? $v['stock_status'] : 'instock',
                    'weight' => num_or_null($v['weight'] ?? null),
                    'length' => num_or_null($v['length'] ?? null),
                    'width' => num_or_null($v['width'] ?? null),
                    'height' => num_or_null($v['height'] ?? null),
                    'enabled' => !empty($v['enabled']) ? 1 : 0,
                    'sort_order' => count($keepVars),
                ];
                if ($vdata['manage_stock']) {
                    $vdata['stock_status'] = $vdata['stock_qty'] > 0 ? 'instock' : 'outofstock';
                }
                $vid = (int) ($v['id'] ?? 0);
                if ($vid && one('SELECT id FROM variations WHERE id = ? AND product_id = ?', [$vid, $id])) {
                    update('variations', $vdata, 'id = ?', [$vid]);
                } else {
                    $vid = insert('variations', $vdata);
                }
                $keepVars[] = $vid;
                // Variation image (one main photo per option)
                q('DELETE FROM product_images WHERE variation_id = ?', [$vid]);
                foreach (array_filter((array) ($v['images'] ?? [])) as $i => $path) {
                    if (preg_match('#^[\w/.-]+$#', (string) $path)) {
                        insert('product_images', ['product_id' => $id, 'variation_id' => $vid, 'path' => $path, 'alt' => null, 'sort_order' => $i]);
                    }
                }
            }
            $gone = $keepVars ? all('SELECT id FROM variations WHERE product_id = ? AND id NOT IN (' . in_list($keepVars) . ')', array_merge([$id], $keepVars))
                : all('SELECT id FROM variations WHERE product_id = ?', [$id]);
            foreach ($gone as $g) {
                q('DELETE FROM product_images WHERE variation_id = ?', [$g['id']]);
                q('DELETE FROM variations WHERE id = ?', [$g['id']]);
            }
        }
    });
    product_refresh_cache($id);
    flash('success', 'Saved “' . $name . '”.');
    redirect('/products/' . $id);
}

function admin_product_delete(string $id): void
{
    $id = (int) $id;
    q('DELETE FROM product_categories WHERE product_id = ?', [$id]);
    q('DELETE FROM variations WHERE product_id = ?', [$id]);
    q('DELETE FROM product_images WHERE product_id = ?', [$id]);
    q('DELETE FROM reviews WHERE product_id = ?', [$id]);
    q('DELETE FROM wishlist WHERE product_id = ?', [$id]);
    q('DELETE FROM products WHERE id = ?', [$id]);
    flash('success', 'Product deleted.');
    redirect('/products');
}

function admin_product_duplicate(string $id): void
{
    $p = one('SELECT * FROM products WHERE id = ?', [(int) $id]);
    if (!$p) {
        redirect('/products');
    }
    $newId = transaction(function () use ($p) {
        $copy = $p;
        unset($copy['id']);
        $copy['name'] .= ' (copy)';
        $copy['slug'] = unique_slug('products', $p['slug'] . '-copy');
        $copy['status'] = 'draft';
        $copy['views'] = 0;
        $copy['rating_avg'] = 0;
        $copy['rating_count'] = 0;
        $copy['woo_id'] = null;
        $copy['created_at'] = $copy['updated_at'] = now();
        $newId = insert('products', $copy);
        foreach (all('SELECT category_id FROM product_categories WHERE product_id = ?', [$p['id']]) as $c) {
            insert('product_categories', ['product_id' => $newId, 'category_id' => $c['category_id']]);
        }
        $varMap = [];
        foreach (all('SELECT * FROM variations WHERE product_id = ?', [$p['id']]) as $v) {
            $old = $v['id'];
            unset($v['id']);
            $v['product_id'] = $newId;
            $v['woo_id'] = null;
            $varMap[$old] = insert('variations', $v);
        }
        foreach (all('SELECT * FROM product_images WHERE product_id = ?', [$p['id']]) as $img) {
            unset($img['id']);
            $img['product_id'] = $newId;
            $img['variation_id'] = $img['variation_id'] ? ($varMap[$img['variation_id']] ?? null) : null;
            insert('product_images', $img);
        }
        return $newId;
    });
    product_refresh_cache($newId);
    flash('success', 'Duplicated. You’re now editing the copy (saved as a draft).');
    redirect('/products/' . $newId);
}

function admin_products_bulk(): void
{
    $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
    $action = (string) input('action');
    if (!$ids) {
        field_error_redirect('Select at least one product.', '/products');
    }
    foreach ($ids as $id) {
        match ($action) {
            'publish' => update('products', ['status' => 'published'], 'id = ?', [$id]),
            'draft' => update('products', ['status' => 'draft'], 'id = ?', [$id]),
            'feature' => update('products', ['featured' => 1], 'id = ?', [$id]),
            'unfeature' => update('products', ['featured' => 0], 'id = ?', [$id]),
            'outofstock' => update('products', ['stock_status' => 'outofstock'], 'id = ?', [$id]),
            'instock' => update('products', ['stock_status' => 'instock'], 'id = ?', [$id]),
            default => null,
        };
    }
    if ($action === 'delete') {
        foreach ($ids as $id) {
            q('DELETE FROM product_categories WHERE product_id = ?', [$id]);
            q('DELETE FROM variations WHERE product_id = ?', [$id]);
            q('DELETE FROM product_images WHERE product_id = ?', [$id]);
            q('DELETE FROM products WHERE id = ?', [$id]);
        }
    }
    flash('success', count($ids) . ' product' . (count($ids) === 1 ? '' : 's') . ' updated.');
    redirect('/products');
}

/** Image upload used by every image picker in the admin (returns JSON). */
function admin_upload(): void
{
    $files = $_FILES['file'] ?? null;
    if (!$files) {
        json_out(['ok' => false, 'error' => 'No file received. The image may be larger than the server allows.'], 422);
    }
    $out = [];
    $list = is_array($files['name']) ? array_keys($files['name']) : [null];
    foreach ($list as $i) {
        $f = $i === null ? $files : ['name' => $files['name'][$i], 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
        try {
            $path = save_uploaded_image($f);
            $out[] = ['path' => $path, 'url' => image_url($path, 'sm'), 'full' => image_url($path, '')];
        } catch (Throwable $e) {
            json_out(['ok' => false, 'error' => $f['name'] . ': ' . $e->getMessage()], 422);
        }
    }
    json_out(['ok' => true, 'files' => $out]);
}

/* ---------- Categories, brands, box types ---------- */

function admin_categories(): void
{
    $edit = ($id = (int) input('edit')) ? one('SELECT * FROM categories WHERE id = ?', [$id]) : null;
    admin_view('categories', ['cats' => categories_all(), 'edit' => $edit], 'Categories', 'categories');
}

function admin_category_save(): void
{
    $id = (int) input('id') ?: null;
    $name = trim((string) input('name'));
    if ($name === '') {
        field_error_redirect('Category name is required.', '/categories');
    }
    $data = [
        'name' => $name,
        'slug' => unique_slug('categories', (string) (input('slug') ?: $name), $id),
        'parent_id' => (int) input('parent_id') ?: null,
        'description' => clean_html((string) input('description')),
        'image' => input('image') ?: null,
        'seo_title' => input('seo_title') ?: null,
        'seo_description' => input('seo_description') ?: null,
        'sort_order' => (int) input('sort_order'),
    ];
    if ($id) {
        update('categories', $data, 'id = ?', [$id]);
    } else {
        insert('categories', $data);
    }
    flash('success', 'Category saved.');
    redirect('/categories');
}

function admin_category_delete(string $id): void
{
    q('DELETE FROM product_categories WHERE category_id = ?', [(int) $id]);
    q('UPDATE categories SET parent_id = NULL WHERE parent_id = ?', [(int) $id]);
    q('DELETE FROM categories WHERE id = ?', [(int) $id]);
    flash('success', 'Category deleted. Products in it were kept.');
    redirect('/categories');
}

function admin_brands(): void
{
    $brands = all('SELECT b.*, (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id) AS n FROM brands b ORDER BY name');
    admin_view('brands', ['brands' => $brands], 'Brands', 'categories');
}

function admin_brand_save(): void
{
    $id = (int) input('id') ?: null;
    $name = trim((string) input('name'));
    if ($name !== '') {
        $id ? update('brands', ['name' => $name], 'id = ?', [$id]) : insert('brands', ['name' => $name, 'slug' => unique_slug('brands', $name)]);
    }
    redirect('/brands');
}

function admin_brand_delete(string $id): void
{
    q('UPDATE products SET brand_id = NULL WHERE brand_id = ?', [(int) $id]);
    q('DELETE FROM brands WHERE id = ?', [(int) $id]);
    redirect('/brands');
}

function admin_box_types(): void
{
    $edit = ($id = (int) input('edit')) ? one('SELECT * FROM box_types WHERE id = ?', [$id]) : null;
    admin_view('box-types', ['types' => box_types(), 'edit' => $edit], 'Box types', 'box-types');
}

function admin_box_type_save(): void
{
    $id = (int) input('id') ?: null;
    $name = trim((string) input('name'));
    if ($name === '') {
        field_error_redirect('Name the box type (e.g. Pinewood).', '/box-types');
    }
    $hex = fn($k, $d) => preg_match('/^#[0-9a-f]{6}$/i', (string) input($k)) ? input($k) : $d;
    $data = [
        'name' => $name,
        'keywords' => trim((string) input('keywords')),
        'bg_color' => $hex('bg_color', '#F5EEE4'),
        'text_color' => $hex('text_color', '#1D1714'),
        'accent_color' => $hex('accent_color', '#BAA183'),
        'bg_image' => input('bg_image') ?: null,
        'grain' => input('grain') ? 1 : 0,
        'description' => trim((string) input('description')),
        'sort_order' => (int) input('sort_order'),
    ];
    $id ? update('box_types', $data, 'id = ?', [$id]) : insert('box_types', $data);
    flash('success', 'Box type “' . $name . '” saved. Product pages using it update instantly.');
    redirect('/box-types');
}

function admin_box_type_delete(string $id): void
{
    q('UPDATE products SET box_type_id = NULL WHERE box_type_id = ?', [(int) $id]);
    q('DELETE FROM box_types WHERE id = ?', [(int) $id]);
    redirect('/box-types');
}

/* ---------- Reviews ---------- */

function admin_reviews(): void
{
    $status = input('status', 'pending');
    $rows = all('SELECT r.*, p.name AS product_name, p.slug FROM reviews r LEFT JOIN products p ON p.id = r.product_id'
        . ($status !== 'all' ? ' WHERE r.status = ?' : '') . ' ORDER BY r.created_at DESC LIMIT 200', $status !== 'all' ? [$status] : []);
    admin_view('reviews', ['rows' => $rows, 'status' => $status], 'Reviews', 'reviews');
}

function admin_review_action(string $id, string $action): void
{
    $r = one('SELECT * FROM reviews WHERE id = ?', [(int) $id]);
    if ($r) {
        if ($action === 'delete') {
            q('DELETE FROM reviews WHERE id = ?', [$r['id']]);
        } else {
            update('reviews', ['status' => $action === 'approve' ? 'approved' : 'pending'], 'id = ?', [$r['id']]);
        }
        product_rating_refresh((int) $r['product_id']);
    }
    back('/reviews');
}
