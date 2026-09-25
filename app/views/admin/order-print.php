<?php /** @var array $o @var array $items @var bool $slip */
$b = json_arr($o['billing_json']); $s = json_arr($o['shipping_json']); ?><!doctype html>
<html><head><meta charset="utf-8"><title><?= $slip ? 'Packing slip' : 'Invoice' ?> <?= e($o['number']) ?></title>
<style>
body{font:13px/1.5 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#1d1d1f;margin:0;padding:40px;max-width:800px;margin:auto}
header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #1d1d1f;padding-bottom:18px;margin-bottom:24px}
header img{width:200px} h1{font-size:22px;margin:0 0 4px;letter-spacing:-.02em} .muted{color:#6e6e73}
.cols{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px} h3{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#6e6e73;margin:0 0 6px}
table{width:100%;border-collapse:collapse} th{text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#6e6e73;border-bottom:1px solid #ddd;padding:8px 0}
td{padding:10px 0;border-bottom:1px solid #eee;vertical-align:top} .r{text-align:right} .tot td{border:0;padding:4px 0} .grand td{font-weight:700;font-size:15px;border-top:2px solid #1d1d1f;padding-top:10px}
.gift{margin-top:24px;padding:16px 18px;border:1px dashed #BAA183;border-radius:12px;font-family:Georgia,serif;font-size:17px;font-style:italic}
.check{display:inline-block;width:14px;height:14px;border:1.5px solid #1d1d1f;border-radius:3px;vertical-align:middle}
footer{margin-top:40px;font-size:11.5px;color:#6e6e73;text-align:center} @media print{body{padding:0}.noprint{display:none}}
.noprint{position:fixed;top:16px;right:16px;padding:8px 16px;border-radius:999px;border:0;background:#0A84FF;color:#fff;font:inherit;cursor:pointer}
</style></head><body>
<button class="noprint" onclick="print()">Print</button>
<header><div><img src="<?= e(site_url('assets/img/logo-dark.svg')) ?>" alt=""><p class="muted" style="margin:8px 0 0"><?= nl2br(e(setting('store_address'))) ?><br><?= e(setting('store_phone')) ?> · <?= e(setting('store_email')) ?><?= setting('store_gstin') ? '<br>GSTIN ' . e(setting('store_gstin')) : '' ?></p></div>
<div class="r"><h1><?= $slip ? 'Packing slip' : 'Tax invoice' ?></h1><p class="muted" style="margin:0">Order <?= e($o['number']) ?><br><?= e(nice_date($o['created_at'], 'j F Y')) ?><br><?= e(payment_method_label($o['payment_method'])) ?><?= $o['payment_method'] === 'cod' ? ' — collect ' . money($o['total']) : '' ?></p></div></header>
<div class="cols"><div><h3>Ship to</h3><?= e($s['name'] ?? '') ?><br><?= e($s['address1'] ?? '') ?><?= !empty($s['address2']) ? '<br>' . e($s['address2']) : '' ?><br><?= e($s['city'] ?? '') ?>, <?= e($s['state'] ?? '') ?> <?= e($s['pincode'] ?? '') ?><br><?= e($s['phone'] ?? '') ?></div>
<?php if (!$slip): ?><div><h3>Bill to</h3><?= e($b['name'] ?? '') ?><br><?= e($b['address1'] ?? '') ?><br><?= e($b['city'] ?? '') ?>, <?= e($b['state'] ?? '') ?> <?= e($b['pincode'] ?? '') ?><br><?= e($o['email']) ?></div><?php else: ?><div><?php if ($o['delivery_date']): ?><h3>Deliver by</h3><strong style="font-size:16px"><?= e(nice_date($o['delivery_date'], 'l, j M')) ?></strong><?php endif; ?></div><?php endif; ?></div>
<table><thead><tr><?php if ($slip): ?><th style="width:30px"></th><?php endif; ?><th>Item</th><th class="r">Qty</th><?php if (!$slip): ?><th class="r">Price</th><th class="r">Amount</th><?php endif; ?></tr></thead><tbody>
<?php foreach ($items as $it): ?><tr><?php if ($slip): ?><td><span class="check"></span></td><?php endif; ?><td><strong><?= e($it['name']) ?></strong><?= $it['variation_label'] ? '<br><span class="muted">' . e($it['variation_label']) . '</span>' : '' ?><?= $it['sku'] ? '<br><span class="muted">SKU ' . e($it['sku']) . '</span>' : '' ?></td><td class="r"><?= (int) $it['qty'] ?></td><?php if (!$slip): ?><td class="r"><?= money($it['price'], true) ?></td><td class="r"><?= money($it['total'], true) ?></td><?php endif; ?></tr><?php endforeach; ?>
</tbody>
<?php if (!$slip): ?><tfoot>
<tr class="tot"><td colspan="3" class="r muted">Subtotal</td><td class="r"><?= money($o['subtotal'], true) ?></td></tr>
<?php if ((float) $o['discount'] > 0): ?><tr class="tot"><td colspan="3" class="r muted">Discount</td><td class="r">−<?= money($o['discount'], true) ?></td></tr><?php endif; ?>
<tr class="tot"><td colspan="3" class="r muted">Shipping</td><td class="r"><?= money($o['shipping'], true) ?></td></tr>
<?php if ((float) $o['fee'] > 0): ?><tr class="tot"><td colspan="3" class="r muted">COD fee</td><td class="r"><?= money($o['fee'], true) ?></td></tr><?php endif; ?>
<tr class="grand"><td colspan="3" class="r">Total (incl. GST)</td><td class="r"><?= money($o['total'], true) ?></td></tr></tfoot><?php endif; ?>
</table>
<?php if ($slip && $o['gift_message']): ?><h3 style="margin-top:28px">Handwritten card</h3><div class="gift">“<?= e($o['gift_message']) ?>”</div><?php endif; ?>
<?php if ($slip && $o['customer_note']): ?><p style="margin-top:18px"><strong>Note:</strong> <?= e($o['customer_note']) ?></p><?php endif; ?>
<footer>Thank you for choosing <?= e(setting('store_name')) ?> · <?= e(parse_url(site_url(), PHP_URL_HOST)) ?></footer>
</body></html>
