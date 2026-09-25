<?php /** @var array $rows @var array $filters @var array $counts @var array $pg */
$f = $filters;
$toShip = ($counts['processing'] ?? 0) + ($counts['packed'] ?? 0);
$tabs = ['' => ['All', null], 'to_ship' => ['To ship', $toShip], 'shipped' => ['Shipped', $counts['shipped'] ?? 0], 'delivered' => ['Delivered', $counts['delivered'] ?? 0], 'pending_payment' => ['Unpaid', $counts['pending_payment'] ?? 0], 'cancelled' => ['Cancelled', $counts['cancelled'] ?? 0]];
?>
<div class="page-head"><div><h1>Orders</h1><p><?= $toShip ?> waiting to be packed or shipped</p></div>
    <div class="head-actions"><a class="btn secondary" href="/orders/export?<?= e(http_build_query(array_filter($f))) ?>"><?= icon('import') ?> Export CSV</a></div></div>
<div class="card">
    <form class="toolbar" method="get">
        <div class="segmented" style="overflow-x:auto;max-width:100%">
            <?php foreach ($tabs as $k => [$l, $n]): ?><a href="/orders<?= $k ? '?status=' . $k : '' ?>" class="<?= $f['status'] === $k ? 'on' : '' ?>"><?= $l ?><?= $n !== null ? '<em>' . (int) $n . '</em>' : '' ?></a><?php endforeach; ?>
        </div>
        <div class="search"><?= icon('search') ?><input type="search" name="q" value="<?= e($f['term']) ?>" placeholder="Order no., name, email or phone"></div>
        <input type="date" name="from" value="<?= e($f['from']) ?>" style="width:auto" title="From"><input type="date" name="to" value="<?= e($f['to']) ?>" style="width:auto" title="To">
        <?php if ($f['status']): ?><input type="hidden" name="status" value="<?= e($f['status']) ?>"><?php endif; ?>
        <button class="btn secondary sm">Filter</button>
    </form>
    <?php if ($rows): ?>
    <div class="table-wrap"><table class="list">
        <thead><tr><th>Order</th><th>Customer</th><th class="hide-m">Date</th><th>Status</th><th class="hide-m">Payment</th><th class="hide-m">Deliver by</th><th class="right">Total</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $o): $b = json_arr($o['billing_json']); $s = json_arr($o['shipping_json']); ?>
            <tr data-href="/orders/<?= (int) $o['id'] ?>">
                <td><div class="title"><?= e($o['number']) ?></div><?php if ($o['gift_message']): ?><div class="sub">🎁 Gift note</div><?php endif; ?></td>
                <td><div class="title"><?= e($b['name'] ?? '') ?></div><div class="sub"><?= e($s['city'] ?? '') ?><?= !empty($s['pincode']) ? ' · ' . e($s['pincode']) : '' ?></div></td>
                <td class="hide-m muted nowrap"><?= e(nice_date($o['created_at'], 'j M, g:i a')) ?></td>
                <td><?= status_badge($o['status']) ?></td>
                <td class="hide-m"><span class="badge b-<?= $o['payment_status'] === 'paid' ? 'paid' : ($o['payment_status'] === 'cod' ? 'warn' : 'pending') ?>"><?= e(payment_method_label($o['payment_method'])) ?></span></td>
                <td class="hide-m muted nowrap"><?= $o['delivery_date'] ? e(nice_date($o['delivery_date'], 'j M')) : '—' ?></td>
                <td class="right nowrap"><strong><?= money($o['total']) ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php else: ?>
        <div class="empty"><?= icon('orders') ?><h2>No orders here</h2><p>Try another filter.</p></div>
    <?php endif; ?>
    <?php if ($pg['pages'] > 1): ?><nav class="pager"><?php for ($i = 1; $i <= $pg['pages']; $i++): ?><a class="<?= $i === $pg['page'] ? 'on' : '' ?>" href="?<?= e(http_build_query(array_filter($f + ['page' => $i]))) ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
</div>
