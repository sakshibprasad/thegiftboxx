<?php /** @var array $o @var array $items @var array $notes @var int $customerOrders @var ?array $user */
$b = json_arr($o['billing_json']); $s = json_arr($o['shipping_json']);
$flow = ['processing' => 'Confirmed', 'packed' => 'Packed', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
$pos = array_search($o['status'] === 'completed' ? 'delivered' : $o['status'], array_keys($flow), true);
$next = ['processing' => ['packed', 'Mark as packed'], 'packed' => ['shipped', 'Mark as shipped'], 'shipped' => ['delivered', 'Mark as delivered']][$o['status']] ?? null;
$waLink = 'https://wa.me/' . preg_replace('/\D/', '', (strlen(preg_replace('/\D/', '', $o['phone'])) === 10 ? '91' : '') . $o['phone']) . '?text=' . rawurlencode('Hi ' . explode(' ', (string) ($b['name'] ?? ''))[0] . ', this is ' . setting('store_name') . ' about your order ' . $o['number'] . '.');
?>
<div class="page-head">
    <div><a class="back" href="/orders"><?= icon('arrow-left') ?> Orders</a><h1><?= e($o['number']) ?> <?= status_badge($o['status']) ?></h1><p><?= e(nice_date($o['created_at'], 'l, j F Y \a\t g:i a')) ?> · <?= e(payment_method_label($o['payment_method'])) ?> · <?= $o['payment_status'] === 'paid' ? 'Paid' : ($o['payment_status'] === 'cod' ? 'Collect on delivery' : 'Not paid') ?></p></div>
    <div class="head-actions">
        <a class="btn secondary" href="/orders/<?= (int) $o['id'] ?>/print?type=slip" target="_blank"><?= icon('print') ?> Packing slip</a>
        <a class="btn secondary" href="/orders/<?= (int) $o['id'] ?>/print" target="_blank"><?= icon('orders') ?> Invoice</a>
        <?php if ($next): ?><form method="post" action="/orders/<?= (int) $o['id'] ?>/status"><?= csrf_field() ?><input type="hidden" name="status" value="<?= $next[0] ?>"><input type="hidden" name="notify" value="<?= $next[0] === 'packed' ? 0 : 1 ?>"><button class="btn"><?= icon('check') ?> <?= $next[1] ?></button></form><?php endif; ?>
    </div>
</div>

<?php if ($pos !== false): ?>
<div class="card pad" style="margin-bottom:16px"><div class="steps-bar"><?php $i = 0; foreach ($flow as $label): ?><div class="<?= $i <= $pos ? 'done' : '' ?>"><?= $label ?></div><?php $i++; endforeach; ?></div></div>
<?php endif; ?>

<div class="grid g-main">
    <div class="stack">
        <div class="card">
            <div class="card-head"><h3>Items</h3><span class="muted small"><?= array_sum(array_column($items, 'qty')) ?> item(s)</span></div>
            <ul class="rows" style="margin-top:6px">
                <?php foreach ($items as $it): ?>
                    <li><img src="<?= e(image_url($it['image'], 'sm')) ?>" alt="" style="width:48px;height:58px;border-radius:10px;object-fit:cover">
                        <span class="grow"><strong><?php if ($it['product_id']): ?><a href="/products/<?= (int) $it['product_id'] ?>"><?= e($it['name']) ?></a><?php else: ?><?= e($it['name']) ?><?php endif; ?></strong><small><?= e($it['variation_label'] ?: '') ?><?= $it['sku'] ? ' · SKU ' . e($it['sku']) : '' ?></small></span>
                        <span class="muted nowrap"><?= money($it['price']) ?> × <?= (int) $it['qty'] ?></span><strong class="nowrap" style="min-width:80px;text-align:right"><?= money($it['total']) ?></strong></li>
                <?php endforeach; ?>
            </ul>
            <div class="pad totals" style="border-top:1px solid var(--line-2)">
                <div class="r"><span class="muted">Subtotal</span><span><?= money($o['subtotal']) ?></span></div>
                <?php if ((float) $o['discount'] > 0): ?><div class="r"><span class="muted">Discount <?= $o['coupon_code'] ? '(' . e($o['coupon_code']) . ')' : '' ?></span><span>−<?= money($o['discount']) ?></span></div><?php endif; ?>
                <div class="r"><span class="muted">Shipping</span><span><?= (float) $o['shipping'] > 0 ? money($o['shipping']) : 'Free' ?></span></div>
                <?php if ((float) $o['fee'] > 0): ?><div class="r"><span class="muted">COD fee</span><span><?= money($o['fee']) ?></span></div><?php endif; ?>
                <div class="r big"><span>Total</span><span><?= money($o['total']) ?></span></div>
                <?php if ($o['payment_ref']): ?><p class="small muted" style="margin:6px 0 0">Payment reference: <code><?= e($o['payment_ref']) ?></code></p><?php endif; ?>
            </div>
        </div>

        <?php if ($o['gift_message'] || $o['delivery_date'] || $o['customer_note']): ?>
        <div class="card pad fields">
            <h3 style="margin:0">Gift details</h3>
            <?php if ($o['gift_message']): ?><div><p class="small muted" style="margin:0 0 4px">Gift message (print on the card)</p><div class="gift-msg">“<?= e($o['gift_message']) ?>”</div></div><?php endif; ?>
            <?php if ($o['delivery_date']): ?><p style="margin:0"><?= icon('calendar') ?> Preferred delivery: <strong><?= e(nice_date($o['delivery_date'], 'l, j M Y')) ?></strong></p><?php endif; ?>
            <?php if ($o['customer_note']): ?><p style="margin:0"><span class="muted">Customer note:</span> <?= e($o['customer_note']) ?></p><?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="card pad">
            <h3>Shipping</h3>
            <?php if ($o['awb']): ?>
                <p><span class="badge b-shipped"><?= icon('truck') ?> <?= e($o['courier'] ?: 'Courier') ?></span> AWB <strong><?= e($o['awb']) ?></strong> <?php if ($o['tracking_url']): ?>· <a class="link" href="<?= e($o['tracking_url']) ?>" target="_blank" rel="noopener">Track</a><?php endif; ?></p>
            <?php endif; ?>
            <?php if (setting_on('shiprocket_enabled') && !in_array($o['status'], ['pending_payment', 'failed', 'cancelled', 'refunded'], true)): ?>
                <form method="post" action="/orders/<?= (int) $o['id'] ?>/shiprocket" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:14px">
                    <?= csrf_field() ?>
                    <?php if (!$o['shiprocket_order_id']): ?>
                        <button class="btn"><?= icon('send') ?> Create Shiprocket shipment</button><label class="check small"><input type="checkbox" name="mark_packed" value="1" checked> Also mark as packed</label>
                    <?php elseif (!$o['awb']): ?>
                        <span class="muted small">In Shiprocket as #<?= e($o['shiprocket_order_id']) ?></span><button class="btn">Assign courier (AWB)</button>
                    <?php else: ?>
                        <span class="muted small">Shiprocket order #<?= e($o['shiprocket_order_id']) ?> · shipment #<?= e($o['shipment_id']) ?></span><a class="btn secondary sm" href="https://app.shiprocket.in/seller/orders/details/<?= e($o['shiprocket_order_id']) ?>" target="_blank" rel="noopener">Open in Shiprocket</a>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
            <details <?= $o['awb'] ? '' : (setting_on('shiprocket_enabled') ? '' : 'open') ?>><summary class="small" style="cursor:pointer;color:var(--accent)">Enter tracking manually</summary>
                <form method="post" action="/orders/<?= (int) $o['id'] ?>/tracking" class="form-grid" style="margin-top:10px">
                    <?= csrf_field() ?>
                    <label class="f">Courier<input name="courier" value="<?= e($o['courier']) ?>" placeholder="Delhivery, Blue Dart…"></label>
                    <label class="f">AWB / tracking number<input name="awb" value="<?= e($o['awb']) ?>"></label>
                    <label class="f full">Tracking link <small>optional</small><input name="tracking_url" value="<?= e($o['tracking_url']) ?>" placeholder="https://"></label>
                    <label class="check"><input type="checkbox" name="mark_shipped" value="1" <?= $o['status'] === 'shipped' ? '' : 'checked' ?>> Mark as shipped &amp; email the customer</label>
                    <button class="btn secondary">Save tracking</button>
                </form>
            </details>
        </div>

        <div class="card pad">
            <h3>Timeline</h3>
            <form method="post" action="/orders/<?= (int) $o['id'] ?>/note" style="display:flex;gap:8px;margin-bottom:16px"><?= csrf_field() ?><input name="note" placeholder="Add a private note…"><button class="btn secondary">Add</button></form>
            <ul class="timeline"><?php foreach ($notes as $n): ?><li><?= e($n['note']) ?><small><?= e(nice_date($n['created_at'], 'j M Y, g:i a')) ?></small></li><?php endforeach; ?></ul>
        </div>
    </div>

    <div class="stack sticky-col">
        <div class="card pad">
            <h3>Customer</h3>
            <p style="margin:0"><strong><?= e($b['name'] ?? '') ?></strong><br><a class="link" href="mailto:<?= e($o['email']) ?>"><?= e($o['email']) ?></a><br><a class="link" href="tel:<?= e($o['phone']) ?>"><?= e($o['phone']) ?></a></p>
            <p class="small muted" style="margin:8px 0 0"><?= $customerOrders ?> order<?= $customerOrders === 1 ? '' : 's' ?> in total<?= $user ? ' · <a class="link" href="/customers/' . (int) $user['id'] . '">View account</a>' : ' · Guest' ?></p>
            <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap"><a class="btn secondary sm" href="<?= e($waLink) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> WhatsApp</a><a class="btn secondary sm" href="tel:<?= e($o['phone']) ?>"><?= icon('phone') ?> Call</a></div>
        </div>
        <div class="card pad">
            <h3>Ship to</h3>
            <p style="margin:0"><?= e($s['name'] ?? '') ?><br><?= e($s['address1'] ?? '') ?><?= !empty($s['address2']) ? '<br>' . e($s['address2']) : '' ?><br><?= e($s['city'] ?? '') ?>, <?= e($s['state'] ?? '') ?> <strong><?= e($s['pincode'] ?? '') ?></strong><br><?= e($s['phone'] ?? '') ?></p>
            <?php if ($s != $b): ?><p class="small muted" style="margin:10px 0 0">Billing: <?= e($b['name'] ?? '') ?>, <?= e($b['city'] ?? '') ?> <?= e($b['pincode'] ?? '') ?></p><?php endif; ?>
            <button class="btn plain sm" style="padding:0;margin-top:8px" data-copy="<?= e(implode(', ', array_filter([$s['name'] ?? '', $s['address1'] ?? '', $s['address2'] ?? '', $s['city'] ?? '', $s['state'] ?? '', $s['pincode'] ?? '', $s['phone'] ?? '']))) ?>"><?= icon('copy') ?> Copy address</button>
        </div>
        <form class="card pad fields" method="post" action="/orders/<?= (int) $o['id'] ?>/status">
            <?= csrf_field() ?>
            <h3 style="margin:0">Change status</h3>
            <select name="status"><?php foreach (order_statuses() as $k => $l): ?><option value="<?= $k ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
            <label class="check"><input type="checkbox" name="notify" value="1" checked> Email the customer (shipped, delivered, cancelled, refunded)</label>
            <button class="btn secondary">Update</button>
            <p class="help" style="margin:0">Cancelling or refunding puts the stock back. Refund the money itself from your PayU / Cashfree dashboard.</p>
        </form>
        <form method="post" action="/orders/<?= (int) $o['id'] ?>/resend"><?= csrf_field() ?><button class="btn plain sm"><?= icon('mail') ?> Re-send confirmation email</button></form>
    </div>
</div>
