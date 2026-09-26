<?php /** @var array $h @var array $d @var array $cats */
$featured = all("SELECT id, name, featured FROM products WHERE status = 'published' ORDER BY featured DESC, name");
$t = fn($k, $label, $rows = 0, $help = '') => '<label class="f">' . e($label) . ($help ? ' <small>' . e($help) . '</small>' : '')
    . ($rows ? '<textarea name="' . $k . '" rows="' . $rows . '" placeholder="' . e($d[$k] ?? '') . '">' . e($h[$k] ?? '') . '</textarea>'
        : '<input name="' . $k . '" value="' . e($h[$k] ?? '') . '" placeholder="' . e($d[$k] ?? '') . '">') . '</label>';
$links = array_merge([['name' => 'All products', 'url' => '/shop/']], array_map(fn($c) => ['name' => $c['name'], 'url' => category_url($c)], $cats),
    [['name' => 'Design your own box', 'url' => '/custom-box/'], ['name' => 'Corporate gifting', 'url' => '/corporate-gifting/']]);
$linkSelect = function (string $k) use ($h, $links) {
    $out = '<select name="' . $k . '">';
    foreach ($links as $o) {
        $out .= '<option value="' . e($o['url']) . '"' . (($h[$k] ?? '') === $o['url'] ? ' selected' : '') . '>' . e($o['name']) . '</option>';
    }
    return $out . '</select>';
};
?>
<form method="post" action="/homepage" data-savebar>
<?= csrf_field() ?>
<div class="page-head"><div><h1>Homepage</h1><p>Edit the words, photos and sections of your homepage. Empty fields use the grey text.</p></div>
    <div class="head-actions"><a class="btn secondary" href="<?= e(site_url()) ?>" target="_blank" rel="noopener"><?= icon('eye') ?> View homepage</a><button class="btn">Save</button></div></div>
<div class="grid g-main">
    <div class="stack">
        <div class="card pad fields"><h3 style="margin:0">Hero (top of the page)</h3>
            <?= $t('hero_eyebrow', 'Small line above the heading') ?><?= $t('hero_title', 'Main heading', 2) ?><?= $t('hero_text', 'Supporting text', 3) ?>
            <div class="form-grid"><?= $t('hero_button', 'Button text') ?><label class="f">Button goes to<?= $linkSelect('hero_link') ?></label></div>
            <div class="form-grid"><div class="f">Large background photo<?= image_field('hero_image', $h['hero_image'] ?: null) ?><small>It softly fades into the text. Landscape or portrait both work.</small></div><div class="f">Small tilted photo<?= image_field('hero_image_2', $h['hero_image_2'] ?: null) ?></div></div>
        </div>
        <div class="card pad fields"><h3 style="margin:0">Scrolling ribbon</h3><?= $t('marquee', 'Words separated by ·', 2) ?></div>
        <div class="card pad fields"><h3 style="margin:0">Our story</h3>
            <div class="form-grid"><?= $t('intro_eyebrow', 'Small line') ?><?= $t('intro_title', 'Heading') ?></div>
            <?= $t('intro_text', 'Text', 6, 'Leave an empty line between paragraphs') ?>
            <div class="f">Photo<?= image_field('intro_image', $h['intro_image'] ?: null) ?></div>
            <p class="group-title" style="margin:10px 0 0">Four highlights</p>
            <?php foreach (array_pad($h['features'], 4, ['title' => '', 'text' => '']) as $f): ?>
                <div class="form-grid"><label class="f">Title<input name="feature_title[]" value="<?= e($f['title']) ?>"></label><label class="f">Text<input name="feature_text[]" value="<?= e($f['text']) ?>"></label></div>
            <?php endforeach; ?>
        </div>
        <div class="card pad fields"><h3 style="margin:0">Section headings</h3>
            <div class="form-grid">
                <?= $t('collections_title', 'Occasions heading') ?><?= $t('collections_text', 'Occasions text') ?>
                <?= $t('boxes_title', 'Wood showcase heading') ?><?= $t('boxes_text', 'Wood showcase text') ?>
                <?= $t('popular_title', 'Bestsellers heading') ?><?= $t('popular_text', 'Bestsellers text') ?>
                <?= $t('reviews_title', 'Reviews heading') ?><?= $t('faq_title', 'FAQ heading') ?>
            </div>
            <p class="help" style="margin:0">Wood colours and descriptions come from <a class="link" href="/box-types">Box types</a>. Reviews appear automatically once customers leave 4★ or 5★ reviews.</p>
        </div>
        <div class="card pad fields"><h3 style="margin:0">Feature banner</h3>
            <div class="form-grid"><?= $t('promo_eyebrow', 'Small line') ?><?= $t('promo_title', 'Heading') ?></div>
            <?= $t('promo_text', 'Text', 3) ?>
            <div class="form-grid"><?= $t('promo_button', 'Button text') ?><label class="f">Button goes to<?= $linkSelect('promo_link') ?></label></div>
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
        <div class="group">
            <div class="row"><span class="row-label">Sections on the homepage</span></div>
            <?php foreach (home_sections() as $k => $label): ?>
                <div class="row"><span class="row-label" style="font-weight:400"><?= e($label) ?></span><input type="hidden" name="<?= $k ?>" value="0"><span class="switch sm"><input type="checkbox" name="<?= $k ?>" value="1" <?= (string) ($h[$k] ?? '1') !== '0' ? 'checked' : '' ?>><span></span></span></div>
            <?php endforeach; ?>
        </div>
        <div class="group">
            <div class="row"><span class="row-label">Most loved boxes<small>Switch on the boxes to feature</small></span></div>
            <?php foreach ($featured as $p): ?><div class="row"><span class="row-label" style="font-weight:400"><?= e($p['name']) ?></span><input type="hidden" name="featured[<?= (int) $p['id'] ?>]" value="0"><span class="switch sm"><input type="checkbox" name="featured[<?= (int) $p['id'] ?>]" value="1" <?= $p['featured'] ? 'checked' : '' ?>><span></span></span></div><?php endforeach; ?>
            <?php if (!$featured): ?><div class="row"><span class="muted">Publish products first.</span></div><?php endif; ?>
        </div>
        <div class="card pad"><h3>Announcement bar</h3><p class="muted small">The thin strip at the very top of every page.</p><a class="btn secondary sm" href="/settings/store">Edit in Store settings</a></div>
    </div>
</div>
</form>
