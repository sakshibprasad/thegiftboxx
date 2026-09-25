<?php
declare(strict_types=1);

function orders_filter(): array
{
    $status = (string) input('status');
    $term = trim((string) input('q'));
    $from = (string) input('from');
    $to = (string) input('to');
    $where = ['1=1'];
    $params = [];
    if ($status === 'to_ship') {
        $where[] = "status IN ('processing','packed')";
    } elseif ($status !== '' && isset(order_statuses()[$status])) {
        $where[] = 'status = ?';
        $params[] = $status;
    } else {
        $where[] = "status <> 'pending_payment' OR created_at > ?";
        $params[] = date('Y-m-d H:i:s', time() - 86400); // hide stale unpaid attempts from "All"
    }
    if ($term !== '') {
        $where[] = '(number LIKE ? OR email LIKE ? OR phone LIKE ? OR billing_json LIKE ?)';
        array_push($params, "%{$term}%", "%{$term}%", "%{$term}%", "%{$term}%");
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
        $where[] = 'created_at >= ?';
        $params[] = $from . ' 00:00:00';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        $where[] = 'created_at <= ?';
        $params[] = $to . ' 23:59:59';
    }
    return ['(' . implode(') AND (', $where) . ')', $params, compact('status', 'term', 'from', 'to')];
}

function admin_orders(): void
{
    [$w, $params, $filters] = orders_filter();
    $page = max(1, (int) input('page', 1));
    $per = 40;
    $total = (int) val("SELECT COUNT(*) FROM orders WHERE {$w}", $params);
    $rows = all("SELECT * FROM orders WHERE {$w} ORDER BY created_at DESC LIMIT {$per} OFFSET " . (($page - 1) * $per), $params);
    $counts = [];
    foreach (all('SELECT status, COUNT(*) AS n FROM orders GROUP BY status') as $c) {
        $counts[$c['status']] = (int) $c['n'];
    }
    admin_view('orders', ['rows' => $rows, 'filters' => $filters, 'counts' => $counts, 'pg' => admin_paginate($total, $per, $page)], 'Orders', 'orders');
}

function admin_order(string $id): void
{
    $o = order_find((int) $id);
    if (!$o) {
        field_error_redirect('Order not found.', '/orders');
    }
    $items = order_items((int) $o['id']);
    foreach ($items as &$it) {
        $it['image'] = $it['variation_id'] ? val('SELECT path FROM product_images WHERE variation_id = ? ORDER BY sort_order LIMIT 1', [$it['variation_id']]) : null;
        $it['image'] = $it['image'] ?: ($it['product_id'] ? val('SELECT path FROM product_images WHERE product_id = ? AND variation_id IS NULL ORDER BY sort_order LIMIT 1', [$it['product_id']]) : null);
    }
    unset($it);
    admin_view('order', [
        'o' => $o, 'items' => $items,
        'notes' => all('SELECT * FROM order_notes WHERE order_id = ? ORDER BY created_at DESC, id DESC', [$o['id']]),
        'customerOrders' => (int) val("SELECT COUNT(*) FROM orders WHERE email = ? AND status NOT IN ('pending_payment','failed')", [$o['email']]),
        'user' => $o['user_id'] ? one('SELECT * FROM users WHERE id = ?', [$o['user_id']]) : null,
    ], 'Order ' . $o['number'], 'orders');
}

function admin_order_status(string $id): void
{
    $o = order_find((int) $id);
    if ($o) {
        order_set_status($o, (string) input('status'), (bool) input('notify'));
        flash('success', 'Order marked as ' . strtolower(order_status_label((string) input('status'))) . (input('notify') ? ' and the customer was emailed.' : '.'));
    }
    redirect('/orders/' . (int) $id);
}

function admin_order_note(string $id): void
{
    $note = trim((string) input('note'));
    if ($note !== '') {
        order_note((int) $id, '✎ ' . (admin_user()['name'] ?? 'Admin') . ': ' . $note);
    }
    redirect('/orders/' . (int) $id);
}

function admin_order_tracking(string $id): void
{
    $o = order_find((int) $id);
    if (!$o) {
        redirect('/orders');
    }
    $awb = trim((string) input('awb'));
    $courier = trim((string) input('courier'));
    $url = trim((string) input('tracking_url')) ?: ($awb ? 'https://shiprocket.co/tracking/' . rawurlencode($awb) : null);
    update('orders', ['awb' => $awb ?: null, 'courier' => $courier ?: null, 'tracking_url' => $url, 'updated_at' => now()], 'id = ?', [$o['id']]);
    order_note((int) $o['id'], "Tracking updated: {$courier} {$awb}");
    if (input('mark_shipped')) {
        order_set_status(order_find((int) $o['id']), 'shipped', true);
    }
    flash('success', 'Tracking saved.');
    redirect('/orders/' . $o['id']);
}

function admin_order_shiprocket(string $id): void
{
    $o = order_find((int) $id);
    try {
        if (!setting_on('shiprocket_enabled')) {
            throw new RuntimeException('Turn on Shiprocket in Settings → Shipping first.');
        }
        if ($o['shiprocket_order_id'] && !$o['awb']) {
            shiprocket_assign_awb($o);
            flash('success', 'Courier assigned.');
        } else {
            shiprocket_push_order($o);
            flash('success', 'Sent to Shiprocket.');
        }
        if (input('mark_packed') && $o['status'] === 'processing') {
            order_set_status(order_find((int) $o['id']), 'packed', false);
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('/orders/' . (int) $id);
}

function admin_order_resend(string $id): void
{
    $o = order_find((int) $id);
    if ($o) {
        send_mail($o['email'], 'Your order ' . $o['number'], render('emails/order_customer', ['order' => $o, 'items' => order_items((int) $o['id'])]));
        order_note((int) $o['id'], 'Confirmation email re-sent to customer.');
        flash('success', 'Confirmation email sent to ' . $o['email'] . '.');
    }
    redirect('/orders/' . (int) $id);
}

function admin_order_print(string $id): void
{
    $o = order_find((int) $id);
    if (!$o) {
        redirect('/orders');
    }
    echo render('admin/order-print', ['o' => $o, 'items' => order_items((int) $o['id']), 'slip' => input('type') === 'slip']);
}

function admin_orders_export(): void
{
    [$w, $params] = orders_filter();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="orders-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Order', 'Date', 'Status', 'Payment', 'Customer', 'Email', 'Phone', 'City', 'Pincode', 'Items', 'Subtotal', 'Discount', 'Shipping', 'Total', 'Gift message', 'Delivery date', 'AWB'], ',', '"', '\\');
    foreach (all("SELECT * FROM orders WHERE {$w} ORDER BY created_at DESC", $params) as $o) {
        $b = json_arr($o['shipping_json']);
        $items = implode('; ', array_map(fn($i) => $i['name'] . ($i['variation_label'] ? " ({$i['variation_label']})" : '') . ' ×' . $i['qty'], order_items((int) $o['id'])));
        fputcsv($out, [$o['number'], $o['created_at'], order_status_label($o['status']), payment_method_label($o['payment_method']),
            $b['name'] ?? '', $o['email'], $o['phone'], $b['city'] ?? '', $b['pincode'] ?? '', $items,
            $o['subtotal'], $o['discount'], $o['shipping'], $o['total'], $o['gift_message'], $o['delivery_date'], $o['awb']], ',', '"', '\\');
    }
    fclose($out);
}

/* ---------- Customers ---------- */

function admin_customers(): void
{
    $term = trim((string) input('q'));
    $page = max(1, (int) input('page', 1));
    $per = 40;
    // Customers = registered accounts + guest buyers (grouped by email).
    $where = $term !== '' ? 'WHERE email LIKE ? OR name LIKE ? OR phone LIKE ?' : '';
    $params = $term !== '' ? ["%{$term}%", "%{$term}%", "%{$term}%"] : [];
    $sql = "SELECT email, MAX(name) AS name, MAX(phone) AS phone, MAX(user_id) AS user_id, SUM(orders) AS orders, SUM(spent) AS spent, MAX(last_order) AS last_order, MIN(since) AS since FROM (
            SELECT o.email, '' AS name, o.phone, o.user_id, COUNT(*) AS orders, SUM(o.total) AS spent, MAX(o.created_at) AS last_order, MIN(o.created_at) AS since
              FROM orders o WHERE o.status NOT IN ('pending_payment','failed') GROUP BY o.email, o.phone, o.user_id
            UNION ALL
            SELECT u.email, u.name, u.phone, u.id, 0, 0, NULL, u.created_at FROM users u WHERE u.role = 'customer'
        ) x {$where} GROUP BY email ORDER BY last_order DESC, since DESC";
    $allRows = all($sql, $params);
    $total = count($allRows);
    $rows = array_slice($allRows, ($page - 1) * $per, $per);
    foreach ($rows as &$r) {
        if (!$r['name']) {
            $o = one('SELECT billing_json FROM orders WHERE email = ? ORDER BY id DESC LIMIT 1', [$r['email']]);
            $r['name'] = json_arr($o['billing_json'] ?? null)['name'] ?? '';
        }
    }
    unset($r);
    admin_view('customers', ['rows' => $rows, 'term' => $term, 'pg' => admin_paginate($total, $per, $page)], 'Customers', 'customers');
}

function admin_customer(string $id): void
{
    $c = one('SELECT * FROM users WHERE id = ?', [(int) $id]);
    if (!$c) {
        field_error_redirect('Customer not found.', '/customers');
    }
    $orders = all('SELECT * FROM orders WHERE user_id = ? OR email = ? ORDER BY created_at DESC', [$c['id'], $c['email']]);
    admin_view('customer', ['c' => $c, 'orders' => $orders, 'address' => json_arr($c['address_json']),
        'wish' => products_query(['ids' => array_column(all('SELECT product_id FROM wishlist WHERE user_id = ?', [$c['id']]), 'product_id'), 'include_drafts' => true])],
        $c['name'] ?: $c['email'], 'customers');
}

function admin_abandoned(): void
{
    $rows = all('SELECT * FROM abandoned_carts ORDER BY updated_at DESC LIMIT 200');
    $stats = [
        'open' => (int) val("SELECT COUNT(*) FROM abandoned_carts WHERE status IN ('open','reminded')"),
        'recovered' => (int) val("SELECT COUNT(*) FROM abandoned_carts WHERE status = 'recovered'"),
        'value' => (float) val("SELECT COALESCE(SUM(total),0) FROM abandoned_carts WHERE status IN ('open','reminded')"),
    ];
    admin_view('abandoned', compact('rows', 'stats'), 'Abandoned carts', 'abandoned');
}

/* ---------- Coupons ---------- */

function admin_coupons(): void
{
    $edit = ($id = (int) input('edit')) ? one('SELECT * FROM coupons WHERE id = ?', [$id]) : null;
    admin_view('coupons', ['rows' => all('SELECT * FROM coupons ORDER BY created_at DESC'), 'edit' => $edit], 'Coupons', 'coupons');
}

function admin_coupon_save(): void
{
    $id = (int) input('id') ?: null;
    $code = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', (string) input('code')));
    if ($code === '') {
        field_error_redirect('Enter a coupon code (letters and numbers).', '/coupons');
    }
    $dupe = one('SELECT id FROM coupons WHERE code = ?', [$code]);
    if ($dupe && (int) $dupe['id'] !== $id) {
        field_error_redirect('A coupon with that code already exists.', '/coupons');
    }
    $data = [
        'code' => $code,
        'type' => input('type') === 'fixed' ? 'fixed' : 'percent',
        'amount' => max(0, (float) input('amount')),
        'min_subtotal' => max(0, (float) input('min_subtotal')),
        'max_uses' => input('max_uses') !== '' ? (int) input('max_uses') : null,
        'expires_at' => input('expires_at') ? date('Y-m-d 23:59:59', strtotime((string) input('expires_at'))) : null,
        'active' => input('active') ? 1 : 0,
    ];
    $id ? update('coupons', $data, 'id = ?', [$id]) : insert('coupons', $data + ['created_at' => now()]);
    flash('success', "Coupon {$code} saved.");
    redirect('/coupons');
}

function admin_coupon_delete(string $id): void
{
    q('DELETE FROM coupons WHERE id = ?', [(int) $id]);
    redirect('/coupons');
}
