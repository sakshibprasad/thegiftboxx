<?php /** @var array $lines @var array $totals @var array $cross */ ?>
<section class="page-hero compact"><div class="wrap"><h1>Your cart</h1></div></section>
<section class="section section-tight">
    <div class="wrap">
        <?php if (!$lines): ?>
            <div class="empty-state"><?= icon('gift') ?><h2>Your cart is empty</h2><p>Let’s find something they’ll love.</p><a class="btn" href="/shop/">Browse gift boxes</a></div>
        <?php else: ?>
        <div class="cart-grid">
            <form method="post" action="/cart/update" class="cart-lines">
                <?= csrf_field() ?>
                <?php foreach ($lines as $l): ?>
                    <div class="cart-line">
                        <a href="<?= e($l['url']) ?>"><img src="<?= e(image_url($l['image'], 'sm')) ?>" alt="" width="110" height="138"></a>
                        <div class="cart-line-info">
                            <a href="<?= e($l['url']) ?>" class="name"><?= e($l['name']) ?></a>
                            <?php if ($l['variation_label']): ?><span class="muted"><?= e($l['variation_label']) ?></span><?php endif; ?>
                            <span class="muted"><?= money($l['price']) ?> each</span>
                            <?php if (!$l['in_stock']): ?><span class="warn">Only <?= (int) $l['max_qty'] ?> left — please reduce the quantity.</span><?php endif; ?>
                        </div>
                        <div class="qty">
                            <button name="qty[<?= e($l['key']) ?>]" value="<?= $l['qty'] - 1 ?>" aria-label="Decrease"><?= icon('minus') ?></button>
                            <span><?= $l['qty'] ?></span>
                            <button name="qty[<?= e($l['key']) ?>]" value="<?= min($l['max_qty'], $l['qty'] + 1) ?>" aria-label="Increase"><?= icon('plus') ?></button>
                        </div>
                        <strong class="line-total"><?= money($l['total']) ?></strong>
                        <button class="icon-btn" name="remove" value="<?= e($l['key']) ?>" aria-label="Remove"><?= icon('trash') ?></button>
                    </div>
                <?php endforeach; ?>
            </form>

            <aside class="summary">
                <h2>Order summary</h2>
                <div class="row"><span>Subtotal</span><span><?= money($totals['subtotal']) ?></span></div>
                <?php if ($totals['discount'] > 0): ?><div class="row good"><span>Coupon <?= e($totals['coupon']) ?></span><span>−<?= money($totals['discount']) ?></span></div><?php endif; ?>
                <div class="row"><span>Delivery</span><span><?= $totals['shipping'] > 0 ? money($totals['shipping']) : 'Free' ?></span></div>
                <?php if ($totals['free_shipping_gap'] > 0): ?><p class="small muted">Add <?= money($totals['free_shipping_gap']) ?> more for free delivery.</p><?php endif; ?>
                <div class="row total"><span>Total</span><span><?= money($totals['total']) ?></span></div>
                <form method="post" action="/cart/coupon" class="coupon">
                    <?= csrf_field() ?>
                    <?php if ($totals['coupon']): ?>
                        <input type="hidden" name="remove_coupon" value="1"><span class="chip on"><?= icon('tag') ?> <?= e($totals['coupon']) ?></span><button class="link">Remove</button>
                    <?php else: ?>
                        <input name="coupon" placeholder="Coupon code" aria-label="Coupon code"><button class="btn btn-ghost btn-small">Apply</button>
                    <?php endif; ?>
                </form>
                <a class="btn btn-lg btn-block" href="/checkout/">Checkout securely <?= icon('arrow') ?></a>
                <p class="small muted center"><?= icon('shield') ?> UPI · Cards · Netbanking</p>
            </aside>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php if ($cross): ?>
<section class="section"><div class="wrap"><div class="section-head"><h2>Add a little extra</h2></div><div class="grid-products"><?php foreach ($cross as $p): ?><?= render('store/partials/product-card', ['p' => $p]) ?><?php endforeach; ?></div></div></section>
<?php endif; ?>
