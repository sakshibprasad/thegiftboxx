<?php /** @var array $c @var array $orders @var array $address @var array $wish */
$spent = array_sum(array_map(fn($o) => in_array($o['status'], ['pending_payment', 'failed', 'cancelled', 'refunded'], true) ? 0 : (float) $o['total'], $orders)); ?>
<div class="page-head"><div><a class="back" href="/customers"><?= icon('arrow-left') ?> Customers</a><h1><?= e($c['name'] ?: $c['email']) ?></h1><p>Customer since <?= e(nice_date($c['created_at'])) ?><?= $c['password_hash'] ? '' : ' · hasn’t set a password on the new site yet' ?></p></div></div>
<div class="grid g4" style="margin-bottom:16px">
    <div class="card kpi"><div class="kpi-label">Orders</div><div class="kpi-value"><?= count($orders) ?></div></div>
    <div class="card kpi"><div class="kpi-label">Total spent</div><div class="kpi-value"><?= money($spent) ?></div></div>
    <div class="card kpi"><div class="kpi-label">Wishlist</div><div class="kpi-value"><?= count($wish) ?></div></div>
    <div class="card kpi"><div class="kpi-label">Last seen</div><div class="kpi-value" style="font-size:20px"><?= $c['last_login'] ? e(time_ago($c['last_login'])) : '—' ?></div></div>
</div>
<div class="grid g-main">
    <div class="card"><div class="card-head"><h3>Orders</h3></div>
        <?php if ($orders): ?><div class="rows" style="margin-top:6px"><?php foreach ($orders as $o): ?><a href="/orders/<?= (int) $o['id'] ?>"><span class="grow"><strong><?= e($o['number']) ?></strong><small><?= e(nice_date($o['created_at'])) ?></small></span><?= status_badge($o['status']) ?><strong class="nowrap"><?= money($o['total']) ?></strong></a><?php endforeach; ?></div>
        <?php else: ?><p class="muted" style="padding:10px 22px 18px">No orders yet.</p><?php endif; ?>
    </div>
    <div class="stack">
        <div class="card pad"><h3>Contact</h3><p style="margin:0"><a class="link" href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a><br><?= e($c['phone']) ?></p>
            <?php if ($address): ?><p class="muted" style="margin:10px 0 0"><?= e($address['address1'] ?? '') ?><br><?= e($address['city'] ?? '') ?>, <?= e($address['state'] ?? '') ?> <?= e($address['pincode'] ?? '') ?></p><?php endif; ?></div>
        <?php if ($wish): ?><div class="card"><div class="card-head"><h3>Saved to wishlist</h3></div><ul class="rows" style="margin-top:6px"><?php foreach ($wish as $w): ?><li><span class="grow"><strong><?= e($w['name']) ?></strong></span></li><?php endforeach; ?></ul></div><?php endif; ?>
    </div>
</div>
