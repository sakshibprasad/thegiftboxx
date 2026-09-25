<?php /** @var array $rows */ ?>
<div class="page-head"><div><a class="back" href="/pages"><?= icon('arrow-left') ?> Pages</a><h1>Redirects</h1><p>Send an old address to a new one, so Google and old links never hit a dead page.</p></div></div>
<div class="card">
    <form method="post" action="/redirects/save" class="toolbar"><?= csrf_field() ?><input name="from_path" placeholder="Old path, e.g. /old-product/" required style="flex:1;min-width:180px"><span class="muted">→</span><input name="to_url" placeholder="New path or full URL, e.g. /product/new-name/" required style="flex:1;min-width:180px"><button class="btn sm">Add</button></form>
    <?php if ($rows): ?><table class="list"><thead><tr><th>From</th><th>To</th><th class="right">Hits</th><th></th></tr></thead><tbody>
        <?php foreach ($rows as $r): ?><tr><td><code><?= e($r['from_path']) ?></code></td><td><code><?= e($r['to_url']) ?></code></td><td class="right"><?= (int) $r['hits'] ?></td><td class="right"><form method="post" action="/redirects/<?= (int) $r['id'] ?>/delete"><?= csrf_field() ?><button class="icon-btn" aria-label="Delete"><?= icon('trash') ?></button></form></td></tr><?php endforeach; ?>
    </tbody></table><?php else: ?><div class="empty" style="padding:30px"><p>No redirects yet. Your old WordPress product and category links already work without any.</p></div><?php endif; ?>
</div>
