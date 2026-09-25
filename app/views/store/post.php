<?php /** @var array $post @var array $more @var array $products */ ?>
<article class="article">
    <header class="wrap narrow article-head">
        <nav class="crumbs"><a href="/">Home</a><?= icon('chevron-right') ?><a href="/blog/">Journal</a></nav>
        <h1><?= e($post['title']) ?></h1>
        <p class="muted"><?= e($post['author'] ?: setting('store_name')) ?> · <?= e(nice_date($post['published_at'] ?: $post['created_at'])) ?> · <?= max(1, (int) round(str_word_count(strip_tags((string) $post['content'])) / 200)) ?> min read</p>
    </header>
    <?php if ($post['cover_image']): ?>
        <figure class="wrap article-cover"><img src="<?= e(image_url($post['cover_image'], 'lg')) ?>" alt="<?= e($post['title']) ?>" width="1600" height="900"></figure>
    <?php endif; ?>
    <div class="wrap narrow rte article-body"><?= $post['content'] ?></div>
</article>
<?php if ($products): ?>
<section class="section section-tint"><div class="wrap"><div class="section-head"><h2>Gift boxes you might like</h2></div><div class="grid-products three"><?php foreach ($products as $p): ?><?= render('store/partials/product-card', ['p' => $p]) ?><?php endforeach; ?></div></div></section>
<?php endif; ?>
<?php if ($more): ?>
<section class="section"><div class="wrap"><div class="section-head"><h2>Keep reading</h2></div><div class="post-grid"><?php foreach ($more as $m): ?><?= render('store/partials/post-card', ['post' => $m]) ?><?php endforeach; ?></div></div></section>
<?php endif; ?>
