<?php /** @var array $e */
$wa = preg_replace('/\D/', '', $e['phone']);
$reply = 'Hi ' . explode(' ', $e['name'])[0] . ",\n\nThank you for reaching out to " . setting('store_name') . '. ';
?>
<div class="page-head"><div><a class="back" href="/enquiries"><?= icon('arrow-left') ?> Enquiries</a><h1><?= e($e['name']) ?></h1><p><?= e(enquiry_types()[$e['type']] ?? '') ?> · <?= e(nice_date($e['created_at'], 'j M Y, g:i a')) ?></p></div>
    <div class="head-actions">
        <a class="btn" href="mailto:<?= e($e['email']) ?>?subject=<?= rawurlencode('Re: your enquiry – ' . setting('store_name')) ?>&body=<?= rawurlencode($reply) ?>"><?= icon('mail') ?> Reply by email</a>
        <?php if ($wa): ?><a class="btn secondary" target="_blank" rel="noopener" href="https://wa.me/<?= e(strlen($wa) === 10 ? '91' . $wa : $wa) ?>?text=<?= rawurlencode($reply) ?>"><?= icon('whatsapp') ?> WhatsApp</a><?php endif; ?>
    </div></div>
<div class="grid g-main">
    <div class="card pad">
        <dl class="kv" style="margin-bottom:18px">
            <dt>Email</dt><dd><a class="link" href="mailto:<?= e($e['email']) ?>"><?= e($e['email']) ?></a></dd>
            <?php foreach (['phone' => 'Phone', 'company' => 'Company', 'occasion' => $e['type'] === 'custom_box' ? 'For' : 'Occasion', 'quantity' => $e['type'] === 'custom_box' ? 'Box type' : 'Quantity', 'budget' => 'Budget', 'needed_by' => 'Needed by'] as $k => $l): if ($e[$k] === '') continue; ?>
                <dt><?= e($l) ?></dt><dd><?= e($k === 'needed_by' ? nice_date($e[$k]) : $e[$k]) ?></dd>
            <?php endforeach; ?>
        </dl>
        <p style="white-space:pre-line;font-size:15px;line-height:1.6"><?= e($e['message']) ?></p>
    </div>
    <form class="card pad fields sticky-col" method="post" action="/enquiries/<?= (int) $e['id'] ?>">
        <?= csrf_field() ?>
        <h3 style="margin:0">Status</h3>
        <div class="segmented"><?php foreach (['new' => 'New', 'replied' => 'Replied', 'won' => 'Won', 'closed' => 'Closed'] as $k => $l): ?><label><input type="radio" name="status" value="<?= $k ?>" <?= $e['status'] === $k ? 'checked' : '' ?>><span><?= $l ?></span></label><?php endforeach; ?></div>
        <label class="f">Private notes<textarea name="admin_note" rows="5" placeholder="Quote sent, follow up on…"><?= e($e['admin_note']) ?></textarea></label>
        <button class="btn">Save</button>
    </form>
</div>
