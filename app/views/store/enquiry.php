<?php
/** @var string $type contact | corporate | custom_box */
$sent = input('sent') === '1';
$copy = [
    'contact' => ['Get in touch', 'Questions about an order, a custom box or bulk gifting? Send us a note or just call. We usually reply within a few hours.', 'Send message'],
    'corporate' => ['Corporate & bulk gifting', 'Diwali hampers, new-joiner kits, client thank-yous and event giveaways. We handle curation, branding and delivery for orders of 10 to 1,000 boxes.', 'Request a quote'],
    'custom_box' => ['Design your own box', 'Tell us who it’s for, what they love and roughly what you’d like to spend. We’ll put together a box around your idea and get back to you within one working day with options and a price.', 'Send my idea'],
][$type];
?>
<section class="page-hero">
    <div class="wrap narrow center">
        <h1><?= e($copy[0]) ?></h1>
        <p class="lead"><?= e($copy[1]) ?></p>
    </div>
</section>
<section class="section section-tight">
    <div class="wrap enquiry-grid">
        <?php if ($sent): ?>
            <div class="panel center thanks"><span class="big-icon"><?= icon('check') ?></span><h2>Thank you!</h2><p>We’ve received your message and will be in touch soon.</p><a class="btn btn-ghost" href="/shop/">Browse gift boxes</a></div>
        <?php else: ?>
        <form method="post" class="panel form-grid">
            <?= csrf_field() ?>
            <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
            <label>Your name<input name="name" required value="<?= e(old('name', customer()['name'] ?? '')) ?>"></label>
            <label>Email<input type="email" name="email" required value="<?= e(old('email', customer()['email'] ?? '')) ?>"></label>
            <label>Phone / WhatsApp<input type="tel" name="phone" value="<?= e(old('phone')) ?>"></label>
            <?php if ($type === 'corporate'): ?>
                <label>Company<input name="company" value="<?= e(old('company')) ?>"></label>
                <label>How many boxes?<select name="quantity"><?php foreach (['10–25', '25–50', '50–100', '100–250', '250+'] as $q): ?><option<?= old('quantity') === $q ? ' selected' : '' ?>><?= $q ?></option><?php endforeach; ?></select></label>
                <label>Budget per box<select name="budget"><?php foreach (['Under ₹2,000', '₹2,000–₹4,000', '₹4,000–₹7,000', '₹7,000+', 'Not sure yet'] as $b): ?><option<?= old('budget') === $b ? ' selected' : '' ?>><?= $b ?></option><?php endforeach; ?></select></label>
                <label>Occasion<input name="occasion" placeholder="Diwali, onboarding, client gifting…" value="<?= e(old('occasion')) ?>"></label>
                <label>Needed by<input type="date" name="needed_by" value="<?= e(old('needed_by')) ?>"></label>
                <label class="full">Tell us more<textarea name="message" rows="5" required placeholder="Branding on the box? Items you’d like included? Delivery to one address or many?"><?= e(old('message')) ?></textarea></label>
            <?php elseif ($type === 'custom_box'): ?>
                <label>Who is it for?<input name="occasion" placeholder="My sister’s 30th, a new mum, my boss…" value="<?= e(old('occasion')) ?>"></label>
                <label>Budget<select name="budget"><?php foreach (['₹2,000–₹4,000', '₹4,000–₹6,000', '₹6,000–₹10,000', '₹10,000+', 'Not sure yet'] as $b): ?><option<?= old('budget') === $b ? ' selected' : '' ?>><?= $b ?></option><?php endforeach; ?></select></label>
                <label>Box type<select name="quantity"><option>No preference</option><?php foreach (box_types() as $bt): ?><option<?= old('quantity') === $bt['name'] ? ' selected' : '' ?>><?= e($bt['name']) ?></option><?php endforeach; ?></select></label>
                <label>Needed by<input type="date" name="needed_by" value="<?= e(old('needed_by')) ?>"></label>
                <label class="full">Your idea<textarea name="message" rows="6" required placeholder="What do they love? Any items you definitely want in (or out)? Colours, themes, a message you’d like printed…"><?= e(old('message')) ?></textarea></label>
            <?php else: ?>
                <label>Subject<select name="occasion"><?php foreach (['General question', 'About my order', 'Custom box', 'Bulk / corporate order', 'Something else'] as $s): ?><option<?= old('occasion') === $s ? ' selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></label>
                <label class="full">Message<textarea name="message" rows="6" required><?= e(old('message')) ?></textarea></label>
            <?php endif; ?>
            <button class="btn btn-lg"><?= e($copy[2]) ?> <?= icon('send') ?></button>
        </form>
        <?php endif; ?>
        <aside class="contact-aside">
            <div class="panel">
                <h3>Talk to us directly</h3>
                <p class="with-icon"><?= icon('phone') ?><a href="tel:<?= e(preg_replace('/[^\d+]/', '', (string) setting('store_phone'))) ?>"><?= e(setting('store_phone')) ?></a></p>
                <?php if ($wa = preg_replace('/\D/', '', (string) setting('whatsapp_number'))): ?><p class="with-icon"><?= icon('whatsapp') ?><a href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener">Chat on WhatsApp</a></p><?php endif; ?>
                <p class="with-icon"><?= icon('mail') ?><a href="mailto:<?= e(setting('store_email')) ?>"><?= e(setting('store_email')) ?></a></p>
                <p class="with-icon"><?= icon('pin') ?><span><?= nl2br(e(setting('store_address'))) ?></span></p>
                <p class="with-icon"><?= icon('clock') ?><span><?= nl2br(e(setting('store_hours'))) ?></span></p>
            </div>
            <?php if ($type !== 'contact'): ?>
                <div class="panel tint">
                    <h3>How it works</h3>
                    <ol class="steps">
                        <li>You share the brief</li>
                        <li>We send options and a price within a day</li>
                        <li>You approve, we pack and ship</li>
                    </ol>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</section>
