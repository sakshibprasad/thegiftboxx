<?php /** @var ?array $order @var ?array $tracking @var array $items */
$steps = ['processing' => 'Order confirmed', 'packed' => 'Packed', 'shipped' => 'On the way', 'delivered' => 'Delivered'];
?>
<section class="page-hero">
    <div class="wrap narrow center">
        <h1>Track your order</h1>
        <p class="lead">Enter your order number (from your confirmation email) and the email or mobile number you used at checkout.</p>
    </div>
</section>
<section class="section section-tight">
    <div class="wrap narrow">
        <form method="post" action="/track-order/" class="panel form-grid track-form">
            <?= csrf_field() ?>
            <label>Order number<input name="number" required placeholder="e.g. TGB-1024" value="<?= e($order['number'] ?? '') ?>" autocomplete="off"></label>
            <label>Email or mobile<input name="contact" required placeholder="you@example.com or 98XXXXXXXX" autocomplete="email"></label>
            <button class="btn btn-lg"><?= icon('search') ?> Track order</button>
        </form>

        <?php if ($order):
            $cur = $order['status'] === 'completed' ? 'delivered' : $order['status'];
            $idx = array_search($cur, array_keys($steps), true);
            $ship = json_arr($order['shipping_json']); ?>
            <div class="panel track-result">
                <div class="track-top">
                    <div><p class="eyebrow">Order <?= e($order['number']) ?></p><h2><?= e(order_status_label($order['status'])) ?></h2>
                        <p class="muted">Placed on <?= e(nice_date($order['created_at'])) ?> · <?= money($order['total']) ?><?php if ($order['delivery_date']): ?> · Preferred delivery <?= e(nice_date($order['delivery_date'])) ?><?php endif; ?></p></div>
                    <?php if ($order['tracking_url']): ?><a class="btn" href="<?= e($order['tracking_url']) ?>" target="_blank" rel="noopener"><?= icon('truck') ?> Courier tracking</a><?php endif; ?>
                </div>
                <?php if ($idx !== false): ?>
                    <ol class="progress">
                        <?php $i = 0; foreach ($steps as $label): ?><li class="<?= $i <= $idx ? 'done' : '' ?>"><span></span><?= e($label) ?></li><?php $i++; endforeach; ?>
                    </ol>
                <?php endif; ?>
                <?php if ($order['awb']): ?><p class="muted small"><?= e($order['courier'] ?: 'Courier') ?> · AWB <?= e($order['awb']) ?></p><?php endif; ?>
                <?php $acts = $tracking['shipment_track_activities'] ?? []; if ($acts): ?>
                    <h3>Shipment updates</h3>
                    <ul class="track-events">
                        <?php foreach (array_slice($acts, 0, 12) as $a): ?>
                            <li><strong><?= e($a['activity'] ?? '') ?></strong><small><?= e(($a['location'] ?? '') . ' · ' . nice_date($a['date'] ?? null, 'j M, g:i a')) ?></small></li>
                        <?php endforeach; ?>
                    </ul>
                <?php elseif ($idx !== false && $idx < 2): ?>
                    <p class="muted">We’re preparing your box. You’ll get an email with the courier tracking link as soon as it ships.</p>
                <?php endif; ?>
                <div class="track-items">
                    <?php foreach ($items as $it): ?><div class="row"><span><?= e($it['name']) ?><?php if ($it['variation_label']): ?> <small class="muted">· <?= e($it['variation_label']) ?></small><?php endif; ?> × <?= (int) $it['qty'] ?></span><span><?= money($it['total']) ?></span></div><?php endforeach; ?>
                </div>
                <p class="small muted">Delivering to <?= e($ship['name'] ?? '') ?>, <?= e($ship['city'] ?? '') ?> <?= e($ship['pincode'] ?? '') ?></p>
            </div>
        <?php endif; ?>
        <p class="center muted small">Need help? Call <?= e(setting('store_phone')) ?> or <a href="/contact/">send us a message</a>.</p>
    </div>
</section>
