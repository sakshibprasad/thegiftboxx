<?php /** @var array $products @var array $connectors @var array $feeds */
$ready = count(array_filter($products, fn($x) => !$x['issues'] && $x['synced']));
$connected = count(array_filter($connectors, fn($c) => $c[2]));
?>
<div class="page-head"><div><h1>Sales channels</h1><p>Where your products and sales data go: Google Shopping, Facebook &amp; Instagram, Pinterest, and your analytics.</p></div>
    <div class="head-actions"><button class="btn secondary" data-test="/channels/test/ga4">Check GA4 tag</button><button class="btn secondary" data-test="/channels/test/meta">Check Meta Pixel</button></div></div>

<div class="grid g3" style="margin-bottom:16px">
    <div class="card kpi"><div class="kpi-label"><span class="dot" style="background:var(--green)"><?= icon('check') ?></span>Connections</div><div class="kpi-value"><?= $connected ?> / <?= count($connectors) ?></div><span class="small dim">set up</span></div>
    <div class="card kpi"><div class="kpi-label"><span class="dot" style="background:var(--blue)"><?= icon('box') ?></span>Products in feeds</div><div class="kpi-value"><?= count(array_filter($products, fn($x) => $x['synced'])) ?></div><span class="small dim">of <?= count($products) ?> live products</span></div>
    <div class="card kpi"><div class="kpi-label"><span class="dot" style="background:var(--purple)"><?= icon('sparkle') ?></span>Ready for Google</div><div class="kpi-value"><?= $ready ?></div><span class="small dim">no blocking issues</span></div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-head"><h2>Connections</h2><a href="/settings/integrations">Edit keys</a></div>
    <div class="rows" style="margin-top:8px">
        <?php foreach ($connectors as [$name, $sub, $ok, $note, $ext, $edit]): ?>
            <div class="rows-item" style="display:flex;align-items:center;gap:12px;padding:12px 22px;border-top:1px solid var(--line-2)">
                <span class="tick <?= $ok ? 'on' : '' ?>"><?= $ok ? icon('check') : '' ?></span>
                <span class="grow" style="flex:1;min-width:0"><strong><?= e($name) ?></strong> <span class="muted small">· <?= e($sub) ?></span><br><small class="muted"><?= e($note) ?></small></span>
                <span class="badge <?= $ok ? 'b-ok' : 'b-warn' ?>"><?= $ok ? 'Connected' : 'Not set up' ?></span>
                <a class="btn plain sm" href="<?= e($edit) ?>"><?= $ok ? 'Edit' : 'Set up' ?></a>
                <a class="icon-btn" href="<?= e($ext) ?>" target="_blank" rel="noopener" title="Open <?= e($name) ?>"><?= icon('external') ?></a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card pad" style="margin-bottom:16px">
    <h2>Product feeds</h2>
    <p class="muted">Paste these links into each platform once — they update automatically whenever you change a product. Merchant Center checks the feed daily.</p>
    <div class="group" style="box-shadow:none;border:1px solid var(--line)">
        <?php foreach ($feeds as [$label, $url]): ?>
            <div class="row"><span class="row-label"><?= e($label) ?><small><code><?= e($url) ?></code></small></span><button class="btn secondary sm" data-copy="<?= e($url) ?>"><?= icon('copy') ?> Copy</button><a class="btn plain sm" href="<?= e($url) ?>" target="_blank" rel="noopener">Open</a></div>
        <?php endforeach; ?>
        <div class="row"><span class="row-label">Sitemap for Google &amp; Bing<small><code><?= e(site_url('sitemap.xml')) ?></code></small></span><button class="btn secondary sm" data-copy="<?= e(site_url('sitemap.xml')) ?>"><?= icon('copy') ?> Copy</button></div>
        <div class="row"><span class="row-label">AI summary (llms.txt)<small><code><?= e(site_url('llms.txt')) ?></code></small></span><a class="btn plain sm" href="<?= e(site_url('llms.txt')) ?>" target="_blank" rel="noopener">Open</a></div>
    </div>
    <p class="help" style="margin-top:10px">Approval status (e.g. “disapproved for policy”) is shown inside Merchant Center → Products → Diagnostics. This page checks everything on our side before Google sees it.</p>
</div>

<div class="card">
    <div class="card-head"><h2>Product health &amp; Shopping preview</h2></div>
    <?php if (!$products): ?><div class="empty"><p>No live products yet.</p></div><?php endif; ?>
    <div class="rows" style="margin-top:8px">
        <?php foreach ($products as ['p' => $p, 'issues' => $issues, 'warn' => $warn, 'synced' => $synced]):
            $pr = $p['pricing']; $img = $p['images'][0]['path'] ?? null; ?>
            <details style="border-top:1px solid var(--line-2)">
                <summary style="display:flex;align-items:center;gap:12px;padding:12px 22px;cursor:pointer;list-style:none">
                    <img src="<?= e(image_url($img, 'sm')) ?>" alt="" style="width:40px;height:48px;border-radius:8px;object-fit:cover">
                    <span style="flex:1;min-width:0"><strong><?= e($p['name']) ?></strong><br><small class="muted"><?= $pr['min'] !== null ? money($pr['min']) . ($pr['max'] != $pr['min'] ? ' – ' . money($pr['max']) : '') : 'No price' ?> · <?= count($p['variations']) ?: 1 ?> item<?= count($p['variations']) > 1 ? 's' : '' ?> in feed</small></span>
                    <?php if (!$synced): ?><span class="badge">Excluded</span>
                    <?php elseif ($issues): ?><span class="badge b-err"><?= count($issues) ?> issue<?= count($issues) > 1 ? 's' : '' ?></span>
                    <?php elseif ($warn): ?><span class="badge b-warn"><?= count($warn) ?> tip<?= count($warn) > 1 ? 's' : '' ?></span>
                    <?php else: ?><span class="badge b-ok">Ready</span><?php endif; ?>
                </summary>
                <div style="display:grid;grid-template-columns:200px 1fr;gap:20px;padding:6px 22px 20px" class="ch-body">
                    <div class="shop-card"><img src="<?= e(image_url($img, 'md')) ?>" alt=""><div class="b"><div class="t"><?= e($p['name']) ?></div><div class="p"><?= $pr['min'] !== null ? money($pr['min']) : '—' ?><?php if ($pr['on_sale'] && $pr['regular_min'] > $pr['min']): ?><del><?= money($pr['regular_min']) ?></del><?php endif; ?></div><div class="s"><?= e(parse_url(site_url(), PHP_URL_HOST)) ?></div></div></div>
                    <div>
                        <?php foreach ($issues as $i): ?><p style="margin:0 0 6px;color:var(--red)"><?= icon('alert') ?> <?= e($i) ?></p><?php endforeach; ?>
                        <?php foreach ($warn as $w): ?><p style="margin:0 0 6px" class="muted">• <?= e($w) ?></p><?php endforeach; ?>
                        <?php if (!$issues && !$warn): ?><p style="color:#1E9E47;margin:0 0 6px"><?= icon('check') ?> Everything Google needs is here.</p><?php endif; ?>
                        <div class="serp" style="margin-top:12px"><div class="site"><span class="fav"><img src="/assets/favicon.png" alt=""></span><span><?= e(setting('store_name')) ?><small><?= e(parse_url(site_url(), PHP_URL_HOST)) ?> › product › <?= e($p['slug']) ?></small></span></div>
                            <div class="t"><?= e(str_limit(($p['seo_title'] ?: $p['name']) . ' | ' . setting('store_name'), 62)) ?></div>
                            <div class="meta-row"><?= $pr['min'] !== null ? money($pr['min']) : '' ?><?= (int) $p['rating_count'] ? ' · ★ ' . number_format((float) $p['rating_avg'], 1) . ' (' . (int) $p['rating_count'] . ')' : '' ?> · <?= $p['stock_status'] === 'outofstock' ? 'Out of stock' : 'In stock' ?></div>
                            <div class="d"><?= e(str_limit((string) ($p['seo_description'] ?: $p['short_description'] ?: $p['description']), 158)) ?></div></div>
                        <a class="btn secondary sm" style="margin-top:12px" href="/products/<?= (int) $p['id'] ?>">Edit product</a>
                    </div>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
</div>
<style>@media (max-width:700px){.ch-body{grid-template-columns:1fr !important}}</style>
