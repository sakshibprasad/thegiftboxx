<?php $wa = preg_replace('/\D/', '', (string) setting('whatsapp_number')); ?><!doctype html>
<html lang="en-IN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(setting('maintenance_title')) ?> | <?= e(setting('store_name')) ?></title><meta name="robots" content="noindex">
<link rel="icon" href="/assets/img/favicon-32.png">
<link rel="stylesheet" href="<?= e(google_fonts_url((string) setting('theme_heading_font'), (string) setting('theme_body_font'))) ?>">
<link rel="stylesheet" href="<?= asset('css/store.css') ?>">
<style>:root{--bg:<?= e(setting('theme_bg')) ?>;--ink:<?= e(setting('theme_text')) ?>;--accent:<?= e(setting('theme_accent')) ?>;--dark:<?= e(setting('theme_dark')) ?>;--font-head:'<?= e(setting('theme_heading_font')) ?>',serif;--font-body:'<?= e(setting('theme_body_font')) ?>',system-ui,sans-serif}</style>
</head><body>
<main class="maint"><div class="maint-card">
    <img src="<?= e(store_logo()) ?>" alt="<?= e(setting('store_name')) ?>">
    <h1><?= e(setting('maintenance_title')) ?></h1>
    <p class="lead"><?= nl2br(e(setting('maintenance_text'))) ?></p>
    <div class="row-links">
        <?php if ($wa): ?><a class="btn" href="https://wa.me/<?= e($wa) ?>"><?= icon('whatsapp') ?> WhatsApp us</a><?php endif; ?>
        <a class="btn btn-ghost" href="tel:<?= e(preg_replace('/[^\d+]/', '', (string) setting('store_phone'))) ?>"><?= icon('phone') ?> <?= e(setting('store_phone')) ?></a>
    </div>
</div></main>
</body></html>
