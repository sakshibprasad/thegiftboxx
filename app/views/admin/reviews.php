<?php /** @var array $rows @var string $status */ ?>
<div class="page-head"><div><h1>Reviews</h1><p>New reviews wait here until you approve them.</p></div>
    <div class="segmented"><?php foreach (['pending' => 'Waiting', 'approved' => 'Approved', 'all' => 'All'] as $k => $l): ?><a href="/reviews?status=<?= $k ?>" class="<?= $status === $k ? 'on' : '' ?>"><?= $l ?></a><?php endforeach; ?></div></div>
<div class="card">
    <?php if (!$rows): ?><div class="empty"><?= icon('star') ?><h2>Nothing here</h2></div><?php endif; ?>
    <ul class="rows">
        <?php foreach ($rows as $r): ?>
            <li style="align-items:flex-start">
                <span class="grow"><strong><?= str_repeat('★', (int) $r['rating']) ?><span class="dim"><?= str_repeat('★', 5 - (int) $r['rating']) ?></span> · <?= e($r['name']) ?></strong>
                    <small><?= e($r['product_name'] ?? 'Deleted product') ?> · <?= e($r['email']) ?> · <?= e(time_ago($r['created_at'])) ?></small>
                    <p style="margin:6px 0 0;white-space:pre-line"><?= e($r['body']) ?></p></span>
                <span class="badge b-<?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span>
                <?php if ($r['status'] !== 'approved'): ?><form method="post" action="/reviews/<?= (int) $r['id'] ?>/approve"><?= csrf_field() ?><button class="btn sm">Approve</button></form><?php else: ?><form method="post" action="/reviews/<?= (int) $r['id'] ?>/pending"><?= csrf_field() ?><button class="btn secondary sm">Hide</button></form><?php endif; ?>
                <form method="post" action="/reviews/<?= (int) $r['id'] ?>/delete" data-confirm="Delete this review?"><?= csrf_field() ?><button class="icon-btn" aria-label="Delete"><?= icon('trash') ?></button></form>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
