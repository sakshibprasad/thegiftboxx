<?php
/** @var string $content @var array $meta @var string $bodyClass @var string $bodyStyle */
$store = setting('store_name');
$headingFont = setting('theme_heading_font');
$bodyFont = setting('theme_body_font');
$fontsUrl = google_fonts_url((string) $headingFont, (string) $bodyFont);
$cats = array_values(array_filter(categories_all(), fn($c) => !$c['parent_id'] && $c['product_count'] > 0));
$cartCount = cart_count();
$wishCount = count(wishlist_ids());
$path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$wa = preg_replace('/\D/', '', (string) setting('whatsapp_number'));
$footerPages = all("SELECT slug, title FROM pages WHERE status = 'published' AND show_in_footer = 1 AND slug <> 'about' ORDER BY title");
$blogOn = setting_on('blog_enabled');
$favicon = setting('favicon') ? image_url(setting('favicon'), '') : '/assets/img/favicon-32.png';
$social = array_filter([
    'instagram' => setting('instagram_url'), 'facebook' => setting('facebook_url'), 'youtube' => setting('youtube_url'),
    'pinterest' => setting('pinterest_url'), 'linkedin' => setting('linkedin_url'),
]);
$nav = [['/', 'Home'], ['/shop/', 'Shop']];
$navAfter = [['/corporate-gifting/', 'Corporate'], ['/about/', 'Our story']];
if ($blogOn) {
    $navAfter[] = ['/blog/', 'Journal'];
}
$navAfter[] = ['/contact/', 'Contact'];
$isActive = fn(string $href) => $href === '/' ? $path === '/' : str_starts_with((string) $path, $href);
$tel = preg_replace('/[^\d+]/', '', (string) setting('store_phone'));
?><!doctype html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <?= meta_tags($meta) ?>

    <meta name="theme-color" content="<?= e(setting('theme_bg')) ?>">
    <link rel="icon" href="<?= e($favicon) ?>">
    <link rel="apple-touch-icon" href="<?= e(setting('favicon') ? $favicon : '/assets/img/apple-touch-icon.png') ?>">
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
        <div class="h-left">
            <button class="icon-btn only-mobile" data-open="menu" aria-label="Open menu"><?= icon('menu') ?></button>
            <a href="/" class="logo logo-desk" aria-label="<?= e($store) ?> home"><img src="<?= e(store_logo()) ?>" alt="<?= e($store) ?>" width="180" height="29"></a>
        </div>
        <a href="/" class="logo logo-mob" aria-label="<?= e($store) ?> home"><img src="<?= e(store_logo()) ?>" alt="<?= e($store) ?>" width="150" height="24"></a>
        <nav class="main-nav" aria-label="Main">
            <?php foreach ($nav as [$href, $label]): ?><a href="<?= e($href) ?>" class="<?= $isActive($href) ? 'active' : '' ?>"><?= e($label) ?></a><?php endforeach; ?>
            <div class="has-menu">
                <button type="button" aria-expanded="false" class="<?= str_starts_with((string) $path, '/product-category/') ? 'active' : '' ?>">Occasions <?= icon('chevron') ?></button>
                <div class="mega">
                    <div class="mega-links">
                        <?php foreach ($cats as $c): ?>
                            <a href="<?= e(category_url($c)) ?>"><?= e($c['name']) ?><small><?= (int) $c['product_count'] ?> box<?= $c['product_count'] == 1 ? '' : 'es' ?></small></a>
                        <?php endforeach; ?>
                    </div>
                    <a class="mega-card" href="/custom-box/">
                        <?= icon('sparkle') ?><strong>Design your own box</strong><span>Share your idea, we’ll make it happen.</span>
                    </a>
                </div>
            </div>
            <?php foreach ($navAfter as [$href, $label]): ?><a href="<?= e($href) ?>" class="<?= $isActive($href) ? 'active' : '' ?>"><?= e($label) ?></a><?php endforeach; ?>
        </nav>
        <div class="header-actions">
            <?php if (setting_on('search_enabled')): ?><button class="icon-btn" data-open="search" aria-label="Search"><?= icon('search') ?></button><?php endif; ?>
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

<?php $footImg = setting('footer_image') ?: (home_content()['intro_image'] ?? '');
$footImg = $footImg ? image_url($footImg, 'lg') : '/assets/img/hero-2.jpg'; ?>
<footer class="foot">
    <section class="foot-close">
        <div class="wrap">
            <div class="fc-card">
                <div class="fc-media"><img src="<?= e($footImg) ?>" alt="" loading="lazy" width="900" height="1100"></div>
                <div class="fc-copy">
                    <p class="fc-eyebrow"><?= e($store) ?></p>
                    <p class="fc-title"><?= e(setting('footer_statement')) ?></p>
                    <?php if (setting('footer_text')): ?><p class="fc-text"><?= e(setting('footer_text')) ?></p><?php endif; ?>
                    <div class="fc-cta">
                        <a class="btn btn-light" href="/shop/">Shop gift boxes <?= icon('arrow') ?></a>
                        <?php if ($wa): ?><a class="btn btn-outline-light" href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> Chat with us</a><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <div class="foot-main">
        <div class="wrap">
            <ul class="foot-promises">
                <li><?= icon('box') ?><span>Premium wooden boxes</span></li>
                <li><?= icon('sparkle') ?><span>Branded favourites inside</span></li>
                <li><?= icon('gift') ?><span>Packed to order, ready to gift</span></li>
                <li><?= icon('truck') ?><span>Delivered across India</span></li>
            </ul>
            <div class="foot-brand">
                <a href="/" aria-label="<?= e($store) ?> home"><img src="<?= e(store_logo()) ?>" alt="<?= e($store) ?>" width="220" height="34" loading="lazy"></a>
                <p><?= nl2br(e(setting('footer_tagline'))) ?></p>
            </div>
            <nav class="foot-nav" aria-label="Footer">
                <a href="/">Home</a><a href="/shop/">Shop</a><a href="/corporate-gifting/">Corporate</a><a href="/custom-box/">Design your own</a><a href="/about/">Our story</a><?php if ($blogOn): ?><a href="/blog/">Journal</a><?php endif; ?><a href="/track-order/">Track order</a><a href="/contact/">Contact</a>
            </nav>
            <?php if ($cats): ?>
            <nav class="foot-occ" aria-label="Occasions">
                <?php foreach (array_slice($cats, 0, 8) as $c): ?><a href="<?= e(category_url($c)) ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
            </nav>
            <?php endif; ?>
            <div class="foot-contact">
                <div class="fct">
                    <a href="tel:<?= e($tel) ?>"><?= e(setting('store_phone')) ?></a>
                    <span class="dot" aria-hidden="true"></span>
                    <a href="mailto:<?= e(setting('store_email')) ?>"><?= e(setting('store_email')) ?></a>
                </div>
                <?php if (setting('store_hours')): ?><p><?= e(str_replace("\n", ' · ', trim((string) setting('store_hours')))) ?></p><?php endif; ?>
                <?php if ($social || $wa): ?>
                <div class="foot-social">
                    <?php foreach ($social as $net => $url): ?><a href="<?= e($url) ?>" rel="noopener" target="_blank" aria-label="<?= e(ucfirst($net)) ?>"><?= icon(in_array($net, ['instagram', 'facebook'], true) ? $net : 'external') ?></a><?php endforeach; ?>
                    <?php if ($wa): ?><a href="https://wa.me/<?= e($wa) ?>" rel="noopener" target="_blank" aria-label="WhatsApp"><?= icon('whatsapp') ?></a><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="foot-bottom">
                <span>© <?= date('Y') ?> <?= e($store) ?></span>
                <span class="foot-legal"><?php foreach ($footerPages as $fp): ?><a href="/<?= e($fp['slug']) ?>/"><?= e($fp['title']) ?></a><?php endforeach; ?></span>
                <span class="pay-note"><?= icon('shield') ?> Secure payments</span>
            </div>
        </div>
    </div>
</footer>

<?php if ($wa && setting_on('whatsapp_float')): ?>
    <a class="wa-float" href="https://wa.me/<?= e($wa) ?>?text=<?= rawurlencode((string) setting('whatsapp_message')) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"><?= icon('whatsapp') ?></a>
<?php endif; ?>

<!-- Phone & tablet menu -->
<div class="sheet sheet-left menu-sheet" id="sheet-menu" hidden>
    <div class="sheet-head"><img src="<?= e(store_logo()) ?>" alt="" width="140" height="22"><button class="icon-btn" data-close aria-label="Close"><?= icon('close') ?></button></div>
    <div class="menu-body">
        <nav class="menu-main">
            <a href="/" class="<?= $path === '/' ? 'on' : '' ?>" style="--i:0">Home</a>
            <a href="/shop/" class="<?= $path === '/shop/' ? 'on' : '' ?>" style="--i:1">Shop all</a>
            <details style="--i:2" <?= str_starts_with((string) $path, '/product-category/') ? 'open' : '' ?>>
                <summary>Occasions <?= icon('chevron') ?></summary>
                <div class="menu-chips">
                    <?php foreach ($cats as $c): ?><a href="<?= e(category_url($c)) ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
                </div>
            </details>
            <a href="/custom-box/" style="--i:3">Design your own</a>
            <a href="/corporate-gifting/" style="--i:4">Corporate</a>
            <a href="/about/" style="--i:5">Our story</a>
            <?php if ($blogOn): ?><a href="/blog/" style="--i:6">Journal</a><?php endif; ?>
            <a href="/contact/" style="--i:7">Contact</a>
        </nav>
        <div class="menu-quick">
            <a href="/track-order/"><?= icon('truck') ?><span>Track order</span></a>
            <a href="/wishlist/"><?= icon('heart') ?><span>Wishlist</span></a>
            <a href="/my-account/"><?= icon('user') ?><span><?= customer() ? 'Account' : 'Sign in' ?></span></a>
        </div>
        <div class="menu-foot">
            <?php if ($wa): ?><a class="btn btn-block" href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> Chat on WhatsApp</a><?php endif; ?>
            <a class="menu-tel" href="tel:<?= e($tel) ?>"><?= e(setting('store_phone')) ?></a>
        </div>
    </div>
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
