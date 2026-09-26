<?php
declare(strict_types=1);

/**
 * Activity history with undo.
 * Every admin change stores a snapshot of what the item looked like *before*
 * the change, so it can be reverted — one change at a time, or everything
 * back to a chosen point.
 */
function history_tables(): array
{
    return [
        'page' => 'pages', 'post' => 'posts', 'category' => 'categories', 'box_type' => 'box_types',
        'coupon' => 'coupons', 'redirect' => 'redirects', 'brand' => 'brands', 'review' => 'reviews',
    ];
}

function history_labels(): array
{
    return ['product' => 'Product', 'page' => 'Page', 'post' => 'Blog post', 'category' => 'Category', 'box_type' => 'Box type',
        'coupon' => 'Coupon', 'redirect' => 'Redirect', 'brand' => 'Brand', 'review' => 'Review', 'settings' => 'Settings',
        'order' => 'Order', 'team' => 'Team', 'import' => 'Import', 'featured' => 'Homepage'];
}

function history_snapshot(string $type, $id): ?array
{
    if ($id === null || $id === '') {
        return null;
    }
    if ($type === 'product') {
        $p = one('SELECT * FROM products WHERE id = ?', [(int) $id]);
        if (!$p) {
            return null;
        }
        return [
            'product' => $p,
            'variations' => all('SELECT * FROM variations WHERE product_id = ?', [(int) $id]),
            'images' => all('SELECT * FROM product_images WHERE product_id = ?', [(int) $id]),
            'categories' => all('SELECT * FROM product_categories WHERE product_id = ?', [(int) $id]),
        ];
    }
    if ($type === 'settings') {
        $out = [];
        foreach (explode(',', (string) $id) as $key) {
            $out[$key] = one('SELECT * FROM settings WHERE skey = ?', [$key]);
        }
        return ['settings' => $out];
    }
    if ($type === 'featured') {
        return ['featured' => array_column(all('SELECT id, featured FROM products'), 'featured', 'id')];
    }
    $table = history_tables()[$type] ?? null;
    if (!$table) {
        return null;
    }
    $row = one("SELECT * FROM {$table} WHERE id = ?", [(int) $id]);
    return $row ? ['row' => $row] : null;
}

function history_insert_row(string $table, array $row): void
{
    $cols = array_keys($row);
    $existing = [];
    foreach ($cols as $c) {
        if (column_exists($table, $c)) {
            $existing[$c] = $row[$c];
        }
    }
    insert($table, $existing);
}

function history_restore(string $type, $id, ?array $snap): void
{
    transaction(function () use ($type, $id, $snap) {
        if ($type === 'product') {
            $pid = (int) $id;
            q('DELETE FROM product_categories WHERE product_id = ?', [$pid]);
            q('DELETE FROM product_images WHERE product_id = ?', [$pid]);
            q('DELETE FROM variations WHERE product_id = ?', [$pid]);
            q('DELETE FROM products WHERE id = ?', [$pid]);
            if ($snap) {
                history_insert_row('products', $snap['product']);
                foreach ($snap['variations'] as $r) history_insert_row('variations', $r);
                foreach ($snap['images'] as $r) history_insert_row('product_images', $r);
                foreach ($snap['categories'] as $r) history_insert_row('product_categories', $r);
            }
            return;
        }
        if ($type === 'settings') {
            foreach ($snap['settings'] ?? [] as $key => $row) {
                q('DELETE FROM settings WHERE skey = ?', [$key]);
                if ($row) {
                    insert('settings', $row);
                }
            }
            unset($GLOBALS['__settings']);
            return;
        }
        if ($type === 'featured') {
            foreach ($snap['featured'] ?? [] as $pid => $f) {
                update('products', ['featured' => (int) $f], 'id = ?', [(int) $pid]);
            }
            return;
        }
        $table = history_tables()[$type] ?? null;
        if (!$table) {
            throw new RuntimeException('This change can’t be reverted.');
        }
        q("DELETE FROM {$table} WHERE id = ?", [(int) $id]);
        if ($snap) {
            history_insert_row($table, $snap['row']);
        }
    });
    if ($type === 'product' && $snap) {
        product_refresh_cache((int) $id);
    }
}

/** Record a change. $snapshot = state *before* the change (null = it didn't exist). */
function history_log(string $action, string $type, $id, string $summary, ?array $snapshot = null, bool $revertible = true): int
{
    $u = function_exists('admin_user') ? admin_user() : null;
    try {
        $logId = (int) insert('activity_log', [
        'user_id' => $u['id'] ?? null,
        'user_name' => $u['name'] ?? '',
        'action' => $action,
        'entity_type' => $type,
        'entity_id' => $id === null ? null : (string) $id,
        'summary' => mb_substr($summary, 0, 250),
        'snapshot' => $revertible ? json_encode(['s' => $snapshot], JSON_UNESCAPED_UNICODE) : null,
        'created_at' => now(),
        ]);
    } catch (Throwable $e) {
        // History must never stop a save (e.g. while a database upgrade is pending).
        app_log('history', 'could not log', ['error' => $e->getMessage()]);
        return 0;
    }
    if ($revertible) {
        $_SESSION['_undo'] = $logId;
    }
    return $logId;
}

/**
 * Snapshot an item, run the change, then log it.
 * $fn returns the item id (important when creating something new).
 */
function history_track(string $type, $id, string $action, string $summary, callable $fn)
{
    $before = history_snapshot($type, $id);
    $result = $fn();
    $entityId = $id ?: $result;
    history_log($action, $type, $entityId, $summary, $before);
    return $result;
}

function history_revert(int $logId): string
{
    $entry = one('SELECT * FROM activity_log WHERE id = ?', [$logId]);
    if (!$entry || $entry['snapshot'] === null) {
        throw new RuntimeException('This change can’t be undone.');
    }
    if ($entry['reverted_at']) {
        throw new RuntimeException('This change was already undone.');
    }
    $snap = json_decode((string) $entry['snapshot'], true)['s'] ?? null;
    $current = history_snapshot($entry['entity_type'], $entry['entity_id']);
    history_restore($entry['entity_type'], $entry['entity_id'], $snap);
    update('activity_log', ['reverted_at' => now()], 'id = ?', [$logId]);
    history_log('revert', $entry['entity_type'], $entry['entity_id'], 'Undid: ' . $entry['summary'], $current);
    unset($_SESSION['_undo']);
    return $entry['summary'];
}

/** Undo every change made after $logId (newest first). */
function history_restore_to(int $logId): int
{
    $rows = all('SELECT id FROM activity_log WHERE id > ? AND reverted_at IS NULL AND snapshot IS NOT NULL AND action <> ? ORDER BY id DESC', [$logId, 'revert']);
    $n = 0;
    foreach ($rows as $r) {
        try {
            history_revert((int) $r['id']);
            $n++;
        } catch (Throwable $e) {
            app_log('history', 'restore step failed', ['id' => $r['id'], 'error' => $e->getMessage()]);
        }
    }
    return $n;
}
