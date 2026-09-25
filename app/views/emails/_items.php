<?php /** @var array $order @var array $items */ ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0;border-collapse:collapse;font-size:14px">
<?php foreach ($items as $it): ?>
  <tr><td style="padding:10px 0;border-bottom:1px solid #EEE6DD"><?= e($it['name']) ?><?php if ($it['variation_label']): ?><br><span style="color:#8A7B6E;font-size:13px"><?= e($it['variation_label']) ?></span><?php endif; ?></td>
  <td style="padding:10px 0;border-bottom:1px solid #EEE6DD;text-align:center;color:#8A7B6E">× <?= (int) $it['qty'] ?></td>
  <td style="padding:10px 0;border-bottom:1px solid #EEE6DD;text-align:right"><?= money($it['total']) ?></td></tr>
<?php endforeach; ?>
  <tr><td colspan="2" style="padding:8px 0 2px;color:#8A7B6E">Subtotal</td><td style="text-align:right;padding:8px 0 2px"><?= money($order['subtotal']) ?></td></tr>
  <?php if ((float) $order['discount'] > 0): ?><tr><td colspan="2" style="padding:2px 0;color:#8A7B6E">Discount<?= $order['coupon_code'] ? ' (' . e($order['coupon_code']) . ')' : '' ?></td><td style="text-align:right">−<?= money($order['discount']) ?></td></tr><?php endif; ?>
  <tr><td colspan="2" style="padding:2px 0;color:#8A7B6E">Delivery</td><td style="text-align:right"><?= (float) $order['shipping'] > 0 ? money($order['shipping']) : 'Free' ?></td></tr>
  <?php if ((float) $order['fee'] > 0): ?><tr><td colspan="2" style="padding:2px 0;color:#8A7B6E">COD fee</td><td style="text-align:right"><?= money($order['fee']) ?></td></tr><?php endif; ?>
  <tr><td colspan="2" style="padding:10px 0;font-weight:700">Total</td><td style="text-align:right;font-weight:700;padding:10px 0"><?= money($order['total']) ?></td></tr>
</table>
