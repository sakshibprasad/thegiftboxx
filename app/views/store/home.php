<?php
/** @var array $home @var array $featured @var array $cats @var array $posts */
$heroImg = $home['hero_image'] ? image_url($home['hero_image'], 'lg') : '/assets/img/hero.jpg';
$heroImg2 = $home['hero_image_2'] ? image_url($home['hero_image_2'], 'md') : '/assets/img/hero-2.jpg';
$promoImg = $home['promo_image'] ? image_url($home['promo_image'], 'lg') : '/assets/img/about.jpg';
$wishIds = wishlist_ids();
?>
<section class="hero">
    <div class="wrap hero-grid">
        <div class="hero-copy">
            <p class="eyebrow reveal"><?= e($home['hero_eyebrow']) ?></p>
            <h1 class="reveal"><?= e($home['hero_title']) ?></h1>
            <p class="lead reveal"><?= e($home['hero_text']) ?></p>
            <div class="hero-cta reveal">
                <a class="btn btn-lg" href="<?= e($home['hero_link']) ?>"><?= e($home['hero_button']) ?> <?= icon('arrow') ?></a>
                <a class="btn btn-lg btn-ghost" href="/custom-box/">Design your own</a>
            </div>
            <ul class="hero-trust reveal">
                <li><?= icon('box') ?> Real wooden boxes</li>
                <li><?= icon('gift') ?> Ready to gift</li>
                <li><?= icon('truck') ?> Pan-India delivery</li>
            </ul>
        </div>
        <div class="hero-media">
            <figure class="hero-img main parallax" data-speed="0.06"><img src="<?= e($heroImg) ?>" alt="Premium wooden gift hamper by The Gift Boxx" width="960" height="1280" fetchpriority="high"></figure>
            <figure class="hero-img second parallax" data-speed="-0.04"><img src="<?= e($heroImg2) ?>" alt="Curated gift box with chocolates, candle and keepsakes" width="896" height="1196" loading="lazy"></figure>
        </div>
    </div>
</section>

<section class="section intro">
    <div class="wrap narrow center">
        <h2 class="reveal"><?= e($home['intro_title']) ?></h2>
        <?php foreach (preg_split('/\n\s*\n/', trim($home['intro_text'])) as $para): ?>
            <p class="reveal"><?= e($para) ?></p>
        <?php endforeach; ?>
    </div>
    <div class="wrap features">
        <?php foreach ($home['features'] as $i => $f): ?>
            <div class="feature reveal" style="--d:<?= $i * 80 ?>ms">
                <span class="feature-icon"><?= icon(['wood', 'gift', 'star', 'box'][$i % 4]) ?></span>
                <h3><?= e($f['title']) ?></h3>
                <p><?= e($f['text']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($cats): ?>
<section class="section">
    <div class="wrap">
        <div class="section-head">
            <div>
                <h2 class="reveal"><?= e($home['collections_title']) ?></h2>
                <p class="reveal"><?= e($home['collections_text']) ?></p>
            </div>
            <a href="/shop/" class="link-arrow reveal">Shop all <?= icon('arrow') ?></a>
        </div>
        <div class="collections">
            <?php foreach (array_slice($cats, 0, 6) as $i => $c): ?>
                <a class="collection reveal" href="<?= e(category_url($c)) ?>" style="--d:<?= $i * 70 ?>ms">
                    <img src="<?= e($c['image'] ? image_url($c['image'], 'md') : ($i % 2 ? '/assets/img/hero-2.jpg' : '/assets/img/hero.jpg')) ?>" alt="<?= e($c['name']) ?>" loading="lazy" width="600" height="750">
                    <span class="collection-label"><strong><?= e($c['name']) ?></strong><small><?= (int) $c['product_count'] ?> box<?= $c['product_count'] == 1 ? '' : 'es' ?> <?= icon('arrow') ?></small></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($featured): ?>
<section class="section section-tint">
    <div class="wrap">
        <div class="section-head">
            <div>
                <h2 class="reveal"><?= e($home['popular_title']) ?></h2>
                <p class="reveal"><?= e($home['popular_text']) ?></p>
            </div>
            <a href="/shop/" class="link-arrow reveal">See everything <?= icon('arrow') ?></a>
        </div>
        <div class="grid-products">
            <?php foreach ($featured as $p): ?><?= render('store/partials/product-card', ['p' => $p, 'wishIds' => $wishIds]) ?><?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="wrap promo reveal">
        <div class="promo-media"><img src="<?= e($promoImg) ?>" alt="<?= e($home['promo_title']) ?>" loading="lazy" width="736" height="736"></div>
        <div class="promo-copy">
            <p class="eyebrow">Limited edition</p>
            <h2><?= e($home['promo_title']) ?></h2>
            <p><?= e($home['promo_text']) ?></p>
            <a class="btn btn-light" href="<?= e($home['promo_link']) ?>"><?= e($home['promo_button']) ?> <?= icon('arrow') ?></a>
        </div>
    </div>
</section>

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

<?php if ($posts): ?>
<section class="section">
    <div class="wrap">
        <div class="section-head"><h2 class="reveal">From the journal</h2><a href="/blog/" class="link-arrow">All articles <?= icon('arrow') ?></a></div>
        <div class="post-grid"><?php foreach ($posts as $post): ?><?= render('store/partials/post-card', ['post' => $post]) ?><?php endforeach; ?></div>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="wrap narrow">
        <h2 class="center reveal">Questions we often get</h2>
        <div class="faq">
            <?php foreach ($home['faq'] as $f): ?>
                <details class="reveal"><summary><?= e($f['q']) ?><?= icon('plus') ?></summary><p><?= e($f['a']) ?></p></details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
