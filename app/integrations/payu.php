<?php
declare(strict_types=1);

/**
 * PayU India hosted checkout (the customer pays on PayU's secure page).
 * Keys come from Admin → Settings → Payments.
 */
function payu_base(): string
{
    return setting('payu_mode') === 'live' ? 'https://secure.payu.in' : 'https://test.payu.in';
}

/** Fields for the auto-submitting form that sends the customer to PayU. */
function payu_form(array $order): array
{
    $key = setting('payu_key');
    $salt = setting('payu_salt');
    $billing = json_arr($order['billing_json']);
    $txnid = $order['number'] . '-' . substr(random_token(3), 0, 6);
    update('orders', ['gateway_order_id' => $txnid], 'id = ?', [$order['id']]);
    $fields = [
        'key' => $key,
        'txnid' => $txnid,
        'amount' => number_format((float) $order['total'], 2, '.', ''),
        'productinfo' => 'Order ' . $order['number'],
        'firstname' => preg_replace('/[^\p{L}\s.]/u', '', (string) $billing['name']) ?: 'Customer',
        'email' => $order['email'],
        'phone' => preg_replace('/\D/', '', $order['phone']),
        'udf1' => (string) $order['id'],
        'udf2' => '', 'udf3' => '', 'udf4' => '', 'udf5' => '',
        'surl' => site_url('payment/payu/return'),
        'furl' => site_url('payment/payu/return'),
    ];
    $hashString = implode('|', [$fields['key'], $fields['txnid'], $fields['amount'], $fields['productinfo'],
            $fields['firstname'], $fields['email'], $fields['udf1'], $fields['udf2'], $fields['udf3'], $fields['udf4'],
            $fields['udf5'], '', '', '', '', '', $salt]);
    $fields['hash'] = strtolower(hash('sha512', $hashString));
    return ['action' => payu_base() . '/_payment', 'fields' => $fields];
}

/** Verify the response PayU posts back. Returns [ok(bool), status, order_id, txnid, mihpayid]. */
function payu_verify_response(array $post): array
{
    $salt = setting('payu_salt');
    $parts = [$salt, $post['status'] ?? '', '', '', '', '', '', $post['udf5'] ?? '', $post['udf4'] ?? '', $post['udf3'] ?? '',
        $post['udf2'] ?? '', $post['udf1'] ?? '', $post['email'] ?? '', $post['firstname'] ?? '', $post['productinfo'] ?? '',
        $post['amount'] ?? '', $post['txnid'] ?? '', $post['key'] ?? ''];
    if (!empty($post['additionalCharges'])) {
        array_unshift($parts, $post['additionalCharges']);
    }
    $expected = strtolower(hash('sha512', implode('|', $parts)));
    $ok = hash_equals($expected, strtolower((string) ($post['hash'] ?? '')));
    return [
        'ok' => $ok,
        'status' => $post['status'] ?? '',
        'order_id' => (int) ($post['udf1'] ?? 0),
        'txnid' => $post['txnid'] ?? '',
        'amount' => (float) ($post['amount'] ?? 0),
        'mihpayid' => $post['mihpayid'] ?? '',
        'error' => $post['error_Message'] ?? ($post['field9'] ?? ''),
    ];
}

/** Double-check a transaction with PayU's server (defence against tampered posts). */
function payu_server_verify(string $txnid): ?string
{
    $key = setting('payu_key');
    $salt = setting('payu_salt');
    $command = 'verify_payment';
    $hash = strtolower(hash('sha512', "{$key}|{$command}|{$txnid}|{$salt}"));
    $url = setting('payu_mode') === 'live'
        ? 'https://info.payu.in/merchant/postservice?form=2'
        : 'https://test.payu.in/merchant/postservice.php?form=2';
    $res = http_request('POST', $url, ['Content-Type: application/x-www-form-urlencoded'],
        http_build_query(['key' => $key, 'command' => $command, 'var1' => $txnid, 'hash' => $hash]));
    $status = $res['json']['transaction_details'][$txnid]['status'] ?? null;
    return is_string($status) ? $status : null;
}
