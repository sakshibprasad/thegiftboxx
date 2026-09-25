<?php
/** @var string $title @var ?string $intro @var array $products @var int $total @var int $page @var int $per @var string $sort @var array $cats @var ?string $current */
$pages = (int) ceil($total / max(1, $per));
$base = strtok($_SERVER['REQUEST_URI'] ?? '/shop/', '?');
$wishIds = wishlist_ids();
$sorts = ['manual' => 'Featured', 'latest' => 'Newest', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'popular' => 'Most viewed'];
?>
<section class="page-hero">
    <div class="wrap">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="/">Home</a><?= icon('chevron-right') ?><?php if (!empty($category)): ?><a href="/shop/">Shop</a><?= icon('chevron-right') ?><span><?= e($title) ?></span><?php else: ?><span><?= e($title) ?></span><?php endif; ?></nav>
        <h1><?= e($title) ?></h1>
        <?php if ($intro): ?><div class="lead"><?= str_contains((string) $intro, '<') ? $intro : '<p>' . e($intro) . '</p>' ?></div><?php endif; ?>
    </div>
</section>

<section class="section section-tight">
    <div class="wrap">
        <div class="toolbar">
            <div class="chips" role="list">
                <a role="listitem" class="chip<?= !$current && empty($search) ? ' on' : '' ?>" href="/shop/">All</a>
                <?php foreach ($cats as $c): if (!$c['product_count']) continue; ?>
                    <a role="listitem" class="chip<?= $current === $c['slug'] ? ' on' : '' ?>" href="<?= e(category_url($c)) ?>"><?= e($c['name']) ?></a>
                <?php endforeach; ?>
            </div>
            <?php if (empty($search)): ?>
            <form class="sort" method="get">
                <label for="sort" class="sr">Sort by</label>
                <select id="sort" name="sort" onchange="this.form.submit()">
                    <?php foreach ($sorts as $k => $label): ?><option value="<?= $k ?>"<?= $sort === $k ? ' selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
        </div>

        <?php if ($products): ?>
            <div class="grid-products">
                <?php foreach ($products as $p): ?><?= render('store/partials/product-card', ['p' => $p, 'wishIds' => $wishIds]) ?><?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <?= icon('gift') ?>
                <h2>Nothing here yet</h2>
                <p>We’re putting new boxes together. Meanwhile, tell us what you have in mind and we’ll make it for you.</p>
                <a class="btn" href="/custom-box/">Design your own box</a>
            </div>
        <?php endif; ?>

        <?php if ($pages > 1): ?>
            <nav class="pager" aria-label="Pages">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a href="<?= e($base . '?' . http_build_query(array_filter(['sort' => $sort !== 'manual' ? $sort : null, 'page' => $i > 1 ? $i : null]))) ?>" class="<?= $i === $page ? 'on' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    </div>
</section>

