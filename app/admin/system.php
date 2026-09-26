<?php
declare(strict_types=1);

/* ---------- Activity history ---------- */

function admin_activity(): void
{
    require_admin(true);
    $type = (string) input('type');
    $page = max(1, (int) input('page', 1));
    $per = 50;
    $where = $type !== '' && isset(history_labels()[$type]) ? 'WHERE entity_type = ?' : '';
    $params = $where ? [$type] : [];
    $total = (int) val("SELECT COUNT(*) FROM activity_log {$where}", $params);
    $rows = all("SELECT id, user_name, action, entity_type, entity_id, summary, reverted_at, created_at, snapshot IS NOT NULL AS revertible
        FROM activity_log {$where} ORDER BY id DESC LIMIT {$per} OFFSET " . (($page - 1) * $per), $params);
    admin_view('activity', ['rows' => $rows, 'type' => $type, 'pg' => admin_paginate($total, $per, $page)], 'History', 'activity');
}

function admin_activity_revert(string $id): void
{
    require_admin(true);
    try {
        $summary = history_revert((int) $id);
        flash('success', 'Undone: ' . $summary);
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    back('/activity');
}

function admin_activity_restore(string $id): void
{
    require_admin(true);
    $n = history_restore_to((int) $id);
    flash('success', $n ? "Went back in time: {$n} later change" . ($n === 1 ? ' was' : 's were') . ' undone.' : 'Nothing to undo after that point.');
    redirect('/activity');
}

/* ---------- One-click updates ---------- */

function app_version(): string
{
    return trim((string) @file_get_contents(APP_DIR . '/VERSION')) ?: 'dev';
}

function storefront_root(): string
{
    return dirname(uploads_dir());
}

function admin_updates(): void
{
    require_admin(true);
    $backups = glob(APP_ROOT . '/storage/backups/*.zip') ?: [];
    rsort($backups);
    admin_view('updates', [
        'version' => app_version(),
        'zipOk' => class_exists('ZipArchive'),
        'backups' => array_slice($backups, 0, 5),
        'paths' => ['app' => APP_DIR, 'admin' => $GLOBALS['__docroot'], 'public' => storefront_root()],
    ], 'Updates', 'updates');
}

function admin_updates_install(): void
{
    require_admin(true);
    @set_time_limit(300);
    try {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('The “zip” PHP extension is off. Turn it on in hPanel → Advanced → PHP Configuration → PHP extensions, or update manually (see the steps below).');
        }
        $f = $_FILES['package'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Choose the update file (thegiftboxx-update.zip) first.');
        }
        $zip = new ZipArchive();
        if ($zip->open($f['tmp_name']) !== true || $zip->locateName('app/bootstrap.php') === false || $zip->locateName('app/VERSION') === false) {
            throw new RuntimeException('This isn’t a Gift Boxx update file. Please upload thegiftboxx-update.zip.');
        }
        $newVersion = trim((string) $zip->getFromName('app/VERSION'));
        $backup = update_backup();
        $targets = [
            'app/' => APP_DIR,
            'cron/' => APP_ROOT . '/cron',
            'admin/' => $GLOBALS['__docroot'],
            'public/' => storefront_root(),
        ];
        $written = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (str_ends_with($name, '/') || str_contains($name, '..') || $name === 'app/config.php'
                || str_starts_with($name, 'public/uploads/') || $name === 'admin/app-path.php') {
                continue;
            }
            foreach ($targets as $prefix => $dir) {
                if (str_starts_with($name, $prefix)) {
                    $dest = rtrim($dir, '/') . '/' . substr($name, strlen($prefix));
                    @mkdir(dirname($dest), 0775, true);
                    if (file_put_contents($dest, $zip->getFromIndex($i)) === false) {
                        throw new RuntimeException('Could not write ' . $dest . '. Check folder permissions.');
                    }
                    $written++;
                    break;
                }
            }
        }
        $zip->close();
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        history_log('update', 'settings', 'app_version', 'Installed update ' . $newVersion . ' (' . $written . ' files)', null, false);
        flash('success', "Updated to version {$newVersion}. Your settings, keys, products and orders were kept. Backup saved: " . basename($backup));
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('/updates');
}

/** Zip the current code (not uploads, not config) before updating. */
function update_backup(): string
{
    $dir = APP_ROOT . '/storage/backups';
    @mkdir($dir, 0775, true);
    $file = $dir . '/before-update-' . date('Y-m-d-His') . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($file, ZipArchive::CREATE) !== true) {
        return '(backup skipped)';
    }
    foreach (['app' => APP_DIR, 'admin' => $GLOBALS['__docroot'], 'public' => storefront_root()] as $prefix => $root) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $path => $info) {
            $rel = substr((string) $path, strlen(rtrim($root, '/')) + 1);
            if ($info->isDir() || str_starts_with($rel, 'uploads/') || $rel === 'config.php'
                || ($prefix === 'public' && (str_starts_with($rel, 'admin/') || str_starts_with($rel, 'new/') || str_starts_with($rel, '_old')))) {
                continue;
            }
            $zip->addFile((string) $path, $prefix . '/' . $rel);
        }
    }
    $zip->close();
    // Keep the five most recent backups.
    $all = glob($dir . '/before-update-*.zip') ?: [];
    rsort($all);
    foreach (array_slice($all, 5) as $old) {
        @unlink($old);
    }
    return $file;
}
