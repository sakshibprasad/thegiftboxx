<?php /** @var string $php */ ?>
<?= render('admin/_head', ['title' => 'Almost done']) ?>
<body><main class="auth-page"><div class="auth-card wide">
    <h1>Almost done</h1>
    <p>Your database is ready, but the server didn’t allow saving the settings file. In Hostinger’s File Manager, create the file <code>app/config.php</code> with exactly this content, then reload this page:</p>
    <pre class="code"><?= e($php) ?></pre>
    <a class="btn" href="/">I’ve saved it — continue</a>
</div></main></body></html>
