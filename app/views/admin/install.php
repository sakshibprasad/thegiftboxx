<?php /** @var array $errors @var array $values @var bool $appWritable */ ?>
<?= render('admin/_head', ['title' => 'Set up your store']) ?>
<body>
<main class="auth-page">
    <form class="auth-card wide" method="post" action="/install">
        <div style="display:flex;gap:14px;align-items:center">
            <div class="logo" style="margin:0;width:52px;height:52px"><img src="/assets/icon.png" alt=""></div>
            <div><h1>Set up The Gift Boxx</h1><p class="muted" style="margin:0">One-time setup. Takes about a minute.</p></div>
        </div>
        <?php foreach ($errors as $err): ?><div class="err-box"><?= e($err) ?></div><?php endforeach; ?>
        <?php if (!$appWritable): ?><div class="err-box">The <code>app</code> folder is not writable, so the config file can’t be saved automatically. You can continue — you’ll be shown the file to paste manually.</div><?php endif; ?>
        <?= csrf_field() ?>

        <p class="group-title" style="margin-top:6px">1 · Addresses</p>
        <div class="form-grid">
            <label class="f">Website address<input name="site_url" value="<?= e($values['site_url']) ?>" required></label>
            <label class="f">Admin address<input name="admin_url" value="<?= e($values['admin_url']) ?>" required></label>
            <label class="f full">Uploads folder (inside your website’s public_html)<input name="uploads_dir" value="<?= e($values['uploads_dir']) ?>" required><small>Product photos are saved here. The default is usually right.</small></label>
        </div>

        <p class="group-title">2 · Database <span class="dim">(hPanel → Databases → MySQL)</span></p>
        <div class="segmented" style="justify-self:start">
            <label><input type="radio" name="db_driver" value="mysql" <?= ($values['db_driver'] ?? 'mysql') === 'mysql' ? 'checked' : '' ?>><span>MySQL (Hostinger)</span></label>
            <label><input type="radio" name="db_driver" value="sqlite" <?= ($values['db_driver'] ?? '') === 'sqlite' ? 'checked' : '' ?>><span>SQLite (testing only)</span></label>
        </div>
        <div class="form-grid">
            <label class="f">Database name<input name="db_name" value="<?= e($values['db_name']) ?>" placeholder="u123456789_giftboxx"></label>
            <label class="f">Database user<input name="db_user" value="<?= e($values['db_user']) ?>" placeholder="u123456789_admin"></label>
            <label class="f">Database password<input type="password" name="db_pass" autocomplete="new-password"></label>
            <label class="f">Host<input name="db_host" value="<?= e($values['db_host']) ?>"></label>
        </div>

        <p class="group-title">3 · Your admin account</p>
        <div class="form-grid">
            <label class="f">Store name<input name="store_name" value="<?= e($values['store_name']) ?>"></label>
            <label class="f">Your name<input name="name" value="<?= e($values['name']) ?>"></label>
            <label class="f">Email<input type="email" name="email" value="<?= e($values['email']) ?>" required></label>
            <label class="f">Password <small>(10+ characters)</small><input type="password" name="password" minlength="10" required autocomplete="new-password"></label>
        </div>
        <label class="check"><input type="checkbox" name="seed" value="1" <?= !empty($values['seed']) ? 'checked' : '' ?>> Load my 5 current products and their photos from the old website</label>
        <button class="btn lg">Create my store</button>
    </form>
</main>
</body></html>
