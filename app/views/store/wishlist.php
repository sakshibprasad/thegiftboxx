<?php /** @var array $products */ ?>
<section class="page-hero compact"><div class="wrap"><h1>Your wishlist</h1><?php if (!customer() && $products): ?><p class="muted"><a href="/my-account/">Log in</a> to keep your wishlist on every device.</p><?php endif; ?></div></section>
<section class="section section-tight">
    <div class="wrap">
        <?php if ($products): ?>
            <div class="grid-products"><?php foreach ($products as $p): ?><?= render('store/partials/product-card', ['p' => $p]) ?><?php endforeach; ?></div>
        <?php else: ?>
            <div class="empty-state"><?= icon('heart') ?><h2>Nothing saved yet</h2><p>Tap the heart on any box to save it here for later.</p><a class="btn" href="/shop/">Browse gift boxes</a></div>
        <?php endif; ?>
    </div>
</section>
