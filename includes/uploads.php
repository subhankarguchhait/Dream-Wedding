<?php
/**
 * Secure image uploads.
 *  - Images: JPG, JPEG, PNG, WEBP. Videos (hero slider only): MP4, WEBM.
 *  - Checks: upload error code, size limit, extension whitelist, real MIME type (finfo),
 *    image decoding (getimagesize), pixel limits.
 *  - Raster images are re-encoded with GD (strips hidden payloads/EXIF), resized to UPLOAD_MAX_DIMENSION
 *    and saved as WebP when the server supports it.
 *  - Stored under assets/uploads/YYYY/MM/ with a random, safe filename. PHP execution is disabled there by .htaccess.
 */
declare(strict_types=1);
defined('APP_NAME') || exit; // no direct access

const UPLOAD_TYPES = [
    'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'webp' => ['image/webp'],
];
const VIDEO_TYPES = ['mp4' => ['video/mp4', 'application/mp4'], 'm4v' => ['video/mp4', 'video/x-m4v'], 'webm' => ['video/webm', 'audio/webm']];
const THUMB_WIDTH = 640;

final class UploadError extends RuntimeException {}

/** Validate + store one uploaded image. Returns the new media row. */
function store_uploaded_image(array $file, string $folder = 'general', ?string $replacePath = null): array
{
    if (!isset($file['error']) || is_array($file['error'])) throw new UploadError('Invalid upload.');
    switch ($file['error']) {
        case UPLOAD_ERR_OK: break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE: throw new UploadError('The file is larger than ' . (UPLOAD_MAX_BYTES / 1048576) . ' MB.');
        case UPLOAD_ERR_NO_FILE: throw new UploadError('Please choose a file.');
        default: throw new UploadError('Upload failed (code ' . (int)$file['error'] . '). Please try again.');
    }
    if (!is_uploaded_file($file['tmp_name'])) throw new UploadError('Invalid upload.');
    if ($file['size'] <= 0 || $file['size'] > UPLOAD_MAX_BYTES) throw new UploadError('The file must be between 1 byte and ' . (UPLOAD_MAX_BYTES / 1048576) . ' MB.');

    $original = basename((string)$file['name']);
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!isset(UPLOAD_TYPES[$ext])) throw new UploadError('Only JPG, PNG and WebP images are allowed.');
    // Reject double extensions like "photo.php.jpg"
    if (preg_match('/\.(php\d?|phtml|phar|pl|py|cgi|asp|aspx|jsp|sh|exe|js|html?|htaccess)\./i', $original)) throw new UploadError('That filename is not allowed.');

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    if (!in_array($mime, UPLOAD_TYPES[$ext], true)) throw new UploadError("The file content ($mime) does not match its .$ext extension.");

    $folder = in_array($folder, MEDIA_FOLDERS, true) ? $folder : 'general';
    $subdir = UPLOAD_DIR . '/' . date('Y/m');
    $absDir = ROOT_PATH . '/' . $subdir;
    if (!is_dir($absDir) && !mkdir($absDir, 0755, true) && !is_dir($absDir)) throw new UploadError('Upload folder is not writable. Check permissions on assets/uploads.');

    $base = slugify(pathinfo($original, PATHINFO_FILENAME), 40);
    $random = bin2hex(random_bytes(6));
    $width = $height = null;

    $info = @getimagesize($file['tmp_name']);
    if (!$info || $info[0] < 1 || $info[1] < 1) throw new UploadError('The image could not be read. It may be corrupted.');
    if ($info[0] * $info[1] > 60_000_000) throw new UploadError('The image dimensions are too large (max 60 megapixels).');
    [$name, $type, $width, $height] = reencode_image($file['tmp_name'], $info, $absDir, "$random-$base", $ext);

    $path = "$subdir/$name";
    $size = (int)filesize(ROOT_PATH . '/' . $path);
    $thumb = make_thumbnail($path) ?? $path;
    return ['file_name' => $name, 'original_name' => mb_substr($original, 0, 190), 'file_path' => $path, 'thumb_path' => $thumb, 'file_type' => $type,
        'file_size' => $size, 'width' => $width, 'height' => $height, 'folder' => $folder];
}

/**
 * Re-encode a raster image with GD (removes metadata and any appended payload), resize, prefer WebP.
 * Falls back to moving the validated original when GD isn't available.
 */
function reencode_image(string $tmp, array $info, string $absDir, string $basename, string $ext): array
{
    [$w, $h] = $info;
    $mime = $info['mime'];
    $canGd = function_exists('imagecreatetruecolor') && match ($mime) {
        'image/jpeg' => function_exists('imagecreatefromjpeg'),
        'image/png' => function_exists('imagecreatefrompng'),
        'image/webp' => function_exists('imagecreatefromwebp'),
        default => false,
    };
    if (!$canGd) {
        $name = "$basename.$ext";
        if (!move_uploaded_file($tmp, "$absDir/$name")) throw new UploadError('Could not save the file.');
        return [$name, $mime, $w, $h];
    }
    $src = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($tmp),
        'image/png' => @imagecreatefrompng($tmp),
        'image/webp' => @imagecreatefromwebp($tmp),
    };
    if (!$src) throw new UploadError('The image could not be processed.');

    $scale = min(1, UPLOAD_MAX_DIMENSION / max($w, $h));
    $nw = max(1, (int)round($w * $scale));
    $nh = max(1, (int)round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($src);

    if (function_exists('imagewebp')) {
        $name = "$basename.webp";
        $ok = imagewebp($dst, "$absDir/$name", 82);
        $type = 'image/webp';
    } elseif ($mime === 'image/png') {
        $name = "$basename.png";
        $ok = imagepng($dst, "$absDir/$name", 7);
        $type = 'image/png';
    } else {
        $name = "$basename.jpg";
        $ok = imagejpeg($dst, "$absDir/$name", 85);
        $type = 'image/jpeg';
    }
    imagedestroy($dst);
    if (!$ok) throw new UploadError('Could not save the processed image.');
    return [$name, $type, $nw, $nh];
}

/**
 * Create a small copy for grids (THUMB_WIDTH wide) next to the original: name-thumb.webp/jpg.
 * Returns the thumbnail path, or null when GD is unavailable or the image is already small.
 */
function make_thumbnail(string $path): ?string
{
    $abs = ROOT_PATH . '/' . $path;
    $info = @getimagesize($abs);
    if (!$info || $info[0] <= THUMB_WIDTH || !function_exists('imagecreatetruecolor')) return null;
    $src = match ($info['mime']) {
        'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($abs) : false,
        'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($abs) : false,
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($abs) : false,
        default => false,
    };
    if (!$src) return null;
    $w = THUMB_WIDTH;
    $h = max(1, (int)round($info[1] * $w / $info[0]));
    $dst = imagecreatetruecolor($w, $h);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $info[0], $info[1]);
    $base = preg_replace('/\.[a-z0-9]+$/i', '', $path) . '-thumb';
    if (function_exists('imagewebp')) { $out = "$base.webp"; $ok = imagewebp($dst, ROOT_PATH . "/$out", 78); }
    else { $out = "$base.jpg"; $ok = imagejpeg($dst, ROOT_PATH . "/$out", 82); }
    imagedestroy($src);
    imagedestroy($dst);
    return $ok ? $out : null;
}

/**
 * Validate + store an uploaded hero video (MP4/WebM). Checks extension, MIME type, container
 * signature and size; stores under assets/uploads/videos/YYYY/MM/ with a random name.
 */
function store_uploaded_video(array $file): array
{
    if (!isset($file['error']) || is_array($file['error'])) throw new UploadError('Invalid upload.');
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) throw new UploadError('The video is larger than the server allows. Raise upload_max_filesize/post_max_size in PHP or compress the video.');
    if ($file['error'] !== UPLOAD_ERR_OK) throw new UploadError('Upload failed (code ' . (int)$file['error'] . ').');
    if (!is_uploaded_file($file['tmp_name'])) throw new UploadError('Invalid upload.');
    if ($file['size'] <= 0 || $file['size'] > VIDEO_MAX_BYTES) throw new UploadError('Videos must be under ' . (VIDEO_MAX_BYTES / 1048576) . ' MB. Please compress the clip (e.g. HandBrake, 1080p, “Web Optimized”).');
    $original = basename((string)$file['name']);
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!isset(VIDEO_TYPES[$ext])) throw new UploadError('Only MP4 and WebM videos are allowed.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    if (!in_array($mime, VIDEO_TYPES[$ext], true)) throw new UploadError("The file content ($mime) does not match its .$ext extension.");
    $head = (string)file_get_contents($file['tmp_name'], false, null, 0, 16);
    $valid = $ext === 'webm' ? str_starts_with($head, "\x1A\x45\xDF\xA3") : substr($head, 4, 4) === 'ftyp';
    if (!$valid) throw new UploadError('This does not look like a valid video file.');
    $subdir = UPLOAD_DIR . '/videos/' . date('Y/m');
    $absDir = ROOT_PATH . '/' . $subdir;
    if (!is_dir($absDir) && !mkdir($absDir, 0755, true) && !is_dir($absDir)) throw new UploadError('Upload folder is not writable.');
    $name = bin2hex(random_bytes(6)) . '-' . slugify(pathinfo($original, PATHINFO_FILENAME), 40) . '.' . ($ext === 'm4v' ? 'mp4' : $ext);
    if (!move_uploaded_file($file['tmp_name'], "$absDir/$name")) throw new UploadError('Could not save the video.');
    return ['file_name' => $name, 'original_name' => mb_substr($original, 0, 190), 'file_path' => "$subdir/$name", 'thumb_path' => '',
        'file_type' => $ext === 'webm' ? 'video/webm' : 'video/mp4', 'file_size' => (int)$file['size'], 'width' => null, 'height' => null, 'folder' => 'videos'];
}

/**
 * Allow-list SVG sanitiser: keeps only drawing elements/attributes; removes scripts, event handlers,
 * foreignObject, external references and entity declarations. Rejects files it cannot parse safely.
 */
function sanitize_svg(string $svg): string
{
    if (strlen($svg) > UPLOAD_MAX_BYTES || $svg === '') throw new UploadError('Invalid SVG file.');
    if (preg_match('/<!(DOCTYPE|ENTITY)/i', $svg)) throw new UploadError('SVG files with DOCTYPE/ENTITY declarations are not allowed.');

    $allowedTags = ['svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'text', 'tspan', 'defs', 'lineargradient', 'radialgradient', 'stop', 'clippath', 'mask', 'pattern', 'symbol', 'use', 'title', 'desc', 'filter', 'fegaussianblur', 'feoffset', 'feblend', 'fecolormatrix', 'feturbulence', 'femerge', 'femergenode', 'feflood', 'fecomposite', 'image'];
    $allowedAttrs = ['id', 'class', 'style', 'd', 'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'width', 'height', 'viewbox', 'fill', 'fill-opacity', 'fill-rule', 'clip-rule', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-opacity', 'stroke-dasharray', 'stroke-miterlimit', 'opacity', 'transform', 'points', 'offset', 'stop-color', 'stop-opacity', 'gradientunits', 'gradienttransform', 'fx', 'fy', 'preserveaspectratio', 'xmlns', 'version', 'font-family', 'font-size', 'font-weight', 'font-style', 'text-anchor', 'letter-spacing', 'dominant-baseline', 'clip-path', 'mask', 'filter', 'href', 'xlink:href', 'patternunits', 'stddeviation', 'dx', 'dy', 'in', 'in2', 'result', 'mode', 'values', 'type', 'basefrequency', 'numoctaves', 'stitchtiles', 'operator', 'flood-color', 'flood-opacity', 'xmlns:xlink'];

    $prev = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = $dom->loadXML($svg, LIBXML_NONET | LIBXML_NOBLANKS);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    if (!$loaded || !$dom->documentElement || strtolower($dom->documentElement->localName) !== 'svg') throw new UploadError('Invalid SVG file.');

    $walk = function (DOMNode $node) use (&$walk, $allowedTags, $allowedAttrs): void {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMProcessingInstruction || $child instanceof DOMComment) { $node->removeChild($child); continue; }
            if (!$child instanceof DOMElement) continue;
            if (!in_array(strtolower($child->localName), $allowedTags, true)) { $node->removeChild($child); continue; }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $n = strtolower($attr->nodeName);
                $v = trim($attr->nodeValue ?? '');
                $bad = !in_array($n, $allowedAttrs, true) || str_starts_with($n, 'on')
                    || preg_match('/(javascript|vbscript|data:(?!image\/(png|jpe?g|webp|gif);base64,))/i', $v)
                    || (in_array($n, ['href', 'xlink:href'], true) && !preg_match('/^(#|data:image\/(png|jpe?g|webp|gif);base64,)/i', $v))
                    || ($n === 'style' && preg_match('/(url\s*\(|expression|@import|javascript)/i', $v) && !preg_match('/^[^()]*url\(\s*#/i', $v));
                if ($bad) $child->removeAttributeNode($attr);
            }
            $walk($child);
        }
    };
    $walk($dom);
    // The root element itself
    foreach (iterator_to_array($dom->documentElement->attributes) as $attr) {
        if (str_starts_with(strtolower($attr->nodeName), 'on')) $dom->documentElement->removeAttributeNode($attr);
    }
    $out = $dom->saveXML($dom->documentElement);
    if ($out === false || preg_match('/<script|on[a-z]+\s*=|javascript:/i', $out)) throw new UploadError('This SVG contains unsafe content and was rejected.');
    return $out;
}

/** Delete a stored file safely (only inside assets/uploads). */
function delete_upload_file(string $path): void
{
    if (!str_starts_with($path, UPLOAD_DIR . '/') || str_contains($path, '..')) return;
    $abs = realpath(ROOT_PATH . '/' . $path);
    $root = realpath(ROOT_PATH . '/' . UPLOAD_DIR);
    if ($abs && $root && str_starts_with($abs, $root . DIRECTORY_SEPARATOR) && is_file($abs)) @unlink($abs);
}

/** Columns that can hold an image path. */
function image_reference_columns(): array
{
    return [
        ['page_sections', 'image'], ['hero_slides', 'poster_image'], ['hero_slides', 'video_path'], ['hero_slides', 'video_webm_path'], ['hero_slides', 'mobile_video_path'],
        ['services', 'cover_image'], ['portfolio_albums', 'cover_image'], ['films', 'thumbnail'],
        ['testimonials', 'client_image'], ['team_members', 'profile_image'], ['settings', 'setting_value'],
    ];
}

/** JSON columns that may contain image paths. */
function image_reference_json_columns(): array
{
    return [['page_sections', 'extra'], ['services', 'gallery']];
}

/** How many places use this image path (columns + section JSON). */
function media_usage_count(string $path): int
{
    $n = 0;
    foreach (image_reference_columns() as [$table, $col]) {
        $n += (int)db_val("SELECT COUNT(*) FROM $table WHERE $col = ?", [$path]);
    }
    foreach (image_reference_json_columns() as [$table, $col]) $n += (int)db_val("SELECT COUNT(*) FROM $table WHERE $col LIKE ?", ['%"' . $path . '"%']);
    return $n;
}

/** After replacing an image, point every reference at the new path. Returns rows changed. */
function replace_media_references(string $old, string $new): int
{
    $n = 0;
    foreach (image_reference_columns() as [$table, $col]) {
        $n += db_run("UPDATE $table SET $col = ? WHERE $col = ?", [$new, $old])->rowCount();
    }
    foreach (image_reference_json_columns() as [$table, $col]) {
        $n += db_run("UPDATE $table SET $col = REPLACE($col, ?, ?), updated_at = ? WHERE $col LIKE ?", ['"' . $old . '"', '"' . $new . '"', now(), '%"' . $old . '"%'])->rowCount();
    }
    return $n;
}

/** Remove an album photo (and its thumbnail) from disk unless something else still uses the file. */
function delete_album_photo_files(array $img): void
{
    foreach (array_unique(array_filter([$img['file_path'] ?? '', $img['thumb_path'] ?? ''])) as $p) {
        if (!str_starts_with($p, UPLOAD_DIR . '/')) continue;           // never touch bundled sample images
        $used = (int)db_val('SELECT COUNT(*) FROM portfolio_images WHERE file_path = ? OR thumb_path = ?', [$p, $p])
              + (int)db_val('SELECT COUNT(*) FROM media WHERE file_path = ?', [$p])
              + (int)db_val('SELECT COUNT(*) FROM portfolio_albums WHERE cover_image = ?', [$p]);
        if ($used === 0) delete_upload_file($p);
    }
}
