<?php
declare(strict_types=1);

/**
 * Cashfree Payments (PG API 2023-08-01) with the hosted Cashfree checkout.
 */
function cashfree_base(): string
{
    return setting('cashfree_mode') === 'production' ? 'https://api.cashfree.com/pg' : 'https://sandbox.cashfree.com/pg';
}

function cashfree_headers(): array
{
    return [
        'x-client-id' => setting('cashfree_app_id'),
        'x-client-secret' => setting('cashfree_secret'),
        'x-api-version' => '2023-08-01',
        'Accept' => 'application/json',
    ];
}

/** Create a Cashfree order and return its payment_session_id. */
function cashfree_create(array $order): string
{
    $billing = json_arr($order['billing_json']);
    $cfOrderId = str_replace(['-', ' '], '_', $order['number']) . '_' . substr(random_token(3), 0, 6);
    $phone = substr(preg_replace('/\D/', '', $order['phone']), -10);
    $res = http_request('POST', cashfree_base() . '/orders', cashfree_headers(), [
        'order_id' => $cfOrderId,
        'order_amount' => round((float) $order['total'], 2),
        'order_currency' => 'INR',
        'customer_details' => [
            'customer_id' => 'cust_' . substr(hash('sha256', $order['email']), 0, 20),
            'customer_name' => (string) ($billing['name'] ?? ''),
            'customer_email' => $order['email'],
            'customer_phone' => $phone ?: '9999999999',
        ],
        'order_meta' => [
            'return_url' => site_url('payment/cashfree/return?order_id={order_id}'),
            'notify_url' => site_url('payment/cashfree/webhook'),
        ],
        'order_note' => 'Order ' . $order['number'],
    ]);
    if ($res['status'] >= 300 || empty($res['json']['payment_session_id'])) {
        app_log('cashfree', 'create order failed', ['status' => $res['status'], 'body' => substr($res['body'], 0, 500)]);
        throw new RuntimeException($res['json']['message'] ?? 'Could not start Cashfree payment.');
    }
    update('orders', ['gateway_order_id' => $cfOrderId], 'id = ?', [$order['id']]);
    return $res['json']['payment_session_id'];
}

/** Ask Cashfree for the order status: PAID, ACTIVE, EXPIRED ... */
function cashfree_fetch(string $cfOrderId): ?array
{
    $res = http_request('GET', cashfree_base() . '/orders/' . rawurlencode($cfOrderId), cashfree_headers());
    return $res['status'] === 200 ? $res['json'] : null;
}

function cashfree_webhook_valid(string $rawBody, string $timestamp, string $signature): bool
{
    $expected = base64_encode(hash_hmac('sha256', $timestamp . $rawBody, (string) setting('cashfree_secret'), true));
    return $signature !== '' && hash_equals($expected, $signature);
}

/** Confirm the order locally if Cashfree says it is paid. */
function cashfree_sync(array $order): array
{
    if (!$order['gateway_order_id']) {
        return $order;
    }
    $cf = cashfree_fetch($order['gateway_order_id']);
    if (!$cf) {
        return $order;
    }
    if (($cf['order_status'] ?? '') === 'PAID' && abs((float) $cf['order_amount'] - (float) $order['total']) < 0.01) {
        return order_confirm($order, 'paid', $cf['cf_order_id'] ?? $order['gateway_order_id']);
    }
    if (in_array($cf['order_status'] ?? '', ['EXPIRED', 'TERMINATED'], true)) {
        order_fail($order, 'Cashfree order ' . strtolower($cf['order_status']));
    }
    return order_find((int) $order['id']);
}
