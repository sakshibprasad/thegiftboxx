<?php /** @var string $group @var array $schema @var array $def @var string $cronUrl @var string $previewUrl */
$tests = ['payments' => [['/settings-test/payu', 'Test PayU'], ['/settings-test/cashfree', 'Test Cashfree']], 'shipping' => [['/settings-test/shiprocket', 'Test Shiprocket']], 'email' => [['/settings-test/email', 'Send test email']]];
$sections = [
    'payments' => ['payu_' => 'PayU', 'cashfree_' => 'Cashfree', 'cod_' => 'Cash on Delivery'],
    'shipping' => ['shipping_' => 'Delivery charges', 'delivery_' => 'Checkout', 'gift_' => 'Checkout', 'shiprocket_' => 'Shiprocket', 'pincode_' => 'Product page'],
    'integrations' => ['ga4_' => 'Google', 'gsc_' => 'Google', 'gtm_' => 'Google', 'gads_' => 'Google', 'gmc_' => 'Google', 'meta_' => 'Meta (Facebook & Instagram)', 'pinterest_' => 'Pinterest', 'bing_' => 'Bing', 'whatsapp_' => 'WhatsApp', 'header_' => 'Custom code', 'footer_' => 'Custom code'],
    'email' => ['smtp_' => 'Outgoing email (SMTP)', 'mail_' => 'Outgoing email (SMTP)', 'abandoned_' => 'Abandoned cart reminders'],
    'website' => ['maintenance_' => 'Maintenance mode', 'checkout_' => 'Holiday mode', 'logo_' => 'Branding', 'og_' => 'Branding', 'seo_' => 'Google (SEO)', 'noindex_' => 'Google (SEO)', 'blog_' => 'Features', 'reviews_' => 'Features', 'wishlist_' => 'Features', 'cookie_' => 'Features'],
    'appearance' => ['theme_' => 'Your shop', 'admin_' => 'This admin'],
];
$sectionOf = function (string $key) use ($sections, $group): string {
    foreach ($sections[$group] ?? [] as $prefix => $label) if (str_starts_with($key, $prefix)) return $label;
    return '';
};
?>
<div class="page-head">
    <div><a class="back" href="/settings"><?= icon('arrow-left') ?> Settings</a><h1><?= e($def['label']) ?></h1></div>
    <div class="head-actions">
        <?php foreach ($tests[$group] ?? [] as [$url, $label]): ?><button class="btn secondary" data-test="<?= e($url) ?>"><?= icon('refresh') ?> <?= e($label) ?></button><?php endforeach; ?>
        <?php if ($group === 'website'): ?><a class="btn secondary" href="<?= e($previewUrl) ?>" target="_blank" rel="noopener"><?= icon('eye') ?> Private preview link</a><?php endif; ?>
    </div>
</div>
<div class="grid <?= $group === 'appearance' ? 'g-main' : '' ?>">
<form method="post" action="/settings/<?= e($group) ?>" data-savebar style="max-width:<?= $group === 'appearance' ? 'none' : '760px' ?>">
    <?= csrf_field() ?>
    <?php $last = null; $open = false;
    foreach ($def['fields'] as $key => $f):
        $sec = $sectionOf($key);
        if ($sec !== $last) { if ($open) echo '</div>'; echo '<p class="group-title">' . e($sec ?: $def['label']) . '</p><div class="group stack-fields-auto">'; $open = true; $last = $sec; }
        $val = setting($key);
        $inline = in_array($f['type'], ['bool', 'color'], true);
    ?>
        <div class="row" <?= !$inline && $f['type'] !== 'select' && $f['type'] !== 'number' ? 'style="flex-direction:column;align-items:stretch"' : '' ?>>
            <span class="row-label"><?= e($f['label']) ?><?php if (!empty($f['help'])): ?><small><?= e($f['help']) ?></small><?php endif; ?></span>
            <?php switch ($f['type']):
                case 'bool': ?><span class="switch"><input type="checkbox" name="<?= e($key) ?>" value="1" <?= (string) $val === '1' ? 'checked' : '' ?>><span></span></span><?php break;
                case 'secret': $hint = secret_hint($key); ?>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap"><input type="password" name="<?= e($key) ?>" autocomplete="new-password" placeholder="<?= $hint ? e($hint) . ' — saved. Type to replace' : 'Paste here' ?>" style="flex:1;max-width:none">
                    <?php if ($hint): ?><span class="badge b-ok"><?= icon('check') ?> Saved securely</span><label class="check small"><input type="checkbox" name="<?= e($key) ?>__clear" value="1"> Remove</label><?php endif; ?></div><?php break;
                case 'textarea': ?><textarea name="<?= e($key) ?>" rows="3"><?= e($val) ?></textarea><?php break;
                case 'code': ?><textarea name="<?= e($key) ?>" rows="5" class="code" style="font-family:ui-monospace,Menlo,monospace;font-size:12.5px" placeholder="<script>…</script>"><?= e($val) ?></textarea><?php break;
                case 'select': ?><select name="<?= e($key) ?>" style="max-width:280px"><?php foreach (field_options($f) as $k => $l): ?><option value="<?= e($k) ?>" <?= (string) $val === (string) $k ? 'selected' : '' ?> <?= str_starts_with($key, 'theme_') && str_contains($key, 'font') ? 'style="font-family:\'' . e($k) . '\'"' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select><?php break;
                case 'color': ?><span class="color-field"><input type="color" name="<?= e($key) ?>" value="<?= e($val) ?>"><code><?= e(strtoupper((string) $val)) ?></code></span><?php break;
                case 'number': ?><input type="number" step="any" min="0" name="<?= e($key) ?>" value="<?= e($val) ?>" style="max-width:160px"><?php break;
                case 'image': ?><?= image_field($key, $val ?: null, 'Upload') ?><?php break;
                default: ?><input name="<?= e($key) ?>" value="<?= e($val) ?>" placeholder="<?= e($f['placeholder'] ?? '') ?>"><?php endswitch; ?>
        </div>
    <?php endforeach; if ($open) echo '</div>'; ?>

    <?php if ($group === 'email'): ?>
        <p class="group-title">Scheduled tasks (cron)</p>
        <div class="group"><div class="row" style="flex-direction:column;align-items:stretch">
            <span class="row-label">Abandoned-cart reminders and payment checks run on a schedule.<small>In Hostinger hPanel → Advanced → Cron Jobs, add a job every 30 minutes with this command:</small></span>
            <pre class="code">curl -s "<?= e($cronUrl) ?>" &gt; /dev/null</pre>
            <span class="small muted">Last run: <?= setting('cron_last_run', '') ? e(time_ago(setting('cron_last_run'))) : 'never' ?></span>
        </div></div>
    <?php endif; ?>
    <?php if ($group === 'payments'): ?>
        <p class="group-foot">Webhook for Cashfree (Developers → Webhooks): <code><?= e(site_url('payment/cashfree/webhook')) ?></code>. PayU needs no webhook. Always place a small test order after switching to live mode.</p>
    <?php endif; ?>
    <?php if ($group === 'appearance'): ?>
        <div style="margin-top:14px;display:flex;gap:8px"><button class="btn">Save</button><button class="btn secondary" name="reset_theme" value="1" data-confirm="Reset colours and fonts to the original Gift Boxx look?">Reset to original</button></div>
    <?php else: ?>
        <div style="margin-top:16px"><button class="btn">Save</button></div>
    <?php endif; ?>
</form>
<?php if ($group === 'appearance'): ?>
    <div class="sticky-col">
        <p class="group-title">Live preview</p>
        <div id="appearance-preview" class="card" style="background:var(--p-bg);color:var(--p-ink);font-family:var(--p-body);padding:0">
            <div style="background:var(--p-dark);color:#fff;padding:14px 18px;display:flex;justify-content:space-between;align-items:center"><strong style="font-family:var(--p-head);font-size:20px;font-weight:500"><?= e(setting('store_name')) ?></strong><span style="font-size:12px;opacity:.8">Shop · Occasions · Contact</span></div>
            <div style="padding:22px 18px">
                <p style="color:var(--p-accent);font-size:11px;letter-spacing:.14em;text-transform:uppercase;font-weight:600;margin:0 0 8px">Premium gift hampers</p>
                <h2 style="font-family:var(--p-head);font-weight:500;font-size:30px;line-height:1.05;margin:0 0 10px">Gifts that feel personal</h2>
                <p style="opacity:.7;font-size:14px">Hand-picked hampers in real wooden boxes.</p>
                <div style="display:flex;gap:10px;margin:16px 0"><span style="background:var(--p-dark);color:#fff;padding:10px 18px;border-radius:999px;font-size:13px">Explore gift boxes</span><span style="border:1px solid rgba(0,0,0,.15);padding:10px 18px;border-radius:999px;font-size:13px">Design your own</span></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <?php foreach ([0, 1] as $i): ?><div style="background:var(--p-surface);border-radius:var(--p-radius);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08)"><img src="<?= e(site_url('assets/img/' . ($i ? 'hero-2.jpg' : 'hero.jpg'))) ?>" alt="" style="aspect-ratio:4/5;object-fit:cover;width:100%"><div style="padding:10px;font-size:13px">Gift box for her<br><strong>₹4,999</strong></div></div><?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
</div>
