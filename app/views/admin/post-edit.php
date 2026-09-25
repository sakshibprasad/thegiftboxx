<?php /** @var array $post @var string $previewToken */ ?>
<form method="post" action="/blog/save" data-savebar>
<?= csrf_field() ?><input type="hidden" name="id" value="<?= e($post['id']) ?>">
<div class="page-head"><div><a class="back" href="/blog"><?= icon('arrow-left') ?> Blog</a><h1><?= $post['id'] ? e($post['title']) : 'New post' ?></h1></div>
    <div class="head-actions"><?php if ($post['id']): ?><a class="btn secondary" href="<?= e(site_url('blog/' . $post['slug'] . '/?preview=' . $previewToken)) ?>" target="_blank" rel="noopener"><?= icon('eye') ?> Preview</a><?php endif; ?><button class="btn">Save</button></div></div>
<div class="grid g-main">
    <div class="card pad fields">
        <label class="f">Title<input name="title" value="<?= e($post['title']) ?>" required data-count="70" placeholder="The 12 best gift hampers for a new mum"></label>
        <label class="f">Summary <small>one or two sentences shown on the blog page</small><textarea name="excerpt" rows="2"><?= e($post['excerpt']) ?></textarea></label>
        <label class="f">Article<textarea name="content" data-rte rows="22"><?= e($post['content']) ?></textarea></label>
    </div>
    <div class="stack sticky-col">
        <div class="group">
            <div class="row"><span class="row-label">Published</span><span class="switch"><input type="checkbox" name="status" value="published" <?= $post['status'] === 'published' ? 'checked' : '' ?>><span></span></span></div>
        </div>
        <div class="card pad fields">
            <div class="f">Cover image<?= image_field('cover_image', $post['cover_image']) ?></div>
            <label class="f">Author<input name="author" value="<?= e($post['author']) ?>"></label>
            <label class="f">Publish date <small>future date = scheduled</small><input type="datetime-local" name="published_at" value="<?= $post['published_at'] ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : '' ?>"></label>
            <label class="f">Tags<input name="tags" value="<?= e($post['tags']) ?>" placeholder="diwali, corporate"></label>
        </div>
        <div class="card pad fields"><h3 style="margin:0">SEO</h3>
            <label class="f">URL<input name="slug" value="<?= e($post['slug']) ?>"></label>
            <label class="f">Focus keyword<input name="focus_keyword" value="<?= e($post['focus_keyword']) ?>"></label>
            <label class="f">SEO title<input name="seo_title" value="<?= e($post['seo_title']) ?>" data-count="60"></label>
            <label class="f">Meta description<textarea name="seo_description" rows="3" data-count="160"><?= e($post['seo_description']) ?></textarea></label>
        </div>
        <?php if ($post['id']): ?><button class="btn danger" form="del-post" data-confirm="Delete this post?"><?= icon('trash') ?> Delete post</button><?php endif; ?>
    </div>
</div>
</form>
<?php if ($post['id']): ?><form id="del-post" method="post" action="/blog/<?= (int) $post['id'] ?>/delete"><?= csrf_field() ?></form><?php endif; ?>
