<?php /** @var array $state @var string $wooUrl @var bool $hasKeys @var bool $seedAvailable */
$running = input('run') === '1' || ($state['step'] ?? 'categories') !== 'categories' && ($state['step'] ?? '') !== 'done';
$done = ($state['step'] ?? '') === 'done';
?>
<div class="page-head"><div><h1>Import</h1><p>Bring everything over from your old WooCommerce store. Safe to run more than once — nothing gets duplicated.</p></div></div>
<div class="grid g2">
    <div class="card pad fields">
        <h2 style="margin:0">Full migration from WooCommerce</h2>
        <p class="muted" style="margin:0">Copies categories, brands, products (with all options &amp; photos), reviews, customers and past orders.</p>
        <ol class="small muted" style="margin:0;padding-left:18px;line-height:1.7">
            <li>In your <strong>old</strong> WordPress admin go to <strong>WooCommerce → Settings → Advanced → REST API → Add key</strong>.</li>
            <li>Description “Migration”, Permissions <strong>Read</strong> → Generate.</li>
            <li>Paste the Consumer key and secret below and press Start. Keep this tab open.</li>
        </ol>
        <form method="post" action="/import/woo/start" class="fields">
            <?= csrf_field() ?>
            <label class="f">Old store address<input name="woo_url" value="<?= e($wooUrl ?: 'https://thegiftboxx.com') ?>"></label>
            <label class="f">Consumer key<input name="woo_key" placeholder="<?= $hasKeys ? 'Saved — paste to replace' : 'ck_…' ?>" autocomplete="off"></label>
            <label class="f">Consumer secret<input type="password" name="woo_secret" placeholder="<?= $hasKeys ? 'Saved — paste to replace' : 'cs_…' ?>" autocomplete="new-password"></label>
            <button class="btn"><?= icon('import') ?> <?= $done ? 'Run again' : 'Start migration' ?></button>
        </form>
        <?php if ($running || $done || !empty($state['log'])): ?>
            <div id="woo-runner" data-autostart="<?= $running && !$done ? '1' : '0' ?>" class="fields">
                <div style="display:flex;justify-content:space-between;align-items:center"><strong id="woo-step"><?= $done ? 'Finished' : 'Ready' ?></strong><button type="button" class="btn secondary sm" id="woo-retry" hidden>Retry</button></div>
                <div style="height:6px;border-radius:3px;background:var(--fill);overflow:hidden"><div id="woo-bar" style="height:100%;width:<?= $done ? 100 : 0 ?>%;background:var(--accent);transition:width .4s"></div></div>
                <pre class="code" id="woo-log" style="max-height:240px;margin:0"><?= e(implode("\n", array_slice($state['log'] ?? [], -40))) ?></pre>
                <?php if (!empty($state['counts'])): ?><p class="small muted" style="margin:0"><?= e(implode(' · ', array_map(fn($k, $v) => "{$v} {$k}", array_keys($state['counts']), $state['counts']))) ?></p><?php endif; ?>
            </div>
            <form method="post" action="/import/woo/reset"><?= csrf_field() ?><button class="btn plain sm">Reset progress</button></form>
        <?php endif; ?>
        <p class="help" style="margin:0">Customers’ passwords can’t be copied (they’re encrypted). Returning customers simply use “Forgot password” once — the login page tells them.</p>
    </div>
    <div class="stack">
        <?php if ($seedAvailable): ?>
        <form class="card pad fields" method="post" action="/import/csv">
            <?= csrf_field() ?><input type="hidden" name="seed" value="1">
            <h2 style="margin:0">Your 5 current products</h2>
            <p class="muted" style="margin:0">From the product export you shared. Photos are downloaded from the live site.</p>
            <label class="check"><input type="checkbox" name="images" value="1" checked> Download photos</label>
            <button class="btn secondary" data-confirm="Import/update the 5 products from the bundled export?"><?= icon('box') ?> Import them now</button>
        </form>
        <?php endif; ?>
        <form class="card pad fields" method="post" action="/import/csv" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <h2 style="margin:0">Import a product CSV</h2>
            <p class="muted" style="margin:0">Use a file exported from <strong>WooCommerce → Products → Export</strong>, or fill in the same columns in Excel / Google Sheets for bulk uploads.</p>
            <input type="file" name="csv" accept=".csv,text/csv" required>
            <label class="check"><input type="checkbox" name="images" value="1" checked> Download photos from the image links</label>
            <button class="btn secondary"><?= icon('import') ?> Upload &amp; import</button>
        </form>
    </div>
</div>
