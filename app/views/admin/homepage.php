<?php /** @var array $h @var array $d @var array $cats */
$featured = all("SELECT id, name, featured FROM products WHERE status = 'published' ORDER BY featured DESC, name");
$t = fn($k, $label, $rows = 0, $help = '') => '<label class="f">' . e($label) . ($help ? ' <small>' . e($help) . '</small>' : '') . ($rows ? '<textarea name="' . $k . '" rows="' . $rows . '" placeholder="' . e($d[$k]) . '">' . e($h[$k]) . '</textarea>' : '<input name="' . $k . '" value="' . e($h[$k]) . '" placeholder="' . e($d[$k]) . '">') . '</label>';
?>
<form method="post" action="/homepage" data-savebar>
<?= csrf_field() ?>
<div class="page-head"><div><h1>Homepage</h1><p>Edit the words and photos on your homepage. Empty fields fall back to the text shown in grey.</p></div>
    <div class="head-actions"><a class="btn secondary" href="<?= e(site_url()) ?>" target="_blank" rel="noopener"><?= icon('eye') ?> View homepage</a><button class="btn">Save</button></div></div>
<div class="grid g-main">
    <div class="stack">
        <div class="card pad fields"><h3 style="margin:0">Hero (top of the page)</h3>
            <?= $t('hero_eyebrow', 'Small line above the heading') ?><?= $t('hero_title', 'Main heading', 2) ?><?= $t('hero_text', 'Supporting text', 3) ?>
            <div class="form-grid"><?= $t('hero_button', 'Button text') ?><?= $t('hero_link', 'Button link') ?></div>
            <div class="form-grid"><div class="f">Main photo<?= image_field('hero_image', $h['hero_image'] ?: null) ?><small>Tall photo works best (e.g. 1200 × 1500)</small></div><div class="f">Small overlapping photo<?= image_field('hero_image_2', $h['hero_image_2'] ?: null) ?></div></div>
        </div>
        <div class="card pad fields"><h3 style="margin:0">About your boxes</h3>
            <?= $t('intro_title', 'Heading') ?><?= $t('intro_text', 'Text', 6, 'Leave an empty line between paragraphs') ?>
            <p class="group-title" style="margin:10px 0 0">Four highlights</p>
            <?php foreach (array_pad($h['features'], 4, ['title' => '', 'text' => '']) as $i => $f): ?>
                <div class="form-grid"><label class="f">Title<input name="feature_title[]" value="<?= e($f['title']) ?>"></label><label class="f">Text<input name="feature_text[]" value="<?= e($f['text']) ?>"></label></div>
            <?php endforeach; ?>
        </div>
        <div class="card pad fields"><h3 style="margin:0">Sections</h3>
            <div class="form-grid"><?= $t('collections_title', 'Occasions heading') ?><?= $t('collections_text', 'Occasions text') ?><?= $t('popular_title', 'Bestsellers heading') ?><?= $t('popular_text', 'Bestsellers text') ?></div>
        </div>
        <div class="card pad fields"><h3 style="margin:0">Feature banner</h3>
            <?= $t('promo_title', 'Heading') ?><?= $t('promo_text', 'Text', 3) ?>
            <div class="form-grid"><?= $t('promo_button', 'Button text') ?><label class="f">Button link<select name="promo_link"><?php foreach (array_merge([['name' => 'All products', 'url' => '/shop/']], array_map(fn($c) => ['name' => $c['name'], 'url' => category_url($c)], $cats), [['name' => 'Design your own box', 'url' => '/custom-box/'], ['name' => 'Corporate gifting', 'url' => '/corporate-gifting/']]) as $o): ?><option value="<?= e($o['url']) ?>" <?= $h['promo_link'] === $o['url'] ? 'selected' : '' ?>><?= e($o['name']) ?></option><?php endforeach; ?></select></label></div>
            <div class="f">Banner photo<?= image_field('promo_image', $h['promo_image'] ?: null) ?></div>
        </div>
        <div class="card pad fields"><h3 style="margin:0">Custom &amp; corporate cards</h3>
            <div class="form-grid"><?= $t('custom_title', 'Custom box heading') ?><?= $t('corporate_title', 'Corporate heading') ?><?= $t('custom_text', 'Custom box text', 3) ?><?= $t('corporate_text', 'Corporate text', 3) ?></div>
        </div>
        <div class="card pad fields"><h3 style="margin:0">FAQ <span class="dim small" style="font-weight:400">— also shown to Google as FAQ results</span></h3>
            <?php foreach (array_pad($h['faq'], count($h['faq']) + 2, ['q' => '', 'a' => '']) as $f): ?>
                <div class="fields" style="gap:6px;padding-bottom:12px;border-bottom:1px solid var(--line-2)"><input name="faq_q[]" value="<?= e($f['q']) ?>" placeholder="Question"><textarea name="faq_a[]" rows="2" placeholder="Answer"><?= e($f['a']) ?></textarea></div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="stack sticky-col">
        <div class="card"><div class="card-head"><h3>Bestsellers on homepage</h3></div><p class="help" style="padding:4px 22px 0">Switch on the boxes to feature.</p>
            <div class="group" style="box-shadow:none;border-radius:0">
                <?php foreach ($featured as $p): ?><div class="row"><span class="row-label" style="font-weight:400"><?= e($p['name']) ?></span><input type="hidden" name="featured[<?= (int) $p['id'] ?>]" value="0"><span class="switch sm"><input type="checkbox" name="featured[<?= (int) $p['id'] ?>]" value="1" <?= $p['featured'] ? 'checked' : '' ?>><span></span></span></div><?php endforeach; ?>
                <?php if (!$featured): ?><p class="muted" style="padding:0 22px 16px">Publish products first.</p><?php endif; ?>
            </div>
        </div>
        <div class="card pad"><h3>Announcement bar</h3><p class="muted small">The thin strip at the very top of every page.</p><a class="btn secondary sm" href="/settings/store">Edit in Store settings</a></div>
    </div>
</div>
</form>
