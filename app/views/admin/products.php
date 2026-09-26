<?php /** @var array $rows @var array $catsByProduct @var string $status @var int $cat @var string $term @var array $counts @var array $pg @var array $cats */ ?>
<div class="page-head">
    <div><h1>Products</h1><p><?= $counts['published'] ?> live · <?= $counts['draft'] ?> drafts</p></div>
    <div class="head-actions">
        <a class="btn secondary" href="/import"><?= icon('import') ?> Import</a>
        <a class="btn" href="/products/new"><?= icon('plus') ?> Add product</a>
    </div>
</div>
<div class="card">
    <form class="toolbar" method="get">
        <div class="segmented">
            <?php foreach (['' => ['All', $counts['all']], 'published' => ['Live', $counts['published']], 'draft' => ['Drafts', $counts['draft']], 'outofstock' => ['Sold out', $counts['outofstock']]] as $k => [$label, $n]): ?>
                <a href="/products<?= $k ? '?status=' . $k : '' ?>" class="<?= $status === $k ? 'on' : '' ?>"><?= $label ?><em><?= $n ?></em></a>
            <?php endforeach; ?>
        </div>
        <div class="search"><?= icon('search') ?><input type="search" name="q" value="<?= e($term) ?>" placeholder="Search name, SKU or tag"></div>
        <select name="category" onchange="this.form.submit()" style="width:auto">
            <option value="">All categories</option>
            <?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $cat === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
        <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    </form>
    <form method="post" action="/products/bulk">
        <?= csrf_field() ?>
        <div class="bulkbar" hidden>
            <strong data-count-sel></strong>
            <select name="action" style="width:auto;height:30px">
                <option value="publish">Publish</option><option value="draft">Move to drafts</option>
                <option value="feature">Mark as bestseller</option><option value="unfeature">Remove bestseller</option>
                <option value="instock">Mark in stock</option><option value="outofstock">Mark sold out</option>
                <option value="delete">Delete</option>
            </select>
            <button class="btn sm" data-confirm="Apply this to the selected products?">Apply</button>
        </div>
        <?php if ($rows): ?>
        <div class="table-wrap">
            <table class="list">
                <thead><tr><th style="width:36px"><input type="checkbox" data-select-all aria-label="Select all"></th><th colspan="2">Product</th><th class="hide-m">Status</th><th class="hide-m">Stock</th><th class="right">Price</th><th class="hide-m right">Views</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $p): ?>
                    <?php $sold = $p['stock_status'] === 'outofstock'; $draft = $p['status'] !== 'published'; ?>
                    <tr data-href="/products/<?= (int) $p['id'] ?>" class="<?= $sold || $draft ? 'is-muted' : '' ?>">
                        <td><input type="checkbox" name="ids[]" value="<?= (int) $p['id'] ?>" aria-label="Select"></td>
                        <td style="width:56px"><span class="thumb-tag"><img class="thumb" src="<?= e(image_url($p['image'], 'sm')) ?>" alt="" loading="lazy"><?php if ($draft): ?><em class="t-draft">Draft</em><?php elseif ($sold): ?><em class="t-sold">Sold out</em><?php endif; ?></span></td>
                        <td><div class="title"><?= e($p['name']) ?> <?= $p['featured'] ? '<span class="badge b-warn">★ Bestseller</span>' : '' ?></div><div class="sub"><?= e(implode(', ', $catsByProduct[$p['id']] ?? ['No category'])) ?><?= $p['type'] === 'variable' ? ' · Has options' : '' ?></div></td>
                        <td class="hide-m"><span class="badge b-<?= e($p['status']) ?>"><?= $p['status'] === 'published' ? 'Live' : 'Draft' ?></span></td>
                        <td class="hide-m"><?= $p['stock_status'] === 'outofstock' ? '<span class="badge b-outofstock">Sold out</span>' : ($p['manage_stock'] && $p['stock_qty'] !== null ? (int) $p['stock_qty'] . ' in stock' : '<span class="muted">In stock</span>') ?></td>
                        <td class="right nowrap"><?= (float) $p['price_min'] > 0 ? money($p['price_min']) . ((float) $p['price_max'] > (float) $p['price_min'] ? ' – ' . money($p['price_max']) : '') : '<span class="dim">—</span>' ?></td>
                        <td class="hide-m right muted"><?= (int) $p['views'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="empty"><?= icon('box') ?><h2><?= $term || $status || $cat ? 'No products match' : 'No products yet' ?></h2><p>Add your first gift box or import them from WooCommerce.</p><a class="btn" href="/products/new">Add product</a></div>
        <?php endif; ?>
    </form>
    <?php if ($pg['pages'] > 1): ?><nav class="pager"><?php for ($i = 1; $i <= $pg['pages']; $i++): ?><a class="<?= $i === $pg['page'] ? 'on' : '' ?>" href="?<?= e(http_build_query(array_filter(['status' => $status, 'q' => $term, 'category' => $cat ?: null, 'page' => $i]))) ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
</div>
