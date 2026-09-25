<?php /** @var array $post */ ?>
<article class="post-card reveal">
    <a href="/blog/<?= e($post['slug']) ?>/" class="post-media">
        <img src="<?= e(image_url($post['cover_image'], 'md')) ?>" alt="<?= e($post['title']) ?>" loading="lazy" width="600" height="400">
    </a>
    <p class="muted small"><?= e(nice_date($post['published_at'] ?: $post['created_at'])) ?></p>
    <h3><a href="/blog/<?= e($post['slug']) ?>/"><?= e($post['title']) ?></a></h3>
    <p><?= e(str_limit((string) ($post['excerpt'] ?: $post['content']), 140)) ?></p>
</article>
