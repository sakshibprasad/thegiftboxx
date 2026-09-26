<?php /** Minimal <head> for screens without the sidebar (login, installer). @var string $title */ ?><!doctype html>
<html lang="en" data-theme="<?= e(app_installed() ? setting('admin_theme') : 'auto') ?>">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title><meta name="robots" content="noindex, nofollow">
<meta name="apple-mobile-web-app-capable" content="yes"><link rel="manifest" href="/manifest.webmanifest">
<link rel="stylesheet" href="<?= asset('admin.css') ?>">
<?php if (app_installed()): ?><style>:root{--accent:<?= e(setting('admin_accent')) ?>;--toggle:<?= e(setting('admin_toggle')) ?>}</style><?php endif; ?>
</head>
