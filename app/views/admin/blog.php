<?php /** @var array $rows @var bool $enabled */ ?>
<div class="page-head"><div><h1>Blog</h1><p>Helpful articles bring visitors from Google. Write for real people: gift guides, occasion ideas, behind-the-scenes.</p></div><a class="btn" href="/blog/new"><?= icon('plus') ?> New post</a></div>
<?php if (!$enabled): ?>
    <div class="card pad" style="margin-bottom:16px;display:flex;gap:14px;align-items:center;flex-wrap:wrap;background:color-mix(in srgb,var(--orange) 10%,var(--panel))">
        <?= icon('eye') ?><div style="flex:1;min-width:220px"><strong>The blog is hidden from your website.</strong><br><span class="muted">Write and save posts now; switch it on when you have 3–4 good ones.</span></div><a class="btn secondary sm" href="/settings/website">Turn on the blog</a>
    </div>
<?php endif; ?>
<div class="card">
    <?php if ($rows): ?><div class="rows"><?php foreach ($rows as $p): ?>
        <a href="/blog/<?= (int) $p['id'] ?>"><img src="<?= e(image_url($p['cover_image'], 'sm')) ?>" alt="" style="width:64px;height:44px;border-radius:8px;object-fit:cover"><span class="grow"><strong><?= e($p['title']) ?></strong><small><?= e($p['author']) ?> · <?= e(nice_date($p['published_at'] ?: $p['created_at'])) ?></small></span><span class="badge b-<?= e($p['status']) ?>"><?= e(ucfirst($p['status'])) ?></span></a>
    <?php endforeach; ?></div>
    <?php else: ?><div class="empty"><?= icon('blog') ?><h2>No posts yet</h2><p>Ideas: “10 Diwali gift hampers for clients”, “How to choose a gift for someone who has everything”.</p><a class="btn" href="/blog/new">Write the first post</a></div><?php endif; ?>
</div>
