<?php /** @var array $rows */ ?>
<div class="page-head"><div><h1>Pages</h1><p>About, policies and any other simple page.</p></div><div class="head-actions"><a class="btn secondary" href="/redirects">Redirects</a><a class="btn" href="/pages/new"><?= icon('plus') ?> New page</a></div></div>
<div class="card"><div class="rows">
    <?php foreach ($rows as $p): ?><a href="/pages/<?= (int) $p['id'] ?>"><?= icon('page') ?><span class="grow"><strong><?= e($p['title']) ?></strong><small>/<?= e($p['slug']) ?>/ · updated <?= e(time_ago($p['updated_at'])) ?></small></span><?= $p['show_in_footer'] ? '<span class="badge">Footer</span>' : '' ?><span class="badge b-<?= e($p['status']) ?>"><?= e(ucfirst($p['status'])) ?></span></a><?php endforeach; ?>
</div></div>
<p class="muted small" style="margin:14px 4px">Contact, Corporate gifting, Design your own box and Track order pages are built in — edit their contact details in Settings → Store.</p>
