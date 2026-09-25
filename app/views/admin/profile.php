<?php /** @var array $u */ ?>
<div class="page-head"><div><h1>Your profile</h1><p><?= e($u['email']) ?></p></div></div>
<form class="card pad fields" method="post" action="/profile" style="max-width:520px">
    <?= csrf_field() ?>
    <label class="f">Name<input name="name" value="<?= e($u['name']) ?>"></label>
    <label class="f">New password <small>leave empty to keep the current one</small><input type="password" name="new" minlength="10" autocomplete="new-password"></label>
    <label class="f">Current password <small>required to save</small><input type="password" name="current" required autocomplete="current-password"></label>
    <button class="btn">Save</button>
</form>
