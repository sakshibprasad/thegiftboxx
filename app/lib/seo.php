<?php
declare(strict_types=1);

function meta_tags(array $m): string
{
    $store = setting('store_name');
    $title = $m['title'] ?? $store;
    if (empty($m['raw_title']) && !str_contains($title, $store)) {
        $title .= ' | ' . $store;
    }
    $desc = str_limit($m['description'] ?? setting('store_tagline'), 160);
    $canonical = $m['canonical'] ?? site_url(strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/');
    $image = $m['image'] ?? (setting('og_image') ? image_url(setting('og_image'), '') : site_url('assets/img/hero.jpg'));
    if (setting_on('noindex_site')) {
        $m['noindex'] = true;
    }
    $out = [];
    $out[] = '<title>' . e($title) . '</title>';
    $out[] = '<meta name="description" content="' . e($desc) . '">';
    $out[] = '<link rel="canonical" href="' . e($canonical) . '">';
    $out[] = '<meta name="robots" content="' . (!empty($m['noindex']) ? 'noindex, follow' : 'index, follow, max-image-preview:large, max-snippet:-1') . '">';
    $out[] = '<meta property="og:site_name" content="' . e($store) . '">';
    $out[] = '<meta property="og:type" content="' . e($m['type'] ?? 'website') . '">';
    $out[] = '<meta property="og:title" content="' . e($title) . '">';
    $out[] = '<meta property="og:description" content="' . e($desc) . '">';
    $out[] = '<meta property="og:url" content="' . e($canonical) . '">';
    $out[] = '<meta property="og:image" content="' . e($image) . '">';
    $out[] = '<meta property="og:locale" content="en_IN">';
    $out[] = '<meta name="twitter:card" content="summary_large_image">';
    if (!empty($m['price'])) {
        $out[] = '<meta property="product:price:amount" content="' . e($m['price']) . '">';
        $out[] = '<meta property="product:price:currency" content="INR">';
    }
    foreach (['gsc_verification' => 'google-site-verification', 'bing_verification' => 'msvalidate.01',
                 'meta_domain_verification' => 'facebook-domain-verification', 'pinterest_verification' => 'p:domain_verify'] as $key => $name) {
        if ($v = setting($key)) {
            $out[] = '<meta name="' . $name . '" content="' . e($v) . '">';
        }
    }
    $schemas = array_merge([organization_jsonld()], $m['jsonld'] ?? []);
    foreach ($schemas as $schema) {
        $out[] = '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>';
    }
    return implode("\n    ", $out);
}

function organization_jsonld(): array
{
    $same = array_values(array_filter([setting('instagram_url'), setting('facebook_url')]));
    return array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Store',
        'name' => setting('store_name'),
        'url' => site_url(),
        'logo' => store_logo(false, true),
        'image' => setting('og_image') ? image_url(setting('og_image'), '') : site_url('assets/img/hero.jpg'),
        'telephone' => setting('store_phone'),
        'email' => setting('store_email'),
        'priceRange' => '₹₹',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => str_replace("\n", ', ', (string) setting('store_address')),
            'addressCountry' => 'IN',
        ],
        'sameAs' => $same ?: null,
    ]);
}

function breadcrumb_jsonld(array $crumbs): array
{
    $items = [];
    foreach (array_values($crumbs) as $i => [$name, $url]) {
        $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $name, 'item' => site_url($url)];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
}

function product_jsonld(array $p, array $reviews = []): array
{
    $images = array_map(fn($i) => image_url($i['path'], ''), $p['images']);
    $availability = $p['stock_status'] === 'outofstock' ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock';
    $url = site_url(product_url($p));
    $offer = $p['pricing']['min'] !== $p['pricing']['max']
        ? ['@type' => 'AggregateOffer', 'lowPrice' => $p['pricing']['min'], 'highPrice' => $p['pricing']['max'],
            'offerCount' => count($p['variations']), 'priceCurrency' => 'INR', 'availability' => $availability, 'url' => $url]
        : ['@type' => 'Offer', 'price' => $p['pricing']['min'], 'priceCurrency' => 'INR', 'availability' => $availability,
            'url' => $url, 'itemCondition' => 'https://schema.org/NewCondition',
            'priceValidUntil' => date('Y-12-31'), 'seller' => ['@type' => 'Organization', 'name' => setting('store_name')]];
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $p['name'],
        'description' => str_limit($p['short_description'] ?: $p['description'], 500),
        'image' => $images,
        'sku' => $p['sku'] ?: 'TGB-' . $p['id'],
        'brand' => ['@type' => 'Brand', 'name' => $p['brand']['name'] ?? setting('store_name')],
        'url' => $url,
        'offers' => $offer,
    ];
    if ($p['gtin']) {
        $data['gtin'] = $p['gtin'];
    }
    if ((int) $p['rating_count'] > 0) {
        $data['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => (float) $p['rating_avg'], 'reviewCount' => (int) $p['rating_count']];
        $data['review'] = array_map(fn($r) => [
            '@type' => 'Review', 'author' => ['@type' => 'Person', 'name' => $r['name']],
            'reviewRating' => ['@type' => 'Rating', 'ratingValue' => (int) $r['rating']],
            'reviewBody' => $r['body'], 'datePublished' => substr($r['created_at'], 0, 10),
        ], array_slice($reviews, 0, 5));
    }
    return $data;
}

function sitemap_xml(): string
{
    $urls = [[site_url(), null, '1.0'], [site_url('shop/'), null, '0.9']];
    foreach (all("SELECT slug, updated_at FROM products WHERE status = 'published' ORDER BY updated_at DESC") as $p) {
        $urls[] = [site_url('product/' . $p['slug'] . '/'), $p['updated_at'], '0.8'];
    }
    foreach (all('SELECT slug FROM categories') as $c) {
        $urls[] = [site_url('product-category/' . $c['slug'] . '/'), null, '0.7'];
    }
    foreach (all("SELECT slug, updated_at FROM pages WHERE status = 'published'") as $pg) {
        $urls[] = [site_url($pg['slug'] . '/'), $pg['updated_at'], '0.5'];
    }
    if (setting_on('blog_enabled')) {
        $urls[] = [site_url('blog/'), null, '0.6'];
        foreach (all("SELECT slug, updated_at FROM posts WHERE status = 'published' AND published_at <= ?", [now()]) as $post) {
            $urls[] = [site_url('blog/' . $post['slug'] . '/'), $post['updated_at'], '0.6'];
        }
    }
    foreach (['contact/', 'corporate-gifting/', 'custom-box/', 'track-order/'] as $path) {
        $urls[] = [site_url($path), null, '0.6'];
    }
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as [$loc, $mod, $prio]) {
        $xml .= '  <url><loc>' . e($loc) . '</loc>' . ($mod ? '<lastmod>' . date('c', strtotime($mod)) . '</lastmod>' : '')
            . '<priority>' . $prio . '</priority></url>' . "\n";
    }
    return $xml . '</urlset>';
}

function robots_txt(): string
{
    if (setting_on('noindex_site')) {
        return "User-agent: *\nDisallow: /\n";
    }
    return "User-agent: *\nDisallow: /cart/\nDisallow: /checkout/\nDisallow: /my-account/\nDisallow: /order/\nDisallow: /*?add-to-cart=\nAllow: /\n\nSitemap: " . site_url('sitemap.xml') . "\n";
}

function llms_txt(): string
{
    $out = '# ' . setting('store_name') . "\n\n> " . setting('store_tagline') . ". Premium curated gift boxes (gift hampers) in reusable wooden packaging, delivered across India.\n\n";
    $out .= "## Contact\n- Website: " . site_url() . "\n- Email: " . setting('store_email') . "\n- Phone: " . setting('store_phone') . "\n- Address: " . str_replace("\n", ', ', (string) setting('store_address')) . "\n\n";
    $out .= "## Collections\n";
    foreach (categories_all() as $c) {
        if ($c['product_count'] > 0) {
            $out .= '- [' . $c['name'] . '](' . site_url(category_url($c)) . ')' . ($c['description'] ? ': ' . str_limit($c['description'], 140) : '') . "\n";
        }
    }
    $out .= "\n## Products\n";
    foreach (products_query(['limit' => 200, 'sort' => 'manual']) as $p) {
        $range = ['min' => (float) $p['price_min'], 'max' => (float) $p['price_max']];
        $price = $range['min'] == $range['max'] ? money($range['min']) : money($range['min']) . '–' . money($range['max']);
        $out .= '- [' . $p['name'] . '](' . site_url(product_url($p)) . ') — ' . $price . ': ' . str_limit((string) $p['short_description'], 180) . "\n";
    }
    $out .= "\n## Services\n- [Corporate & bulk gifting](" . site_url('corporate-gifting/') . ")\n- [Design your own box (send us your idea)](" . site_url('custom-box/') . ")\n";
    foreach (all("SELECT slug, title FROM pages WHERE status = 'published'") as $pg) {
        $out .= '- [' . $pg['title'] . '](' . site_url($pg['slug'] . '/') . ")\n";
    }
    return $out;
}

/**
 * Product feed (RSS 2.0 + Google namespace). Works for Google Merchant Center
 * and Meta Commerce Manager (Facebook/Instagram catalog).
 */
function product_feed_xml(): string
{
    $items = [];
    foreach (all("SELECT * FROM products WHERE status = 'published' AND google_sync = 1") as $row) {
        $p = product_full($row);
        $brand = $p['brand']['name'] ?? setting('store_name');
        $category = $p['categories'][0]['name'] ?? 'Gift Boxes';
        $mainImages = array_map(fn($i) => image_url($i['path'], ''), $p['images']);
        $base = [
            'title' => $p['name'],
            'description' => str_limit(strip_tags((string) ($p['description'] ?: $p['short_description'])), 4900),
            'link' => site_url(product_url($p)),
            'brand' => $brand,
            'condition' => 'new',
            'product_type' => $category,
            'google_product_category' => '5709',
        ];
        $variants = $p['type'] === 'variable' ? array_filter($p['variations'], fn($v) => $v['enabled']) : [null];
        foreach ($variants as $v) {
            $src = $v ?? $p;
            $pr = effective_price($src);
            if ($pr['price'] === null) {
                continue;
            }
            $imgs = $v && $v['images'] ? array_map(fn($i) => image_url($i['path'], ''), $v['images']) : $mainImages;
            $item = $base + [
                'id' => $v ? 'TGB-' . $p['id'] . '-' . $v['id'] : 'TGB-' . $p['id'],
                'availability' => $src['stock_status'] === 'outofstock' ? 'out_of_stock' : 'in_stock',
                'price' => number_format((float) ($pr['regular'] ?? $pr['price']), 2, '.', '') . ' INR',
                'image_link' => $imgs[0] ?? '',
            ];
            if ($v) {
                $item['title'] = $p['name'] . ' – ' . variation_label($v['attributes']);
                $item['item_group_id'] = 'TGB-' . $p['id'];
                $item['link'] = site_url(product_url($p)) . '?variation=' . $v['id'];
            }
            if ($pr['on_sale']) {
                $item['sale_price'] = number_format((float) $pr['price'], 2, '.', '') . ' INR';
            }
            $gtin = $src['gtin'] ?: $p['gtin'];
            if ($gtin) {
                $item['gtin'] = $gtin;
            } else {
                $item['identifier_exists'] = 'no';
            }
            $item['mpn'] = ($src['sku'] ?: $p['sku']) ?: '';
            $item['additional_image_link'] = array_slice($imgs, 1, 10);
            if ($src['weight'] ?: $p['weight']) {
                $item['shipping_weight'] = (float) ($src['weight'] ?: $p['weight']) . ' kg';
            }
            $items[] = $item;
        }
    }
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0"><channel>' . "\n"
        . '<title>' . e(setting('store_name')) . '</title><link>' . e(site_url()) . '</link><description>' . e(setting('store_tagline')) . "</description>\n";
    foreach ($items as $item) {
        $xml .= "<item>\n";
        foreach ($item as $k => $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            $tag = in_array($k, ['title', 'description', 'link'], true) ? $k : 'g:' . $k;
            foreach ((array) $v as $vv) {
                $xml .= "  <{$tag}>" . htmlspecialchars((string) $vv, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</{$tag}>\n";
            }
        }
        $xml .= "</item>\n";
    }
    return $xml . '</channel></rss>';
}

/** Logo URL: uploaded in Admin → Settings → Website, or the bundled one. */
function store_logo(bool $forDark = false, bool $absolute = false): string
{
    $custom = setting($forDark ? 'logo_image_light' : 'logo_image');
    if ($custom) {
        return image_url($custom, '');
    }
    $path = $forDark ? 'assets/img/logo-white.svg' : 'assets/img/logo-dark.svg';
    return $absolute ? site_url($path) : '/' . $path;
}
