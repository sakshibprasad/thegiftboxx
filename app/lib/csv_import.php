<?php
declare(strict_types=1);

/**
 * Import products from a WooCommerce product CSV export
 * (WooCommerce → Products → Export). Also used by the installer to load
 * the products from the old site (app/seed/woocommerce-products.csv).
 * Matching is by the WooCommerce "ID" column, so importing twice updates instead of duplicating.
 */
function import_woo_csv(string $file, bool $downloadImages = true, array $slugMap = []): array
{
    $fh = fopen($file, 'r');
    if (!$fh) {
        throw new RuntimeException('Cannot read the CSV file.');
    }
    $bom = fread($fh, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($fh);
    }
    $header = fgetcsv($fh, 0, ',', '"', '\\');
    if (!$header || !in_array('Type', $header, true) || !in_array('Name', $header, true)) {
        throw new RuntimeException('This does not look like a WooCommerce product export (missing Type/Name columns).');
    }
    $rows = [];
    while (($r = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
        if (count($r) === count($header)) {
            $rows[] = array_combine($header, $r);
        }
    }
    fclose($fh);

    $stats = ['products' => 0, 'variations' => 0, 'images' => 0, 'errors' => []];
    $num = fn($v) => ($v === '' || $v === null) ? null : (float) str_replace(',', '', (string) $v);
    $date = fn($v) => $v ? date('Y-m-d H:i:s', strtotime((string) $v)) : null;
    $attrs = function (array $r): array {
        $out = [];
        for ($i = 1; $i <= 6; $i++) {
            $name = $r["Attribute {$i} name"] ?? '';
            if ($name === '') {
                continue;
            }
            $out[] = [
                'name' => $name,
                'options' => array_values(array_filter(array_map('trim', explode(',', (string) ($r["Attribute {$i} value(s)"] ?? ''))), 'strlen')),
                'visible' => ($r["Attribute {$i} visible"] ?? '1') === '1',
                'default' => $r["Attribute {$i} default"] ?? '',
            ];
        }
        return $out;
    };

    // Parents first, then variations.
    usort($rows, fn($a, $b) => ($a['Type'] === 'variation') <=> ($b['Type'] === 'variation'));
    foreach ($rows as $r) {
        try {
            $type = strtolower(trim((string) $r['Type']));
            if ($type === 'variation') {
                $parentWoo = (int) preg_replace('/\D/', '', (string) $r['Parent']);
                $pid = woo_map('products', $parentWoo);
                if (!$pid) {
                    continue;
                }
                $vAttrs = [];
                foreach ($attrs($r) as $a) {
                    $vAttrs[$a['name']] = $a['options'][0] ?? '';
                }
                $data = [
                    'product_id' => $pid,
                    'attributes_json' => json_encode($vAttrs, JSON_UNESCAPED_UNICODE),
                    'sku' => $r['SKU'] ?: null,
                    'gtin' => ($r['GTIN, UPC, EAN, or ISBN'] ?? '') ?: null,
                    'description' => trim(strip_tags((string) $r['Description'])),
                    'regular_price' => $num($r['Regular price']),
                    'sale_price' => $num($r['Sale price']),
                    'sale_from' => $date($r['Date sale price starts']),
                    'sale_to' => $date($r['Date sale price ends']),
                    'manage_stock' => $r['Stock'] !== '' ? 1 : 0,
                    'stock_qty' => $r['Stock'] !== '' ? (int) $r['Stock'] : null,
                    'stock_status' => $r['In stock?'] === '0' ? 'outofstock' : ($r['In stock?'] === 'backorder' ? 'onbackorder' : 'instock'),
                    'weight' => $num($r['Weight (kg)']),
                    'length' => $num($r['Length (in)']),
                    'width' => $num($r['Width (in)']),
                    'height' => $num($r['Height (in)']),
                    'enabled' => $r['Published'] === '1' ? 1 : 0,
                    'sort_order' => (int) ($r['Position'] ?? 0),
                ];
                $vid = woo_map('variations', (int) $r['ID']);
                if ($vid) {
                    update('variations', $data, 'id = ?', [$vid]);
                } else {
                    $vid = insert('variations', $data + ['woo_id' => (int) $r['ID'] ?: null]);
                    $img = trim(explode(',', (string) $r['Images'])[0]);
                    if ($downloadImages && $img && ($path = save_remote_image($img))) {
                        insert('product_images', ['product_id' => $pid, 'variation_id' => $vid, 'path' => $path, 'alt' => $r['Name'], 'sort_order' => 0]);
                        $stats['images']++;
                    }
                }
                product_refresh_cache($pid);
                $stats['variations']++;
                continue;
            }

            $productAttrs = $attrs($r);
            $defaults = [];
            $attrJson = [];
            foreach ($productAttrs as $a) {
                $attrJson[] = ['name' => $a['name'], 'options' => $a['options'], 'variation' => $type === 'variable', 'visible' => $a['visible']];
                if ($a['default'] !== '') {
                    $defaults[$a['name']] = $a['default'];
                }
            }
            $brandName = trim(explode(',', (string) ($r['Brands'] ?? ''))[0]);
            $brandId = null;
            if ($brandName !== '') {
                $brandId = val('SELECT id FROM brands WHERE name = ?', [$brandName])
                    ?: insert('brands', ['name' => $brandName, 'slug' => unique_slug('brands', $brandName)]);
            }
            $data = [
                'type' => $type === 'variable' ? 'variable' : 'simple',
                'name' => $r['Name'],
                'status' => $r['Published'] === '1' ? 'published' : 'draft',
                'featured' => $r['Is featured?'] === '1' ? 1 : 0,
                'sku' => $r['SKU'] ?: null,
                'gtin' => ($r['GTIN, UPC, EAN, or ISBN'] ?? '') ?: null,
                'brand_id' => $brandId,
                'short_description' => clean_html(nl2br_if_plain((string) $r['Short description'])),
                'description' => clean_html(nl2br_if_plain((string) $r['Description'])),
                'regular_price' => $num($r['Regular price']),
                'sale_price' => $num($r['Sale price']),
                'sale_from' => $date($r['Date sale price starts']),
                'sale_to' => $date($r['Date sale price ends']),
                'manage_stock' => $r['Stock'] !== '' ? 1 : 0,
                'stock_qty' => $r['Stock'] !== '' ? (int) $r['Stock'] : null,
                'stock_status' => $r['In stock?'] === '0' ? 'outofstock' : 'instock',
                'weight' => $num($r['Weight (kg)']),
                'length' => $num($r['Length (in)']),
                'width' => $num($r['Width (in)']),
                'height' => $num($r['Height (in)']),
                'tax_status' => $r['Tax status'] ?: 'taxable',
                'tags' => (string) $r['Tags'],
                'attributes_json' => json_encode($attrJson, JSON_UNESCAPED_UNICODE),
                'default_attributes_json' => json_encode($defaults, JSON_UNESCAPED_UNICODE),
                'upsell_ids' => (string) $r['Upsells'],
                'cross_sell_ids' => (string) $r['Cross-sells'],
                'sort_order' => (int) ($r['Position'] ?? 0),
                'updated_at' => now(),
            ];
            $pid = $r['ID'] !== '' ? woo_map('products', (int) $r['ID']) : null;
            $isNew = !$pid;
            if ($pid) {
                update('products', $data, 'id = ?', [$pid]);
            } else {
                $slug = $slugMap['products'][(string) $r['ID']] ?? $r['Name'];
                $pid = insert('products', $data + ['slug' => unique_slug('products', $slug), 'woo_id' => (int) $r['ID'] ?: null, 'created_at' => now()]);
            }
            q('DELETE FROM product_categories WHERE product_id = ?', [$pid]);
            foreach (array_filter(array_map('trim', explode(',', (string) $r['Categories']))) as $path) {
                $parent = null;
                $catId = null;
                foreach (array_map('trim', explode('>', $path)) as $name) {
                    $name = html_entity_decode($name, ENT_QUOTES);
                    $curly = str_replace("'", '’', $name);
                    if (isset($slugMap['categories'][$curly])) {
                        $name = $curly;
                    }
                    $catId = val('SELECT id FROM categories WHERE name = ?', [$name]);
                    if (!$catId) {
                        $catId = insert('categories', ['name' => $name, 'slug' => unique_slug('categories', $slugMap['categories'][$name] ?? $name), 'parent_id' => $parent]);
                    }
                    $parent = (int) $catId;
                }
                if ($catId && !one('SELECT 1 FROM product_categories WHERE product_id = ? AND category_id = ?', [$pid, $catId])) {
                    insert('product_categories', ['product_id' => $pid, 'category_id' => (int) $catId]);
                }
            }
            if ($downloadImages && ($isNew || !val('SELECT COUNT(*) FROM product_images WHERE product_id = ? AND variation_id IS NULL', [$pid]))) {
                foreach (array_values(array_filter(array_map('trim', explode(',', (string) $r['Images'])))) as $i => $url) {
                    if ($path = save_remote_image($url)) {
                        insert('product_images', ['product_id' => $pid, 'variation_id' => null, 'path' => $path, 'alt' => $r['Name'], 'sort_order' => $i]);
                        $stats['images']++;
                    }
                }
            }
            product_refresh_cache($pid);
            $stats['products']++;
        } catch (Throwable $e) {
            $stats['errors'][] = ($r['Name'] ?? '?') . ': ' . $e->getMessage();
        }
    }
    // Upsells / cross-sells reference WooCommerce IDs ("id:123") – convert to new IDs.
    foreach (all("SELECT id, upsell_ids, cross_sell_ids FROM products WHERE upsell_ids LIKE '%id:%' OR cross_sell_ids LIKE '%id:%'") as $p) {
        $conv = fn($csv) => implode(',', array_filter(array_map(fn($x) => woo_map('products', (int) preg_replace('/\D/', '', $x)), explode(',', (string) $csv))));
        update('products', ['upsell_ids' => $conv($p['upsell_ids']), 'cross_sell_ids' => $conv($p['cross_sell_ids'])], 'id = ?', [$p['id']]);
    }
    return $stats;
}

/** WooCommerce CSV descriptions often use plain line breaks instead of HTML. */
function nl2br_if_plain(string $text): string
{
    $text = str_replace('\n', "\n", $text);
    if ($text === '' || preg_match('/<(p|ul|ol|h\d|div|br)\b/i', $text)) {
        return $text;
    }
    $paras = preg_split('/\n\s*\n/', trim($text));
    return implode('', array_map(fn($p) => '<p>' . nl2br(trim($p)) . '</p>', $paras));
}

/** The bundled export of the old site's products + their original URLs. */
function import_seed_products(bool $downloadImages = true): array
{
    $map = json_decode((string) @file_get_contents(APP_DIR . '/seed/slugs.json'), true) ?: [];
    return import_woo_csv(APP_DIR . '/seed/woocommerce-products.csv', $downloadImages, $map);
}
