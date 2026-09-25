<?php /** @var array $order @var array $items */
$b = json_arr($order['billing_json']);
$body = '<p><strong>' . e($b['name'] ?? '') . '</strong> · ' . e($order['email']) . ' · ' . e($order['phone']) . '</p>'
    . '<p style="margin:0;color:#8A7B6E">' . e(payment_method_label($order['payment_method'])) . ' · ' . e(order_status_label($order['status'])) . '</p>'
    . render('emails/_items', ['order' => $order, 'items' => $items]) . render('emails/_address', ['order' => $order])
    . ($order['customer_note'] ? '<p><strong>Customer note:</strong> ' . e($order['customer_note']) . '</p>' : '');
echo email_layout('New order ' . $order['number'], $body, ['Open in admin', admin_url('orders/' . $order['id'])]);
