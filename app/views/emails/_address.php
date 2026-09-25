<?php /** @var array $order */ $s = json_arr($order['shipping_json']); ?>
<p style="margin:0 0 6px;color:#8A7B6E;font-size:13px;text-transform:uppercase;letter-spacing:.06em">Delivering to</p>
<p style="margin:0 0 16px"><?= e($s['name'] ?? '') ?><br><?= e($s['address1'] ?? '') ?><?= !empty($s['address2']) ? ', ' . e($s['address2']) : '' ?><br><?= e($s['city'] ?? '') ?>, <?= e($s['state'] ?? '') ?> <?= e($s['pincode'] ?? '') ?><br><?= e($s['phone'] ?? '') ?></p>
<?php if ($order['gift_message']): ?><p style="margin:0 0 6px;color:#8A7B6E;font-size:13px;text-transform:uppercase;letter-spacing:.06em">Gift message</p><p style="margin:0 0 16px;font-style:italic">“<?= e($order['gift_message']) ?>”</p><?php endif; ?>
<?php if ($order['delivery_date']): ?><p style="margin:0 0 16px">Preferred delivery date: <strong><?= e(nice_date($order['delivery_date'])) ?></strong></p><?php endif; ?>
