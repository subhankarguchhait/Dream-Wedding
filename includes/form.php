<?php
/**
 * Schema-driven form rendering (admin) and server-side validation.
 * Inputs are named like f[title], f[items][3][text], f[social_links][instagram].
 * validate_fields() never trusts the browser: every value is type-checked, trimmed, length-limited
 * and filtered before it reaches the database.
 */
declare(strict_types=1);
defined('APP_NAME') || exit; // no direct access

const ICON_PICKS = ['fa-solid fa-bolt', 'fa-solid fa-star', 'fa-solid fa-gem', 'fa-solid fa-chart-line', 'fa-solid fa-bullseye', 'fa-solid fa-hashtag', 'fa-solid fa-video', 'fa-solid fa-camera', 'fa-solid fa-laptop-code', 'fa-solid fa-magnifying-glass-chart', 'fa-solid fa-bullhorn', 'fa-solid fa-pen-nib', 'fa-solid fa-palette', 'fa-solid fa-chess-queen', 'fa-solid fa-chess-knight', 'fa-solid fa-handshake', 'fa-solid fa-heart', 'fa-solid fa-rocket', 'fa-solid fa-lightbulb', 'fa-solid fa-users', 'fa-solid fa-clapperboard', 'fa-solid fa-utensils', 'fa-solid fa-hotel', 'fa-solid fa-stethoscope', 'fa-solid fa-graduation-cap', 'fa-solid fa-trophy', 'fa-solid fa-leaf', 'fa-solid fa-scale-balanced'];

/* ================================================================ render */

function field_name(string $prefix, string $k): string { return $prefix . '[' . $k . ']'; }
function field_id(string $name): string { return 'f_' . trim(preg_replace('/[^a-z0-9]+/i', '_', $name), '_'); }

function render_fields(array $fields, array $values, string $prefix = 'f'): void
{
    echo '<div class="form-grid">';
    foreach ($fields as $f) render_field($f, $values[$f['k']] ?? null, $prefix);
    echo '</div>';
}

function render_field(array $f, mixed $value, string $prefix): void
{
    $t = $f['t'] ?? 'text';
    $name = field_name($prefix, $f['k']);
    $id = field_id($name);
    $full = !empty($f['full']) || in_array($t, ['textarea', 'image', 'repeater', 'group', 'tags', 'icon', 'image_list', 'video'], true);
    $req = !empty($f['required']);
    $label = e($f['label']) . ($req ? ' <span class="req">*</span>' : '');
    $help = isset($f['help']) ? '<small class="f__help">' . e($f['help']) . '</small>' : '';
    $err = '<small class="f__err" data-err-for="' . e($name) . '"></small>';
    $maxlen = isset($f['maxlen']) ? ' maxlength="' . (int)$f['maxlen'] . '"' : '';
    $ph = isset($f['placeholder']) ? ' placeholder="' . e($f['placeholder']) . '"' : '';
    $reqAttr = $req ? ' required' : '';

    echo '<div class="f' . ($full ? ' f--full' : '') . '" data-field="' . e($name) . '">';
    switch ($t) {
        case 'textarea':
            echo "<label for=\"$id\">$label</label><textarea id=\"$id\" name=\"" . e($name) . "\" rows=\"" . (int)($f['rows'] ?? 4) . "\"$maxlen$ph$reqAttr data-count>" . e($value) . '</textarea>';
            break;
        case 'toggle':
        case 'status':
            $checked = $t === 'status' ? (($value ?? $f['default'] ?? 'published') === 'published') : (bool)($value ?? $f['default'] ?? false);
            echo '<input type="hidden" name="' . e($name) . '" value="0"><label class="switch" for="' . $id . '"><input type="checkbox" id="' . $id . '" name="' . e($name) . '" value="1"' . ($checked ? ' checked' : '') . '><span class="switch__track"></span><span>' . e($f['label']) . '</span></label>';
            break;
        case 'select':
            echo "<label for=\"$id\">$label</label><select id=\"$id\" name=\"" . e($name) . "\"$reqAttr>";
            foreach ($f['options'] as $ov => $ol) echo '<option value="' . e($ov) . '"' . ((string)$value === (string)$ov ? ' selected' : '') . '>' . e($ol) . '</option>';
            echo '</select>';
            break;
        case 'tags':
            $lines = is_array($value) ? implode("\n", $value) : (string)$value;
            echo "<label for=\"$id\">$label</label><textarea id=\"$id\" name=\"" . e($name) . "\" rows=\"" . max(3, min(8, substr_count($lines, "\n") + 2)) . "\"$reqAttr>" . e($lines) . '</textarea>';
            if (!isset($f['help'])) $help = '<small class="f__help">One item per line.</small>';
            break;
        case 'number':
            $v = $value ?? ($f['default'] ?? '');
            $mm = (isset($f['min']) ? ' min="' . (int)$f['min'] . '"' : '') . (isset($f['max']) ? ' max="' . (int)$f['max'] . '"' : '');
            echo "<label for=\"$id\">$label</label><input type=\"number\" id=\"$id\" name=\"" . e($name) . '" value="' . e($v) . "\"$mm$reqAttr>";
            break;
        case 'icon':
            echo "<label for=\"$id\">$label</label><div data-icon-field><div class=\"icon-field\"><span class=\"icon-field__preview\"><i class=\"" . e(safe_icon((string)$value)) . '"></i></span>'
                . "<input type=\"text\" id=\"$id\" name=\"" . e($name) . '" value="' . e($value) . '" placeholder="fa-solid fa-star" autocomplete="off"></div><div class="icon-picks">';
            foreach (ICON_PICKS as $ic) echo '<button type="button" title="' . e($ic) . '" data-icon="' . e($ic) . '"><i class="' . e($ic) . '"></i></button>';
            echo '</div></div>';
            if (!isset($f['help'])) $help = '<small class="f__help">Pick an icon or paste any Font Awesome class, e.g. fa-solid fa-rocket.</small>';
            break;
        case 'image':
            $src = media_url((string)$value);
            $prev = $src !== '' ? '<img src="' . e($src) . '" alt="">' : '<i class="fa-regular fa-image"></i>';
            echo "<label for=\"$id\">$label</label>"
                . '<div class="img-field" data-image-field data-folder="' . e($f['folder'] ?? 'general') . '">'
                . '<div class="img-field__preview">' . $prev . '</div><div class="img-field__body">'
                . "<input type=\"text\" id=\"$id\" name=\"" . e($name) . '" value="' . e($value) . "\" placeholder=\"Upload, pick from the library or paste an image path\"$reqAttr>"
                . '<div class="img-field__btns"><button type="button" class="btn btn--forest btn--sm" data-img-upload><i class="fa-solid fa-cloud-arrow-up"></i>Upload</button>'
                . '<button type="button" class="btn btn--ghost btn--sm" data-img-library><i class="fa-regular fa-images"></i>Library</button>'
                . '<button type="button" class="btn btn--ghost btn--sm" data-img-remove' . ($src === '' ? ' hidden' : '') . '><i class="fa-solid fa-trash-can"></i>Remove</button></div>'
                . '<div class="progress" hidden><span></span></div><input type="file" accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml" hidden></div></div>';
            if (!isset($f['help'])) $help = '<small class="f__help">JPG, PNG, WebP or SVG up to ' . (UPLOAD_MAX_BYTES / 1048576) . ' MB. Large photos are resized and converted to WebP automatically.</small>';
            break;
        case 'date':
            echo "<label for=\"$id\">$label</label><input type=\"date\" id=\"$id\" name=\"" . e($name) . '" value="' . e($value) . "\"$reqAttr>";
            break;
        case 'video':
            $vsrc = video_src((string)$value);
            $acc = ($f['accept'] ?? 'mp4') === 'webm' ? '.webm,video/webm' : '.mp4,.m4v,video/mp4';
            echo "<label for=\"$id\">$label</label>"
                . '<div class="img-field" data-video-field>'
                . '<div class="img-field__preview img-field__preview--video">' . ($vsrc !== '' ? '<video src="' . e($vsrc) . '" muted playsinline preload="metadata"></video>' : '<i class="fa-solid fa-film"></i>') . '</div><div class="img-field__body">'
                . "<input type=\"text\" id=\"$id\" name=\"" . e($name) . '" value="' . e($value) . "\" placeholder=\"Upload a video or paste its path\"$reqAttr>"
                . '<div class="img-field__btns"><button type="button" class="btn btn--forest btn--sm" data-video-upload><i class="fa-solid fa-cloud-arrow-up"></i>Upload video</button>'
                . '<button type="button" class="btn btn--ghost btn--sm" data-video-remove' . ($vsrc === '' ? ' hidden' : '') . '><i class="fa-solid fa-trash-can"></i>Remove</button></div>'
                . '<div class="progress" hidden><span></span></div><input type="file" accept="' . $acc . '" hidden></div></div>';
            if (!isset($f['help'])) $help = '<small class="f__help">MP4 or WebM up to ' . (VIDEO_MAX_BYTES / 1048576) . ' MB.</small>';
            break;
        case 'image_list':
            $paths = is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
            echo '<span class="f__label">' . $label . '</span><div class="img-list" data-image-list data-name="' . e($name) . '" data-folder="' . e($f['folder'] ?? 'general') . '" data-max="' . (int)($f['max'] ?? 12) . '">'
                . '<input type="hidden" name="' . e($name) . '" value="">'
                . '<div class="img-list__items">';
            foreach ($paths as $p) echo image_list_item($name, $p);
            echo '</div><div class="img-field__btns"><button type="button" class="btn btn--forest btn--sm" data-list-upload><i class="fa-solid fa-cloud-arrow-up"></i>Upload photos</button>'
                . '<button type="button" class="btn btn--ghost btn--sm" data-list-library><i class="fa-regular fa-images"></i>Add from library</button></div>'
                . '<div class="progress" hidden><span></span></div><input type="file" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" hidden></div>';
            break;
        case 'group':
            echo '<fieldset class="fieldset"><legend>' . e($f['label']) . '</legend>';
            render_fields($f['fields'], is_array($value) ? $value : [], $name);
            echo '</fieldset>';
            break;
        case 'repeater':
            $items = is_array($value) ? array_values($value) : [];
            echo '<span class="f__label">' . $label . '</span><div class="repeater" data-repeater data-max="' . (int)($f['max'] ?? 20) . '" data-label-field="' . e($f['item_label'] ?? 'title') . '">';
            echo '<div class="repeater__list">';
            foreach ($items as $i => $item) render_repeater_item($f, is_array($item) ? $item : [], $name, (string)$i);
            echo '</div><template>';
            render_repeater_item($f, [], $name, '__INDEX__', true);
            echo '</template><div><button type="button" class="btn btn--ghost btn--sm" data-rep-add><i class="fa-solid fa-plus"></i>Add ' . e($f['singular'] ?? 'item') . '</button></div></div>';
            break;
        default:
            $type = match ($t) { 'email' => 'email', 'url', 'video_url' => 'url', default => 'text' };
            $list = isset($f['list']) ? ' list="' . e($f['list']) . '"' : ($t === 'link' ? ' list="link-suggestions"' : '');
            echo "<label for=\"$id\">$label</label><input type=\"$type\" id=\"$id\" name=\"" . e($name) . '" value="' . e($value) . "\"$maxlen$ph$reqAttr$list autocomplete=\"off\">";
            if ($t === 'link' && !isset($f['help'])) $help = '<small class="f__help">A page (contact.php), a section (#about), #whatsapp / #email / #phone, or a full https:// link.</small>';
            if ($t === 'url' && !isset($f['help'])) $help = '<small class="f__help">Full link starting with https://</small>';
    }
    echo $help . $err . '</div>';
}

function image_list_item(string $name, string $path): string
{
    return '<div class="img-list__item" draggable="true"><img src="' . e(media_url($path)) . '" alt=""><input type="hidden" name="' . e($name) . '[]" value="' . e($path) . '">'
        . '<button type="button" data-list-remove aria-label="Remove photo"><i class="fa-solid fa-xmark"></i></button></div>';
}

function render_repeater_item(array $f, array $item, string $name, string $index, bool $open = false): void
{
    $labelKey = $f['item_label'] ?? 'title';
    $title = trim((string)($item[$labelKey] ?? '')) ?: ucfirst($f['singular'] ?? 'item');
    echo '<div class="rep-item' . ($open ? '' : ' is-collapsed') . '"><div class="rep-item__head" data-rep-toggle><span class="rep-item__num"></span><strong>' . e($title) . '</strong>'
        . '<button type="button" class="btn btn--ghost btn--icon btn--sm" data-rep-up title="Move up"><i class="fa-solid fa-arrow-up"></i></button>'
        . '<button type="button" class="btn btn--ghost btn--icon btn--sm" data-rep-down title="Move down"><i class="fa-solid fa-arrow-down"></i></button>'
        . '<button type="button" class="btn btn--danger btn--icon btn--sm" data-rep-remove title="Remove"><i class="fa-solid fa-trash-can"></i></button>'
        . '<i class="fa-solid fa-chevron-down" style="color:var(--muted);font-size:11px"></i></div><div class="rep-item__body">';
    render_fields($f['fields'], $item, $name . '[' . $index . ']');
    echo '</div></div>';
}

/* ============================================================== validate */

/**
 * Validate and clean submitted values against a schema.
 * Returns [array $clean, array $errors] — errors are keyed by input name (e.g. "f[items][2][title]").
 */
function validate_fields(array $fields, mixed $input, string $prefix = 'f'): array
{
    $input = is_array($input) ? $input : [];
    $clean = [];
    $errors = [];
    foreach ($fields as $f) {
        $t = $f['t'] ?? 'text';
        $name = field_name($prefix, $f['k']);
        $raw = $input[$f['k']] ?? null;
        $label = $f['label'];
        $err = '';

        switch ($t) {
            case 'toggle':
                $v = is_array($raw) ? (string)end($raw) : (string)$raw;
                $clean[$f['k']] = $v === '1' ? 1 : 0;
                continue 2;
            case 'status':
                $v = is_array($raw) ? (string)end($raw) : (string)$raw;
                $clean[$f['k']] = $v === '1' ? 'published' : 'draft';
                continue 2;
            case 'group':
                [$c, $e2] = validate_fields($f['fields'], $raw, $name);
                $clean[$f['k']] = $c;
                $errors += $e2;
                continue 2;
            case 'repeater':
                $items = [];
                foreach (is_array($raw) ? $raw : [] as $idx => $item) {
                    if (!is_array($item)) continue;
                    [$c, $e2] = validate_fields($f['fields'], $item, $name . '[' . $idx . ']');
                    if (!array_filter($c, fn($x) => $x !== '' && $x !== [] && $x !== 0)) continue;   // skip completely empty rows
                    $items[] = $c;
                    $errors += $e2;
                }
                if (count($items) > ($f['max'] ?? 20)) $err = 'Maximum ' . ($f['max'] ?? 20) . ' items.';
                if (!empty($f['required']) && !$items) $err = "Add at least one " . ($f['singular'] ?? 'item') . '.';
                $clean[$f['k']] = array_slice($items, 0, $f['max'] ?? 20);
                if ($err) $errors[$name] = $err;
                continue 2;
            case 'image_list':
                $list = [];
                foreach (is_array($raw) ? $raw : [] as $p) {
                    $p = is_string($p) ? trim($p) : '';
                    if ($p !== '' && is_safe_media_path($p) && !in_array($p, $list, true)) $list[] = $p;
                }
                if (count($list) > ($f['max'] ?? 12)) $errors[$name] = 'Maximum ' . ($f['max'] ?? 12) . ' photos.';
                $clean[$f['k']] = array_slice($list, 0, $f['max'] ?? 12);
                continue 2;
            case 'tags':
                $s = is_string($raw) ? $raw : '';
                $list = [];
                foreach (preg_split('/\R/u', $s) ?: [] as $line) {
                    $line = clean_text($line, 150);
                    if ($line !== '' && !in_array($line, $list, true)) $list[] = $line;
                }
                $clean[$f['k']] = array_slice($list, 0, 40);
                if (!empty($f['required']) && !$list) $errors[$name] = "$label is required.";
                continue 2;
        }

        $v = is_string($raw) || is_numeric($raw) ? (string)$raw : '';
        $multiline = $t === 'textarea';
        $v = clean_text($v, (int)($f['maxlen'] ?? ($multiline ? 5000 : 255)), $multiline);

        if ($v === '' && !empty($f['required'])) $err = "$label is required.";
        elseif ($v !== '') {
            switch ($t) {
                case 'email':
                    if (!filter_var($v, FILTER_VALIDATE_EMAIL)) $err = 'Enter a valid email address.';
                    break;
                case 'url':
                    if (!preg_match('#^https?://#i', $v) || !filter_var($v, FILTER_VALIDATE_URL)) $err = 'Enter a full link starting with https://';
                    break;
                case 'link':
                    if (!is_valid_link($v)) $err = 'That doesn\'t look like a valid link.';
                    break;
                case 'number':
                    if (!is_numeric($v)) { $err = 'Enter a number.'; break; }
                    $n = $v + 0;
                    if (isset($f['min']) && $n < $f['min']) $err = "Minimum is {$f['min']}.";
                    if (isset($f['max']) && $n > $f['max']) $err = "Maximum is {$f['max']}.";
                    $v = (string)(int)round((float)$n);
                    break;
                case 'icon':
                    if (safe_icon($v, '') === '') $err = 'Use a Font Awesome class like fa-solid fa-star.';
                    break;
                case 'date':
                    $d = DateTime::createFromFormat('Y-m-d', $v);
                    if (!$d || $d->format('Y-m-d') !== $v) $err = 'Enter a valid date.';
                    break;
                case 'video':
                    if (!is_safe_video_path($v)) $err = 'Upload an MP4/WebM video with the Upload button.';
                    break;
                case 'video_url':
                    if (!video_embed($v)) $err = 'Use a YouTube, Vimeo or direct .mp4 link.';
                    break;
                case 'image':
                    if (!preg_match('#^https://#i', $v) && !is_safe_media_path($v)) $err = 'Choose an image with Upload or Library (or an https:// image link).';
                    break;
                case 'select':
                    if (!array_key_exists($v, $f['options'])) $err = 'Choose one of the options.';
                    break;
            }
            if (($f['pattern'] ?? '') === 'phone' && !preg_match('/^[+()\d\s\-]{6,20}$/', $v)) $err = 'Enter a valid phone number (digits, spaces, + and - only).';
        }
        if ($t === 'number') $v = $v === '' ? (int)($f['default'] ?? 0) : (int)$v;
        if ($err) $errors[$name] = $err;
        $clean[$f['k']] = $v;
    }
    return [$clean, $errors];
}
