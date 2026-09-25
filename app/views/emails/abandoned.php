<?php /** @var array $ac @var array $lines @var bool $second @var string $coupon @var string $link */
$rows = '';
foreach ($lines as $l) {
    $rows .= '<tr><td style="padding:8px 12px 8px 0"><img src="' . e(image_url($l['image'], 'sm')) . '" width="64" style="border-radius:10px;display:block"></td><td style="padding:8px 0">' . e($l['name']) . ($l['variation_label'] ? '<br><span style="color:#8A7B6E;font-size:13px">' . e($l['variation_label']) . '</span>' : '') . '</td><td style="text-align:right">' . money($l['total']) . '</td></tr>';
}
$name = explode(' ', (string) $ac['name'])[0];
$intro = $second
    ? 'Just a gentle nudge: your gift box is still waiting in your cart. Boxes are packed to order, so the sooner you check out, the sooner it’s on its way.'
    : 'Looks like you were halfway through picking a gift. We’ve saved your cart so you can finish in a tap.';
$couponHtml = $coupon ? '<p style="background:#F7F1EA;border-radius:12px;padding:14px 16px">Use code <strong>' . e($coupon) . '</strong> at checkout for a little something off.</p>' : '';
echo email_layout($second ? 'Still thinking it over?' : 'You left something lovely behind',
    '<p>Hi ' . e($name ?: 'there') . ',</p><p>' . $intro . '</p><table role="presentation" width="100%" style="margin:16px 0;font-size:14px">' . $rows . '</table>' . $couponHtml
    . '<p style="color:#8A7B6E;font-size:13px">Need help choosing? Reply to this email or WhatsApp us on ' . e(setting('store_phone')) . '.</p>', ['Return to your cart', $link]);
