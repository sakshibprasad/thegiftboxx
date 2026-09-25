<?= render('admin/_head', ['title' => 'Sign in · ' . setting('store_name')]) ?>
<body>
<main class="auth-page">
    <form class="auth-card" method="post" action="/login">
        <div class="logo"><img src="/assets/icon.png" alt=""></div>
        <h1>Sign in to <?= e(setting('store_name')) ?></h1>
        <p class="muted" style="margin:0">Manage orders, products and your website.</p>
        <?php foreach (flashes() as $f): ?><div class="err-box"><?= e($f['message']) ?></div><?php endforeach; ?>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <input type="email" name="email" placeholder="Email" required autocomplete="username" autofocus>
        <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
        <button class="btn lg">Sign in</button>
        <a class="small muted" href="<?= e(site_url()) ?>">← Back to the store</a>
    </form>
</main>
</body></html>
