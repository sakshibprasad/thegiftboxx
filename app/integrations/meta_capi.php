<?php
declare(strict_types=1);

/**
 * Server-side Purchase event for Meta (Facebook/Instagram) ads.
 * Uses the same event_id as the browser Pixel so Meta de-duplicates them.
 */
function meta_capi_purchase(array $order): void
{
    $pixel = trim((string) setting('meta_pixel_id'));
    $token = (string) setting('meta_capi_token');
    if (!$pixel || !$token) {
        return;
    }
    $billing = json_arr($order['billing_json']);
    $hash = fn(string $v) => hash('sha256', strtolower(trim($v)));
    $phone = preg_replace('/\D/', '', (string) $order['phone']);
    if (strlen($phone) === 10) {
        $phone = '91' . $phone;
    }
    $items = order_items((int) $order['id']);
    $payload = ['data' => [[
        'event_name' => 'Purchase',
        'event_time' => time(),
        'event_id' => 'order-' . $order['number'],
        'action_source' => 'website',
        'event_source_url' => site_url('checkout/'),
        'user_data' => array_filter([
            'em' => [$hash($order['email'])],
            'ph' => $phone ? [$hash($phone)] : null,
            'fn' => !empty($billing['name']) ? [$hash(explode(' ', $billing['name'])[0])] : null,
            'ct' => !empty($billing['city']) ? [$hash(str_replace(' ', '', $billing['city']))] : null,
            'zp' => !empty($billing['pincode']) ? [$hash($billing['pincode'])] : null,
            'country' => [$hash('in')],
        ]),
        'custom_data' => [
            'currency' => 'INR',
            'value' => (float) $order['total'],
            'order_id' => $order['number'],
            'content_type' => 'product',
            'contents' => array_map(fn($i) => ['id' => $i['variation_id'] ? 'TGB-' . $i['product_id'] . '-' . $i['variation_id'] : 'TGB-' . $i['product_id'], 'quantity' => (int) $i['qty'], 'item_price' => (float) $i['price']], $items),
        ],
    ]]];
    $res = http_request('POST', 'https://graph.facebook.com/v19.0/' . rawurlencode($pixel) . '/events?access_token=' . rawurlencode($token), [], $payload, 15);
    if ($res['status'] >= 300) {
        app_log('meta', 'CAPI purchase failed', ['status' => $res['status'], 'body' => substr($res['body'], 0, 300)]);
    }
}
