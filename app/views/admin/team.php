<?php /** @var array $rows */ ?>
<div class="page-head"><div><a class="back" href="/settings"><?= icon('arrow-left') ?> Settings</a><h1>Team</h1><p>People who can sign in here. Shop managers can handle orders and products but not settings.</p></div></div>
<div class="grid g-main">
    <div class="card"><ul class="rows">
        <?php foreach ($rows as $u): ?><li><span class="avatar"><?= e(mb_strtoupper(mb_substr($u['name'] ?: $u['email'], 0, 1))) ?></span><span class="grow"><strong><?= e($u['name']) ?></strong><small><?= e($u['email']) ?> · last sign-in <?= $u['last_login'] ? e(time_ago($u['last_login'])) : 'never' ?></small></span><span class="badge"><?= e(admin_roles()[$u['role']] ?? $u['role']) ?></span>
            <?php if ((int) $u['id'] !== (int) admin_user()['id']): ?><form method="post" action="/team/<?= (int) $u['id'] ?>/delete" data-confirm="Remove admin access for <?= e($u['email']) ?>?"><?= csrf_field() ?><button class="icon-btn" aria-label="Remove"><?= icon('trash') ?></button></form><?php endif; ?></li><?php endforeach; ?>
    </ul></div>
    <form class="card pad fields" method="post" action="/team/save">
        <?= csrf_field() ?><h3 style="margin:0">Add someone</h3>
        <label class="f">Name<input name="name"></label>
        <label class="f">Email<input type="email" name="email" required></label>
        <label class="f">Role<select name="role"><?php foreach (admin_roles() as $k => $l): ?><option value="<?= $k ?>" <?= $k === 'manager' ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
        <label class="f">Password <small>10+ characters — share it privately</small><input type="password" name="password" minlength="10" required autocomplete="new-password"></label>
        <button class="btn">Add to team</button>
    </form>
</div>
