<?php
declare(strict_types=1);

const CART_MAX_QTY = 50;

function cart_raw(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_add(int $productId, ?int $variationId, int $qty): void
{
    $key = $productId . ':' . ($variationId ?: 0);
    $cart = cart_raw();
    $current = $cart[$key]['qty'] ?? 0;
    $cart[$key] = ['product_id' => $productId, 'variation_id' => $variationId ?: null,
        'qty' => min(CART_MAX_QTY, $current + max(1, $qty))];
    $_SESSION['cart'] = $cart;
}

function cart_set_qty(string $key, int $qty): void
{
    $cart = cart_raw();
    if (!isset($cart[$key])) {
        return;
    }
    if ($qty <= 0) {
        unset($cart[$key]);
    } else {
        $cart[$key]['qty'] = min(CART_MAX_QTY, $qty);
    }
    $_SESSION['cart'] = $cart;
}

function cart_clear(): void
{
    unset($_SESSION['cart'], $_SESSION['coupon'], $_SESSION['abandoned_token']);
}

function cart_count(): int
{
    return array_sum(array_column(cart_raw(), 'qty'));
}

/**
 * Resolve the session cart into priced lines, always from the database
 * (never trust prices from the browser). Drops items that no longer exist.
 */
function cart_lines(?array $raw = null): array
{
    $raw ??= cart_raw();
    $lines = [];
    foreach ($raw as $key => $item) {
        $p = one("SELECT * FROM products WHERE id = ? AND status = 'published'", [(int) $item['product_id']]);
        if (!$p) {
            continue;
        }
        $v = null;
        if ($p['type'] === 'variable') {
            $v = $item['variation_id']
                ? one('SELECT * FROM variations WHERE id = ? AND product_id = ? AND enabled = 1', [(int) $item['variation_id'], $p['id']])
                : null;
            if (!$v) {
                continue;
            }
        }
        $src = $v ?? $p;
        $pricing = effective_price($src);
        if ($pricing['price'] === null) {
            continue;
        }
        $image = null;
        if ($v) {
            $image = val('SELECT path FROM product_images WHERE variation_id = ? ORDER BY sort_order, id LIMIT 1', [$v['id']]);
        }
        $image = $image ?: val('SELECT path FROM product_images WHERE product_id = ? AND variation_id IS NULL ORDER BY sort_order, id LIMIT 1', [$p['id']]);
        $qty = (int) $item['qty'];
        $maxQty = $src['manage_stock'] && $src['stock_qty'] !== null ? max(0, (int) $src['stock_qty']) : CART_MAX_QTY;
        $lines[] = [
            'key' => $key,
            'product' => $p,
            'variation' => $v,
            'name' => $p['name'],
            'variation_label' => $v ? variation_label(json_arr($v['attributes_json'])) : '',
            'sku' => $src['sku'] ?: $p['sku'],
            'image' => $image,
            'url' => product_url($p),
            'qty' => $qty,
            'max_qty' => $maxQty,
            'in_stock' => $src['stock_status'] !== 'outofstock' && $qty <= $maxQty,
            'price' => $pricing['price'],
            'regular' => $pricing['regular'],
            'total' => round($pricing['price'] * $qty, 2),
            'weight' => (float) ($src['weight'] ?: $p['weight'] ?: 0.5),
            'length' => (float) ($src['length'] ?: $p['length'] ?: 10),
            'width' => (float) ($src['width'] ?: $p['width'] ?: 10),
            'height' => (float) ($src['height'] ?: $p['height'] ?: 10),
        ];
    }
    return $lines;
}

function coupon_check(string $code, float $subtotal): array
{
    $c = one('SELECT * FROM coupons WHERE code = ? AND active = 1', [strtoupper(trim($code))]);
    if (!$c) {
        return ['ok' => false, 'error' => 'This coupon code is not valid.'];
    }
    if ($c['expires_at'] && $c['expires_at'] < now()) {
        return ['ok' => false, 'error' => 'This coupon has expired.'];
    }
    if ($c['max_uses'] !== null && (int) $c['used'] >= (int) $c['max_uses']) {
        return ['ok' => false, 'error' => 'This coupon has reached its usage limit.'];
    }
    if ($subtotal < (float) $c['min_subtotal']) {
        return ['ok' => false, 'error' => 'Add ' . money((float) $c['min_subtotal'] - $subtotal) . ' more to use this coupon.'];
    }
    $discount = $c['type'] === 'percent' ? round($subtotal * (float) $c['amount'] / 100, 2) : (float) $c['amount'];
    return ['ok' => true, 'coupon' => $c, 'discount' => min($subtotal, $discount)];
}

function shipping_for(float $amount): float
{
    $free = (float) setting('shipping_free_above');
    if ($free > 0 && $amount >= $free) {
        return 0.0;
    }
    return (float) setting('shipping_flat');
}

function cart_totals(array $lines, ?string $couponCode = null, string $paymentMethod = ''): array
{
    $subtotal = round(array_sum(array_column($lines, 'total')), 2);
    $discount = 0.0;
    $couponError = null;
    $couponCode = $couponCode ?? ($_SESSION['coupon'] ?? null);
    if ($couponCode) {
        $check = coupon_check($couponCode, $subtotal);
        if ($check['ok']) {
            $discount = $check['discount'];
        } else {
            $couponError = $check['error'];
            $couponCode = null;
        }
    }
    $afterDiscount = max(0, $subtotal - $discount);
    $shipping = $lines ? shipping_for($afterDiscount) : 0.0;
    $fee = $paymentMethod === 'cod' ? (float) setting('cod_fee') : 0.0;
    return [
        'subtotal' => $subtotal,
        'discount' => $discount,
        'coupon' => $couponCode,
        'coupon_error' => $couponError,
        'shipping' => $shipping,
        'fee' => $fee,
        'total' => round($afterDiscount + $shipping + $fee, 2),
        'free_shipping_gap' => ((float) setting('shipping_free_above') > 0 && $shipping > 0)
            ? (float) setting('shipping_free_above') - $afterDiscount : 0,
    ];
}

/** Payment methods currently enabled in admin, for a given order total. */
function payment_methods(float $total = 0): array
{
    $m = [];
    if (setting_on('payu_enabled') && setting('payu_key') && setting('payu_salt')) {
        $m['payu'] = setting('payu_title');
    }
    if (setting_on('cashfree_enabled') && setting('cashfree_app_id') && setting('cashfree_secret')) {
        $m['cashfree'] = setting('cashfree_title');
    }
    $codMax = (float) setting('cod_max');
    if (setting_on('cod_enabled') && ($codMax <= 0 || $total <= $codMax)) {
        $fee = (float) setting('cod_fee');
        $m['cod'] = 'Cash on Delivery' . ($fee > 0 ? ' (+' . money($fee) . ')' : '');
    }
    return $m;
}

/** Save the in-progress checkout so abandoned-cart reminders can be sent. */
function abandoned_capture(string $email, string $name = '', string $phone = ''): void
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !cart_raw()) {
        return;
    }
    $lines = cart_lines();
    $totals = cart_totals($lines);
    $token = $_SESSION['abandoned_token'] ?? null;
    $data = ['email' => strtolower($email), 'name' => $name, 'phone' => $phone,
        'cart_json' => json_encode(cart_raw()), 'total' => $totals['total'], 'updated_at' => now()];
    if ($token && one('SELECT id FROM abandoned_carts WHERE token = ?', [$token])) {
        update('abandoned_carts', $data, "token = ? AND status <> 'recovered'", [$token]);
    } else {
        $token = random_token(16);
        $_SESSION['abandoned_token'] = $token;
        insert('abandoned_carts', $data + ['token' => $token, 'status' => 'open', 'created_at' => now()]);
    }
}
