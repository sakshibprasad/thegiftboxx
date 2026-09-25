<?php /** @var array $c @var array $orders @var array $address */ ?>
<section class="page-hero compact"><div class="wrap account-head"><h1>Hello, <?= e(explode(' ', $c['name'])[0] ?: 'there') ?></h1><a class="btn btn-ghost btn-small" href="/my-account/logout/">Log out</a></div></section>
<section class="section section-tight">
    <div class="wrap account-grid">
        <div class="panel">
            <h2>Your orders</h2>
            <?php if (!$orders): ?>
                <p class="muted">No orders yet. <a href="/shop/">Find a gift</a></p>
            <?php else: ?>
                <div class="order-list">
                    <?php foreach ($orders as $o): ?>
                        <a class="order-row" href="/order/<?= e($o['number']) ?>/?key=<?= e($o['access_key']) ?>">
                            <span><strong><?= e($o['number']) ?></strong><small class="muted"><?= e(nice_date($o['created_at'])) ?></small></span>
                            <span class="status s-<?= e($o['status']) ?>"><?= e(order_status_label($o['status'])) ?></span>
                            <span><?= money($o['total']) ?></span>
                            <?= icon('chevron-right') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <form class="panel" method="post" action="/my-account/details" id="details">
            <h2>Your details</h2>
            <?= csrf_field() ?>
            <div class="form-grid">
                <label>Name<input name="name" value="<?= e($c['name']) ?>"></label>
                <label>Mobile<input name="phone" value="<?= e($c['phone']) ?>"></label>
                <label class="full">Address<input name="address1" value="<?= e($address['address1'] ?? '') ?>"></label>
                <label class="full">Area / landmark<input name="address2" value="<?= e($address['address2'] ?? '') ?>"></label>
                <label>City<input name="city" value="<?= e($address['city'] ?? '') ?>"></label>
                <label>Pincode<input name="pincode" value="<?= e($address['pincode'] ?? '') ?>"></label>
                <label class="full">State<select name="state"><?php foreach (indian_states() as $s): ?><option<?= ($address['state'] ?? 'Maharashtra') === $s ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></label>
                <label class="full">New password <em>(leave empty to keep current)</em><input type="password" name="new_password" minlength="8" autocomplete="new-password"></label>
            </div>
            <button class="btn">Save changes</button>
        </form>
    </div>
</section>
