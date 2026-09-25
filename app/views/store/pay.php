<?php /** @var array $order @var ?string $error @var ?array $payu @var ?array $cashfree */ ?>
<section class="section pay-page">
    <div class="wrap narrow center">
        <div class="pay-card">
            <span class="spinner" <?= $error ? 'hidden' : '' ?>></span>
            <h1>Taking you to secure payment…</h1>
            <p class="muted">Order <?= e($order['number']) ?> · <?= money($order['total']) ?></p>
            <?php if ($error): ?>
                <div class="notice warn"><?= e($error) ?></div>
                <p>Please try again in a moment, or <a href="/contact/">contact us</a> and we’ll help you complete the order.</p>
                <a class="btn" href="">Try again</a>
            <?php elseif (!empty($payu)): ?>
                <form id="payu" action="<?= e($payu['action']) ?>" method="post">
                    <?php foreach ($payu['fields'] as $k => $val): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($val) ?>"><?php endforeach; ?>
                    <noscript><button class="btn">Continue to PayU</button></noscript>
                </form>
                <script>setTimeout(function(){document.getElementById('payu').submit()},400)</script>
            <?php elseif (!empty($cashfree)): ?>
                <button class="btn" id="cf-pay" hidden>Continue to payment</button>
                <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
                <script>
                    (function () {
                        var go = function () { Cashfree({mode: <?= json_encode($cashfree['mode']) ?>}).checkout({paymentSessionId: <?= json_encode($cashfree['session']) ?>, redirectTarget: '_self'}); };
                        var b = document.getElementById('cf-pay'); b.onclick = go;
                        try { go(); } catch (e) { b.hidden = false; }
                        setTimeout(function () { b.hidden = false; }, 4000);
                    })();
                </script>
            <?php endif; ?>
            <p class="small muted"><?= icon('shield') ?> Your card and UPI details are handled by the payment provider. We never see or store them.</p>
        </div>
    </div>
</section>
