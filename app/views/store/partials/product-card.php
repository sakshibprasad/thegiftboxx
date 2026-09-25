<?php
/** @var array $p (row from products_query) */
$range = ['min' => $p['price_min'] > 0 ? (float) $p['price_min'] : null, 'max' => (float) $p['price_max'], 'on_sale' => false, 'regular_min' => null];
if ($p['type'] === 'simple') {
    $ep = effective_price($p);
    $range = ['min' => $ep['price'], 'max' => $ep['price'], 'on_sale' => $ep['on_sale'], 'regular_min' => $ep['regular']];
}
$wish = in_array((int) $p['id'], $wishIds ?? wishlist_ids(), true);
?>
<article class="card reveal">
    <a href="<?= e(product_url($p)) ?>" class="card-media">
        <img src="<?= e(image_url($p['image'], 'md')) ?>" alt="<?= e($p['image_alt'] ?? $p['name']) ?>" loading="lazy" width="600" height="750">
        <?php if (!empty($p['image_hover'])): ?>
            <img class="hover" src="<?= e(image_url($p['image_hover'], 'md')) ?>" alt="" loading="lazy" width="600" height="750">
        <?php endif; ?>
        <?php if ($range['on_sale']): ?><span class="pill pill-sale">Sale</span>
        <?php elseif ($p['stock_status'] === 'outofstock'): ?><span class="pill">Sold out</span>
        <?php elseif ($p['featured']): ?><span class="pill">Bestseller</span><?php endif; ?>
    </a>
    <button class="wish-btn<?= $wish ? ' on' : '' ?>" data-wish="<?= (int) $p['id'] ?>" aria-label="Save to wishlist" aria-pressed="<?= $wish ? 'true' : 'false' ?>"><?= icon('heart') ?></button>
    <div class="card-body">
        <h3><a href="<?= e(product_url($p)) ?>"><?= e($p['name']) ?></a></h3>
        <?php if ((int) $p['rating_count'] > 0): ?>
            <div class="card-rating"><?= stars_html((float) $p['rating_avg']) ?><small>(<?= (int) $p['rating_count'] ?>)</small></div>
        <?php endif; ?>
        <div class="card-price"><?= $range['min'] !== null && $p['type'] === 'variable' && $range['min'] != $range['max'] ? '<span class="from">From</span> ' . money($range['min']) : price_html($range) ?></div>
    </div>
</article>
