<?php
declare(strict_types=1);

/* ---------- Homepage ---------- */

function admin_homepage(): void
{
    admin_view('homepage', ['h' => home_content(), 'd' => home_defaults(), 'cats' => categories_all()], 'Homepage', 'homepage', ['wide' => true]);
}

function admin_homepage_save(): void
{
    $d = home_defaults();
    $out = [];
    foreach ($d as $k => $default) {
        if (is_array($default)) {
            continue;
        }
        $out[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $out['features'] = [];
    foreach ((array) ($_POST['feature_title'] ?? []) as $i => $t) {
        if (trim((string) $t) !== '') {
            $out['features'][] = ['title' => trim((string) $t), 'text' => trim((string) ($_POST['feature_text'][$i] ?? ''))];
        }
    }
    $out['faq'] = [];
    foreach ((array) ($_POST['faq_q'] ?? []) as $i => $qq) {
        if (trim((string) $qq) !== '') {
            $out['faq'][] = ['q' => trim((string) $qq), 'a' => trim((string) ($_POST['faq_a'][$i] ?? ''))];
        }
    }
    // Only keep what differs from the built-in text, so improved defaults still reach untouched fields.
    $out = array_filter($out, fn($v, $k) => array_key_exists($k, home_sections()) || $v !== $d[$k], ARRAY_FILTER_USE_BOTH);
    history_log('update', 'settings', 'home_content', 'Edited the homepage', history_snapshot('settings', 'home_content'));
    if (!empty($_POST['featured'])) {
        history_log('update', 'featured', 'all', 'Changed homepage bestsellers', history_snapshot('featured', 'all'));
    }
    setting_set('home_content', json_encode($out, JSON_UNESCAPED_UNICODE));
    foreach ((array) ($_POST['featured'] ?? []) as $pid => $on) {
        update('products', ['featured' => $on ? 1 : 0], 'id = ?', [(int) $pid]);
    }
    flash('success', 'Homepage updated.');
    redirect('/homepage');
}

/* ---------- Pages ---------- */

function admin_pages(): void
{
    admin_view('pages', ['rows' => all('SELECT * FROM pages ORDER BY title')], 'Pages', 'pages');
}

function admin_page_edit(?string $id = null): void
{
    $page = $id ? one('SELECT * FROM pages WHERE id = ?', [(int) $id]) : ['id' => null, 'slug' => '', 'title' => '', 'content' => '', 'seo_title' => '', 'seo_description' => '', 'status' => 'published', 'show_in_footer' => 1];
    if (!$page) {
        redirect('/pages');
    }
    admin_view('page-edit', ['page' => $page], $id ? $page['title'] : 'New page', 'pages', ['wide' => true]);
}

function admin_page_save(): void
{
    $id = (int) input('id') ?: null;
    $title = trim((string) input('title'));
    if ($title === '') {
        field_error_redirect('Give the page a title.', $id ? "/pages/{$id}" : '/pages/new');
    }
    $reserved = ['shop', 'cart', 'checkout', 'my-account', 'product', 'product-category', 'blog', 'contact', 'corporate-gifting', 'custom-box', 'wishlist', 'search', 'order', 'pay', 'feeds', 'track-order', 'restore-cart', 'payment', 'cron'];
    $slug = unique_slug('pages', (string) (input('slug') ?: $title), $id);
    if (in_array($slug, $reserved, true)) {
        $slug .= '-page';
    }
    $data = ['title' => $title, 'slug' => $slug, 'content' => clean_html((string) input('content')),
        'seo_title' => input('seo_title') ?: null, 'seo_description' => input('seo_description') ?: null,
        'status' => input('status') === 'published' ? 'published' : 'draft', 'show_in_footer' => input('show_in_footer') ? 1 : 0, 'updated_at' => now()] + seo_input('page');
    $id = (int) history_track('page', $id, $id ? 'update' : 'create', ($id ? 'Edited' : 'Added') . ' page “' . $title . '”',
        fn() => $id ? (update('pages', $data, 'id = ?', [$id]) ? $id : $id) : insert('pages', $data)) ?: $id;
    flash('success', 'Page saved.');
    redirect('/pages/' . $id);
}

function admin_page_delete(string $id): void
{
    $before = history_snapshot('page', (int) $id);
    history_log('delete', 'page', (int) $id, 'Deleted page “' . ($before['row']['title'] ?? '') . '”', $before);
    q('DELETE FROM pages WHERE id = ?', [(int) $id]);
    flash('success', 'Page deleted.');
    redirect('/pages');
}

/* ---------- Blog ---------- */

function admin_blog(): void
{
    admin_view('blog', ['rows' => all('SELECT * FROM posts ORDER BY COALESCE(published_at, created_at) DESC'), 'enabled' => setting_on('blog_enabled')], 'Blog', 'blog');
}

function admin_post_edit(?string $id = null): void
{
    $post = $id ? one('SELECT * FROM posts WHERE id = ?', [(int) $id]) : ['id' => null, 'title' => '', 'slug' => '', 'excerpt' => '', 'content' => '',
        'cover_image' => null, 'author' => admin_user()['name'] ?? '', 'tags' => '', 'status' => 'draft', 'seo_title' => '', 'seo_description' => '',
        'focus_keyword' => '', 'published_at' => null];
    if (!$post) {
        redirect('/blog');
    }
    admin_view('post-edit', ['post' => $post, 'previewToken' => hash_hmac('sha256', 'preview', (string) config('app_key'))], $id ? $post['title'] : 'New post', 'blog', ['wide' => true]);
}

function admin_post_save(): void
{
    $id = (int) input('id') ?: null;
    $title = trim((string) input('title'));
    if ($title === '') {
        field_error_redirect('Give the post a title.', $id ? "/blog/{$id}" : '/blog/new');
    }
    $status = input('status') === 'published' ? 'published' : 'draft';
    $published = input('published_at') ? date('Y-m-d H:i:s', strtotime((string) input('published_at'))) : ($status === 'published' ? now() : null);
    $data = ['title' => $title, 'slug' => unique_slug('posts', (string) (input('slug') ?: $title), $id), 'excerpt' => trim((string) input('excerpt')),
        'content' => clean_html((string) input('content')), 'cover_image' => input('cover_image') ?: null, 'author' => trim((string) input('author')),
        'tags' => trim((string) input('tags')), 'status' => $status, 'seo_title' => input('seo_title') ?: null,
        'seo_description' => input('seo_description') ?: null, 'focus_keyword' => input('focus_keyword') ?: null,
        'published_at' => $published, 'updated_at' => now()] + seo_input('post');
    $id = (int) history_track('post', $id, $id ? 'update' : 'create', ($id ? 'Edited' : 'Added') . ' blog post “' . $title . '”',
        fn() => $id ? (update('posts', $data, 'id = ?', [$id]) ? $id : $id) : insert('posts', $data + ['created_at' => now()])) ?: $id;
    flash('success', $status === 'published' ? (setting_on('blog_enabled') ? 'Post published.' : 'Post saved as published — it will appear once you turn on the blog in Settings → Website.') : 'Draft saved.');
    redirect('/blog/' . $id);
}

function admin_post_delete(string $id): void
{
    $before = history_snapshot('post', (int) $id);
    history_log('delete', 'post', (int) $id, 'Deleted blog post “' . ($before['row']['title'] ?? '') . '”', $before);
    q('DELETE FROM posts WHERE id = ?', [(int) $id]);
    redirect('/blog');
}

/* ---------- Redirects (keep old links working for Google) ---------- */

function admin_redirects(): void
{
    admin_view('redirects', ['rows' => all('SELECT * FROM redirects ORDER BY from_path')], 'Redirects', 'pages');
}

function admin_redirect_save(): void
{
    $from = '/' . ltrim(trim((string) parse_url((string) input('from_path'), PHP_URL_PATH)), '/');
    $to = trim((string) input('to_url'));
    if ($from === '/' || $to === '') {
        field_error_redirect('Enter both the old path and the new address.', '/redirects');
    }
    $r = one('SELECT id FROM redirects WHERE from_path = ?', [$from]);
    history_track('redirect', $r['id'] ?? null, $r ? 'update' : 'create', "Redirect {$from} → {$to}",
        fn() => $r ? update('redirects', ['to_url' => $to], 'id = ?', [$r['id']]) : insert('redirects', ['from_path' => $from, 'to_url' => $to]));
    flash('success', 'Redirect saved.');
    redirect('/redirects');
}

function admin_redirect_delete(string $id): void
{
    $before = history_snapshot('redirect', (int) $id);
    history_log('delete', 'redirect', (int) $id, 'Deleted redirect ' . ($before['row']['from_path'] ?? ''), $before);
    q('DELETE FROM redirects WHERE id = ?', [(int) $id]);
    redirect('/redirects');
}

/* ---------- Enquiries ---------- */

function enquiry_types(): array
{
    return ['contact' => 'Contact', 'corporate' => 'Corporate', 'custom_box' => 'Custom box idea'];
}

function admin_enquiries(): void
{
    $type = (string) input('type');
    $status = (string) input('status');
    $where = ['1=1'];
    $params = [];
    if (isset(enquiry_types()[$type])) {
        $where[] = 'type = ?';
        $params[] = $type;
    }
    if (in_array($status, ['new', 'replied', 'won', 'closed'], true)) {
        $where[] = 'status = ?';
        $params[] = $status;
    }
    $rows = all('SELECT * FROM enquiries WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT 300', $params);
    admin_view('enquiries', compact('rows', 'type', 'status'), 'Enquiries', 'enquiries');
}

function admin_enquiry(string $id): void
{
    $e = one('SELECT * FROM enquiries WHERE id = ?', [(int) $id]);
    if (!$e) {
        redirect('/enquiries');
    }
    admin_view('enquiry', ['e' => $e], $e['name'], 'enquiries');
}

function admin_enquiry_save(string $id): void
{
    update('enquiries', ['status' => in_array(input('status'), ['new', 'replied', 'won', 'closed'], true) ? input('status') : 'new',
        'admin_note' => trim((string) input('admin_note'))], 'id = ?', [(int) $id]);
    flash('success', 'Saved.');
    redirect('/enquiries/' . (int) $id);
}
