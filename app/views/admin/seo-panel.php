<?php
/**
 * Rank Math–style SEO panel, shared by products, pages, blog posts and categories.
 * @var array $row  the item being edited
 * @var string $kind product|page|post|category
 * @var string $base URL path before the slug, e.g. /product/
 * @var string $nameField form field holding the item name/title
 * @var string $bodyField form field holding the main content
 * @var bool $flat render without card chrome (inside another card)
 */
$flat ??= false;
$v = fn($k) => (string) ($row[$k] ?? '');
$robots = array_filter(explode(',', $v('robots')));
$host = parse_url(site_url(), PHP_URL_HOST);
$uid = 'seo' . substr(md5($kind), 0, 6);
?>
<div class="<?= $flat ? 'seo-box flat' : 'card pad seo-box' ?>" data-seo data-kind="<?= e($kind) ?>" data-base="<?= e($base) ?>" data-host="<?= e($host) ?>"
     data-store="<?= e(setting('store_name')) ?>" data-sep="<?= e(setting('seo_separator') ?: '|') ?>" data-name-field="<?= e($nameField) ?>" data-body-field="<?= e($bodyField) ?>">
    <div class="seo-head">
        <div><h3>SEO</h3><p class="help">How this appears on Google and when shared on WhatsApp, Instagram or Facebook.</p></div>
        <span class="seo-score" data-seo-score title="SEO score"><b>0</b><small>/100</small></span>
    </div>
    <div class="segmented seo-tabs" role="tablist">
        <button type="button" class="on" data-seo-tab="general">General</button>
        <button type="button" data-seo-tab="social">Social</button>
        <button type="button" data-seo-tab="advanced">Advanced</button>
        <button type="button" data-seo-tab="schema">Schema</button>
    </div>

    <div class="seo-pane" data-pane="general">
        <div class="serp-wrap">
            <div class="serp-mode segmented"><button type="button" class="on" data-serp="desktop"><?= icon('monitor') ?> Desktop</button><button type="button" data-serp="mobile"><?= icon('mobile') ?> Mobile</button></div>
            <div class="serp" data-serp-box><div class="site"><span class="fav"><img src="<?= e(setting('favicon') ? image_url(setting('favicon'), 'sm') : '/assets/favicon.png') ?>" alt=""></span><span><?= e(setting('store_name')) ?><small></small></span></div><div class="t"></div><div class="d"></div></div>
        </div>
        <div class="fields">
            <label class="f">Focus keyword <small>the main phrase people type into Google</small><input name="focus_keyword" value="<?= e($v('focus_keyword')) ?>" placeholder="<?= $kind === 'product' ? 'birthday gift box for her' : ($kind === 'category' ? 'anniversary gift hampers' : 'corporate diwali gifts') ?>"></label>
            <label class="f">Secondary keywords <small>optional, comma separated</small><input name="secondary_keywords" value="<?= e($v('secondary_keywords')) ?>" placeholder="wooden gift hamper, gift box india"></label>
            <label class="f">SEO title<input name="seo_title" value="<?= e($v('seo_title')) ?>" data-count="60"></label>
            <label class="f">Meta description<textarea name="seo_description" rows="3" data-count="160"><?= e($v('seo_description')) ?></textarea></label>
            <label class="f">URL<span class="slug-field"><em><?= e($base) ?></em><input name="slug" value="<?= e($v('slug')) ?>"></span></label>
        </div>
        <div class="seo-checks" data-seo-checks></div>
    </div>

    <div class="seo-pane" data-pane="social" hidden>
        <div class="og-card" data-og-card><img alt="" hidden><div class="b"><small><?= e(strtoupper((string) $host)) ?></small><div class="t"></div><div class="d"></div></div></div>
        <div class="fields">
            <label class="f">Share title <small>empty = SEO title</small><input name="og_title" value="<?= e($v('og_title')) ?>"></label>
            <label class="f">Share description <small>empty = meta description</small><textarea name="og_description" rows="2"><?= e($v('og_description')) ?></textarea></label>
            <div class="f">Share image <small>1200 × 630 works best. Empty = main image</small><?= image_field('og_image', $v('og_image') ?: null) ?></div>
        </div>
    </div>

    <div class="seo-pane" data-pane="advanced" hidden>
        <p class="group-title">Robots meta</p>
        <div class="robots-grid">
            <?php foreach (SEO_ROBOTS as $k => $label): ?>
                <label class="check"><input type="checkbox" name="robots[]" value="<?= $k ?>" <?= in_array($k, $robots, true) ? 'checked' : '' ?>> <?= e($label) ?></label>
            <?php endforeach; ?>
        </div>
        <p class="help">Leave all unticked for normal pages. <b>No index</b> hides this <?= e($kind) ?> from Google and the sitemap.</p>
        <label class="f">Canonical URL <small>only if this content lives at another address</small><input type="url" name="canonical_url" value="<?= e($v('canonical_url')) ?>" placeholder="<?= e(site_url(ltrim($base, '/') . ($v('slug') ?: 'example') . '/')) ?>"></label>
    </div>

    <div class="seo-pane" data-pane="schema" hidden>
        <label class="f">Schema type <small>structured data that helps Google show rich results</small>
            <select name="schema_type"><?php foreach (seo_schema_types($kind) as $k => $label): ?><option value="<?= e($k) ?>" <?= $v('schema_type') === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
        <p class="help"><?= match ($kind) {
            'product' => 'Product schema sends the price, stock, brand and star ratings so Google can show them under your listing.',
            'post' => 'Article schema tells Google the headline, author, date and cover image.',
            'category' => 'Tells Google this page is a collection of products.',
            default => 'Pick the type that matches the page. FAQ page suits pages that are mostly questions and answers.',
        } ?> Breadcrumbs and your store details are always included.</p>
    </div>
</div>
