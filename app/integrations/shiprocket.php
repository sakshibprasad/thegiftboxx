<?php
declare(strict_types=1);

const SHIPROCKET_API = 'https://apiv2.shiprocket.in/v1/external';

function shiprocket_token(bool $refresh = false): string
{
    $cached = setting('shiprocket_token_cache', '');
    $expires = (int) setting('shiprocket_token_expires', '0');
    if (!$refresh && $cached && $expires > time()) {
        return $cached;
    }
    $res = http_request('POST', SHIPROCKET_API . '/auth/login', [], [
        'email' => setting('shiprocket_email'),
        'password' => setting('shiprocket_password'),
    ]);
    $token = $res['json']['token'] ?? null;
    if (!$token) {
        throw new RuntimeException('Shiprocket login failed: ' . ($res['json']['message'] ?? 'check API user email/password'));
    }
    setting_set('shiprocket_token_cache', $token, true);
    setting_set('shiprocket_token_expires', (string) (time() + 9 * 86400));
    return $token;
}

function shiprocket_call(string $method, string $path, ?array $body = null): array
{
    $res = http_request($method, SHIPROCKET_API . $path, ['Authorization' => 'Bearer ' . shiprocket_token()], $body);
    if ($res['status'] === 401) {
        $res = http_request($method, SHIPROCKET_API . $path, ['Authorization' => 'Bearer ' . shiprocket_token(true)], $body);
    }
    return $res;
}

/** Is this pincode deliverable? Returns ['ok' => bool, 'message' => string, 'days' => ?int]. */
function shiprocket_serviceability(string $pincode, float $weight = 1.0, bool $cod = false): array
{
    $query = http_build_query([
        'pickup_postcode' => setting('shiprocket_pickup_pincode'),
        'delivery_postcode' => $pincode,
        'weight' => max(0.1, $weight),
        'cod' => $cod ? 1 : 0,
    ]);
    $res = shiprocket_call('GET', '/courier/serviceability/?' . $query);
    $couriers = $res['json']['data']['available_courier_companies'] ?? [];
    if (!$couriers) {
        return ['ok' => false, 'message' => 'Sorry, we do not deliver to this pincode yet. Contact us for help.', 'days' => null];
    }
    $days = min(array_map(fn($c) => (int) ($c['estimated_delivery_days'] ?? 7) ?: 7, $couriers));
    return ['ok' => true, 'message' => 'Delivery available to ' . $pincode, 'days' => $days];
}

/** Create the order in Shiprocket and assign a courier (AWB). */
function shiprocket_push_order(array $order): array
{
    if ($order['shiprocket_order_id']) {
        throw new RuntimeException('This order is already in Shiprocket (#' . $order['shiprocket_order_id'] . ').');
    }
    $billing = json_arr($order['billing_json']);
    $ship = json_arr($order['shipping_json']);
    $items = order_items((int) $order['id']);
    $weight = 0.0;
    $dims = ['length' => 10, 'breadth' => 10, 'height' => 10];
    $orderItems = [];
    foreach ($items as $it) {
        $src = $it['variation_id'] ? one('SELECT * FROM variations WHERE id = ?', [$it['variation_id']]) : null;
        $p = $it['product_id'] ? one('SELECT * FROM products WHERE id = ?', [$it['product_id']]) : null;
        $w = (float) (($src['weight'] ?? null) ?: ($p['weight'] ?? null) ?: 0.5);
        $weight += $w * (int) $it['qty'];
        $dims['length'] = max($dims['length'], (float) (($src['length'] ?? null) ?: ($p['length'] ?? 10)) * 2.54);
        $dims['breadth'] = max($dims['breadth'], (float) (($src['width'] ?? null) ?: ($p['width'] ?? 10)) * 2.54);
        $dims['height'] = max($dims['height'], (float) (($src['height'] ?? null) ?: ($p['height'] ?? 10)) * 2.54);
        $orderItems[] = [
            'name' => $it['name'] . ($it['variation_label'] ? ' - ' . $it['variation_label'] : ''),
            'sku' => $it['sku'] ?: 'TGB-' . $it['product_id'] . '-' . ($it['variation_id'] ?: 0),
            'units' => (int) $it['qty'],
            'selling_price' => (float) $it['price'],
        ];
    }
    [$first, $last] = array_pad(explode(' ', trim((string) $billing['name']), 2), 2, '');
    [$sFirst, $sLast] = array_pad(explode(' ', trim((string) $ship['name']), 2), 2, '');
    $sameAddress = $billing == $ship;
    $payload = [
        'order_id' => $order['number'],
        'order_date' => date('Y-m-d H:i', strtotime($order['created_at'])),
        'pickup_location' => setting('shiprocket_pickup'),
        'comment' => trim(($order['gift_message'] ? 'Gift message: ' . $order['gift_message'] . ' ' : '') . ($order['delivery_date'] ? 'Preferred delivery: ' . $order['delivery_date'] : '')),
        'billing_customer_name' => $first,
        'billing_last_name' => $last,
        'billing_address' => $billing['address1'],
        'billing_address_2' => $billing['address2'] ?? '',
        'billing_city' => $billing['city'],
        'billing_pincode' => $billing['pincode'],
        'billing_state' => $billing['state'],
        'billing_country' => 'India',
        'billing_email' => $order['email'],
        'billing_phone' => substr(preg_replace('/\D/', '', (string) $billing['phone']), -10),
        'shipping_is_billing' => $sameAddress,
        'order_items' => $orderItems,
        'payment_method' => $order['payment_method'] === 'cod' ? 'COD' : 'Prepaid',
        'shipping_charges' => (float) $order['shipping'],
        'total_discount' => (float) $order['discount'],
        'sub_total' => (float) $order['total'] - (float) $order['shipping'],
        'length' => round($dims['length'], 1),
        'breadth' => round($dims['breadth'], 1),
        'height' => round($dims['height'], 1),
        'weight' => round(max(0.1, $weight), 2),
    ];
    if (!$sameAddress) {
        $payload += [
            'shipping_customer_name' => $sFirst, 'shipping_last_name' => $sLast,
            'shipping_address' => $ship['address1'], 'shipping_address_2' => $ship['address2'] ?? '',
            'shipping_city' => $ship['city'], 'shipping_pincode' => $ship['pincode'], 'shipping_state' => $ship['state'],
            'shipping_country' => 'India', 'shipping_email' => $order['email'],
            'shipping_phone' => substr(preg_replace('/\D/', '', (string) $ship['phone']), -10),
        ];
    }
    $res = shiprocket_call('POST', '/orders/create/adhoc', $payload);
    $srOrder = $res['json']['order_id'] ?? null;
    $shipment = $res['json']['shipment_id'] ?? null;
    if (!$srOrder) {
        $msg = $res['json']['message'] ?? 'Unknown error';
        if (!empty($res['json']['errors'])) {
            $msg .= ' ' . json_encode($res['json']['errors']);
        }
        throw new RuntimeException('Shiprocket: ' . $msg);
    }
    update('orders', ['shiprocket_order_id' => (string) $srOrder, 'shipment_id' => (string) $shipment, 'updated_at' => now()], 'id = ?', [$order['id']]);
    order_note((int) $order['id'], "Sent to Shiprocket (order #{$srOrder}, shipment #{$shipment}).");
    try {
        shiprocket_assign_awb(order_find((int) $order['id']));
    } catch (Throwable $e) {
        order_note((int) $order['id'], 'Courier not assigned automatically: ' . $e->getMessage() . ' — assign it in Shiprocket.');
    }
    return order_find((int) $order['id']);
}

function shiprocket_assign_awb(array $order): void
{
    if (!$order['shipment_id']) {
        throw new RuntimeException('No Shiprocket shipment yet.');
    }
    $res = shiprocket_call('POST', '/courier/assign/awb', ['shipment_id' => (int) $order['shipment_id']]);
    $data = $res['json']['response']['data'] ?? [];
    $awb = $data['awb_code'] ?? null;
    if (!$awb) {
        throw new RuntimeException($res['json']['message'] ?? 'AWB not assigned (check wallet balance / pickup address).');
    }
    update('orders', [
        'awb' => $awb,
        'courier' => $data['courier_name'] ?? '',
        'tracking_url' => 'https://shiprocket.co/tracking/' . $awb,
        'updated_at' => now(),
    ], 'id = ?', [$order['id']]);
    order_note((int) $order['id'], 'Courier assigned: ' . ($data['courier_name'] ?? '') . ", AWB {$awb}.");
}

function shiprocket_track(string $awb): ?array
{
    $res = shiprocket_call('GET', '/courier/track/awb/' . rawurlencode($awb));
    return $res['json']['tracking_data'] ?? null;
}
