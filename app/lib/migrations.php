<?php
declare(strict_types=1);

/**
 * Database upgrades. When a new version of the code is uploaded, any
 * missing steps below run automatically (once) on the next page load.
 * Your settings, API keys, products and orders are never touched.
 */
const SCHEMA_VERSION = 3;

function run_migrations(): void
{
    $current = (int) setting('schema_version', '1');
    if ($current >= SCHEMA_VERSION) {
        return;
    }
    $lock = APP_ROOT . '/storage/migrate.lock';
    $fh = @fopen($lock, 'c');
    if ($fh && !flock($fh, LOCK_EX | LOCK_NB)) {
        return; // another request is upgrading right now
    }
    try {
        for ($v = $current + 1; $v <= SCHEMA_VERSION; $v++) {
            $fn = 'migration_' . $v;
            if (function_exists($fn)) {
                $fn();
            }
            setting_set('schema_version', (string) $v);
        }
    } catch (Throwable $e) {
        app_log('migrate', 'upgrade failed', ['error' => $e->getMessage()]);
    } finally {
        if ($fh) {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }
}

function column_exists(string $table, string $column): bool
{
    if (db_driver() === 'sqlite') {
        foreach (all("PRAGMA table_info({$table})") as $c) {
            if ($c['name'] === $column) {
                return true;
            }
        }
        return false;
    }
    return (bool) one("SHOW COLUMNS FROM {$table} LIKE ?", [$column]);
}

function add_column(string $table, string $column, string $definition): void
{
    if (!column_exists($table, $column)) {
        db()->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }
}

function table_exists(string $table): bool
{
    try {
        db()->query("SELECT 1 FROM {$table} LIMIT 1");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/** v2: SEO panel fields, activity history, festive category, content fixes. */
function migration_2(): void
{
    require_once APP_DIR . '/store/content.php';
    foreach (['products', 'pages', 'posts', 'categories'] as $t) {
        add_column($t, 'og_title', 'VARCHAR(255) NULL');
        add_column($t, 'og_description', 'TEXT NULL');
        add_column($t, 'og_image', 'VARCHAR(255) NULL');
        add_column($t, 'robots', "VARCHAR(60) NULL");
        add_column($t, 'canonical_url', 'VARCHAR(255) NULL');
        add_column($t, 'secondary_keywords', 'VARCHAR(255) NULL');
        add_column($t, 'schema_type', 'VARCHAR(30) NULL');
    }
    add_column('pages', 'focus_keyword', 'VARCHAR(190) NULL');
    add_column('categories', 'focus_keyword', 'VARCHAR(190) NULL');

    if (!table_exists('activity_log')) {
        $id = db_driver() === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY';
        $mediumtext = db_driver() === 'sqlite' ? 'TEXT' : 'MEDIUMTEXT';
        db()->exec("CREATE TABLE activity_log (
            id {$id},
            user_id INT NULL,
            user_name VARCHAR(120) NOT NULL DEFAULT '',
            action VARCHAR(40) NOT NULL,
            entity_type VARCHAR(30) NOT NULL,
            entity_id VARCHAR(60) NULL,
            summary VARCHAR(255) NOT NULL,
            snapshot {$mediumtext} NULL,
            reverted_at DATETIME NULL,
            created_at DATETIME NOT NULL
        )");
        db()->exec('CREATE INDEX idx_activity_created ON activity_log (created_at)');
    }

    // Festive boxes category for the homepage feature banner.
    if (!one("SELECT id FROM categories WHERE slug = 'festive-gift-boxes'")) {
        insert('categories', ['name' => 'Festive Gift Boxes', 'slug' => 'festive-gift-boxes',
            'description' => 'Premium festive gift boxes for Diwali, Raksha Bandhan, Christmas and every celebration in between.', 'sort_order' => 5]);
    }
    // Men in Black gets a dark page to match the box.
    q("UPDATE products SET page_bg_color = '#121212', page_text_color = '#F2EDE6' WHERE slug = 'men-in-black-box' AND page_bg_color IS NULL");
    // WooCommerce exports sometimes contain a literal "\n".
    foreach (all('SELECT id, description FROM variations') as $v) {
        if (str_contains((string) $v['description'], '\\n')) {
            update('variations', ['description' => trim(preg_replace('/\s*\\\\n\s*/', ' ', (string) $v['description']))], 'id = ?', [$v['id']]);
        }
    }
    migration_scrub_home();
}

/** Drop saved homepage texts that still use the old wording (or equal the defaults). */
function migration_scrub_home(): void
{
    require_once APP_DIR . '/store/content.php';
    $d = home_defaults();
    $home = setting_json('home_content');
    if ($home && str_contains((string) ($home['promo_link'] ?? ''), 'valentines')) {
        // The whole limited-edition banner was the old Valentine's one: go back to the festive defaults.
        $home = array_filter($home, fn($k) => !str_starts_with($k, 'promo_'), ARRAY_FILTER_USE_KEY);
    }
    if ($home) {
        foreach ($home as $k => $v) {
            $flat = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v;
            if ((str_starts_with($k, 'promo_') && preg_match('/Valentine|valentines-gift/iu', $flat)) || preg_match('/real wood|6–10|6 to 10|6-10|handwrit|Valentine’s Gift Boxes|We put the thought in|Gifts that feel personal|Navi Mumbai/u', $flat)
                || (array_key_exists($k, $d) && $v === $d[$k] && !array_key_exists($k, home_sections()))) {
                unset($home[$k]);
            }
        }
        setting_set('home_content', json_encode($home, JSON_UNESCAPED_UNICODE));
    }
}

function migration_3(): void
{
    migration_scrub_home();
    $about = one("SELECT id, content FROM pages WHERE slug = 'about'");
    if ($about && str_contains((string) $about['content'], '6–10 items')) {
        update('pages', ['content' => clean_html(default_pages()['about'][1] ?? $about['content'])], 'id = ?', [$about['id']]);
    }
    if (setting('announcement', '') !== '' && str_contains((string) setting('announcement'), 'Free delivery')) {
        setting_set('announcement', 'Premium gift boxes, curated with love and delivered across India');
    }
    // Checkout: nothing selectable for the first 6 days.
    if (setting('delivery_min_days', '') === '5') {
        setting_set('delivery_min_days', '7');
    }
}
