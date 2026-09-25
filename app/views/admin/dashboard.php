<?php
/** @var array $daily @var float $revenue @var ?float $revenueChange @var int $orders @var ?float $ordersChange @var float $aov @var int $toShip
 *  @var array $recent @var array $top @var array $lowStock @var array $enquiries @var int $abandoned @var int $recovered @var array $setup @var int $range */
$u = admin_user();
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
// Chart geometry
$W = 800; $H = 220; $padL = 8; $padR = 8; $padT = 16; $padB = 26;
$vals = array_values($daily);
$max = max(1, max($vals ?: [0]));
$niceMax = $max <= 1 ? 1000 : ceil($max / pow(10, floor(log10($max)))) * pow(10, floor(log10($max)));
$n = count($vals);
$pts = [];
foreach ($vals as $i => $v) {
    $x = $padL + ($n > 1 ? $i / ($n - 1) : 0) * ($W - $padL - $padR);
    $y = $padT + (1 - $v / $niceMax) * ($H - $padT - $padB);
    $pts[] = [round($x, 1), round($y, 1)];
}
$path = '';
foreach ($pts as $i => [$x, $y]) {
    $path .= ($i ? ' L' : 'M') . "{$x} {$y}";
}
$area = $path . " L{$pts[$n - 1][0]} " . ($H - $padB) . " L{$pts[0][0]} " . ($H - $padB) . ' Z';
$labels = array_keys($daily);
$tipData = array_map(fn($d, $v) => ['l' => date('D, j M', strtotime($d)), 'v' => $v], $labels, $vals);
$done = count(array_filter($setup, fn($s) => $s[1]));
$pct = (int) round($done / max(1, count($setup)) * 100);
$delta = function (?float $c): string {
    if ($c === null) return '<span class="delta dim">—</span>';
    return '<span class="delta ' . ($c >= 0 ? 'up' : 'down') . '">' . ($c >= 0 ? '↑' : '↓') . ' ' . abs(round($c)) . '%</span>';
};
?>
<div class="page-head">
    <div><h1><?= $greet ?>, <?= e(explode(' ', $u['name'] ?: 'there')[0]) ?></h1><p><?= date('l, j F') ?> · Here’s how the store is doing.</p></div>
    <div class="segmented">
        <?php foreach ([7 => '7 days', 30 => '30 days', 90 => '90 days', 365 => '12 months'] as $d => $label): ?>
            <a href="/?days=<?= $d ?>" class="<?= $range === $d ? 'on' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($pct < 100): ?>
<div class="card pad" style="margin-bottom:16px">
    <div style="display:flex;gap:18px;align-items:center;flex-wrap:wrap">
        <div class="progress-ring" style="--p:<?= $pct ?>"><span><?= $pct ?>%</span></div>
        <div style="flex:1;min-width:220px"><h2 style="margin:0">Finish setting up your store</h2><p class="muted" style="margin:2px 0 0">Complete these steps to start taking orders and tracking sales.</p></div>
    </div>
    <ul class="rows setup" style="margin:12px -22px -20px">
        <?php foreach ($setup as [$label, $ok, $href]): ?>
            <li><span class="tick <?= $ok ? 'on' : '' ?>"><?= $ok ? icon('check') : '' ?></span><span class="grow <?= $ok ? 'dim' : '' ?>"><?= e($label) ?></span><?php if (!$ok): ?><a class="btn sm secondary" href="<?= e($href) ?>">Set up</a><?php endif; ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="grid g4">
    <div class="card kpi"><div class="kpi-label"><span class="dot" style="background:var(--green)"><?= icon('chart') ?></span>Revenue</div><div class="kpi-value"><?= money($revenue) ?></div><?= $delta($revenueChange) ?> <span class="small dim">vs previous <?= $range ?> days</span></div>
    <div class="card kpi"><div class="kpi-label"><span class="dot" style="background:var(--blue)"><?= icon('orders') ?></span>Orders</div><div class="kpi-value"><?= $orders ?></div><?= $delta($ordersChange) ?> <span class="small dim">vs previous <?= $range ?> days</span></div>
    <div class="card kpi"><div class="kpi-label"><span class="dot" style="background:var(--purple)"><?= icon('bag') ?></span>Average order</div><div class="kpi-value"><?= money(round($aov)) ?></div><span class="small dim">per paid order</span></div>
    <a class="card kpi" href="/orders?status=to_ship"><div class="kpi-label"><span class="dot" style="background:var(--orange)"><?= icon('truck') ?></span>To pack &amp; ship</div><div class="kpi-value"><?= $toShip ?></div><span class="small" style="color:var(--accent)">Open orders →</span></a>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-head"><h2>Sales</h2><span class="muted small">Last <?= $range ?> days</span></div>
    <div class="chart-wrap" data-points='<?= e(json_encode($tipData)) ?>'>
        <svg class="chart" viewBox="0 0 <?= $W ?> <?= $H ?>" preserveAspectRatio="none" role="img" aria-label="Sales chart">
            <defs><linearGradient id="chartFill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="var(--accent)" stop-opacity=".28"/><stop offset="1" stop-color="var(--accent)" stop-opacity="0"/></linearGradient></defs>
            <?php for ($g = 0; $g <= 3; $g++): $gy = $padT + $g / 3 * ($H - $padT - $padB); ?>
                <line class="grid-line" x1="0" x2="<?= $W ?>" y1="<?= $gy ?>" y2="<?= $gy ?>"/>
            <?php endfor; ?>
            <path class="area" d="<?= $area ?>"/>
            <path class="line" d="<?= $path ?>" vector-effect="non-scaling-stroke"/>
            <?php foreach ($pts as [$x, $y]): ?><circle class="dot" cx="<?= $x ?>" cy="<?= $y ?>" r="4" vector-effect="non-scaling-stroke"/><?php endforeach; ?>
        </svg>
        <div class="chart-tip"></div>
        <div style="display:flex;justify-content:space-between" class="small dim"><span><?= e(date('j M', strtotime($labels[0]))) ?></span><span>Top: <?= money($max) ?>/day</span><span><?= e(date('j M', strtotime(end($labels)))) ?></span></div>
    </div>
</div>

<div class="grid g-main" style="margin-top:16px">
    <div class="card">
        <div class="card-head"><h2>Recent orders</h2><a href="/orders">See all</a></div>
        <?php if ($recent): ?>
            <div class="rows" style="margin-top:8px">
                <?php foreach ($recent as $o): $b = json_arr($o['billing_json']); ?>
                    <a href="/orders/<?= (int) $o['id'] ?>"><span class="avatar" style="width:36px;height:36px"><?= e(mb_strtoupper(mb_substr($b['name'] ?? $o['email'], 0, 1))) ?></span>
                        <span class="grow"><strong><?= e($b['name'] ?? $o['email']) ?></strong><small><?= e($o['number']) ?> · <?= e(time_ago($o['created_at'])) ?></small></span>
                        <?= status_badge($o['status']) ?><strong class="nowrap" style="min-width:80px;text-align:right"><?= money($o['total']) ?></strong></a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty"><?= icon('orders') ?><h2>No orders yet</h2><p>They’ll show up here the moment someone buys.</p></div>
        <?php endif; ?>
    </div>
    <div class="stack">
        <div class="card">
            <div class="card-head"><h3>Best sellers</h3><span class="small dim"><?= $range ?> days</span></div>
            <?php if ($top): ?><ul class="rows" style="margin-top:6px"><?php foreach ($top as $t): ?><li><span class="grow"><strong><?= e($t['name']) ?></strong><small><?= (int) $t['qty'] ?> sold</small></span><span class="nowrap"><?= money($t['revenue']) ?></span></li><?php endforeach; ?></ul>
            <?php else: ?><p class="muted pad" style="padding:12px 22px 18px;margin:0">No sales in this period yet.</p><?php endif; ?>
        </div>
        <div class="card">
            <div class="card-head"><h3>New enquiries</h3><a href="/enquiries">Inbox</a></div>
            <?php if ($enquiries): ?><div class="rows" style="margin-top:6px"><?php foreach ($enquiries as $q): ?><a href="/enquiries/<?= (int) $q['id'] ?>"><span class="grow"><strong><?= e($q['name']) ?></strong><small><?= e(enquiry_types()[$q['type']] ?? $q['type']) ?> · <?= e(time_ago($q['created_at'])) ?></small></span><?= icon('chevron-right') ?></a><?php endforeach; ?></div>
            <?php else: ?><p class="muted" style="padding:12px 22px 18px;margin:0">You’re all caught up.</p><?php endif; ?>
        </div>
        <div class="card pad">
            <h3>Abandoned carts</h3>
            <p class="muted" style="margin:0"><strong style="color:var(--ink)"><?= $abandoned ?></strong> left at checkout · <strong style="color:var(--green)"><?= $recovered ?></strong> recovered by reminder emails</p>
        </div>
        <?php if ($lowStock): ?>
        <div class="card">
            <div class="card-head"><h3>Low stock</h3></div>
            <ul class="rows" style="margin-top:6px"><?php foreach ($lowStock as $s): ?><li><span class="grow"><strong><?= e($s['name']) ?></strong><?php if ($s['vlabel']): ?><small><?= e(variation_label(json_arr($s['vlabel']))) ?></small><?php endif; ?></span><span class="badge <?= (int) $s['stock_qty'] <= 0 ? 'b-err' : 'b-warn' ?>"><?= (int) $s['stock_qty'] ?> left</span></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>
    </div>
</div>
