<?php /** @var array $types @var ?array $edit */
$e = $edit ?? ['id' => null, 'name' => '', 'keywords' => '', 'bg_color' => '#EFE2CF', 'text_color' => '#3A2A1C', 'accent_color' => '#A67C4E', 'bg_image' => null, 'grain' => 1, 'description' => '', 'sort_order' => count($types) + 1]; ?>
<div class="page-head"><div><h1>Box types</h1><p>Each wood gives the product page its own background. When a customer picks “Pinewood”, the page turns pine; pick “Teakwood” and it turns teak.</p></div></div>
<div class="grid g-main">
    <div class="grid g2" style="align-content:start">
        <?php foreach ($types as $t): ?>
            <a class="card" href="/box-types?edit=<?= (int) $t['id'] ?>" style="<?= (int) $e['id'] === (int) $t['id'] ? 'box-shadow:0 0 0 2px var(--accent)' : '' ?>">
                <div class="wood-swatch <?= $t['grain'] ? 'grain' : '' ?>" style="background:<?= e($t['bg_color']) ?><?= $t['bg_image'] ? ' url(' . e(image_url($t['bg_image'], 'md')) . ') center/cover' : '' ?>;color:<?= e($t['text_color']) ?>"><strong><?= e($t['name']) ?></strong><span class="pillx" style="background:<?= e($t['accent_color']) ?>">Add to cart</span></div>
                <div class="pad" style="padding:12px 16px"><p class="small muted" style="margin:0">Matches options containing: <strong><?= e($t['keywords'] ?: $t['name']) ?></strong></p></div>
            </a>
        <?php endforeach; ?>
    </div>
    <form class="card pad fields sticky-col" method="post" action="/box-types/save" id="boxtype-form">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($e['id']) ?>">
        <h3 style="margin:0"><?= $e['id'] ? 'Edit ' . e($e['name']) : 'New box type' ?></h3>
        <div class="wood-swatch grain" id="bt-preview"><strong></strong><span class="pillx">Add to cart</span></div>
        <label class="f">Name<input name="name" value="<?= e($e['name']) ?>" required placeholder="Pinewood"></label>
        <label class="f">Also match options named <small>comma separated — e.g. “premium wood” should use Teakwood</small><input name="keywords" value="<?= e($e['keywords']) ?>" placeholder="pine, pinewood"></label>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
            <label class="f">Background<span class="color-field"><input type="color" name="bg_color" value="<?= e($e['bg_color']) ?>"><code><?= e($e['bg_color']) ?></code></span></label>
            <label class="f">Text<span class="color-field"><input type="color" name="text_color" value="<?= e($e['text_color']) ?>"><code><?= e($e['text_color']) ?></code></span></label>
            <label class="f">Accent<span class="color-field"><input type="color" name="accent_color" value="<?= e($e['accent_color']) ?>"><code><?= e($e['accent_color']) ?></code></span></label>
        </div>
        <div class="f">Wood texture photo <small>optional — a close-up of the real wood, softly faded behind the page</small>
            <div class="single-img" data-image-field><img class="preview" src="<?= e(image_url($e['bg_image'], 'sm')) ?>" alt="" <?= $e['bg_image'] ? '' : 'hidden' ?>><input type="hidden" name="bg_image" value="<?= e($e['bg_image']) ?>"><label class="btn secondary sm"><?= icon('image') ?> Upload<input type="file" accept="image/*" hidden></label><button type="button" class="btn danger sm" data-remove <?= $e['bg_image'] ? '' : 'hidden' ?>>Remove</button></div>
        </div>
        <label class="check"><input type="checkbox" name="grain" value="1" <?= $e['grain'] ? 'checked' : '' ?>> Subtle wood-grain lines</label>
        <label class="f">Short description<input name="description" value="<?= e($e['description']) ?>"></label>
        <input type="hidden" name="sort_order" value="<?= (int) $e['sort_order'] ?>">
        <div style="display:flex;gap:8px;justify-content:space-between"><button class="btn">Save</button><?php if ($e['id']): ?><a class="btn secondary" href="/box-types">New</a><button class="btn danger" form="del-bt" data-confirm="Delete this box type?">Delete</button><?php endif; ?></div>
    </form>
</div>
<?php if ($e['id']): ?><form id="del-bt" method="post" action="/box-types/<?= (int) $e['id'] ?>/delete"><?= csrf_field() ?></form><?php endif; ?>
