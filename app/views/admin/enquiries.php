<?php /** @var array $rows @var string $type @var string $status */ ?>
<div class="page-head"><div><h1>Enquiries</h1><p>Contact messages, corporate requests and custom box ideas.</p></div>
    <div class="segmented"><a href="/enquiries" class="<?= !$type ? 'on' : '' ?>">All</a><?php foreach (enquiry_types() as $k => $l): ?><a href="/enquiries?type=<?= $k ?>" class="<?= $type === $k ? 'on' : '' ?>"><?= e($l) ?></a><?php endforeach; ?></div></div>
<div class="card">
    <?php if ($rows): ?><div class="rows">
        <?php foreach ($rows as $q): ?>
            <a href="/enquiries/<?= (int) $q['id'] ?>" style="<?= $q['status'] === 'new' ? 'font-weight:600' : '' ?>">
                <span class="avatar" style="<?= $q['type'] === 'corporate' ? 'background:var(--purple)' : ($q['type'] === 'custom_box' ? 'background:var(--orange)' : '') ?>"><?= e(mb_strtoupper(mb_substr($q['name'], 0, 1))) ?></span>
                <span class="grow"><strong><?= e($q['name']) ?><?= $q['company'] ? ' · ' . e($q['company']) : '' ?></strong><small><?= e(enquiry_types()[$q['type']] ?? '') ?> · <?= e(str_limit($q['message'], 90)) ?></small></span>
                <span class="badge b-<?= e($q['status']) ?>"><?= e(ucfirst($q['status'])) ?></span><span class="small dim nowrap"><?= e(time_ago($q['created_at'])) ?></span>
            </a>
        <?php endforeach; ?></div>
    <?php else: ?><div class="empty"><?= icon('chat') ?><h2>Inbox zero</h2><p>New messages from the website land here and in your email.</p></div><?php endif; ?>
</div>
