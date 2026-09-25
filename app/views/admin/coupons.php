<?php /** @var array $rows @var ?array $edit */ $e = $edit ?? ['id' => null, 'code' => '', 'type' => 'percent', 'amount' => '', 'min_subtotal' => '', 'max_uses' => '', 'expires_at' => null, 'active' => 1]; ?>
<div class="page-head"><div><h1>Coupons</h1><p>Discount codes customers enter in the cart.</p></div></div>
<div class="grid g-main">
    <div class="card">
        <?php if ($rows): ?>
        <table class="list"><thead><tr><th>Code</th><th>Discount</th><th class="hide-m">Used</th><th class="hide-m">Expires</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($rows as $c): $expired = $c['expires_at'] && $c['expires_at'] < now(); ?>
                <tr data-href="/coupons?edit=<?= (int) $c['id'] ?>"><td><code style="font-weight:600"><?= e($c['code']) ?></code></td>
                    <td><?= $c['type'] === 'percent' ? (float) $c['amount'] . '% off' : money($c['amount']) . ' off' ?><?= (float) $c['min_subtotal'] > 0 ? '<div class="sub">over ' . money($c['min_subtotal']) . '</div>' : '' ?></td>
                    <td class="hide-m"><?= (int) $c['used'] ?><?= $c['max_uses'] !== null ? ' / ' . (int) $c['max_uses'] : '' ?></td>
                    <td class="hide-m muted"><?= $c['expires_at'] ? e(nice_date($c['expires_at'])) : 'Never' ?></td>
                    <td><span class="badge <?= $c['active'] && !$expired ? 'b-ok' : 'b-closed' ?>"><?= $expired ? 'Expired' : ($c['active'] ? 'Active' : 'Off') ?></span></td></tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php else: ?><div class="empty"><?= icon('coupon') ?><h2>No coupons yet</h2><p>Create one on the right, e.g. WELCOME10 for 10% off.</p></div><?php endif; ?>
    </div>
    <form class="card pad fields sticky-col" method="post" action="/coupons/save">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($e['id']) ?>">
        <h3 style="margin:0"><?= $e['id'] ? 'Edit ' . e($e['code']) : 'New coupon' ?></h3>
        <label class="f">Code<input name="code" value="<?= e($e['code']) ?>" required placeholder="WELCOME10" style="text-transform:uppercase"></label>
        <div class="segmented"><label><input type="radio" name="type" value="percent" <?= $e['type'] === 'percent' ? 'checked' : '' ?>><span>% off</span></label><label><input type="radio" name="type" value="fixed" <?= $e['type'] === 'fixed' ? 'checked' : '' ?>><span>₹ off</span></label></div>
        <label class="f">Amount<input name="amount" inputmode="decimal" value="<?= e($e['amount']) ?>" required placeholder="10"></label>
        <label class="f">Minimum cart value (₹)<input name="min_subtotal" inputmode="decimal" value="<?= e((float) $e['min_subtotal'] ?: '') ?>" placeholder="Optional"></label>
        <label class="f">Usage limit<input name="max_uses" inputmode="numeric" value="<?= e($e['max_uses']) ?>" placeholder="Unlimited"></label>
        <label class="f">Expires on<input type="date" name="expires_at" value="<?= $e['expires_at'] ? date('Y-m-d', strtotime($e['expires_at'])) : '' ?>"></label>
        <label class="check"><input type="checkbox" name="active" value="1" <?= $e['active'] ? 'checked' : '' ?>> Active</label>
        <div style="display:flex;gap:8px;justify-content:space-between"><button class="btn">Save</button><?php if ($e['id']): ?><a class="btn secondary" href="/coupons">New</a><button class="btn danger" form="del-cp" data-confirm="Delete coupon?">Delete</button><?php endif; ?></div>
    </form>
</div>
<?php if ($e['id']): ?><form id="del-cp" method="post" action="/coupons/<?= (int) $e['id'] ?>/delete"><?= csrf_field() ?></form><?php endif; ?>
