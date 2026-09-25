<?php
/** @var string $title @var string $content @var string $active @var bool $wide */
$u = admin_user();
$active = $active ?? '';
$theme = setting('admin_theme');
$accent = setting('admin_accent');
$nav = $u ? admin_nav() : [];
$tabs = [['/', 'Home', 'home', 'dashboard'], ['/orders', 'Orders', 'orders', 'orders'], ['/products', 'Products', 'box', 'products'], ['/enquiries', 'Inbox', 'chat', 'enquiries']];
?><!doctype html>
<html lang="en" data-theme="<?= e($theme) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($title) ?> · <?= e(setting('store_name')) ?> Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="#F5F5F7" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#000000" media="(prefers-color-scheme: dark)">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/assets/favicon.png">
    <link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
    <link rel="stylesheet" href="<?= asset('admin.css') ?>">
    <style>:root{--accent:<?= e($accent) ?>}</style>
</head>
<body class="<?= !empty($wide) ? 'wide' : '' ?>">
<?php if ($u): ?>
<aside class="sidebar" id="sidebar">
    <div class="side-head">
        <a href="/" class="brand"><span class="brand-mark"><img src="/assets/icon.png" alt=""></span><span><strong><?= e(setting('store_name')) ?></strong><small>Admin</small></span></a>
    </div>
    <button class="search-trigger" data-cmdk><?= icon('search') ?><span>Search</span><kbd>⌘K</kbd></button>
    <nav class="side-nav">
        <?php foreach ($nav as [$section, $items]): ?>
            <p class="side-label"><?= e($section) ?></p>
            <?php foreach ($items as $it): ?>
                <a href="<?= e($it[0]) ?>" class="<?= $active === $it[3] ? 'on' : '' ?>"><?= icon($it[2]) ?><span><?= e($it[1]) ?></span><?php if (!empty($it[4])): ?><em><?= (int) $it[4] ?></em><?php endif; ?></a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
    <div class="side-foot">
        <a href="<?= e(site_url()) ?>" target="_blank" rel="noopener" class="view-site"><?= icon('external') ?><span>View store</span></a>
        <div class="me">
            <span class="avatar"><?= e(mb_strtoupper(mb_substr($u['name'] ?: $u['email'], 0, 1))) ?></span>
            <a href="/profile" class="me-name"><strong><?= e($u['name'] ?: 'Admin') ?></strong><small><?= e(admin_roles()[$u['role']] ?? $u['role']) ?></small></a>
            <a href="/logout" class="icon-btn" title="Sign out" aria-label="Sign out"><?= icon('logout') ?></a>
        </div>
    </div>
</aside>
<div class="side-scrim" data-close-side></div>
<?php endif; ?>

<main class="main">
    <?php if ($u): ?>
    <header class="topbar">
        <button class="icon-btn only-mobile" data-open-side aria-label="Menu"><?= icon('menu') ?></button>
        <h1 class="top-title"><?= e($title) ?></h1>
        <div class="top-actions">
            <button class="icon-btn only-mobile" data-cmdk aria-label="Search"><?= icon('search') ?></button>
            <button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode" title="Light / dark"><?= icon('moon') ?></button>
        </div>
    </header>
    <?php endif; ?>
    <div class="toasts">
        <?php foreach (flashes() as $f): ?><div class="toast t-<?= e($f['type']) ?>"><?= icon($f['type'] === 'error' ? 'alert' : 'check') ?><span><?= e($f['message']) ?></span></div><?php endforeach; ?>
    </div>
    <div class="content"><?= $content ?></div>
</main>

<?php if ($u): ?>
<nav class="tabbar">
    <?php foreach ($tabs as [$href, $label, $ic, $key]): ?>
        <a href="<?= e($href) ?>" class="<?= $active === $key ? 'on' : '' ?>"><?= icon($ic) ?><span><?= e($label) ?></span></a>
    <?php endforeach; ?>
    <button data-open-side><?= icon('menu') ?><span>More</span></button>
</nav>

<div class="cmdk" id="cmdk" hidden>
    <div class="cmdk-box" role="dialog" aria-label="Search">
        <div class="cmdk-input"><?= icon('search') ?><input type="search" placeholder="Search orders, products, customers… or jump to a page" autocomplete="off"><kbd>esc</kbd></div>
        <div class="cmdk-results"></div>
    </div>
</div>
<script type="application/json" id="cmdk-pages"><?= json_encode(array_merge(...array_map(fn($s) => array_map(fn($i) => ['title' => $i[1], 'url' => $i[0], 'type' => $s[0]], $s[1]), $nav ?: [['', []]])), JSON_UNESCAPED_UNICODE) ?></script>
<?php endif; ?>
<script>window.ADMIN = {csrf: <?= json_encode(csrf_token()) ?>, site: <?= json_encode(site_url()) ?>};</script>
<script src="<?= asset('admin.js') ?>" defer></script>
</body>
</html>
