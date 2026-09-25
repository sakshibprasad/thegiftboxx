<?php /** @var array $brands */ ?>
<div class="page-head"><div><a class="back" href="/categories"><?= icon('arrow-left') ?> Categories</a><h1>Brands</h1><p>Used for Google Shopping. Leave products without a brand to use “<?= e(setting('store_name')) ?>”.</p></div></div>
<div class="card">
    <ul class="rows">
        <?php foreach ($brands as $b): ?>
            <li><form method="post" action="/brands/save" style="display:flex;gap:8px;flex:1"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><input name="name" value="<?= e($b['name']) ?>"><button class="btn secondary sm">Rename</button></form><span class="muted small nowrap"><?= (int) $b['n'] ?> products</span>
                <form method="post" action="/brands/<?= (int) $b['id'] ?>/delete" data-confirm="Delete brand?"><?= csrf_field() ?><button class="icon-btn" aria-label="Delete"><?= icon('trash') ?></button></form></li>
        <?php endforeach; ?>
        <li><form method="post" action="/brands/save" style="display:flex;gap:8px;flex:1"><?= csrf_field() ?><input name="name" placeholder="New brand name"><button class="btn sm">Add</button></form></li>
    </ul>
</div>
