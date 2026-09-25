<?php /** @var array $posts @var int $page @var int $total */ ?>
<section class="page-hero"><div class="wrap narrow center"><h1>The Journal</h1><p class="lead">Gift ideas, occasion guides and notes from our packing table.</p></div></section>
<section class="section section-tight">
    <div class="wrap">
        <?php if ($posts): ?>
            <div class="post-grid"><?php foreach ($posts as $post): ?><?= render('store/partials/post-card', ['post' => $post]) ?><?php endforeach; ?></div>
            <?php if ($total > 12): ?><nav class="pager"><?php for ($i = 1; $i <= ceil($total / 12); $i++): ?><a class="<?= $i === $page ? 'on' : '' ?>" href="/blog/<?= $i > 1 ? '?page=' . $i : '' ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
        <?php else: ?>
            <div class="empty-state"><?= icon('blog') ?><h2>First stories coming soon</h2></div>
        <?php endif; ?>
    </div>
</section>
