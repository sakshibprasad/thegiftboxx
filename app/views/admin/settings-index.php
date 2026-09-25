<?php /** @var array $schema */
$extra = [['/box-types', 'Box types', 'wood', 'Pinewood, Teakwood & Plywood page colours'], ['/channels', 'Sales channels', 'plug', 'Google Shopping, Meta, Pinterest status & feeds'], ['/team', 'Team', 'users', 'Who can sign in to this admin'], ['/import', 'Import', 'import', 'Bring products, customers and orders from WooCommerce'], ['/redirects', 'Redirects', 'refresh', 'Keep old links working'], ['/profile', 'Your profile', 'user', 'Name and password']];
$colors = ['website' => '#0A84FF', 'appearance' => '#AF52DE', 'store' => '#FF9F0A', 'payments' => '#34C759', 'shipping' => '#FF6B35', 'integrations' => '#30B0C7', 'email' => '#FF375F'];
$desc = ['website' => 'Maintenance mode, logo, SEO defaults, blog switch', 'appearance' => 'Colours, fonts and corners — shop & admin', 'store' => 'Name, contact details, social links', 'payments' => 'PayU, Cashfree and Cash on Delivery', 'shipping' => 'Charges, delivery dates, Shiprocket', 'integrations' => 'Google Analytics, Ads, Search Console, Meta, Pinterest, WhatsApp', 'email' => 'SMTP and abandoned cart reminders'];
?>
<div class="page-head"><div><h1>Settings</h1><p>Everything about how your store looks and works.</p></div></div>
<div class="grid g2">
    <div class="group">
        <?php foreach ($schema as $key => $g): ?>
            <a class="row" href="/settings/<?= e($key) ?>"><span class="kpi-label" style="flex:1;color:var(--ink);font-size:14px"><span class="dot" style="background:<?= $colors[$key] ?? 'var(--accent)' ?>"><?= icon($g['icon']) ?></span><span><?= e($g['label']) ?><br><small class="muted" style="font-weight:400"><?= e($desc[$key] ?? '') ?></small></span></span><?= icon('chevron-right') ?></a>
        <?php endforeach; ?>
    </div>
    <div class="group">
        <?php foreach ($extra as [$href, $label, $ic, $d]): ?>
            <a class="row" href="<?= e($href) ?>"><span class="kpi-label" style="flex:1;color:var(--ink);font-size:14px"><span class="dot" style="background:#8E8E93"><?= icon($ic) ?></span><span><?= e($label) ?><br><small class="muted" style="font-weight:400"><?= e($d) ?></small></span></span><?= icon('chevron-right') ?></a>
        <?php endforeach; ?>
    </div>
</div>
