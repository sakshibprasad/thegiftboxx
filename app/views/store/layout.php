<?php
/** @var string $content @var array $meta @var string $bodyClass @var string $bodyStyle */
$store = setting('store_name');
$headingFont = setting('theme_heading_font');
$bodyFont = setting('theme_body_font');
$fontsUrl = 'https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $headingFont) . ':ital,wght@0,400;0,500;0,600;1,400'
    . ($bodyFont !== $headingFont ? '&family=' . str_replace(' ', '+', $bodyFont) . ':wght@300;400;500;600' : '') . '&display=swap';
$cats = array_values(array_filter(categories_all(), fn($c) => !$c['parent_id'] && $c['product_count'] > 0));
$cartCount = cart_count();
$wishCount = count(wishlist_ids());
$path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$wa = preg_replace('/\D/', '', (string) setting('whatsapp_number'));
$footerPages = all("SELECT slug, title FROM pages WHERE status = 'published' AND show_in_footer = 1 AND slug <> 'about' ORDER BY title");
$blogOn = setting_on('blog_enabled');
?><!doctype html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <?= meta_tags($meta) ?>

    <meta name="theme-color" content="<?= e(setting('theme_dark')) ?>">
    <link rel="icon" href="/assets/img/favicon-32.png" sizes="32x32">
    <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= e($fontsUrl) ?>">
    <link rel="stylesheet" href="<?= asset('css/store.css') ?>">
    <style>:root{--bg:<?= e(setting('theme_bg')) ?>;--surface:<?= e(setting('theme_surface')) ?>;--ink:<?= e(setting('theme_text')) ?>;--accent:<?= e(setting('theme_accent')) ?>;--dark:<?= e(setting('theme_dark')) ?>;--radius:<?= (int) setting('theme_radius') ?>px;--font-head:'<?= e($headingFont) ?>',Georgia,serif;--font-body:'<?= e($bodyFont) ?>',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}</style>
    <?= tracking_head() ?>
</head>
<body class="<?= e($bodyClass) ?><?= setting_on('wishlist_enabled') ? '' : ' no-wishlist' ?>"<?= $bodyStyle ? ' style="' . e($bodyStyle) . '"' : '' ?>>
<a class="skip" href="#main">Skip to content</a>
<?php if ($ann = setting('announcement')): ?>
    <div class="announce"><span><?= e($ann) ?></span></div>
<?php endif; ?>

<header class="site-header" id="top">
    <div class="wrap header-inner">
        <button class="icon-btn only-mobile" data-open="menu" aria-label="Open menu"><?= icon('menu') ?></button>
        <a href="/" class="logo" aria-label="<?= e($store) ?> home">
            <img src="<?= e(store_logo()) ?>" alt="<?= e($store) ?>" width="180" height="29">
        </a>
        <nav class="main-nav" aria-label="Main">
            <a href="/shop/" class="<?= $path === '/shop/' ? 'active' : '' ?>">Shop all</a>
            <div class="has-menu">
                <button type="button" aria-expanded="false">Occasions <?= icon('chevron') ?></button>
                <div class="mega">
                    <div class="mega-links">
                        <?php foreach ($cats as $c): ?>
                            <a href="<?= e(category_url($c)) ?>"><?= e($c['name']) ?><small><?= (int) $c['product_count'] ?> boxes</small></a>
                        <?php endforeach; ?>
                    </div>
                    <a class="mega-card" href="/custom-box/">
                        <?= icon('sparkle') ?><strong>Design your own box</strong><span>Share your idea, we’ll make it happen.</span>
                    </a>
                </div>
            </div>
            <a href="/corporate-gifting/" class="<?= $path === '/corporate-gifting/' ? 'active' : '' ?>">Corporate</a>
            <a href="/about/" class="<?= $path === '/about/' ? 'active' : '' ?>">Our story</a>
            <?php if ($blogOn): ?><a href="/blog/" class="<?= str_starts_with((string) $path, '/blog/') ? 'active' : '' ?>">Journal</a><?php endif; ?>
            <a href="/contact/" class="<?= $path === '/contact/' ? 'active' : '' ?>">Contact</a>
        </nav>
        <div class="header-actions">
            <button class="icon-btn" data-open="search" aria-label="Search"><?= icon('search') ?></button>
            <a class="icon-btn hide-sm" href="/wishlist/" aria-label="Wishlist"><?= icon('heart') ?><span class="badge" data-wish-count<?= $wishCount ? '' : ' hidden' ?>><?= $wishCount ?></span></a>
            <a class="icon-btn hide-sm" href="/my-account/" aria-label="My account"><?= icon('user') ?></a>
            <button class="icon-btn" data-open="cart" aria-label="Cart"><?= icon('bag') ?><span class="badge" data-cart-count<?= $cartCount ? '' : ' hidden' ?>><?= $cartCount ?></span></button>
        </div>
    </div>
</header>

<div class="toasts" aria-live="polite">
    <?php foreach (flashes() as $f): ?>
        <div class="toast toast-<?= e($f['type']) ?>"><?= icon($f['type'] === 'error' ? 'alert' : 'check') ?><span><?= e($f['message']) ?></span></div>
    <?php endforeach; ?>
</div>

<?php if (setting_on('checkout_disabled')): ?><div class="holiday"><?= icon('clock') ?> <?= e(setting('checkout_disabled_text')) ?></div><?php endif; ?>
<main id="main"><?= $content ?></main>

<footer class="site-footer">
    <div class="wrap footer-grid">
        <div class="footer-brand">
            <img src="<?= e(store_logo(true)) ?>" alt="<?= e($store) ?>" width="200" height="32" loading="lazy">
            <p>Premium gift hampers packed in real wooden boxes. Curated in Navi Mumbai, delivered across India.</p>
            <div class="social">
                <?php if ($ig = setting('instagram_url')): ?><a href="<?= e($ig) ?>" rel="noopener" target="_blank" aria-label="Instagram"><?= icon('instagram') ?></a><?php endif; ?>
                <?php if ($fb = setting('facebook_url')): ?><a href="<?= e($fb) ?>" rel="noopener" target="_blank" aria-label="Facebook"><?= icon('facebook') ?></a><?php endif; ?>
                <?php if ($wa): ?><a href="https://wa.me/<?= e($wa) ?>" rel="noopener" target="_blank" aria-label="WhatsApp"><?= icon('whatsapp') ?></a><?php endif; ?>
            </div>
        </div>
        <div>
            <h3>Shop</h3>
            <a href="/shop/">All gift boxes</a>
            <?php foreach (array_slice($cats, 0, 6) as $c): ?><a href="<?= e(category_url($c)) ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
        </div>
        <div>
            <h3>Help</h3>
            <a href="/contact/">Contact us</a>
            <a href="/corporate-gifting/">Corporate gifting</a>
            <a href="/custom-box/">Design your own box</a>
            <a href="/track-order/">Track your order</a>
            <?php if ($blogOn): ?><a href="/blog/">Journal</a><?php endif; ?>
            <?php foreach ($footerPages as $fp): ?><a href="/<?= e($fp['slug']) ?>/"><?= e($fp['title']) ?></a><?php endforeach; ?>
        </div>
        <div>
            <h3>Visit or call</h3>
            <p class="with-icon"><?= icon('pin') ?><span><?= nl2br(e(setting('store_address'))) ?></span></p>
            <p class="with-icon"><?= icon('phone') ?><a href="tel:<?= e(preg_replace('/[^\d+]/', '', (string) setting('store_phone'))) ?>"><?= e(setting('store_phone')) ?></a></p>
            <p class="with-icon"><?= icon('mail') ?><a href="mailto:<?= e(setting('store_email')) ?>"><?= e(setting('store_email')) ?></a></p>
            <p class="with-icon"><?= icon('clock') ?><span><?= nl2br(e(setting('store_hours'))) ?></span></p>
        </div>
    </div>
    <div class="wrap footer-bottom">
        <span>© <?= date('Y') ?> <?= e($store) ?>. All rights reserved.</span>
        <span class="pay-note"><?= icon('shield') ?> Secure payments · UPI · Cards · Netbanking</span>
    </div>
</footer>

<?php if ($wa): ?>
    <a class="wa-float" href="https://wa.me/<?= e($wa) ?>?text=<?= rawurlencode((string) setting('whatsapp_message')) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"><?= icon('whatsapp') ?></a>
<?php endif; ?>

<!-- Mobile menu -->
<div class="sheet sheet-left" id="sheet-menu" hidden>
    <div class="sheet-head"><img src="<?= e(store_logo()) ?>" alt="" width="150" height="24"><button class="icon-btn" data-close aria-label="Close"><?= icon('close') ?></button></div>
    <nav class="sheet-nav">
        <a href="/shop/">Shop all</a>
        <?php foreach ($cats as $c): ?><a href="<?= e(category_url($c)) ?>" class="sub"><?= e($c['name']) ?></a><?php endforeach; ?>
        <a href="/custom-box/">Design your own box</a>
        <a href="/corporate-gifting/">Corporate gifting</a>
        <a href="/about/">Our story</a>
        <?php if ($blogOn): ?><a href="/blog/">Journal</a><?php endif; ?>
        <a href="/contact/">Contact</a>
        <a href="/track-order/"><?= icon('truck') ?> Track your order</a>
        <a href="/wishlist/"><?= icon('heart') ?> Wishlist</a>
        <a href="/my-account/"><?= icon('user') ?> <?= customer() ? 'My account' : 'Log in / Sign up' ?></a>
    </nav>
</div>

<!-- Search -->
<div class="search-overlay" id="sheet-search" hidden>
    <form action="/search/" method="get" class="search-box" role="search">
        <?= icon('search') ?>
        <input type="search" name="q" placeholder="Search gift boxes, occasions…" aria-label="Search" autocomplete="off">
        <button type="button" class="icon-btn" data-close aria-label="Close"><?= icon('close') ?></button>
    </form>
</div>

<!-- Cart drawer -->
<div class="sheet sheet-right" id="sheet-cart" hidden>
    <div class="sheet-head"><h2>Your cart</h2><button class="icon-btn" data-close aria-label="Close"><?= icon('close') ?></button></div>
    <div data-cart-drawer><?= render('store/partials/cart-drawer', ['lines' => cart_lines()]) ?></div>
</div>
<div class="scrim" hidden></div>

<?php if (setting_on('cookie_notice')): ?><div class="cookie" id="cookie" hidden>
    <p>We use cookies to keep your cart and to understand what people like on our site.</p>
    <button class="btn btn-small" data-cookie="1">Okay</button>
</div><?php endif; ?>

<script>window.TGB = {csrf: <?= json_encode(csrf_token()) ?>};</script>
<script src="<?= asset('js/store.js') ?>" defer></script>
<?= tracking_body_end() ?>
</body>
</html>
