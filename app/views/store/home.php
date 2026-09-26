<?php
/** @var array $home @var array $featured @var array $cats @var array $posts @var array $boxTypes @var array $reviews */
$on = fn(string $k) => (string) ($home[$k] ?? '1') !== '0';
$heroImg = $home['hero_image'] ? image_url($home['hero_image'], 'lg') : '/assets/img/hero.jpg';
$heroImg2 = $home['hero_image_2'] ? image_url($home['hero_image_2'], 'md') : '/assets/img/hero-2.jpg';
$introImg = $home['intro_image'] ? image_url($home['intro_image'], 'lg') : '/assets/img/about.jpg';
$promoImg = $home['promo_image'] ? image_url($home['promo_image'], 'lg') : '/assets/img/hero-2.jpg';
$wishIds = wishlist_ids();
$words = array_values(array_filter(array_map('trim', preg_split('/[·|,]/u', (string) $home['marquee']))));
$featureIcons = ['wood', 'star', 'gift', 'sparkle'];
?>
<section class="hero2">
    <div class="hero2-media" aria-hidden="true">
        <img src="<?= e($heroImg) ?>" alt="" width="960" height="1280" fetchpriority="high">
    </div>
    <div class="wrap hero2-inner">
        <div class="hero2-copy">
            <p class="eyebrow reveal"><?= e($home['hero_eyebrow']) ?></p>
            <h1 class="reveal display"><?= e($home['hero_title']) ?></h1>
            <p class="lead reveal"><?= e($home['hero_text']) ?></p>
            <div class="hero-cta reveal">
                <a class="btn btn-lg" href="<?= e($home['hero_link']) ?>"><?= e($home['hero_button']) ?> <?= icon('arrow') ?></a>
                <a class="btn btn-lg btn-ghost" href="/custom-box/">Design your own</a>
            </div>
        </div>
        <figure class="hero2-float reveal" style="--d:200ms"><img src="<?= e($heroImg2) ?>" alt="A curated premium gift box by The Gift Boxx" width="896" height="1196" loading="lazy"></figure>
    </div>
    <a href="#discover" class="scroll-cue" aria-label="Scroll down"><span></span></a>
</section>

<?php if ($on('show_marquee') && $words): ?>
<div class="marquee" aria-hidden="true">
    <div class="marquee-track">
        <?php for ($r = 0; $r < 2; $r++): ?><span><?php foreach ($words as $w): ?><em><?= e($w) ?></em><i>✦</i><?php endforeach; ?></span><?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($on('show_story')): ?>
<section class="section story" id="discover">
    <div class="story-media reveal"><img src="<?= e($introImg) ?>" alt="A Gift Boxx hamper being prepared" width="736" height="736" loading="lazy"></div>
    <div class="wrap story-inner">
        <div class="story-copy">
            <p class="eyebrow reveal"><?= e($home['intro_eyebrow']) ?></p>
            <h2 class="reveal display-2"><?= e($home['intro_title']) ?></h2>
            <?php foreach (preg_split('/\n\s*\n/', trim((string) $home['intro_text'])) as $para): ?>
                <p class="reveal story-text"><?= e($para) ?></p>
            <?php endforeach; ?>
            <ul class="highlights">
                <?php foreach ($home['features'] as $i => $f): ?>
                    <li class="reveal" style="--d:<?= $i * 90 ?>ms"><span class="hl-icon"><?= icon($featureIcons[$i % 4]) ?></span><div><h3><?= e($f['title']) ?></h3><p><?= e($f['text']) ?></p></div></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($on('show_collections') && $cats): ?>
<section class="section">
    <div class="wrap">
        <div class="section-head">
            <div><h2 class="reveal"><?= e($home['collections_title']) ?></h2><p class="reveal"><?= e($home['collections_text']) ?></p></div>
            <a href="/shop/" class="link-arrow reveal">Shop all <?= icon('arrow') ?></a>
        </div>
    </div>
    <div class="collections-rail">
        <?php foreach ($cats as $i => $c): ?>
            <a class="collection reveal" href="<?= e(category_url($c)) ?>" style="--d:<?= $i * 70 ?>ms">
                <img src="<?= e($c['image'] ? image_url($c['image'], 'md') : ($c['cover'] ?? '/assets/img/hero.jpg')) ?>" alt="<?= e($c['name']) ?>" loading="lazy" width="600" height="750">
                <span class="collection-label"><strong><?= e($c['name']) ?></strong><small>Explore <?= icon('arrow') ?></small></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($on('show_boxes') && $boxTypes): ?>
<section class="section woods">
    <div class="wrap">
        <div class="section-head center-head"><div><p class="eyebrow reveal">The keepsake</p><h2 class="reveal"><?= e($home['boxes_title']) ?></h2><p class="reveal"><?= e($home['boxes_text']) ?></p></div></div>
        <div class="wood-grid">
            <?php foreach ($boxTypes as $i => $bt): ?>
                <a class="wood-card reveal" href="/shop/" style="<?= e(box_type_css_vars($bt)) ?>--d:<?= $i * 90 ?>ms">
                    <span class="wood-surface"></span>
                    <span class="wood-body"><strong><?= e($bt['name']) ?></strong><span><?= e($bt['description']) ?></span></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($on('show_popular') && $featured): ?>
<section class="section section-tint">
    <div class="wrap">
        <div class="section-head">
            <div><h2 class="reveal"><?= e($home['popular_title']) ?></h2><p class="reveal"><?= e($home['popular_text']) ?></p></div>
            <a href="/shop/" class="link-arrow reveal">See everything <?= icon('arrow') ?></a>
        </div>
        <div class="grid-products">
            <?php foreach ($featured as $p): ?><?= render('store/partials/product-card', ['p' => $p, 'wishIds' => $wishIds]) ?><?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($on('show_promo')): ?>
<section class="promo2">
    <div class="promo2-bg parallax" data-speed="0.08" style="background-image:url('<?= e($promoImg) ?>')"></div>
    <div class="wrap promo2-inner">
        <div class="promo2-copy reveal">
            <p class="eyebrow"><?= e($home['promo_eyebrow']) ?></p>
            <h2 class="display-2"><?= e($home['promo_title']) ?></h2>
            <p><?= e($home['promo_text']) ?></p>
            <a class="btn btn-light btn-lg" href="<?= e($home['promo_link']) ?>"><?= e($home['promo_button']) ?> <?= icon('arrow') ?></a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($on('show_reviews') && $reviews): ?>
<section class="section">
    <div class="wrap">
        <div class="section-head center-head"><div><h2 class="reveal"><?= e($home['reviews_title']) ?></h2></div></div>
        <div class="quotes">
            <?php foreach ($reviews as $i => $r): ?>
                <figure class="quote reveal" style="--d:<?= $i * 80 ?>ms">
                    <?= stars_html((float) $r['rating']) ?>
                    <blockquote><?= e(str_limit($r['body'], 260)) ?></blockquote>
                    <figcaption><strong><?= e($r['name']) ?></strong><?php if ($r['product_name']): ?><span> on <a href="/product/<?= e($r['product_slug']) ?>/"><?= e($r['product_name']) ?></a></span><?php endif; ?></figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($on('show_split')): ?>
<section class="section">
    <div class="wrap split-cards">
        <a class="split-card reveal" href="/custom-box/">
            <span class="feature-icon"><?= icon('sparkle') ?></span>
            <h2><?= e($home['custom_title']) ?></h2>
            <p><?= e($home['custom_text']) ?></p>
            <span class="link-arrow">Share your idea <?= icon('arrow') ?></span>
        </a>
        <a class="split-card dark reveal" href="/corporate-gifting/" style="--d:80ms">
            <span class="feature-icon"><?= icon('store') ?></span>
            <h2><?= e($home['corporate_title']) ?></h2>
            <p><?= e($home['corporate_text']) ?></p>
            <span class="link-arrow">Get a quote <?= icon('arrow') ?></span>
        </a>
    </div>
</section>
<?php endif; ?>

<?php if ($on('show_journal') && $posts): ?>
<section class="section">
    <div class="wrap">
        <div class="section-head"><h2 class="reveal">From the journal</h2><a href="/blog/" class="link-arrow">All articles <?= icon('arrow') ?></a></div>
        <div class="post-grid"><?php foreach ($posts as $post): ?><?= render('store/partials/post-card', ['post' => $post]) ?><?php endforeach; ?></div>
    </div>
</section>
<?php endif; ?>

<?php if ($on('show_faq') && $home['faq']): ?>
<section class="section">
    <div class="wrap faq-wrap">
        <div class="faq-head"><p class="eyebrow reveal">Good to know</p><h2 class="reveal"><?= e($home['faq_title']) ?></h2><p class="muted reveal">Still curious? <a href="/contact/" class="u">Write to us</a> or WhatsApp anytime.</p></div>
        <div class="faq">
            <?php foreach ($home['faq'] as $f): ?>
                <details class="reveal"><summary><?= e($f['q']) ?><?= icon('plus') ?></summary><p><?= e($f['a']) ?></p></details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
