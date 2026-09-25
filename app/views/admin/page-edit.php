<?php /** @var array $page */ ?>
<form method="post" action="/pages/save" data-savebar>
<?= csrf_field() ?><input type="hidden" name="id" value="<?= e($page['id']) ?>">
<div class="page-head"><div><a class="back" href="/pages"><?= icon('arrow-left') ?> Pages</a><h1><?= $page['id'] ? e($page['title']) : 'New page' ?></h1></div>
    <div class="head-actions"><?php if ($page['id']): ?><a class="btn secondary" href="<?= e(site_url($page['slug'] . '/')) ?>" target="_blank" rel="noopener"><?= icon('eye') ?> View</a><?php endif; ?><button class="btn">Save</button></div></div>
<div class="grid g-main">
    <div class="card pad fields">
        <label class="f">Title<input name="title" value="<?= e($page['title']) ?>" required></label>
        <label class="f">Content<textarea name="content" data-rte rows="18"><?= e($page['content']) ?></textarea></label>
    </div>
    <div class="stack sticky-col">
        <div class="group">
            <div class="row"><span class="row-label">Published</span><span class="switch"><input type="checkbox" name="status" value="published" <?= $page['status'] === 'published' ? 'checked' : '' ?>><span></span></span></div>
            <div class="row"><span class="row-label">Link in footer</span><span class="switch"><input type="checkbox" name="show_in_footer" value="1" <?= $page['show_in_footer'] ? 'checked' : '' ?>><span></span></span></div>
        </div>
        <div class="card pad fields"><h3 style="margin:0">SEO</h3>
            <label class="f">URL<input name="slug" value="<?= e($page['slug']) ?>"></label>
            <label class="f">SEO title<input name="seo_title" value="<?= e($page['seo_title']) ?>" data-count="60"></label>
            <label class="f">Meta description<textarea name="seo_description" rows="3" data-count="160"><?= e($page['seo_description']) ?></textarea></label>
        </div>
        <?php if ($page['id'] && $page['slug'] !== 'about'): ?><button class="btn danger" form="del-page" data-confirm="Delete this page?"><?= icon('trash') ?> Delete page</button><?php endif; ?>
    </div>
</div>
</form>
<?php if ($page['id']): ?><form id="del-page" method="post" action="/pages/<?= (int) $page['id'] ?>/delete"><?= csrf_field() ?></form><?php endif; ?>
