<?php
/** @var array $order @var array $items @var bool $justPlaced */
$ship = json_arr($order['shipping_json']);
$steps = ['processing' => 'Confirmed', 'packed' => 'Packed', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
$order_idx = array_search($order['status'] === 'completed' ? 'delivered' : $order['status'], array_keys($steps), true);
$pending = $order['status'] === 'pending_payment' || $order['status'] === 'failed';
?>
<section class="section order-page">
    <div class="wrap narrow">
        <div class="order-hero center">
            <?php if ($pending): ?>
                <span class="big-icon warn"><?= icon('alert') ?></span>
                <h1>Payment not completed</h1>
                <p class="lead">Order <?= e($order['number']) ?> is saved, but we haven’t received the payment yet.</p>
                <a class="btn btn-lg" href="/pay/<?= e($order['number']) ?>/?key=<?= e($order['access_key']) ?>">Complete payment</a>
            <?php else: ?>
                <span class="big-icon"><?= icon('check') ?></span>
                <h1><?= $justPlaced ? 'Thank you! Your gift is on its way to being packed.' : 'Order ' . e($order['number']) ?></h1>
                <p class="lead">Order <strong><?= e($order['number']) ?></strong> · <?= e(order_status_label($order['status'])) ?>. We’ve emailed a confirmation to <?= e($order['email']) ?>.</p>
            <?php endif; ?>
        </div>

        <?php if (!$pending && $order_idx !== false && !in_array($order['status'], ['cancelled', 'refunded'], true)): ?>
            <ol class="progress">
                <?php $i = 0; foreach ($steps as $label): ?>
                    <li class="<?= $i <= $order_idx ? 'done' : '' ?>"><span></span><?= e($label) ?></li>
                <?php $i++; endforeach; ?>
            </ol>
        <?php endif; ?>
        <?php if ($order['awb']): ?>
            <p class="center"><a class="btn btn-ghost" href="<?= e($order['tracking_url']) ?>" target="_blank" rel="noopener"><?= icon('truck') ?> Track with <?= e($order['courier'] ?: 'courier') ?> · AWB <?= e($order['awb']) ?></a></p>
        <?php endif; ?>

        <div class="panel order-summary">
            <?php foreach ($items as $it): ?>
                <div class="row"><span><?= e($it['name']) ?><?php if ($it['variation_label']): ?> <small class="muted">· <?= e($it['variation_label']) ?></small><?php endif; ?> × <?= (int) $it['qty'] ?></span><span><?= money($it['total']) ?></span></div>
            <?php endforeach; ?>
            <hr>
            <div class="row"><span>Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
            <?php if ((float) $order['discount'] > 0): ?><div class="row good"><span>Discount</span><span>−<?= money($order['discount']) ?></span></div><?php endif; ?>
            <div class="row"><span>Delivery</span><span><?= (float) $order['shipping'] > 0 ? money($order['shipping']) : 'Free' ?></span></div>
            <?php if ((float) $order['fee'] > 0): ?><div class="row"><span>COD fee</span><span><?= money($order['fee']) ?></span></div><?php endif; ?>
            <div class="row total"><span>Total</span><span><?= money($order['total']) ?></span></div>
        </div>

        <div class="two-col">
            <div class="panel">
                <h3>Delivering to</h3>
                <p><?= e($ship['name'] ?? '') ?><br><?= e($ship['address1'] ?? '') ?><?= !empty($ship['address2']) ? ', ' . e($ship['address2']) : '' ?><br><?= e($ship['city'] ?? '') ?>, <?= e($ship['state'] ?? '') ?> <?= e($ship['pincode'] ?? '') ?><br><?= e($ship['phone'] ?? '') ?></p>
            </div>
            <div class="panel">
                <h3>Details</h3>
                <p>Payment: <?= e(payment_method_label($order['payment_method'])) ?><br>
                <?php if ($order['delivery_date']): ?>Preferred delivery: <?= e(nice_date($order['delivery_date'])) ?><br><?php endif; ?>
                Placed on <?= e(nice_date($order['created_at'], 'j M Y, g:i a')) ?></p>
                <?php if ($order['gift_message']): ?><blockquote class="gift-note">“<?= e($order['gift_message']) ?>”</blockquote><?php endif; ?>
            </div>
        </div>
        <p class="center muted">Questions about your order? Call <?= e(setting('store_phone')) ?> or <a href="/contact/">message us</a>.</p>
    </div>
</section>
