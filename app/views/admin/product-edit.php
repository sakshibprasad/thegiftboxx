<?php
/** @var array $p @var array $cats @var array $brands @var array $boxTypes @var array $allProducts @var int $sales */
$isNew = !$p['id'];
$attrs = $p['attributes'] ?: ($p['type'] === 'variable' ? [] : []);
$upsells = array_map('intval', array_filter(explode(',', (string) $p['upsell_ids'])));
$cross = array_map('intval', array_filter(explode(',', (string) $p['cross_sell_ids'])));
$dt = fn($v) => $v ? date('Y-m-d\TH:i', strtotime((string) $v)) : '';
$varField = function (string $prefix, array $v) use ($dt): string {
    ob_start(); ?>
    <div class="form-grid">
        <label class="f">Regular price (₹)<input name="<?= $prefix ?>[regular_price]" inputmode="decimal" value="<?= e($v['regular_price'] ?? '') ?>" placeholder="4999"></label>
        <label class="f">Sale price (₹) <small>optional</small><input name="<?= $prefix ?>[sale_price]" inputmode="decimal" value="<?= e($v['sale_price'] ?? '') ?>"></label>
        <label class="f">Sale starts<input type="datetime-local" name="<?= $prefix ?>[sale_from]" value="<?= $dt($v['sale_from'] ?? null) ?>"></label>
        <label class="f">Sale ends<input type="datetime-local" name="<?= $prefix ?>[sale_to]" value="<?= $dt($v['sale_to'] ?? null) ?>"></label>
        <label class="f">SKU<input name="<?= $prefix ?>[sku]" value="<?= e($v['sku'] ?? '') ?>"></label>
        <label class="f">GTIN / UPC / EAN / ISBN<input name="<?= $prefix ?>[gtin]" value="<?= e($v['gtin'] ?? '') ?>"></label>
        <label class="f">Stock status<select name="<?= $prefix ?>[stock_status]"><?php foreach (['instock' => 'In stock', 'outofstock' => 'Sold out', 'onbackorder' => 'Made to order'] as $k => $l): ?><option value="<?= $k ?>" <?= ($v['stock_status'] ?? 'instock') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
        <label class="f">Track quantity <small>leave empty to not count stock</small>
            <span style="display:flex;gap:8px;align-items:center"><span class="switch sm"><input type="checkbox" name="<?= $prefix ?>[manage_stock]" value="1" <?= !empty($v['manage_stock']) ? 'checked' : '' ?>><span></span></span><input name="<?= $prefix ?>[stock_qty]" inputmode="numeric" value="<?= e($v['stock_qty'] ?? '') ?>" placeholder="Qty" style="max-width:110px"></span></label>
        <label class="f">Weight (kg)<input name="<?= $prefix ?>[weight]" inputmode="decimal" value="<?= e($v['weight'] ?? '') ?>" placeholder="Same as product"></label>
        <label class="f">Size L × W × H (in)<span style="display:flex;gap:6px"><input name="<?= $prefix ?>[length]" value="<?= e($v['length'] ?? '') ?>" placeholder="L"><input name="<?= $prefix ?>[width]" value="<?= e($v['width'] ?? '') ?>" placeholder="W"><input name="<?= $prefix ?>[height]" value="<?= e($v['height'] ?? '') ?>" placeholder="H"></span></label>
        <label class="f full">Short note shown when this option is picked<input name="<?= $prefix ?>[description]" value="<?= e($v['description'] ?? '') ?>" placeholder="e.g. Crafted with pinewood, lightweight with a soft natural finish."></label>
    </div>
    <?php return (string) ob_get_clean();
};
?>
<form method="post" action="/products/save" id="product-form" data-savebar>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e($p['id']) ?>">
    <div class="page-head">
        <div><a class="back" href="/products"><?= icon('arrow-left') ?> Products</a><h1><?= $isNew ? 'New product' : e($p['name']) ?></h1></div>
        <div class="head-actions">
            <?php if (!$isNew): ?>
                <a class="btn secondary" href="<?= e(site_url(product_url($p))) ?>" target="_blank" rel="noopener"><?= icon('eye') ?> View</a>
                <button class="btn secondary" form="dup-form"><?= icon('copy') ?> Duplicate</button>
            <?php endif; ?>
            <button class="btn" type="submit">Save</button>
        </div>
    </div>

    <div class="grid g-main">
        <div class="stack">
            <div class="card pad fields">
                <label class="f">Name<input name="name" value="<?= e($p['name']) ?>" required placeholder="e.g. Valentine’s Gift Box for Her" data-count="70"></label>
                <label class="f">Short description <small>shown next to the price</small><textarea name="short_description" data-rte rows="3" placeholder="Two or three lines that make someone want this box."><?= e($p['short_description']) ?></textarea></label>
                <label class="f">Full description <small>what’s inside, why it’s special, when to gift it</small><textarea name="description" data-rte rows="10"><?= e($p['description']) ?></textarea></label>
            </div>

            <div class="card pad">
                <h3>Photos</h3>
                <p class="help" style="margin-top:-6px">Drag to reorder. The first photo is the main one. JPG, PNG or WEBP, up to 15 MB — we create fast, resized versions automatically.</p>
                <div class="dropzone" data-gallery="images">
                    <div class="gallery-grid">
                        <?php foreach ($p['images'] as $img): ?>
                            <div class="gimg" draggable="true"><img src="<?= e(image_url($img['path'], 'sm')) ?>" alt=""><button type="button" class="x" aria-label="Remove"><?= icon('close') ?></button><input type="hidden" name="images[]" value="<?= e($img['path']) ?>"><input type="hidden" name="image_alt[]" value="<?= e($img['alt']) ?>"></div>
                        <?php endforeach; ?>
                        <button type="button" class="add-tile"><?= icon('plus') ?>Add photos</button>
                    </div>
                    <input type="file" accept="image/*" multiple hidden>
                </div>
            </div>

            <div class="card pad">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px">
                    <h3 style="margin:0">Pricing &amp; options</h3>
                    <div class="segmented">
                        <label><input type="radio" name="type" value="simple" <?= $p['type'] === 'simple' ? 'checked' : '' ?>><span>Single price</span></label>
                        <label><input type="radio" name="type" value="variable" <?= $p['type'] === 'variable' ? 'checked' : '' ?>><span>With options (e.g. box type)</span></label>
                    </div>
                </div>

                <div data-simple-only>
                    <div class="form-grid">
                        <label class="f">Regular price (₹)<input name="regular_price" inputmode="decimal" value="<?= e($p['regular_price']) ?>" placeholder="5499"></label>
                        <label class="f">Sale price (₹) <small>optional</small><input name="sale_price" inputmode="decimal" value="<?= e($p['sale_price']) ?>"></label>
                        <label class="f">Sale starts <small>optional</small><input type="datetime-local" name="sale_from" value="<?= $dt($p['sale_from']) ?>"></label>
                        <label class="f">Sale ends <small>optional</small><input type="datetime-local" name="sale_to" value="<?= $dt($p['sale_to']) ?>"></label>
                        <label class="f">SKU<input name="sku" value="<?= e($p['sku']) ?>"></label>
                        <label class="f">GTIN / UPC / EAN / ISBN <small>for Google Shopping, if the product has a barcode</small><input name="gtin" value="<?= e($p['gtin']) ?>"></label>
                        <label class="f">Stock status<select name="stock_status"><?php foreach (['instock' => 'In stock', 'outofstock' => 'Sold out', 'onbackorder' => 'Made to order'] as $k => $l): ?><option value="<?= $k ?>" <?= $p['stock_status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
                        <label class="f">Track quantity
                            <span style="display:flex;gap:8px;align-items:center"><span class="switch sm"><input type="checkbox" name="manage_stock" value="1" id="manage_stock" <?= $p['manage_stock'] ? 'checked' : '' ?>><span></span></span><input name="stock_qty" inputmode="numeric" value="<?= e($p['stock_qty']) ?>" placeholder="Qty" style="max-width:110px" data-show-if="#manage_stock"></span></label>
                    </div>
                </div>

                <div data-variable-only>
                    <p class="help">1. Name the choice customers make and list the values. 2. Press <strong>Create all combinations</strong>. 3. Add a price (and photo) to each.</p>
                    <div id="attributes">
                        <?php foreach ($attrs ?: [['name' => 'Box Type', 'options' => array_column($boxTypes, 'name'), 'variation' => true]] as $i => $a): ?>
                            <div class="attr-row">
                                <input name="attr_name[<?= $i ?>]" value="<?= e($a['name']) ?>" placeholder="Option name, e.g. Box Type">
                                <input name="attr_options[<?= $i ?>]" value="<?= e(implode(', ', $a['options'])) ?>" data-tags placeholder="Values, e.g. Pinewood — press Enter after each">
                                <label class="check small" title="Show as information only (not a choice)"><input type="checkbox" name="attr_info[<?= $i ?>]" value="1" <?= isset($a['variation']) && !$a['variation'] && $p['type'] === 'variable' ? 'checked' : '' ?>>Info only</label>
                                <button type="button" class="icon-btn" data-remove-attr aria-label="Remove option"><?= icon('trash') ?></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <template id="attr-row-tpl"><div class="attr-row"><input name="attr_name[__i__]" placeholder="Option name, e.g. Size"><input name="attr_options[__i__]" data-tags placeholder="Values — press Enter after each"><label class="check small"><input type="checkbox" name="attr_info[__i__]" value="1">Info only</label><button type="button" class="icon-btn" data-remove-attr aria-label="Remove"><?= icon('trash') ?></button></div></template>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin:6px 0 18px">
                        <button type="button" class="btn secondary sm" id="add-attr"><?= icon('plus') ?> Add another option</button>
                        <button type="button" class="btn sm" id="gen-vars"><?= icon('sparkle') ?> Create all combinations</button>
                        <button type="button" class="btn plain sm" id="add-var">Add one manually</button>
                        <button type="button" class="btn plain sm" id="bulk-price">Set one price for all</button>
                    </div>
                    <?php if ($p['default_attributes'] || $attrs): ?>
                        <div class="form-grid" style="margin-bottom:10px">
                            <?php foreach ($attrs as $a): if (isset($a['variation']) && !$a['variation']) continue; ?>
                                <label class="f">Pre-selected <?= e($a['name']) ?><select name="default_attr[<?= e($a['name']) ?>]"><option value="">None</option><?php foreach ($a['options'] as $o): ?><option <?= ($p['default_attributes'][$a['name']] ?? '') === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select></label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div id="var-list">
                        <?php foreach ($p['variations'] as $v): $prefix = 'var[' . $v['id'] . ']'; $vimg = $v['images'][0]['path'] ?? null; ?>
                            <div class="var-card" data-prefix="<?= e($prefix) ?>">
                                <input type="hidden" name="<?= $prefix ?>[id]" value="<?= (int) $v['id'] ?>">
                                <div class="var-top">
                                    <img class="var-img" src="<?= e(image_url($vimg, 'sm')) ?>" alt="">
                                    <span class="grow"></span><span class="var-price"></span>
                                    <label class="switch sm" title="Available"><input type="checkbox" name="<?= $prefix ?>[enabled]" value="1" <?= $v['enabled'] ? 'checked' : '' ?>><span></span></label>
                                    <?= icon('chevron') ?>
                                </div>
                                <div class="var-body">
                                    <div class="form-grid var-attrs" style="margin-bottom:14px">
                                        <?php foreach ($attrs as $a): if (isset($a['variation']) && !$a['variation']) continue; ?>
                                            <label class="f"><?= e($a['name']) ?><select class="var-attr" name="<?= $prefix ?>[attrs][<?= e($a['name']) ?>]"><?php foreach ($a['options'] as $o): ?><option <?= ($v['attributes'][$a['name']] ?? '') === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select></label>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="single-img" data-image-field style="margin-bottom:14px">
                                        <img class="preview" src="<?= e(image_url($vimg, 'sm')) ?>" alt="" <?= $vimg ? '' : 'hidden' ?>>
                                        <input type="hidden" name="<?= $prefix ?>[images][]" value="<?= e($vimg) ?>">
                                        <label class="btn secondary sm"><?= icon('image') ?> Photo for this option<input type="file" accept="image/*" hidden></label>
                                        <button type="button" class="btn danger sm" data-remove <?= $vimg ? '' : 'hidden' ?>>Remove</button>
                                    </div>
                                    <?= $varField($prefix, $v) ?>
                                    <div style="margin-top:12px;text-align:right"><button type="button" class="btn danger sm" data-remove-var>Remove this option</button></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <template id="var-tpl">
                        <div class="var-card" data-prefix="var[__v__]">
                            <div class="var-top"><img class="var-img" src="<?= e(asset_placeholder()) ?>" alt=""><span class="grow"></span><span class="var-price"></span><label class="switch sm"><input type="checkbox" name="var[__v__][enabled]" value="1" checked><span></span></label><?= icon('chevron') ?></div>
                            <div class="var-body">
                                <div class="form-grid var-attrs" style="margin-bottom:14px"></div>
                                <div class="single-img" data-image-field style="margin-bottom:14px">
                                    <img class="preview" src="" alt="" hidden>
                                    <input type="hidden" name="var[__v__][images][]" value="">
                                    <label class="btn secondary sm"><?= icon('image') ?> Photo for this option<input type="file" accept="image/*" hidden></label>
                                    <button type="button" class="btn danger sm" data-remove hidden>Remove</button>
                                </div>
                                <?= $varField('var[__v__]', []) ?>
                                <div style="margin-top:12px;text-align:right"><button type="button" class="btn danger sm" data-remove-var>Remove this option</button></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="card pad">
                <h3>Shipping</h3>
                <p class="help" style="margin-top:-6px">Used by Shiprocket to pick the courier and calculate the charge.</p>
                <div class="form-grid">
                    <label class="f">Weight (kg)<input name="weight" inputmode="decimal" value="<?= e($p['weight']) ?>" placeholder="3"></label>
                    <label class="f">Box size L × W × H (inches)<span style="display:flex;gap:6px"><input name="length" value="<?= e($p['length']) ?>" placeholder="10"><input name="width" value="<?= e($p['width']) ?>" placeholder="12"><input name="height" value="<?= e($p['height']) ?>" placeholder="5"></span></label>
                    <label class="f">Tax<select name="tax_status"><option value="taxable" <?= $p['tax_status'] !== 'none' ? 'selected' : '' ?>>Prices include GST</option><option value="none" <?= $p['tax_status'] === 'none' ? 'selected' : '' ?>>No tax</option></select></label>
                </div>
            </div>

            <div class="card pad" id="previews" data-store="<?= e(setting('store_name')) ?>" data-site="<?= e(parse_url(site_url(), PHP_URL_HOST)) ?>" data-placeholder="<?= e(asset_placeholder()) ?>">
                <h3>Search engine &amp; Google Shopping</h3>
                <p class="help" style="margin-top:-6px">Leave empty to use the product name and short description. Write for people, not robots.</p>
                <div class="fields">
                    <label class="f">SEO title<input name="seo_title" value="<?= e($p['seo_title']) ?>" data-count="60" placeholder="<?= e($p['name'] ?: 'Valentine’s Gift Box for Her | Wooden Gift Hamper') ?>"></label>
                    <label class="f">Meta description<textarea name="seo_description" rows="2" data-count="160" placeholder="A romantic gift box for her with chocolates, candles and keepsakes in a wooden box. Delivered across India."><?= e($p['seo_description']) ?></textarea></label>
                    <div class="form-grid">
                        <label class="f">Focus keyword <small>the phrase people search for</small><input name="focus_keyword" value="<?= e($p['focus_keyword']) ?>" placeholder="valentine gift hamper for her"></label>
                        <label class="f">URL<input name="slug" value="<?= e($p['slug']) ?>" placeholder="valentines-gift-box-for-her"></label>
                    </div>
                </div>
                <p class="group-title" style="margin:20px 0 8px">Preview</p>
                <div class="grid" style="grid-template-columns:minmax(0,1fr) 200px;align-items:start">
                    <div class="serp"><div class="site"><span class="fav"><img src="/assets/favicon.png" alt=""></span><span><?= e(setting('store_name')) ?><small></small></span></div><div class="t"></div><div class="meta-row"></div><div class="d"></div></div>
                    <div class="shop-card"><img alt=""><div class="b"><div class="t"></div><div class="p"></div><div class="s"><?= e(parse_url(site_url(), PHP_URL_HOST)) ?></div><?php if ((float) setting('shipping_free_above') > 0): ?><div class="free">Free delivery above <?= money((float) setting('shipping_free_above')) ?></div><?php endif; ?></div></div>
                </div>
            </div>

            <div class="card pad">
                <h3>Recommended with this box</h3>
                <div class="form-grid">
                    <label class="f">“You may also like” (product page)<select name="upsell_ids[]" multiple size="5"><?php foreach ($allProducts as $ap): if ((int) $ap['id'] === (int) $p['id']) continue; ?><option value="<?= (int) $ap['id'] ?>" <?= in_array((int) $ap['id'], $upsells, true) ? 'selected' : '' ?>><?= e($ap['name']) ?></option><?php endforeach; ?></select><small>Hold Ctrl / ⌘ to select several</small></label>
                    <label class="f">“Add a little extra” (cart page)<select name="cross_sell_ids[]" multiple size="5"><?php foreach ($allProducts as $ap): if ((int) $ap['id'] === (int) $p['id']) continue; ?><option value="<?= (int) $ap['id'] ?>" <?= in_array((int) $ap['id'], $cross, true) ? 'selected' : '' ?>><?= e($ap['name']) ?></option><?php endforeach; ?></select></label>
                </div>
            </div>
        </div>

        <div class="stack sticky-col">
            <div class="group">
                <div class="row"><span class="row-label">Visible on website</span><span class="switch"><input type="checkbox" name="status" value="published" <?= $p['status'] === 'published' ? 'checked' : '' ?>><span></span></span></div>
                <div class="row"><span class="row-label">Bestseller<small>Shown on the homepage</small></span><span class="switch"><input type="checkbox" name="featured" value="1" <?= $p['featured'] ? 'checked' : '' ?>><span></span></span></div>
                <div class="row"><span class="row-label">Google, Meta &amp; Pinterest<small>Include in shopping feeds</small></span><span class="switch"><input type="checkbox" name="google_sync" value="1" <?= $p['google_sync'] ? 'checked' : '' ?>><span></span></span></div>
            </div>

            <div class="card pad fields">
                <h3 style="margin:0">Organise</h3>
                <div class="f">Categories
                    <div style="display:grid;gap:6px;max-height:210px;overflow:auto;padding:4px 2px">
                        <?php foreach ($cats as $c): ?><label class="check"><input type="checkbox" name="categories[]" value="<?= (int) $c['id'] ?>" <?= in_array((int) $c['id'], $p['category_ids'], true) ? 'checked' : '' ?>><?= $c['parent_id'] ? '— ' : '' ?><?= e($c['name']) ?></label><?php endforeach; ?>
                    </div>
                    <a class="small" href="/categories" style="color:var(--accent)">Manage categories</a>
                </div>
                <label class="f">Brand<select name="brand_id"><option value="">The Gift Boxx (default)</option><?php foreach ($brands as $b): ?><option value="<?= (int) $b['id'] ?>" <?= (int) $p['brand_id'] === (int) $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?></select></label>
                <label class="f">Tags <small>comma separated</small><input name="tags" value="<?= e($p['tags']) ?>" placeholder="gift for her, anniversary"></label>
            </div>

            <div class="card pad fields">
                <h3 style="margin:0">Page background</h3>
                <p class="help" style="margin:-4px 0 0">Options named Pinewood, Teakwood or Plywood switch the page colours automatically. For single-price boxes, choose the wood here.</p>
                <label class="f">Box type<select name="box_type_id"><option value="">Automatic (from options)</option><?php foreach ($boxTypes as $bt): ?><option value="<?= (int) $bt['id'] ?>" <?= (int) $p['box_type_id'] === (int) $bt['id'] ? 'selected' : '' ?>><?= e($bt['name']) ?></option><?php endforeach; ?></select></label>
                <label class="check"><input type="checkbox" name="use_custom_color" value="1" id="use_custom_color" <?= $p['page_bg_color'] ? 'checked' : '' ?>> Use a custom colour instead</label>
                <div id="custom-colors" style="display:flex;gap:10px;flex-wrap:wrap">
                    <label class="f">Background<span class="color-field"><input type="color" name="page_bg_color" value="<?= e($p['page_bg_color'] ?: '#F3E9DC') ?>"><code><?= e($p['page_bg_color'] ?: '#F3E9DC') ?></code></span></label>
                    <label class="f">Text<span class="color-field"><input type="color" name="page_text_color" value="<?= e($p['page_text_color'] ?: '#1D1714') ?>"><code><?= e($p['page_text_color'] ?: '#1D1714') ?></code></span></label>
                </div>
                <a class="small" href="/box-types" style="color:var(--accent)">Edit box type colours</a>
            </div>

            <?php if (!$isNew): ?>
            <div class="card pad">
                <h3>Performance</h3>
                <dl class="kv"><dt>Views</dt><dd><?= (int) $p['views'] ?></dd><dt>Units sold</dt><dd><?= $sales ?></dd><dt>Rating</dt><dd><?= (int) $p['rating_count'] ? number_format((float) $p['rating_avg'], 1) . ' ★ (' . (int) $p['rating_count'] . ')' : '—' ?></dd></dl>
                <button class="btn danger sm" form="del-form" style="margin-top:14px;padding:0" data-confirm="Delete this product permanently?"><?= icon('trash') ?> Delete product</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</form>
<?php if (!$isNew): ?>
    <form id="dup-form" method="post" action="/products/<?= (int) $p['id'] ?>/duplicate"><?= csrf_field() ?></form>
    <form id="del-form" method="post" action="/products/<?= (int) $p['id'] ?>/delete" data-confirm="Delete this product permanently? This can’t be undone."><?= csrf_field() ?></form>
<?php endif; ?>
