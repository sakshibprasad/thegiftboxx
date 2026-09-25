<?php
declare(strict_types=1);

/**
 * First-run installer: creates app/config.php, the database tables, the owner
 * account and starting content. Runs only while app/config.php does not exist.
 */
function install_defaults(): array
{
    $host = $_SERVER['HTTP_HOST'] ?? 'admin.thegiftboxx.com';
    $root = preg_replace('/^admin\./', '', $host);
    $publicDir = is_dir(APP_ROOT . '/public_html') ? APP_ROOT . '/public_html' : APP_ROOT . '/public';
    return [
        'site_url' => 'https://' . $root,
        'admin_url' => 'https://' . $host,
        'uploads_dir' => $publicDir . '/uploads',
        'db_host' => 'localhost', 'db_name' => '', 'db_user' => '', 'db_pass' => '',
        'store_name' => 'The Gift Boxx', 'name' => '', 'email' => '', 'password' => '',
        'seed' => '1',
    ];
}

function install_form(array $errors = [], array $values = []): void
{
    start_session('tgb_admin');
    $values = array_merge(install_defaults(), $values);
    $appWritable = is_writable(APP_DIR);
    echo render('admin/install', compact('errors', 'values', 'appWritable'));
}

function install_run(): void
{
    csrf_check();
    $v = array_map(fn($x) => is_string($x) ? trim($x) : $x, $_POST);
    $v += install_defaults();
    $errors = [];
    if (!filter_var($v['site_url'], FILTER_VALIDATE_URL)) $errors[] = 'Website address must look like https://thegiftboxx.com';
    if (!filter_var($v['admin_url'], FILTER_VALIDATE_URL)) $errors[] = 'Admin address must look like https://admin.thegiftboxx.com';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter your email address.';
    if (mb_strlen((string) $v['password']) < 10) $errors[] = 'Choose an admin password with at least 10 characters.';
    $uploads = rtrim((string) $v['uploads_dir'], '/');
    if (!is_dir($uploads) && !@mkdir($uploads, 0775, true)) $errors[] = 'The uploads folder does not exist and could not be created: ' . $uploads;
    elseif (!is_writable($uploads)) $errors[] = 'The uploads folder is not writable: ' . $uploads;

    $sqlite = ($v['db_driver'] ?? 'mysql') === 'sqlite';
    $db = $sqlite
        ? ['driver' => 'sqlite', 'path' => APP_ROOT . '/storage/database.sqlite']
        : ['driver' => 'mysql', 'host' => $v['db_host'], 'port' => 3306, 'name' => $v['db_name'], 'user' => $v['db_user'], 'pass' => $v['db_pass']];
    if (!$errors) {
        try {
            if ($sqlite) {
                @mkdir(APP_ROOT . '/storage', 0775, true);
                new PDO('sqlite:' . $db['path']);
            } else {
                new PDO(sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['name']), $db['user'], $db['pass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]);
            }
        } catch (Throwable $e) {
            $errors[] = 'Could not connect to the database: ' . $e->getMessage();
        }
    }
    if ($errors) {
        unset($v['password'], $v['db_pass']);
        install_form($errors, $v);
        return;
    }

    $config = [
        'site_url' => rtrim((string) $v['site_url'], '/'),
        'admin_url' => rtrim((string) $v['admin_url'], '/'),
        'uploads_dir' => $uploads,
        'uploads_url' => rtrim((string) $v['site_url'], '/') . '/uploads',
        'app_key' => base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)),
        'db' => $db,
        'debug' => false,
    ];
    $GLOBALS['__config'] = $config;
    try {
        run_schema();
    } catch (Throwable $e) {
        if (!str_contains($e->getMessage(), 'already exists')) {
            install_form(['Creating tables failed: ' . $e->getMessage()], $v);
            return;
        }
    }
    install_seed((string) $v['store_name']);
    $email = strtolower((string) $v['email']);
    if ($existing = one('SELECT id FROM users WHERE email = ?', [$email])) {
        update('users', ['role' => 'admin', 'password_hash' => password_hash((string) $v['password'], PASSWORD_DEFAULT), 'name' => $v['name'] ?: 'Owner'], 'id = ?', [$existing['id']]);
        $uid = (int) $existing['id'];
    } else {
        $uid = insert('users', ['email' => $email, 'password_hash' => password_hash((string) $v['password'], PASSWORD_DEFAULT),
            'name' => $v['name'] ?: 'Owner', 'role' => 'admin', 'created_at' => now()]);
    }
    $php = "<?php\n// Created by the installer on " . date('j M Y H:i') . ". Keep this file private.\nreturn " . var_export($config, true) . ";\n";
    if (@file_put_contents(APP_DIR . '/config.php', $php) === false) {
        echo render('admin/install-manual', ['php' => $php]);
        return;
    }
    @chmod(APP_DIR . '/config.php', 0640);
    $_SESSION['admin_id'] = $uid;
    $msg = 'Welcome! Your store is ready.';
    if (!empty($v['seed']) && is_file(APP_DIR . '/seed/woocommerce-products.csv')) {
        try {
            @set_time_limit(300);
            $stats = import_seed_products();
            $msg .= " Imported {$stats['products']} products, {$stats['variations']} options and {$stats['images']} photos from your old site.";
        } catch (Throwable $e) {
            $msg .= ' Products could not be imported automatically (' . $e->getMessage() . ') — use Import to try again.';
        }
    }
    flash('success', $msg);
    redirect('/');
}

function install_seed(string $storeName): void
{
    setting_set('store_name', $storeName ?: 'The Gift Boxx');
    foreach (default_pages() as $slug => [$title, $content]) {
        if (!one('SELECT id FROM pages WHERE slug = ?', [$slug])) {
            insert('pages', ['slug' => $slug, 'title' => $title, 'content' => clean_html($content), 'status' => 'published',
                'show_in_footer' => $slug === 'about' ? 0 : 1, 'updated_at' => now()]);
        }
    }
    if (!val('SELECT COUNT(*) FROM box_types')) {
        $types = [
            ['Pinewood', 'pine, pinewood, pine wood', '#EFE2CF', '#3A2A1C', '#A67C4E', 'Light, natural pine with a soft grain. Our classic box.', 1],
            ['Teakwood', 'teak, teakwood, teak wood, premium wood, premium', '#4A3121', '#F5E9DA', '#D8B07A', 'Rich, dark teak. Heavier, more premium and made to last.', 2],
            ['Plywood', 'plywood, ply', '#E6DCCB', '#2E2620', '#8F7250', 'Sturdy layered plywood with a clean modern finish.', 3],
        ];
        foreach ($types as [$name, $kw, $bg, $text, $accent, $desc, $sort]) {
            insert('box_types', ['name' => $name, 'keywords' => $kw, 'bg_color' => $bg, 'text_color' => $text, 'accent_color' => $accent,
                'grain' => 1, 'description' => $desc, 'sort_order' => $sort]);
        }
    }
    foreach ([['Wedding Gift Boxes', 'Elegant wedding gift boxes to celebrate love, new beginnings and timeless memories.'],
                 ['Corporate Gift Boxes', 'Premium corporate gift boxes that leave a lasting impression on clients and teams.']] as [$name, $desc]) {
        if (!one('SELECT id FROM categories WHERE name = ?', [$name])) {
            insert('categories', ['name' => $name, 'slug' => unique_slug('categories', $name), 'description' => $desc, 'sort_order' => 50]);
        }
    }
}
