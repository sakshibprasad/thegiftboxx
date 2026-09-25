<?php /** @var bool $valid @var string $email @var string $token */ ?>
<section class="section auth">
    <div class="wrap narrow">
        <?php if (!$valid): ?>
            <div class="auth-card center"><h1>This link has expired</h1><p class="muted">Reset links work for one hour.</p><a class="btn" href="/my-account/forgot-password/">Send a new link</a></div>
        <?php else: ?>
            <form class="auth-card" method="post" action="/my-account/reset-password/">
                <h1>Choose a new password</h1>
                <?= csrf_field() ?>
                <input type="hidden" name="email" value="<?= e($email) ?>">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <label>New password <em>(8+ characters)</em><input type="password" name="password" minlength="8" required autocomplete="new-password"></label>
                <button class="btn btn-lg btn-block">Save and log in</button>
            </form>
        <?php endif; ?>
    </div>
</section>
