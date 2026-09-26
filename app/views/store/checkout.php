<?php
/** @var array $lines @var array $totals @var array $methods @var array $prefill */
$v = fn($k) => old($k, $prefill[$k] ?? '');
$minDate = date('Y-m-d', strtotime('+' . (int) setting('delivery_min_days') . ' days'));
$maxDate = date('Y-m-d', strtotime('+' . max((int) setting('delivery_min_days') + 1, (int) setting('delivery_max_days')) . ' days'));
$states = indian_states();
$firstMethod = array_key_first($methods);
$chosen = old('payment_method', $firstMethod);
?>
<section class="section section-tight checkout">
    <div class="wrap">
        <a href="/cart/" class="back-link"><?= icon('arrow-left') ?> Back to cart</a>
        <h1>Checkout</h1>
        <?php if (!$methods): ?>
            <div class="notice warn">Online payments are being set up. Please contact us on <?= e(setting('store_phone')) ?> to place your order.</div>
        <?php endif; ?>
        <form method="post" action="/checkout/" class="checkout-grid" data-checkout>
            <?= csrf_field() ?>
            <div class="checkout-main">
                <fieldset class="panel">
                    <legend><span class="step">1</span> Contact</legend>
                    <div class="form-grid">
                        <label class="full">Email<input type="email" name="email" required autocomplete="email" value="<?= e($v('email')) ?>" data-capture></label>
                        <label>Full name<input name="name" required autocomplete="name" value="<?= e($v('name')) ?>" data-capture></label>
                        <label>Mobile number<input type="tel" name="phone" required autocomplete="tel" inputmode="tel" placeholder="10-digit mobile" value="<?= e($v('phone')) ?>" data-capture></label>
                    </div>
                    <?php if (!customer()): ?>
                        <p class="small muted">Have an account? <a href="/my-account/?next=/checkout/">Log in</a><?php if (google_login_ready()): ?> or <a href="/auth/google/?next=/checkout/">continue with Google</a><?php endif; ?> for faster checkout — or just carry on as a guest.</p>
                    <?php endif; ?>
                </fieldset>

                <fieldset class="panel">
                    <legend><span class="step">2</span> Billing address</legend>
                    <div class="form-grid">
                        <label class="full">Address<input name="address1" required autocomplete="address-line1" placeholder="House / flat, street" value="<?= e($v('address1')) ?>"></label>
                        <label class="full">Area / landmark <em>(optional)</em><input name="address2" autocomplete="address-line2" value="<?= e($v('address2')) ?>"></label>
                        <label>City<input name="city" required autocomplete="address-level2" value="<?= e($v('city')) ?>"></label>
                        <label>Pincode<input name="pincode" required inputmode="numeric" maxlength="6" pattern="\d{6}" autocomplete="postal-code" value="<?= e($v('pincode')) ?>"></label>
                        <label class="full">State
                            <select name="state" required autocomplete="address-level1">
                                <?php foreach ($states as $s): ?><option<?= $v('state') === $s ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <label class="check"><input type="checkbox" name="ship_different" value="1" data-toggle="#ship-block" <?= old('ship_different') ? 'checked' : '' ?>> <span>Send this gift to a different address</span></label>
                    <div id="ship-block" class="form-grid sub-panel" <?= old('ship_different') ? '' : 'hidden' ?>>
                        <label>Recipient’s name<input name="ship_name" value="<?= e(old('ship_name')) ?>"></label>
                        <label>Recipient’s mobile<input type="tel" name="ship_phone" value="<?= e(old('ship_phone')) ?>"></label>
                        <label class="full">Address<input name="ship_address1" value="<?= e(old('ship_address1')) ?>"></label>
                        <label class="full">Area / landmark<input name="ship_address2" value="<?= e(old('ship_address2')) ?>"></label>
                        <label>City<input name="ship_city" value="<?= e(old('ship_city')) ?>"></label>
                        <label>Pincode<input name="ship_pincode" inputmode="numeric" maxlength="6" value="<?= e(old('ship_pincode')) ?>"></label>
                        <label class="full">State<select name="ship_state"><?php foreach ($states as $s): ?><option<?= old('ship_state', 'Maharashtra') === $s ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></label>
                    </div>
                </fieldset>

                <?php if (setting_on('gift_message_enabled') || setting_on('delivery_date_enabled')): ?>
                <fieldset class="panel">
                    <legend><span class="step">3</span> Make it personal</legend>
                    <div class="form-grid">
                        <?php if (setting_on('gift_message_enabled')): ?>
                            <label class="full">Gift message <em>(printed on a card inside the box)</em>
                                <textarea name="gift_message" rows="3" maxlength="300" placeholder="Happy birthday, Riya! Here’s to another year of chaos and cake. – Love, Arjun" data-count><?= e(old('gift_message')) ?></textarea>
                                <small class="muted" data-counter>0 / 300</small>
                            </label>
                        <?php endif; ?>
                        <?php if (setting_on('delivery_date_enabled')): ?>
                            <label>Preferred delivery date <em>(optional)</em><input type="date" name="delivery_date" min="<?= $minDate ?>" max="<?= $maxDate ?>" value="<?= e(old('delivery_date')) ?>"></label>
                            <p class="small muted align-end">Boxes are packed to order, so the earliest date we can promise is <?= e(nice_date($minDate, 'l, j M')) ?>.</p>
                        <?php endif; ?>
                        <label class="full">Order notes <em>(optional)</em><textarea name="customer_note" rows="2" maxlength="500" placeholder="Anything else we should know?"><?= e(old('customer_note')) ?></textarea></label>
                    </div>
                </fieldset>
                <?php endif; ?>

                <fieldset class="panel">
                    <legend><span class="step"><?= setting_on('gift_message_enabled') || setting_on('delivery_date_enabled') ? 4 : 3 ?></span> Payment</legend>
                    <div class="pay-options">
                        <?php foreach ($methods as $key => $label): ?>
                            <label class="pay-option"><input type="radio" name="payment_method" value="<?= e($key) ?>" <?= $chosen === $key ? 'checked' : '' ?> required><span><?= icon($key === 'cod' ? 'truck' : 'card') ?><?= e($label) ?></span></label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!customer()): ?>
                        <label class="check"><input type="checkbox" name="create_account" value="1" data-toggle="#pw-block" <?= old('create_account') ? 'checked' : '' ?>> <span>Create an account to track orders and check out faster next time</span></label>
                        <div id="pw-block" class="sub-panel" <?= old('create_account') ? '' : 'hidden' ?>><label>Choose a password<input type="password" name="password" minlength="8" autocomplete="new-password"></label></div>
                    <?php endif; ?>
                </fieldset>
            </div>

            <aside class="summary sticky">
                <h2>Your order</h2>
                <ul class="mini-lines">
                    <?php foreach ($lines as $l): ?>
                        <li><span class="thumb-wrap"><img src="<?= e(image_url($l['image'], 'sm')) ?>" alt="" width="56" height="70"><b><?= $l['qty'] ?></b></span><span><?= e($l['name']) ?><?php if ($l['variation_label']): ?><small><?= e($l['variation_label']) ?></small><?php endif; ?></span><strong><?= money($l['total']) ?></strong></li>
                    <?php endforeach; ?>
                </ul>
                <div class="row"><span>Subtotal</span><span><?= money($totals['subtotal']) ?></span></div>
                <?php if ($totals['discount'] > 0): ?><div class="row good"><span>Coupon <?= e($totals['coupon']) ?></span><span>−<?= money($totals['discount']) ?></span></div><?php endif; ?>
                <div class="row"><span>Delivery</span><span><?= $totals['shipping'] > 0 ? money($totals['shipping']) : 'Free' ?></span></div>
                <?php if ((float) setting('cod_fee') > 0 && isset($methods['cod'])): ?><div class="row small muted" data-cod-fee hidden><span>COD fee</span><span><?= money((float) setting('cod_fee')) ?></span></div><?php endif; ?>
                <div class="row total"><span>Total</span><span data-total="<?= e((string) $totals['total']) ?>" data-cod="<?= e((string) setting('cod_fee')) ?>"><?= money($totals['total']) ?></span></div>
                <button class="btn btn-lg btn-block" type="submit" <?= $methods ? '' : 'disabled' ?>>Place order <?= icon('arrow') ?></button>
                <p class="small muted center">By placing your order you agree to our <a href="/terms-conditions/">terms</a> and <a href="/refund_returns/">refund policy</a>.</p>
            </aside>
        </form>
    </div>
</section>
