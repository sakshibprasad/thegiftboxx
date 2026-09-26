<?php
declare(strict_types=1);

const IMAGE_SIZES = ['sm' => 480, 'md' => 960, 'lg' => 1600];

function uploads_dir(): string
{
    return rtrim((string) config('uploads_dir'), '/');
}

function uploads_url(): string
{
    return rtrim((string) config('uploads_url', site_url('uploads')), '/');
}

/**
 * Public URL for a stored image. $size = sm | md | lg | '' (original).
 * Stored paths look like "2026/09/valentine-box-a1b2.jpg".
 */
function image_url(?string $path, string $size = 'md'): string
{
    if (!$path) {
        return asset_placeholder();
    }
    if (preg_match('#^https?://#', $path)) {
        return $path;
    }
    if ($size !== '') {
        $variant = preg_replace('/\.(\w+)$/', "-{$size}.webp", $path);
        if (is_file(uploads_dir() . '/' . $variant)) {
            return uploads_url() . '/' . $variant;
        }
    }
    return uploads_url() . '/' . $path;
}

/** Save an uploaded file ($_FILES entry). Returns the stored relative path. */
function save_uploaded_image(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed (code ' . ($file['error'] ?? '?') . ').');
    }
    if ($file['size'] > 15 * 1024 * 1024) {
        throw new RuntimeException('Image is larger than 15 MB.');
    }
    return store_image_file($file['tmp_name'], $file['name'], true);
}

/** Download a remote image (used by the WooCommerce importer). */
function save_remote_image(string $url): ?string
{
    $res = http_request('GET', $url, [], null, 60);
    if ($res['status'] !== 200 || strlen($res['body']) < 100) {
        return null;
    }
    $tmp = tempnam(sys_get_temp_dir(), 'img');
    file_put_contents($tmp, $res['body']);
    try {
        return store_image_file($tmp, basename(parse_url($url, PHP_URL_PATH) ?: 'image.jpg'), false);
    } catch (Throwable $e) {
        app_log('images', 'remote image failed', ['url' => $url, 'error' => $e->getMessage()]);
        return null;
    } finally {
        @unlink($tmp);
    }
}

function store_image_file(string $tmp, string $originalName, bool $isUpload): string
{
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif',
        'image/svg+xml' => 'svg', 'image/avif' => 'avif'][$mime] ?? null;
    if (!$ext) {
        throw new RuntimeException('Only JPG, PNG, WEBP, AVIF, GIF or SVG images are allowed.');
    }
    if ($ext === 'svg') {
        $svg = (string) file_get_contents($tmp);
        if (preg_match('/<script|on\w+\s*=|javascript:/i', $svg)) {
            throw new RuntimeException('This SVG contains scripts and was rejected.');
        }
    }
    ensure_uploads_protected();
    $sub = date('Y/m');
    $dir = uploads_dir() . '/' . $sub;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Cannot create uploads folder: ' . $dir);
    }
    $base = slugify(pathinfo($originalName, PATHINFO_FILENAME));
    $base = substr($base, 0, 60) . '-' . substr(random_token(3), 0, 6);
    $rel = "{$sub}/{$base}.{$ext}";
    $dest = uploads_dir() . '/' . $rel;
    $ok = $isUpload ? move_uploaded_file($tmp, $dest) : copy($tmp, $dest);
    if (!$ok) {
        throw new RuntimeException('Could not save the image.');
    }
    @chmod($dest, 0644);
    if ($ext !== 'svg' && $ext !== 'gif') {
        make_image_variants($dest);
    }
    return $rel;
}

function make_image_variants(string $source): void
{
    if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
        return;
    }
    $img = @imagecreatefromstring((string) file_get_contents($source));
    if (!$img) {
        return;
    }
    // Respect camera orientation for JPEGs.
    if (function_exists('exif_read_data') && preg_match('/\.jpe?g$/i', $source)) {
        $exif = @exif_read_data($source);
        $rot = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 0] ?? 0;
        if ($rot) {
            $img = imagerotate($img, $rot, 0);
        }
    }
    $w = imagesx($img);
    $h = imagesy($img);
    foreach (IMAGE_SIZES as $suffix => $maxW) {
        $nw = min($w, $maxW);
        $nh = (int) round($h * ($nw / $w));
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagewebp($dst, preg_replace('/\.(\w+)$/', "-{$suffix}.webp", $source), 82);
        imagedestroy($dst);
    }
    imagedestroy($img);
}

function delete_image_files(string $rel): void
{
    $full = uploads_dir() . '/' . $rel;
    @unlink($full);
    foreach (array_keys(IMAGE_SIZES) as $s) {
        @unlink(preg_replace('/\.(\w+)$/', "-{$s}.webp", $full));
    }
}

function asset_placeholder(): string
{
    return site_url('assets/img/placeholder.svg');
}

/** The uploads folder must never run scripts. Recreate the rule if it went missing. */
function ensure_uploads_protected(): void
{
    $dir = uploads_dir();
    if ($dir && is_dir($dir) && !is_file($dir . '/.htaccess')) {
        @file_put_contents($dir . '/.htaccess', "# Never run scripts from the uploads folder\n<FilesMatch \"\\.(php|phtml|php\\d|phar|pl|py|cgi|sh)$\">\nRequire all denied\n</FilesMatch>\nOptions -Indexes -ExecCGI\n");
    }
}
