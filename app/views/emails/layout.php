<?php /** @var string $title @var string $body @var ?array $button [label, url] */ ?>
<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title><?= e($title) ?></title></head>
<body style="margin:0;padding:0;background:#F4EFE9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1D1714">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F4EFE9;padding:32px 12px">
<tr><td align="center">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:18px;overflow:hidden">
    <tr><td style="background:<?= e(setting('theme_dark')) ?>;padding:28px 32px;text-align:center">
      <img src="<?= e(site_url('assets/img/logo-full.png')) ?>" alt="<?= e(setting('store_name')) ?>" width="180" style="display:inline-block;max-width:180px;height:auto;filter:brightness(0) invert(1)">
    </td></tr>
    <tr><td style="padding:32px">
      <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-weight:500;font-size:26px;line-height:1.25;color:#1D1714"><?= e($title) ?></h1>
      <div style="font-size:15px;line-height:1.6;color:#3B322C"><?= $body ?></div>
      <?php if (!empty($button)): ?>
        <p style="margin:28px 0 8px"><a href="<?= e($button[1]) ?>" style="display:inline-block;background:<?= e(setting('theme_dark')) ?>;color:#ffffff;text-decoration:none;padding:14px 26px;border-radius:999px;font-weight:600;font-size:15px"><?= e($button[0]) ?></a></p>
      <?php endif; ?>
    </td></tr>
    <tr><td style="padding:20px 32px 28px;border-top:1px solid #EEE6DD;font-size:12px;line-height:1.6;color:#8A7B6E;text-align:center">
      <?= e(setting('store_name')) ?> · <?= e(str_replace("\n", ', ', (string) setting('store_address'))) ?><br>
      <a href="<?= e(site_url()) ?>" style="color:#8A7B6E"><?= e(parse_url(site_url(), PHP_URL_HOST)) ?></a> · <?= e(setting('store_phone')) ?>
    </td></tr>
  </table>
</td></tr></table>
</body></html>
