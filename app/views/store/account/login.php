<?php $tab = input('tab') === 'register' ? 'register' : 'login'; ?>
<section class="section auth">
    <div class="wrap auth-wrap">
        <div class="auth-card">
            <?php if (google_login_ready()): ?>
                <a class="btn btn-ghost btn-lg btn-block google-btn" href="/auth/google/<?= !empty($next) ? '?next=' . rawurlencode($next) : '' ?>"><?= google_icon() ?> Continue with Google</a>
                <div class="or-line"><span>or use your email</span></div>
            <?php endif; ?>
            <div class="tabs" role="tablist">
                <button role="tab" data-tab="login" class="<?= $tab === 'login' ? 'on' : '' ?>">Log in</button>
                <button role="tab" data-tab="register" class="<?= $tab === 'register' ? 'on' : '' ?>">Create account</button>
            </div>
            <form method="post" action="/my-account/login" data-panel="login" <?= $tab === 'login' ? '' : 'hidden' ?>>
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= e($next ?? '') ?>">
                <label>Email<input type="email" name="email" required autocomplete="email"></label>
                <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
                <button class="btn btn-lg btn-block">Log in</button>
                <a class="small" href="/my-account/forgot-password/">Forgot your password?</a>
            </form>
            <form method="post" action="/my-account/register" data-panel="register" <?= $tab === 'register' ? '' : 'hidden' ?>>
                <?= csrf_field() ?>
                <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
                <label>Your name<input name="name" required autocomplete="name"></label>
                <label>Email<input type="email" name="email" required autocomplete="email"></label>
                <label>Password <em>(8+ characters)</em><input type="password" name="password" minlength="8" required autocomplete="new-password"></label>
                <button class="btn btn-lg btn-block">Create account</button>
            </form>
        </div>
        <div class="auth-aside">
            <h1>Your Gift Boxx account</h1>
            <ul class="ticks">
                <li><?= icon('check') ?> Track every order and delivery</li>
                <li><?= icon('check') ?> Save addresses for faster checkout</li>
                <li><?= icon('check') ?> Keep a wishlist of boxes you love</li>
            </ul>
            <p class="muted small">Shopped with us before our new website? Your account is already here. Just use “Forgot your password?” once to set a new one.</p>
        </div>
    </div>
</section>
