<?php
declare(strict_types=1);

function order_statuses(): array
{
    return [
        'pending_payment' => 'Awaiting payment',
        'processing' => 'Processing',
        'packed' => 'Packed',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'completed' => 'Completed',
        'on_hold' => 'On hold',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
        'failed' => 'Payment failed',
    ];
}

function order_status_label(string $s): string
{
    return order_statuses()[$s] ?? ucfirst(str_replace('_', ' ', $s));
}

function payment_method_label(string $m): string
{
    return ['payu' => 'PayU', 'cashfree' => 'Cashfree', 'cod' => 'Cash on Delivery', 'woocommerce' => 'Imported'][$m] ?? $m;
}

function order_find(int $id): ?array
{
    return one('SELECT * FROM orders WHERE id = ?', [$id]);
}

function order_items(int $orderId): array
{
    return all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$orderId]);
}

function order_note(int $orderId, string $note): void
{
    insert('order_notes', ['order_id' => $orderId, 'note' => $note, 'created_at' => now()]);
}

/** Create an order from priced cart lines. Returns the new order row. */
function order_create(array $lines, array $totals, array $form, string $paymentMethod): array
{
    return transaction(function () use ($lines, $totals, $form, $paymentMethod) {
        $address = [
            'name' => $form['name'], 'phone' => $form['phone'], 'email' => $form['email'],
            'address1' => $form['address1'], 'address2' => $form['address2'] ?? '',
            'city' => $form['city'], 'state' => $form['state'], 'pincode' => $form['pincode'], 'country' => 'India',
        ];
        $shipping = $address;
        if (!empty($form['ship_different'])) {
            $shipping = array_merge($address, [
                'name' => $form['ship_name'], 'phone' => $form['ship_phone'],
                'address1' => $form['ship_address1'], 'address2' => $form['ship_address2'] ?? '',
                'city' => $form['ship_city'], 'state' => $form['ship_state'], 'pincode' => $form['ship_pincode'],
            ]);
        }
        $id = insert('orders', [
            'number' => 'TMP-' . random_token(6),
            'access_key' => random_token(16),
            'user_id' => customer()['id'] ?? null,
            'email' => strtolower($form['email']),
            'phone' => $form['phone'],
            'status' => 'pending_payment',
            'payment_method' => $paymentMethod,
            'payment_status' => 'unpaid',
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'shipping' => $totals['shipping'],
            'fee' => $totals['fee'],
            'total' => $totals['total'],
            'coupon_code' => $totals['coupon'],
            'billing_json' => json_encode($address, JSON_UNESCAPED_UNICODE),
            'shipping_json' => json_encode($shipping, JSON_UNESCAPED_UNICODE),
            'gift_message' => $form['gift_message'] ?? null,
            'delivery_date' => $form['delivery_date'] ?? null,
            'customer_note' => $form['customer_note'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $number = (setting('order_prefix') ?: 'TGB') . '-' . (1000 + $id);
        update('orders', ['number' => $number], 'id = ?', [$id]);
        foreach ($lines as $l) {
            insert('order_items', [
                'order_id' => $id,
                'product_id' => $l['product']['id'],
                'variation_id' => $l['variation']['id'] ?? null,
                'name' => $l['name'],
                'variation_label' => $l['variation_label'],
                'sku' => $l['sku'],
                'qty' => $l['qty'],
                'price' => $l['price'],
                'total' => $l['total'],
            ]);
        }
        order_note($id, 'Order placed. Payment method: ' . payment_method_label($paymentMethod) . '.');
        return order_find($id);
    });
}

/** Called once a payment is confirmed (or a COD order is accepted). Safe to call twice. */
function order_confirm(array $order, string $paymentStatus, ?string $paymentRef = null): array
{
    $fresh = order_find((int) $order['id']);
    if (!$fresh) {
        return $order;
    }
    $alreadyConfirmed = $fresh['status'] !== 'pending_payment' && $fresh['status'] !== 'failed';
    if ($alreadyConfirmed) {
        return $fresh;
    }
    update('orders', [
        'status' => 'processing',
        'payment_status' => $paymentStatus,
        'payment_ref' => $paymentRef ?? $fresh['payment_ref'],
        'updated_at' => now(),
    ], 'id = ?', [$fresh['id']]);
    order_note((int) $fresh['id'], $paymentStatus === 'paid'
        ? 'Payment received via ' . payment_method_label($fresh['payment_method']) . ($paymentRef ? " (ref {$paymentRef})" : '') . '.'
        : 'Cash on Delivery order accepted.');
    order_reduce_stock($fresh);
    if ($fresh['coupon_code']) {
        q('UPDATE coupons SET used = used + 1 WHERE code = ?', [$fresh['coupon_code']]);
    }
    q("UPDATE abandoned_carts SET status = 'recovered', updated_at = ? WHERE email = ? AND status <> 'recovered'", [now(), $fresh['email']]);
    $order = order_find((int) $fresh['id']);
    order_send_emails($order);
    if ($paymentStatus === 'paid') {
        meta_capi_purchase($order);
    }
    if (setting_on('shiprocket_enabled') && setting_on('shiprocket_auto')) {
        try {
            shiprocket_push_order($order);
        } catch (Throwable $e) {
            order_note((int) $order['id'], 'Automatic Shiprocket push failed: ' . $e->getMessage());
        }
    }
    return order_find((int) $order['id']);
}

function order_fail(array $order, string $reason): void
{
    if ($order['status'] === 'pending_payment') {
        update('orders', ['status' => 'failed', 'payment_status' => 'failed', 'updated_at' => now()], 'id = ?', [$order['id']]);
    }
    order_note((int) $order['id'], 'Payment failed: ' . $reason);
}

function order_reduce_stock(array $order): void
{
    if ((int) $order['stock_reduced'] === 1) {
        return;
    }
    foreach (order_items((int) $order['id']) as $item) {
        if ($item['variation_id']) {
            q("UPDATE variations SET stock_qty = stock_qty - ?, stock_status = CASE WHEN stock_qty - ? <= 0 THEN 'outofstock' ELSE stock_status END WHERE id = ? AND manage_stock = 1",
                [$item['qty'], $item['qty'], $item['variation_id']]);
        } elseif ($item['product_id']) {
            q("UPDATE products SET stock_qty = stock_qty - ?, stock_status = CASE WHEN stock_qty - ? <= 0 THEN 'outofstock' ELSE stock_status END WHERE id = ? AND manage_stock = 1",
                [$item['qty'], $item['qty'], $item['product_id']]);
        }
        if ($item['product_id']) {
            product_refresh_cache((int) $item['product_id']);
        }
    }
    update('orders', ['stock_reduced' => 1], 'id = ?', [$order['id']]);
}

function order_restock(array $order): void
{
    if ((int) $order['stock_reduced'] !== 1) {
        return;
    }
    foreach (order_items((int) $order['id']) as $item) {
        if ($item['variation_id']) {
            q("UPDATE variations SET stock_qty = stock_qty + ?, stock_status = 'instock' WHERE id = ? AND manage_stock = 1", [$item['qty'], $item['variation_id']]);
        } elseif ($item['product_id']) {
            q("UPDATE products SET stock_qty = stock_qty + ?, stock_status = 'instock' WHERE id = ? AND manage_stock = 1", [$item['qty'], $item['product_id']]);
        }
        if ($item['product_id']) {
            product_refresh_cache((int) $item['product_id']);
        }
    }
    update('orders', ['stock_reduced' => 0], 'id = ?', [$order['id']]);
}

function order_set_status(array $order, string $status, bool $notify = true): void
{
    if (!isset(order_statuses()[$status]) || $status === $order['status']) {
        return;
    }
    update('orders', ['status' => $status, 'updated_at' => now()], 'id = ?', [$order['id']]);
    order_note((int) $order['id'], 'Status changed from ' . order_status_label($order['status']) . ' to ' . order_status_label($status) . '.');
    if (in_array($status, ['cancelled', 'refunded'], true)) {
        order_restock($order);
    }
    if ($notify && in_array($status, ['shipped', 'delivered', 'cancelled', 'refunded'], true)) {
        $o = order_find((int) $order['id']);
        send_mail($o['email'], 'Your order ' . $o['number'] . ' is ' . strtolower(order_status_label($status)),
            render('emails/order_status', ['order' => $o, 'items' => order_items((int) $o['id'])]));
    }
}

function order_view_url(array $order): string
{
    return site_url('order/' . $order['number'] . '/?key=' . $order['access_key']);
}

function order_send_emails(array $order): void
{
    $items = order_items((int) $order['id']);
    send_mail($order['email'], 'Thank you! Your order ' . $order['number'] . ' is confirmed',
        render('emails/order_customer', ['order' => $order, 'items' => $items]));
    $admin = setting('store_email');
    if ($admin) {
        send_mail($admin, 'New order ' . $order['number'] . ' — ' . money($order['total']),
            render('emails/order_admin', ['order' => $order, 'items' => $items]), $order['email']);
    }
}
