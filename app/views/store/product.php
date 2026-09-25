<?php
/** @var array $p @var array $reviews @var array $related @var array $theme @var array $boxTypes @var array $crumbs @var bool $inWishlist @var bool $customColor */
$images = $p['images'];
$varData = [];
$options = [];
foreach ($p['variations'] as $v) {
    if (!$v['enabled']) {
        continue;
    }
    foreach ($v['attributes'] as $name => $val) {
        $options[$name][$val] = true;
    }
    $varData[] = [
        'id' => (int) $v['id'],
        'attrs' => $v['attributes'],
        'price' => $v['pricing']['price'],
        'regular' => $v['pricing']['regular'],
        'on_sale' => $v['pricing']['on_sale'],
        'stock' => $v['stock_status'],
        'sku' => $v['sku'],
        'desc' => $v['description'],
        'images' => array_map(fn($i) => ['lg' => image_url($i['path'], 'lg'), 'sm' => image_url($i['path'], 'sm')], $v['images']),
        'box' => $customColor ? null : ($theme['map'][$v['id']] ?? null),
    ];
}
// Keep option order as defined on the product
foreach ($p['attributes'] as $a) {
    if (isset($options[$a['name']])) {
        $ordered = [];
        foreach ($a['options'] as $opt) {
            if (isset($options[$a['name']][$opt])) {
                $ordered[$opt] = true;
            }
        }
        $options[$a['name']] = $ordered + $options[$a['name']];
    }
}
$infoAttrs = array_filter($p['attributes'], fn($a) => empty($a['variation']) && !empty($a['options']) && ($a['visible'] ?? true));
$isVariable = $p['type'] === 'variable' && $varData;
$defaults = $p['default_attributes'];
$boxJs = [];
foreach ($boxTypes as $id => $bt) {
    $boxJs[$id] = $bt['vars'];
}
$outOfStock = !$isVariable && $p['stock_status'] === 'outofstock';
$eta = setting('shipping_eta');
?>
<div class="wood-backdrop" aria-hidden="true"></div>
<section class="product wrap">
    <nav class="crumbs" aria-label="Breadcrumb">
        <?php foreach ($crumbs as $i => [$name, $url]): ?>
            <?php if ($i === count($crumbs) - 1): ?><span><?= e($name) ?></span>
            <?php else: ?><a href="<?= e($url) ?>"><?= e($name) ?></a><?= icon('chevron-right') ?><?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <div class="product-grid">
        <div class="gallery" data-gallery>
            <div class="gallery-main">
                <?php foreach ($images ?: [['path' => null, 'alt' => $p['name']]] as $i => $img): ?>
                    <figure class="slide<?= $i === 0 ? ' on' : '' ?>" data-slide="<?= $i ?>">
                        <img src="<?= e(image_url($img['path'], 'lg')) ?>" alt="<?= e($img['alt'] ?: $p['name']) ?>" width="1200" height="1500" <?= $i ? 'loading="lazy"' : 'fetchpriority="high"' ?>>
                    </figure>
                <?php endforeach; ?>
                <?php if (count($images) > 1): ?>
                    <button class="gal-nav prev" data-gal="-1" aria-label="Previous image"><?= icon('arrow-left') ?></button>
                    <button class="gal-nav next" data-gal="1" aria-label="Next image"><?= icon('arrow') ?></button>
                <?php endif; ?>
            </div>
            <?php if (count($images) > 1): ?>
                <div class="thumbs">
                    <?php foreach ($images as $i => $img): ?>
                        <button class="thumb<?= $i === 0 ? ' on' : '' ?>" data-thumb="<?= $i ?>" aria-label="Show image <?= $i + 1 ?>"><img src="<?= e(image_url($img['path'], 'sm')) ?>" alt="" width="120" height="150" loading="lazy"></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="buy">
            <div class="buy-inner">
                <?php if ($p['categories']): ?><a class="eyebrow" href="<?= e(category_url($p['categories'][0])) ?>"><?= e($p['categories'][0]['name']) ?></a><?php endif; ?>
                <h1><?= e($p['name']) ?></h1>
                <?php if ((int) $p['rating_count'] > 0): ?>
                    <a href="#reviews" class="rating-line"><?= stars_html((float) $p['rating_avg']) ?> <span><?= number_format((float) $p['rating_avg'], 1) ?> · <?= (int) $p['rating_count'] ?> review<?= $p['rating_count'] == 1 ? '' : 's' ?></span></a>
                <?php endif; ?>
                <div class="buy-price" data-price><?= price_html($p['pricing']) ?></div>
                <p class="tax-note">Inclusive of all taxes</p>
                <?php if ($p['short_description']): ?><div class="short-desc"><?= $p['short_description'] ?></div><?php endif; ?>

                <form class="buy-form" action="/cart/add" method="post" data-add-to-cart>
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                    <input type="hidden" name="variation_id" value="" data-variation-id>
                    <?php foreach ($options as $attr => $vals): ?>
                        <fieldset class="option">
                            <legend><?= e($attr) ?>: <strong data-selected-label="<?= e($attr) ?>"><?= e($defaults[$attr] ?? '') ?></strong></legend>
                            <div class="swatches">
                                <?php foreach (array_keys($vals) as $val): $bt = box_type_match((string) $val); ?>
                                    <label class="swatch<?= $bt ? ' is-wood' : '' ?>">
                                        <input type="radio" name="attr[<?= e($attr) ?>]" value="<?= e($val) ?>" <?= ($defaults[$attr] ?? null) === $val ? 'checked' : '' ?>>
                                        <span><?php if ($bt): ?><i class="wood-dot" style="<?= e(box_type_css_vars($bt)) ?>"></i><?php endif; ?><?= e($val) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                    <?php endforeach; ?>
                    <p class="var-desc muted" data-var-desc hidden></p>

                    <div class="buy-actions">
                        <div class="qty qty-lg">
                            <button type="button" data-step="-1" aria-label="Decrease quantity"><?= icon('minus') ?></button>
                            <input type="number" name="qty" value="1" min="1" max="50" inputmode="numeric" aria-label="Quantity">
                            <button type="button" data-step="1" aria-label="Increase quantity"><?= icon('plus') ?></button>
                        </div>
                        <button class="btn btn-lg btn-grow" type="submit" data-add-btn <?= $outOfStock ? 'disabled' : '' ?>>
                            <span data-add-label><?= $outOfStock ? 'Sold out' : ($isVariable && !$defaults ? 'Choose an option' : 'Add to cart') ?></span>
                        </button>
                        <button type="button" class="icon-btn icon-btn-lg wish-btn inline<?= $inWishlist ? ' on' : '' ?>" data-wish="<?= (int) $p['id'] ?>" aria-label="Save to wishlist" aria-pressed="<?= $inWishlist ? 'true' : 'false' ?>"><?= icon('heart') ?></button>
                    </div>
                </form>

                <ul class="perks">
                    <li><?= icon('truck') ?><span><?= e($eta) ?><?php if ((float) setting('shipping_free_above') > 0): ?> · Free delivery above <?= money((float) setting('shipping_free_above')) ?><?php endif; ?></span></li>
                    <li><?= icon('gift') ?><span>Gift-ready packing with a handwritten message card</span></li>
                    <li><?= icon('shield') ?><span>Secure checkout with UPI, cards and netbanking</span></li>
                </ul>

                <?php if (setting_on('pincode_check')): ?>
                    <form class="pincode" data-pincode>
                        <label for="pin"><?= icon('pin') ?> Check delivery</label>
                        <div class="pincode-row"><input id="pin" name="code" inputmode="numeric" maxlength="6" placeholder="Enter pincode" autocomplete="postal-code"><button class="btn btn-small btn-ghost">Check</button></div>
                        <p class="small" data-pincode-msg></p>
                    </form>
                <?php endif; ?>

                <?php if ($wa = preg_replace('/\D/', '', (string) setting('whatsapp_number'))): ?>
                    <a class="wa-inline" href="https://wa.me/<?= e($wa) ?>?text=<?= rawurlencode('Hi! I have a question about ' . $p['name'] . ' – ' . site_url(product_url($p))) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> Questions? Chat with us on WhatsApp</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight product-details">
    <div class="wrap narrow">
        <div class="accordion">
            <?php if ($p['description']): ?>
                <details open><summary>About this box<?= icon('plus') ?></summary><div class="rte"><?= $p['description'] ?></div></details>
            <?php endif; ?>
            <?php if ($infoAttrs || $p['weight'] || $p['length']): ?>
                <details><summary>Details &amp; size<?= icon('plus') ?></summary>
                    <table class="specs">
                        <?php foreach ($infoAttrs as $a): ?><tr><th><?= e($a['name']) ?></th><td><?= e(implode(', ', $a['options'])) ?></td></tr><?php endforeach; ?>
                        <?php if ($p['length'] && $p['width'] && $p['height']): ?><tr><th>Box size</th><td><?= (float) $p['length'] ?> × <?= (float) $p['width'] ?> × <?= (float) $p['height'] ?> in</td></tr><?php endif; ?>
                        <?php if ($p['weight']): ?><tr><th>Weight</th><td><?= (float) $p['weight'] ?> kg</td></tr><?php endif; ?>
                        <?php if ($p['sku']): ?><tr><th>SKU</th><td><?= e($p['sku']) ?></td></tr><?php endif; ?>
                    </table>
                </details>
            <?php endif; ?>
            <details><summary>Delivery &amp; returns<?= icon('plus') ?></summary>
                <div class="rte"><p><?= e($eta) ?>. You can pick a preferred delivery date at checkout, and we’ll email you a tracking link once it ships.</p><p>Because every box is packed to order, we can’t accept returns on opened gifts. If something arrives damaged, message us within 48 hours with a photo and we’ll make it right. <a href="/refund_returns/">Read the full policy</a>.</p></div>
            </details>
        </div>
    </div>
</section>

<section class="section section-tight" id="reviews">
    <div class="wrap narrow">
        <div class="reviews-head">
            <h2>Reviews</h2>
            <?php if ((int) $p['rating_count'] > 0): ?><div class="big-rating"><strong><?= number_format((float) $p['rating_avg'], 1) ?></strong><?= stars_html((float) $p['rating_avg']) ?><small><?= (int) $p['rating_count'] ?> review<?= $p['rating_count'] == 1 ? '' : 's' ?></small></div><?php endif; ?>
        </div>
        <?php if ($reviews): ?>
            <ul class="reviews">
                <?php foreach ($reviews as $r): ?>
                    <li><div class="review-top"><strong><?= e($r['name']) ?></strong><?= stars_html((float) $r['rating']) ?><small class="muted"><?= e(nice_date($r['created_at'])) ?></small></div><p><?= nl2br(e($r['body'])) ?></p></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted">No reviews yet. If you’ve gifted this box, we’d love to hear how it went.</p>
        <?php endif; ?>
        <?php if (setting_on('reviews_enabled')): ?>
        <details class="review-form">
            <summary class="btn btn-ghost">Write a review</summary>
            <form method="post" action="/product/<?= e($p['slug']) ?>/review" class="form-grid">
                <?= csrf_field() ?>
                <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
                <div class="stars-input" role="radiogroup" aria-label="Your rating">
                    <?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" id="r<?= $i ?>" name="rating" value="<?= $i ?>" required><label for="r<?= $i ?>" title="<?= $i ?> stars">★</label><?php endfor; ?>
                </div>
                <label>Your name<input name="name" required maxlength="80" value="<?= e(customer()['name'] ?? '') ?>"></label>
                <label>Email (not shown)<input type="email" name="email" required value="<?= e(customer()['email'] ?? '') ?>"></label>
                <label class="full">Your review<textarea name="body" rows="4" required maxlength="3000"></textarea></label>
                <button class="btn">Submit review</button>
            </form>
        </details>
        <?php endif; ?>
    </div>
</section>

<?php if ($related): ?>
<section class="section">
    <div class="wrap">
        <div class="section-head"><h2>You may also like</h2></div>
        <div class="grid-products"><?php foreach ($related as $rp): ?><?= render('store/partials/product-card', ['p' => $rp]) ?><?php endforeach; ?></div>
    </div>
</section>
<?php endif; ?>

<div class="sticky-buy" data-sticky-buy hidden>
    <div class="wrap sticky-inner">
        <span class="sticky-name"><?= e($p['name']) ?></span>
        <span class="sticky-price" data-sticky-price><?= price_html($p['pricing']) ?></span>
        <button class="btn" data-scroll-buy>Add to cart</button>
    </div>
</div>

<script type="application/json" id="product-data"><?= json_encode([
    'variable' => $isVariable,
    'variations' => $varData,
    'boxes' => $boxJs,
    'defaultBox' => $customColor ? null : $theme['default'],
    'baseImages' => array_map(fn($i) => ['lg' => image_url($i['path'], 'lg'), 'sm' => image_url($i['path'], 'sm')], $images),
    'name' => $p['name'],
    'id' => (int) $p['id'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
