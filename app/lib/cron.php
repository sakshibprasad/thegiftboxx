<?php
declare(strict_types=1);

/** Secret for the web-cron URL (derived from app_key, shown in Admin → Settings). */
function cron_token(): string
{
    return substr(hash_hmac('sha256', 'cron', (string) config('app_key')), 0, 32);
}

/** Scheduled jobs. Run every 15–30 minutes from Hostinger's Cron Jobs panel. */
function run_cron(): string
{
    $out = [];
    $out[] = 'Abandoned cart reminders sent: ' . cron_abandoned_carts();
    $out[] = 'Pending orders synced: ' . cron_sync_pending();
    setting_set('cron_last_run', now());
    return implode("\n", $out) . "\n";
}

function cron_abandoned_carts(): int
{
    if (!setting_on('abandoned_enabled')) {
        return 0;
    }
    $delay = max(1, (int) setting('abandoned_delay_hours'));
    // Reminder 1 after $delay hours, reminder 2 after 24 hours. Never older than 7 days.
    $rows = all("SELECT * FROM abandoned_carts WHERE status = 'open' AND reminders_sent < 2 AND updated_at > ? ORDER BY id LIMIT 30",
        [date('Y-m-d H:i:s', time() - 7 * 86400)]);
    $sent = 0;
    foreach ($rows as $ac) {
        $wait = $ac['reminders_sent'] == 0 ? $delay * 3600 : 24 * 3600;
        if (strtotime($ac['updated_at']) > time() - $wait) {
            continue;
        }
        if (one("SELECT id FROM orders WHERE email = ? AND created_at > ? AND status NOT IN ('pending_payment','failed')", [$ac['email'], $ac['created_at']])) {
            update('abandoned_carts', ['status' => 'recovered'], 'id = ?', [$ac['id']]);
            continue;
        }
        $lines = cart_lines(json_arr($ac['cart_json']));
        if (!$lines) {
            update('abandoned_carts', ['status' => 'expired'], 'id = ?', [$ac['id']]);
            continue;
        }
        $html = render('emails/abandoned', ['ac' => $ac, 'lines' => $lines, 'second' => $ac['reminders_sent'] > 0,
            'coupon' => setting('abandoned_coupon'), 'link' => site_url('restore-cart/' . $ac['token'] . '/')]);
        $subject = $ac['reminders_sent'] > 0 ? 'Still thinking it over?' : 'You left something lovely in your cart';
        if (send_mail($ac['email'], $subject, $html)) {
            update('abandoned_carts', ['reminders_sent' => $ac['reminders_sent'] + 1, 'status' => $ac['reminders_sent'] >= 1 ? 'reminded' : 'open',
                'updated_at' => $ac['updated_at']], 'id = ?', [$ac['id']]);
            $sent++;
        }
    }
    return $sent;
}

/** Catch Cashfree payments whose customer closed the browser before returning. */
function cron_sync_pending(): int
{
    $n = 0;
    $rows = all("SELECT * FROM orders WHERE status = 'pending_payment' AND payment_method = 'cashfree' AND gateway_order_id IS NOT NULL AND created_at > ?",
        [date('Y-m-d H:i:s', time() - 2 * 86400)]);
    foreach ($rows as $o) {
        try {
            $after = cashfree_sync($o);
            $n += $after['status'] !== 'pending_payment' ? 1 : 0;
        } catch (Throwable $e) {
            app_log('cron', 'cashfree sync failed', ['order' => $o['number'], 'error' => $e->getMessage()]);
        }
    }
    return $n;
}
