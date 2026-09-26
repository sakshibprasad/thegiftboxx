<?php /** @var array $rows @var string $type @var array $pg */
$labels = history_labels();
$actionIcon = ['create' => 'plus', 'update' => 'edit', 'delete' => 'trash', 'revert' => 'refresh'];
?>
<div class="page-head"><div><a class="back" href="/settings"><?= icon('arrow-left') ?> Settings</a><h1>History</h1><p>Every change made in this admin. Undo one change, or go back to how things were at any point.</p></div></div>
<div class="card">
    <form class="toolbar" method="get">
        <select name="type" onchange="this.form.submit()" style="width:auto">
            <option value="">All changes</option>
            <?php foreach ($labels as $k => $l): ?><option value="<?= e($k) ?>" <?= $type === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
        </select>
        <span class="muted small">Newest first</span>
    </form>
    <?php if (!$rows): ?><div class="empty"><?= icon('refresh') ?><h2>No changes yet</h2><p>Edits you make will be listed here.</p></div><?php endif; ?>
    <ul class="rows activity">
        <?php foreach ($rows as $r): ?>
            <li class="<?= $r['reverted_at'] ? 'is-reverted' : '' ?>">
                <span class="act-ic a-<?= e($r['action']) ?>"><?= icon($actionIcon[$r['action']] ?? 'edit') ?></span>
                <span class="grow"><strong><?= e($r['summary']) ?></strong>
                    <small><?= e($labels[$r['entity_type']] ?? $r['entity_type']) ?> · <?= e($r['user_name'] ?: 'System') ?> · <?= e(nice_date($r['created_at'], 'j M Y, g:i a')) ?><?= $r['reverted_at'] ? ' · <b>undone ' . e(time_ago($r['reverted_at'])) . '</b>' : '' ?></small></span>
                <?php if ($r['revertible'] && !$r['reverted_at']): ?>
                    <span class="act-btns">
                        <form method="post" action="/activity/<?= (int) $r['id'] ?>/revert" data-confirm="Undo this change?"><?= csrf_field() ?><button class="btn secondary sm"><?= icon('refresh') ?> Undo</button></form>
                        <form method="post" action="/activity/<?= (int) $r['id'] ?>/restore" data-confirm="Undo every change made AFTER this one, so everything is back to how it was right after this change?"><?= csrf_field() ?><button class="btn plain sm">Restore to here</button></form>
                    </span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if ($pg['pages'] > 1): ?><nav class="pager"><?php for ($i = 1; $i <= $pg['pages']; $i++): ?><a class="<?= $i === $pg['page'] ? 'on' : '' ?>" href="?<?= e(http_build_query(array_filter(['type' => $type, 'page' => $i]))) ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
</div>
<p class="help" style="margin:12px 4px">Orders and emails can’t be undone (they’ve already happened), but they’re listed for reference. Undoing a change is itself recorded, so you can undo an undo.</p>
