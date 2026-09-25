<?php /** @var array $rows @var string $term @var array $pg */ ?>
<div class="page-head"><div><h1>Customers</h1><p><?= $pg['total'] ?> people have shopped or signed up</p></div></div>
<div class="card">
    <form class="toolbar" method="get"><div class="search"><?= icon('search') ?><input type="search" name="q" value="<?= e($term) ?>" placeholder="Name, email or phone"></div></form>
    <?php if ($rows): ?>
    <div class="table-wrap"><table class="list"><thead><tr><th>Customer</th><th class="hide-m">Phone</th><th class="right">Orders</th><th class="right">Spent</th><th class="hide-m">Last order</th></tr></thead><tbody>
        <?php foreach ($rows as $r): ?>
            <tr <?= $r['user_id'] ? 'data-href="/customers/' . (int) $r['user_id'] . '"' : 'data-href="/orders?q=' . e(urlencode($r['email'])) . '"' ?>>
                <td><div style="display:flex;gap:10px;align-items:center"><span class="avatar"><?= e(mb_strtoupper(mb_substr($r['name'] ?: $r['email'], 0, 1))) ?></span><div><div class="title"><?= e($r['name'] ?: '—') ?> <?= $r['user_id'] ? '' : '<span class="badge">Guest</span>' ?></div><div class="sub"><?= e($r['email']) ?></div></div></div></td>
                <td class="hide-m muted"><?= e($r['phone']) ?></td>
                <td class="right"><?= (int) $r['orders'] ?></td>
                <td class="right nowrap"><?= money((float) $r['spent']) ?></td>
                <td class="hide-m muted"><?= $r['last_order'] ? e(time_ago($r['last_order'])) : 'No orders' ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <?php else: ?><div class="empty"><?= icon('users') ?><h2>No customers yet</h2></div><?php endif; ?>
    <?php if ($pg['pages'] > 1): ?><nav class="pager"><?php for ($i = 1; $i <= $pg['pages']; $i++): ?><a class="<?= $i === $pg['page'] ? 'on' : '' ?>" href="?<?= e(http_build_query(array_filter(['q' => $term, 'page' => $i]))) ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
</div>
