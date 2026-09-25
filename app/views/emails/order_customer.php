<?php /** @var array $order @var array $items */
$name = explode(' ', (string) (json_arr($order['billing_json'])['name'] ?? ''))[0];
$body = '<p>Hi ' . e($name ?: 'there') . ',</p><p>Thank you for your order! We’ve started putting your box together and will email you a tracking link as soon as it ships.</p>'
    . '<p style="margin:0;color:#8A7B6E">Order <strong style="color:#1D1714">' . e($order['number']) . '</strong> · ' . e(nice_date($order['created_at'])) . ' · ' . e(payment_method_label($order['payment_method'])) . '</p>'
    . render('emails/_items', ['order' => $order, 'items' => $items]) . render('emails/_address', ['order' => $order])
    . '<p>If anything needs changing, just reply to this email or call us on ' . e(setting('store_phone')) . '.</p><p>Warmly,<br>The Gift Boxx team</p>';
echo email_layout('Your order is confirmed', $body, ['View your order', order_view_url($order)]);
