<?php /** @var string $version @var bool $zipOk @var array $backups @var array $paths */ ?>
<div class="page-head"><div><a class="back" href="/settings"><?= icon('arrow-left') ?> Settings</a><h1>Updates</h1><p>You’re on version <strong><?= e($version) ?></strong>.</p></div></div>
<div class="grid g2">
    <form class="card pad fields" method="post" action="/updates" enctype="multipart/form-data" data-native data-confirm="Install this update now? A backup of the current code is saved first.">
        <?= csrf_field() ?>
        <h2 style="margin:0">Install an update</h2>
        <p class="muted" style="margin:0">Upload the <strong>thegiftboxx-update.zip</strong> file you received. Only the website’s code is replaced — your settings, API keys, products, orders, customers and photos stay exactly as they are. Nothing needs to be set up again.</p>
        <input type="file" name="package" accept=".zip" required>
        <button class="btn" <?= $zipOk ? '' : 'disabled' ?>><?= icon('import') ?> Install update</button>
        <?php if (!$zipOk): ?><p class="err-box" style="margin:0">Turn on the <strong>zip</strong> PHP extension in hPanel → Advanced → PHP Configuration → PHP extensions to use one-click updates.</p><?php endif; ?>
    </form>
    <div class="card pad">
        <h3>How updates work</h3>
        <ol class="small muted" style="padding-left:18px;line-height:1.7;margin:0">
            <li>A backup of the current code is saved (last 5 kept).</li>
            <li>New code files are written into these folders:<br>
                <code><?= e($paths['app']) ?></code><br><code><?= e($paths['admin']) ?></code><br><code><?= e($paths['public']) ?></code></li>
            <li>Any database changes run automatically on the next page load.</li>
            <li><code>app/config.php</code> (your database password &amp; encryption key) and the <code>uploads</code> folder are never touched.</li>
        </ol>
        <?php if ($backups): ?>
            <h3 style="margin-top:18px">Recent backups</h3>
            <ul class="small muted" style="padding-left:18px;margin:0"><?php foreach ($backups as $b): ?><li><?= e(basename($b)) ?> — <?= round(filesize($b) / 1024) ?> KB</li><?php endforeach; ?></ul>
            <p class="help">Stored in <code>storage/backups</code>. To roll back, extract a backup over the same folders in File Manager.</p>
        <?php endif; ?>
    </div>
</div>
<div class="card pad" style="margin-top:16px">
    <h3>Updating manually instead (File Manager)</h3>
    <p class="small muted" style="margin:0">Extract <code>1-private-app.zip</code>, <code>2-admin.zip</code> and <code>3-website.zip</code> into the same folders as before and choose <strong>Replace</strong> when asked. <strong>Don’t delete the <code>app</code> folder first</strong> — it contains <code>config.php</code> with your database login and encryption key.</p>
</div>
