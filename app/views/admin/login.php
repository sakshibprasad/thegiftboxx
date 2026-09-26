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
        <?php if (google_login_ready() && setting_on('google_admin_login')): ?>
            <div class="or-line"><span>or</span></div>
            <a class="btn secondary lg google-btn" href="/auth/google<?= $next ? '?next=' . rawurlencode($next) : '' ?>"><?= google_icon() ?> Continue with Google</a>
        <?php endif; ?>
        <a class="small muted" href="<?= e(site_url()) ?>">← Back to the store</a>
    </form>
</main>
</body></html>
