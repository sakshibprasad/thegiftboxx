<?php /** @var array $rows @var array $stats */ ?>
<div class="page-head"><div><h1>Abandoned carts</h1><p>People who entered their email at checkout but didn’t pay. They get up to two friendly reminders automatically.</p></div>
    <a class="btn secondary" href="/settings/email"><?= icon('settings') ?> Reminder settings</a></div>
<div class="grid g3" style="margin-bottom:16px">
    <div class="card kpi"><div class="kpi-label">Open carts</div><div class="kpi-value"><?= $stats['open'] ?></div></div>
    <div class="card kpi"><div class="kpi-label">Value waiting</div><div class="kpi-value"><?= money($stats['value']) ?></div></div>
    <div class="card kpi"><div class="kpi-label">Recovered</div><div class="kpi-value" style="color:var(--green)"><?= $stats['recovered'] ?></div></div>
</div>
<div class="card">
    <?php if ($rows): ?>
    <div class="table-wrap"><table class="list"><thead><tr><th>Customer</th><th>Status</th><th class="hide-m">Reminders</th><th class="hide-m">When</th><th class="right">Cart</th><th></th></tr></thead><tbody>
        <?php foreach ($rows as $r): $wa = preg_replace('/\D/', '', $r['phone']); ?>
            <tr><td><div class="title"><?= e($r['name'] ?: '—') ?></div><div class="sub"><?= e($r['email']) ?><?= $r['phone'] ? ' · ' . e($r['phone']) : '' ?></div></td>
                <td><span class="badge b-<?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                <td class="hide-m"><?= (int) $r['reminders_sent'] ?> sent</td>
                <td class="hide-m muted"><?= e(time_ago($r['updated_at'])) ?></td>
                <td class="right nowrap"><?= money($r['total']) ?></td>
                <td class="right"><?php if ($wa && $r['status'] !== 'recovered'): ?><a class="btn secondary sm" target="_blank" rel="noopener" href="https://wa.me/<?= e(strlen($wa) === 10 ? '91' . $wa : $wa) ?>?text=<?= rawurlencode('Hi ' . explode(' ', $r['name'])[0] . '! We noticed you were looking at a gift box on ' . setting('store_name') . '. Can we help with anything? Here’s your cart: ' . site_url('restore-cart/' . $r['token'] . '/')) ?>"><?= icon('whatsapp') ?> Nudge</a><?php endif; ?></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <?php else: ?><div class="empty"><?= icon('cart') ?><h2>No abandoned carts</h2></div><?php endif; ?>
</div>
