<?php /** @var array $order @var array $items */
$name = explode(' ', (string) (json_arr($order['billing_json'])['name'] ?? ''))[0];
$msg = [
    'shipped' => 'Good news, your gift box is on its way!' . ($order['awb'] ? ' It’s travelling with ' . e($order['courier'] ?: 'our courier partner') . ' (AWB ' . e($order['awb']) . ').' : ''),
    'delivered' => 'Your order has been delivered. We hope it brought a big smile. If you have a moment, we’d love a review.',
    'cancelled' => 'Your order has been cancelled. If you paid online, the refund will reach your account within 5–7 working days.',
    'refunded' => 'We’ve processed a refund for your order. It usually takes 5–7 working days to show up in your account.',
][$order['status']] ?? 'Your order status is now: ' . e(order_status_label($order['status'])) . '.';
$button = $order['status'] === 'shipped' && $order['tracking_url'] ? ['Track your parcel', $order['tracking_url']] : ['View your order', order_view_url($order)];
echo email_layout('Order ' . $order['number'] . ': ' . order_status_label($order['status']),
    '<p>Hi ' . e($name ?: 'there') . ',</p><p>' . $msg . '</p>' . render('emails/_items', ['order' => $order, 'items' => $items]), $button);
