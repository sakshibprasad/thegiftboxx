<?php /** @var array $cats @var ?array $edit */ $e = $edit ?? ['id' => null, 'name' => '', 'slug' => '', 'parent_id' => null, 'description' => '', 'image' => null, 'seo_title' => '', 'seo_description' => '', 'sort_order' => 0]; ?>
<div class="page-head"><div><h1>Categories</h1><p>Occasions customers shop by. Each one gets its own page and menu entry.</p></div><div class="head-actions"><a class="btn secondary" href="/brands">Brands</a></div></div>
<div class="grid g-main">
    <div class="card">
        <?php if ($cats): ?>
        <table class="list"><thead><tr><th colspan="2">Category</th><th class="right">Products</th><th></th></tr></thead><tbody>
            <?php foreach ($cats as $c): ?>
                <tr data-href="/categories?edit=<?= (int) $c['id'] ?>">
                    <td style="width:52px"><img class="thumb" src="<?= e(image_url($c['image'], 'sm')) ?>" alt=""></td>
                    <td><div class="title"><?= $c['parent_id'] ? '<span class="dim">↳</span> ' : '' ?><?= e($c['name']) ?></div><div class="sub">/product-category/<?= e($c['slug']) ?>/</div></td>
                    <td class="right"><?= (int) $c['product_count'] ?></td>
                    <td class="right"><a class="btn plain sm" href="<?= e(site_url(category_url($c))) ?>" target="_blank" rel="noopener"><?= icon('external') ?></a></td>
                </tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php else: ?><div class="empty"><?= icon('tag') ?><h2>No categories yet</h2></div><?php endif; ?>
    </div>
    <form class="card pad fields sticky-col" method="post" action="/categories/save">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($e['id']) ?>">
        <h3 style="margin:0"><?= $e['id'] ? 'Edit category' : 'New category' ?></h3>
        <label class="f">Name<input name="name" value="<?= e($e['name']) ?>" required placeholder="Anniversary Gift Boxes"></label>
        <label class="f">Parent<select name="parent_id"><option value="">None (top level)</option><?php foreach ($cats as $c): if ((int) $c['id'] === (int) $e['id'] || $c['parent_id']) continue; ?><option value="<?= (int) $c['id'] ?>" <?= (int) $e['parent_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></label>
        <label class="f">Intro text <small>shown at the top of the category page — good for SEO</small><textarea name="description" rows="4"><?= e(strip_tags((string) $e['description'])) ?></textarea></label>
        <div class="f">Cover image
            <div class="single-img" data-image-field><img class="preview" src="<?= e(image_url($e['image'], 'sm')) ?>" alt="" <?= $e['image'] ? '' : 'hidden' ?>><input type="hidden" name="image" value="<?= e($e['image']) ?>"><label class="btn secondary sm"><?= icon('image') ?> Choose<input type="file" accept="image/*" hidden></label><button type="button" class="btn danger sm" data-remove <?= $e['image'] ? '' : 'hidden' ?>>Remove</button></div>
        </div>
        <label class="f">Menu order<input type="number" name="sort_order" value="<?= (int) $e['sort_order'] ?>"></label>
        <details class="seo-details"><summary>SEO, URL &amp; social sharing</summary>
            <?= render('admin/seo-panel', ['row' => $e, 'kind' => 'category', 'base' => '/product-category/', 'nameField' => 'name', 'bodyField' => 'description', 'flat' => true]) ?>
        </details>
        <div style="display:flex;gap:8px;justify-content:space-between">
            <button class="btn">Save</button>
            <?php if ($e['id']): ?><a class="btn secondary" href="/categories">Cancel</a><button class="btn danger" form="del-cat" data-confirm="Delete this category? Products stay.">Delete</button><?php endif; ?>
        </div>
    </form>
</div>
<?php if ($e['id']): ?><form id="del-cat" method="post" action="/categories/<?= (int) $e['id'] ?>/delete"><?= csrf_field() ?></form><?php endif; ?>
