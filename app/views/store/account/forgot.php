<section class="section auth">
    <div class="wrap narrow">
        <form class="auth-card" method="post" action="/my-account/forgot-password/">
            <h1>Reset your password</h1>
            <p class="muted">Enter the email you use with us and we’ll send you a link to set a new password.</p>
            <?= csrf_field() ?>
            <label>Email<input type="email" name="email" required autocomplete="email"></label>
            <button class="btn btn-lg btn-block">Send reset link</button>
            <a href="/my-account/" class="small">Back to log in</a>
        </form>
    </div>
</section>
