<?php
/** @var array $lines */
$totals = cart_totals($lines);
?>
<?php if (!$lines): ?>
    <div class="drawer-empty">
        <?= icon('gift') ?>
        <p>Your cart is empty.</p>
        <a class="btn" href="/shop/">Find a gift</a>
    </div>
<?php else: ?>
    <?php if ($totals['free_shipping_gap'] > 0): ?>
        <div class="ship-meter">
            <p>Add <strong><?= money($totals['free_shipping_gap']) ?></strong> more for free delivery</p>
            <div class="meter"><span style="width:<?= min(100, round(($totals['subtotal'] - $totals['discount']) / max(1, (float) setting('shipping_free_above')) * 100)) ?>%"></span></div>
        </div>
    <?php elseif ((float) setting('shipping_free_above') > 0): ?>
        <div class="ship-meter done"><?= icon('truck') ?> You’ve unlocked free delivery</div>
    <?php endif; ?>
    <ul class="drawer-lines">
        <?php foreach ($lines as $l): ?>
            <li>
                <img src="<?= e(image_url($l['image'], 'sm')) ?>" alt="" width="72" height="90">
                <div>
                    <a href="<?= e($l['url']) ?>"><?= e($l['name']) ?></a>
                    <?php if ($l['variation_label']): ?><small><?= e($l['variation_label']) ?></small><?php endif; ?>
                    <div class="qty-row">
                        <form class="qty" data-ajax-cart>
                            <button name="qty[<?= e($l['key']) ?>]" value="<?= $l['qty'] - 1 ?>" aria-label="Decrease"><?= icon('minus') ?></button>
                            <span><?= $l['qty'] ?></span>
                            <button name="qty[<?= e($l['key']) ?>]" value="<?= min($l['max_qty'], $l['qty'] + 1) ?>" aria-label="Increase"><?= icon('plus') ?></button>
                        </form>
                        <strong><?= money($l['total']) ?></strong>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="drawer-foot">
        <div class="row"><span>Subtotal</span><strong><?= money($totals['subtotal']) ?></strong></div>
        <p class="muted small">Shipping and discounts are calculated at checkout.</p>
        <a class="btn btn-block" href="/checkout/">Checkout</a>
        <a class="btn btn-ghost btn-block" href="/cart/">View cart</a>
    </div>
<?php endif; ?>
