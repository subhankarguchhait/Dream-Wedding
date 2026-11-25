<?php
/**
 * Shared helpers: escaping, URLs, settings, page/section loading, collections,
 * responses, flash messages, logging.
 */
declare(strict_types=1);
defined('APP_NAME') || exit; // no direct access

/* ------------------------------------------------------------------ output */

/** Escape for HTML text and attributes. Use for EVERY dynamic value. */
function e(mixed $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** "Grow your *brand*" → escaped HTML with <em>brand</em>; newlines → <br>. */
function rich(?string $text): string
{
    $html = e($text ?? '');
    $html = preg_replace('/\*([^*]+)\*/u', '<em>$1</em>', $html);
    return nl2br($html, false);
}

/** Paragraphs separated by blank lines → <p> elements (escaped). */
function paras(?string $text): string
{
    $parts = preg_split('/\R\s*\R/u', trim((string)$text)) ?: [];
    return implode('', array_map(fn($p) => '<p>' . nl2br(e(trim($p)), false) . '</p>', array_filter($parts, fn($p) => trim($p) !== '')));
}

/** Plain text without the *highlight* markers (for alt text, titles, meta). */
function plain(?string $text): string
{
    return trim(str_replace('*', '', (string)$text));
}

function url(string $path = ''): string
{
    return base_url() . ltrim($path, '/');
}

/** Static asset URL with cache-busting version. */
function asset(string $path): string
{
    $file = ROOT_PATH . '/' . ltrim($path, '/');
    $v = is_file($file) ? (string)filemtime($file) : '1';
    return url($path) . '?v=' . $v;
}

/** URL for a stored image path (relative "assets/…" or absolute https URL). Empty if unsafe. */
function media_url(?string $path): string
{
    $p = trim((string)$path);
    if ($p === '') return '';
    if (preg_match('#^https?://#i', $p)) return filter_var($p, FILTER_VALIDATE_URL) ? $p : '';
    if (!is_safe_media_path($p)) return '';
    return url($p);
}

function is_safe_media_path(string $p): bool
{
    return (bool)preg_match('#^assets/[A-Za-z0-9/_.\-]+\.(jpe?g|png|webp|gif|svg)$#i', $p) && !str_contains($p, '..');
}

function is_safe_video_path(string $p): bool
{
    return (bool)preg_match('#^assets/[A-Za-z0-9/_.\-]+\.(mp4|m4v|webm)$#i', $p) && !str_contains($p, '..');
}

/** URL for a stored video path or external https video file. */
function video_src(?string $path): string
{
    $p = trim((string)$path);
    if ($p === '') return '';
    if (preg_match('#^https://#i', $p)) return filter_var($p, FILTER_VALIDATE_URL) ? $p : '';
    return is_safe_video_path($p) ? url($p) : '';
}

/**
 * Turn a YouTube / Vimeo / .mp4 link into something safe to embed.
 * Returns ['type' => youtube|vimeo|file, 'src' => embed url, 'thumb' => fallback thumbnail] or null.
 */
function video_embed(?string $url): ?array
{
    $u = trim((string)$url);
    if ($u === '' || !filter_var($u, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $u)) return null;
    if (preg_match('#(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $u, $m)) {
        return ['type' => 'youtube', 'id' => $m[1], 'src' => 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0&modestbranding=1&playsinline=1', 'thumb' => 'https://i.ytimg.com/vi/' . $m[1] . '/hqdefault.jpg'];
    }
    if (preg_match('#vimeo\.com/(?:video/)?(\d{6,12})#', $u, $m)) {
        return ['type' => 'vimeo', 'id' => $m[1], 'src' => 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1&title=0&byline=0&portrait=0', 'thumb' => ''];
    }
    if (preg_match('#^https://[^\s]+\.(mp4|webm)(\?.*)?$#i', $u)) return ['type' => 'file', 'id' => '', 'src' => $u, 'thumb' => ''];
    return null;
}

/**
 * Playable media for a film or reel: an uploaded MP4 (video_path) wins over a link (video_url).
 * Returns ['type' => file|youtube|vimeo, 'src', 'thumb', 'file' => local/https MP4 URL or ''] or null.
 */
function film_media(array $item): ?array
{
    $file = video_src($item['video_path'] ?? '');
    if ($file !== '') return ['type' => 'file', 'id' => '', 'src' => $file, 'thumb' => '', 'file' => $file];
    $emb = video_embed($item['video_url'] ?? '');
    if (!$emb) return null;
    $emb['file'] = $emb['type'] === 'file' ? $emb['src'] : '';
    return $emb;
}

/** data-video JSON for the video modal. */
function video_data(array $media, string $title, bool $vertical = false): string
{
    $d = ['type' => $media['type'], 'src' => $media['src'], 'title' => $title];
    if ($vertical) $d['vertical'] = true;
    return json_encode($d, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

const LINK_TOKENS = ['#whatsapp', '#email', '#phone'];

/** Is this an acceptable CMS link? page (about.php), anchor (#about), token, mailto:, tel:, http(s) URL. */
function is_valid_link(string $v): bool
{
    $v = trim($v);
    if ($v === '') return true;
    if (in_array($v, LINK_TOKENS, true)) return true;
    if (preg_match('#^https?://#i', $v)) return (bool)filter_var($v, FILTER_VALIDATE_URL);
    if (preg_match('#^mailto:[^\s@]+@[^\s@]+$#i', $v)) return true;
    if (preg_match('#^tel:[+\d\s()\-]{5,}$#i', $v)) return true;
    if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $v)) return false;           // javascript:, data:, …
    return (bool)preg_match('#^[A-Za-z0-9_\-./?=&%\#]+$#', $v) && !str_contains($v, '..') && !str_starts_with($v, '//');
}

/** Turn a CMS link into a safe href. */
function link_href(?string $v): string
{
    $v = trim((string)$v);
    if ($v === '' || !is_valid_link($v)) return '#';
    return match ($v) {
        '#whatsapp' => contact_href('whatsapp', setting('whatsapp')) ?: '#',
        '#email' => contact_href('email', setting('email')) ?: '#',
        '#phone' => contact_href('phone', setting('phone')) ?: '#',
        default => (preg_match('#^(https?:|mailto:|tel:|\#)#i', $v) ? $v : url($v)),
    };
}

/** target/rel attributes for external links. */
function link_attrs(?string $v): string
{
    return preg_match('#^https?://#i', (string)$v) ? ' target="_blank" rel="noopener"' : '';
}

function contact_href(string $type, ?string $value): string
{
    $v = trim((string)$value);
    if ($v === '') return '';
    return match ($type) {
        'email' => filter_var($v, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $v : '',
        'phone' => 'tel:' . preg_replace('/[^\d+]/', '', $v),
        'whatsapp' => 'https://wa.me/' . preg_replace('/\D/', '', $v),
        'address' => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($v),
        default => '',
    };
}

const SOCIAL_ICONS = [
    'instagram' => 'fa-brands fa-instagram',
    'facebook' => 'fa-brands fa-facebook-f',
    'youtube' => 'fa-brands fa-youtube',
    'vimeo' => 'fa-brands fa-vimeo-v',
    'pinterest' => 'fa-brands fa-pinterest-p',
];

function safe_icon(?string $cls, string $fallback = 'fa-solid fa-star'): string
{
    $c = trim((string)$cls);
    return preg_match('/^fa-(solid|regular|brands)( fa-[a-z0-9\-]+)+$/', $c) ? $c : $fallback;
}

function initials(string $name): string
{
    $words = preg_split('/\s+/', trim($name)) ?: [];
    $out = '';
    foreach (array_slice($words, 0, 2) as $w) $out .= mb_strtoupper(mb_substr($w, 0, 1));
    return $out ?: 'DS';
}

function slugify(string $s, int $max = 80): string
{
    $s = mb_strtolower(trim($s));
    if (function_exists('iconv')) $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim(substr(trim((string)$s, '-'), 0, $max), '-') ?: 'item';
}

function json_enc(mixed $v): string
{
    return json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

function json_dec(?string $s, mixed $default = []): mixed
{
    if ($s === null || $s === '') return $default;
    $v = json_decode($s, true);
    return $v === null ? $default : $v;
}

function format_date(?string $dt, bool $time = false): string
{
    if (!$dt) return '—';
    $ts = strtotime($dt);
    return $ts ? date($time ? 'd M Y, h:i A' : 'd M Y', $ts) : '—';
}

function time_ago(?string $dt): string
{
    $ts = $dt ? strtotime($dt) : false;
    if (!$ts) return '';
    $s = time() - $ts;
    if ($s < 60) return 'just now';
    if ($s < 3600) return floor($s / 60) . ' min ago';
    if ($s < 86400) return floor($s / 3600) . ' h ago';
    if ($s < 30 * 86400) return floor($s / 86400) . ' d ago';
    return format_date($dt);
}

/* --------------------------------------------------------------- logging */

function log_error(string $message): void
{
    $line = sprintf("[%s] %s%s", date('Y-m-d H:i:s'), $message, PHP_EOL);
    @file_put_contents(LOG_PATH . '/app.log', $line, FILE_APPEND | LOCK_EX);
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function log_activity(string $action, string $description = ''): void
{
    try {
        db_insert('activity_logs', [
            'admin_id' => current_admin()['id'] ?? null,
            'action' => mb_substr($action, 0, 100),
            'description' => mb_substr($description, 0, 500),
            'ip_address' => client_ip(),
            'created_at' => now(),
        ]);
    } catch (Throwable $e) {
        log_error('Activity log failed: ' . $e->getMessage());
    }
}

/* -------------------------------------------------------------- settings */

/** All settings (database values over defaults). */
function settings(bool $refresh = false): array
{
    static $cache = null;
    if ($cache !== null && !$refresh) return $cache;
    $cache = default_settings();
    try {
        foreach (db_all('SELECT setting_key, setting_value FROM settings') as $r) {
            $cache[$r['setting_key']] = (string)$r['setting_value'];
        }
    } catch (Throwable $e) {
        log_error('Settings fallback: ' . $e->getMessage());
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $s = settings();
    $v = $s[$key] ?? $default;
    return str_replace('{year}', date('Y'), (string)$v);
}

/* ------------------------------------------------------- pages & sections */

const SECTION_COLUMNS = [
    'title' => 'section_title',
    'eyebrow' => 'section_subtitle',
    'content' => 'section_content',
    'image' => 'image',
    'button_text' => 'button_text',
    'button_url' => 'button_url',
];

/** Convert a page_sections row into a flat field array used by templates and editors. */
function section_from_row(array $row): array
{
    $s = json_dec($row['extra'] ?? null, []);
    foreach (SECTION_COLUMNS as $field => $col) $s[$field] = $row[$col] ?? '';
    $s['_id'] = (int)$row['id'];
    $s['_visible'] = (bool)$row['is_visible'];
    $s['_order'] = (int)$row['display_order'];
    return $s;
}

/** Split a field array back into column values + extra JSON for saving. */
function section_to_row(array $values): array
{
    $row = [];
    foreach (SECTION_COLUMNS as $field => $col) {
        $row[$col] = isset($values[$field]) ? (string)$values[$field] : '';
        unset($values[$field]);
    }
    $row['extra'] = json_enc((object)$values);
    return $row;
}

/**
 * Load a page for the public site: meta + ordered, visible sections.
 * Falls back to the built-in default content for any section missing from the database
 * (or for the whole page if the database is not reachable yet).
 */
function get_page(string $slug): array
{
    $defaults = default_pages()[$slug] ?? ['meta' => [], 'sections' => []];
    $schema = page_schemas()[$slug]['sections'] ?? [];
    $meta = $defaults['meta'];
    $sections = [];
    $position = 0;
    foreach ($defaults['sections'] as $key => $s) {
        $sections[$key] = $s + ['_visible' => true, '_order' => ++$position, '_id' => 0];
    }
    try {
        $page = db_one('SELECT * FROM pages WHERE page_slug = ?', [$slug]);
        if ($page) {
            $meta = ['title' => $page['meta_title'], 'description' => $page['meta_description'], 'name' => $page['page_name']];
            foreach (db_all('SELECT * FROM page_sections WHERE page_id = ? ORDER BY display_order, id', [(int)$page['id']]) as $row) {
                if (isset($schema[$row['section_key']])) $sections[$row['section_key']] = section_from_row($row);
            }
        }
    } catch (Throwable $e) {
        log_error("Page '$slug' fallback to defaults: " . $e->getMessage());
    }
    uasort($sections, fn($a, $b) => $a['_order'] <=> $b['_order']);
    return ['slug' => $slug, 'meta' => $meta, 'sections' => $sections];
}

/** Render the visible sections of a page using includes/sections/{template}.php */
function render_sections(array $page): void
{
    $schema = page_schemas()[$page['slug']]['sections'];
    foreach ($page['sections'] as $key => $s) {
        if (empty($s['_visible']) && empty($schema[$key]['always'])) continue;
        $template = $schema[$key]['template'] ?? null;
        if (!$template) continue;
        render_section($template, $s, $key, $page);
    }
}

function render_section(string $template, array $s, string $key = '', array $page = []): void
{
    $file = ROOT_PATH . '/includes/sections/' . basename($template) . '.php';
    if (is_file($file)) (static function () use ($file, $s, $key, $page) { include $file; })();
}

/** Value helper for templates: field from a section with default ''. */
function sv(array $s, string $k, string $default = ''): string
{
    $v = $s[$k] ?? $default;
    return is_scalar($v) ? (string)$v : $default;
}

function sl(array $s, string $k): array
{
    $v = $s[$k] ?? [];
    return is_array($v) ? $v : [];
}

/* ------------------------------------------------------------ collections */

/** Published items of a collection for the public site (ordered). */
function public_collection(string $key, int $limit = 0): array
{
    $c = collection_schemas()[$key];
    try {
        $sql = "SELECT * FROM {$c['table']} WHERE status = 'published' ORDER BY display_order, id";
        if ($limit > 0) $sql .= ' LIMIT ' . (int)$limit;
        $rows = db_all($sql);
        if (!$rows && !db_val("SELECT COUNT(*) FROM {$c['table']}")) throw new RuntimeException('empty table');
        return array_map(fn($r) => decode_item($key, $r), $rows);
    } catch (Throwable $e) {
        // Database not installed yet → show starter content
        if (!($e instanceof RuntimeException && $e->getMessage() === 'empty table')) log_error("Collection '$key' fallback: " . $e->getMessage());
        $items = array_values(array_filter(default_collections()[$key] ?? [], fn($i) => ($i['status'] ?? 'published') === 'published'));
        return $limit > 0 ? array_slice($items, 0, $limit) : $items;
    }
}

/** Decode JSON columns of a collection row. */
function decode_item(string $key, array $row): array
{
    foreach (collection_schemas()[$key]['fields'] as $f) {
        if (in_array($f['t'] ?? 'text', ['tags', 'group', 'repeater', 'image_list'], true) && isset($row[$f['k']]) && is_string($row[$f['k']])) {
            $row[$f['k']] = json_dec($row[$f['k']], []);
        }
    }
    return $row;
}

/* ----------------------------------------------------- requests/responses */

function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }

function is_ajax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function post_str(string $key, int $max = 255): string
{
    $v = $_POST[$key] ?? '';
    if (!is_string($v)) return '';
    $v = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v) ?? '');
    return mb_substr($v, 0, $max);
}

function get_str(string $key, int $max = 200): string
{
    $v = $_GET[$key] ?? '';
    return is_string($v) ? mb_substr(trim($v), 0, $max) : '';
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function redirect(string $to): never
{
    header('Location: ' . (preg_match('#^https?://#', $to) ? $to : url($to)));
    exit;
}

/**
 * Reply to a form action: JSON for fetch() requests, flash message + redirect otherwise.
 */
function respond(bool $ok, string $message, array $extra = [], ?string $back = null, int $status = 0): never
{
    if (is_ajax()) json_response(['ok' => $ok, 'message' => $message] + $extra, $status ?: ($ok ? 200 : 422));
    flash($ok ? 'success' : 'error', $message);
    $target = $extra['redirect'] ?? $back ?? ($_SERVER['HTTP_REFERER'] ?? 'admin/dashboard.php');
    header('Location: ' . (preg_match('#^https?://#', $target) ? $target : url($target)));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Simple pagination numbers. */
function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage, 'per' => $perPage, 'total' => $total];
}

/** Build a query string from the current GET params with overrides. */
function qs(array $override = []): string
{
    $q = array_merge($_GET, $override);
    $q = array_filter($q, fn($v) => $v !== '' && $v !== null);
    return $q ? '?' . http_build_query($q) : '';
}

/* ------------------------------------------------------- template helpers */

/** Section heading block: eyebrow, title (*rich*), description. */
function sec_head(array $s, string $mods = ''): void
{
    $eyebrow = sv($s, 'eyebrow');
    $desc = sv($s, 'description');
    echo '<div class="sec-head ' . e($mods) . '">';
    if ($eyebrow !== '') echo '<p class="eyebrow reveal"><span class="eyebrow__line"></span><span>' . e($eyebrow) . '</span></p>';
    echo '<h2 class="sec-title reveal">' . rich(sv($s, 'title')) . '</h2>';
    if ($desc !== '') echo '<p class="sec-desc reveal">' . e($desc) . '</p>';
    echo '</div>';
}

/** A CMS-driven button; prints nothing when the text is empty. */
function cms_button(string $text, string $link, string $class = 'btn btn--gold', bool $arrow = true): void
{
    if (trim($text) === '') return;
    echo '<a class="' . e($class) . ' magnetic" href="' . e(link_href($link)) . '"' . link_attrs($link) . '><span>' . e($text) . '</span>'
        . ($arrow ? '<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>' : '') . '</a>';
}

/** Social icon links from a key=>url map. */
function social_links(array $links, string $class = 'socials'): void
{
    $out = '';
    foreach ($links as $k => $u) {
        if (!$u || !isset(SOCIAL_ICONS[$k]) || !filter_var($u, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $u)) continue;
        $out .= '<a href="' . e($u) . '" target="_blank" rel="noopener" aria-label="' . e(ucfirst($k)) . '"><i class="' . SOCIAL_ICONS[$k] . '"></i></a>';
    }
    if ($out !== '') echo '<div class="' . e($class) . '">' . $out . '</div>';
}

function site_socials(): array
{
    $out = [];
    foreach (array_keys(SOCIAL_ICONS) as $k) $out[$k] = setting('social_' . $k);
    return $out;
}

/** Image tag for a stored path (nothing when empty). */
function img_tag(string $path, string $alt = '', string $attrs = 'loading="lazy"'): string
{
    $src = media_url($path);
    return $src === '' ? '' : '<img src="' . e($src) . '" alt="' . e($alt) . '" ' . $attrs . '>';
}

/** Site name with the last word in gold italics. */
function site_name_html(): string
{
    $words = preg_split('/\s+/', trim(setting('site_name', APP_NAME))) ?: [APP_NAME];
    $last = count($words) > 1 ? array_pop($words) : '';
    return e(implode(' ', $words)) . ($last !== '' ? ' <em>' . e($last) . '</em>' : '');
}

/** Trim, strip control characters (keeping newlines in multi-line text) and cap length. */
function clean_text(string $v, int $max = 255, bool $multiline = false): string
{
    $v = str_replace("\r\n", "\n", $v);
    $v = preg_replace($multiline ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u' : '/[\x00-\x1F\x7F]/u', '', $v) ?? '';
    return mb_substr(trim($v), 0, $max);
}

/* ------------------------------------------------- wedding-site helpers */

/** Published items flagged as featured (falls back to the first published items). */
function featured_collection(string $key, int $limit): array
{
    $items = public_collection($key);
    $featured = array_values(array_filter($items, fn($i) => !empty($i['is_featured'])));
    return array_slice($featured ?: $items, 0, max(1, $limit));
}

function album_url(string $slug): string
{
    return url('gallery.php?album=' . rawurlencode($slug));
}

/** Seeded placeholder photos for an album (used only when the database is not installed yet). */
function default_album_images(string $slug): array
{
    $dir = ROOT_PATH . "/assets/images/albums/$slug";
    $out = [];
    foreach (glob("$dir/[0-9][0-9].jpg") ?: [] as $f) {
        [$w, $h] = @getimagesize($f) ?: [0, 0];
        $rel = "assets/images/albums/$slug/" . basename($f);
        $thumb = "assets/images/albums/$slug/thumbs/" . basename($f);
        $out[] = ['id' => 0, 'file_path' => $rel, 'thumb_path' => is_file(ROOT_PATH . '/' . $thumb) ? $thumb : $rel, 'width' => $w, 'height' => $h, 'caption' => '', 'alt_text' => ''];
    }
    return $out;
}

/** Published album by slug, or null. */
function find_album(string $slug): ?array
{
    try {
        $a = db_one("SELECT * FROM portfolio_albums WHERE slug = ? AND status = 'published'", [$slug]);
        if ($a) return $a;
        if ((int)db_val('SELECT COUNT(*) FROM portfolio_albums') > 0) return null;
    } catch (Throwable $e) { log_error('Album fallback: ' . $e->getMessage()); }
    foreach (default_collections()['albums'] as $a) if ($a['slug'] === $slug) return $a + ['id' => 0];
    return null;
}

function album_images(array $album, int $limit = 0): array
{
    if (!empty($album['id'])) {
        try {
            $sql = 'SELECT * FROM portfolio_images WHERE album_id = ? ORDER BY display_order, id' . ($limit ? ' LIMIT ' . (int)$limit : '');
            return db_all($sql, [(int)$album['id']]);
        } catch (Throwable $e) { log_error('Album images: ' . $e->getMessage()); }
    }
    $imgs = default_album_images($album['slug'] ?? '');
    return $limit ? array_slice($imgs, 0, $limit) : $imgs;
}

/** Recent photos from published albums, for the masonry “moments” gallery. */
function recent_moments(int $limit): array
{
    try {
        if ((int)db_val('SELECT COUNT(*) FROM portfolio_images') > 0) {
            return db_all("SELECT i.*, a.title AS album_title, a.slug AS album_slug, a.category FROM portfolio_images i
                JOIN portfolio_albums a ON a.id = i.album_id WHERE a.status = 'published' ORDER BY i.created_at DESC, i.id DESC LIMIT " . (int)$limit);
        }
    } catch (Throwable $e) { log_error('Moments fallback: ' . $e->getMessage()); }
    $out = [];
    foreach (default_collections()['albums'] as $a) foreach (default_album_images($a['slug']) as $img) $out[] = $img + ['album_title' => $a['title'], 'album_slug' => $a['slug'], 'category' => $a['category']];
    usort($out, fn($x, $y) => strcmp($x['file_path'], $y['file_path']));
    return array_slice($out, 0, $limit);
}

function album_photo_count(array $album): int
{
    if (!empty($album['id'])) {
        try { return (int)db_val('SELECT COUNT(*) FROM portfolio_images WHERE album_id = ?', [(int)$album['id']]); } catch (Throwable $e) {}
    }
    return count(default_album_images($album['slug'] ?? ''));
}

/** Image tag with width/height (prevents layout shift) and lazy loading. */
function photo_tag(string $path, string $alt, ?int $w = null, ?int $h = null, string $extra = ''): string
{
    $src = media_url($path);
    if ($src === '') return '';
    $dims = ($w && $h) ? ' width="' . (int)$w . '" height="' . (int)$h . '"' : '';
    return '<img src="' . e($src) . '" alt="' . e($alt) . '"' . $dims . ' loading="lazy" decoding="async"' . ($extra ? ' ' . $extra : '') . '>';
}

function nice_date(?string $d): string
{
    if (!$d) return '';
    $t = strtotime($d);
    return $t ? date('j F Y', $t) : '';
}

/** Absolute URL for a site path or an already absolute URL (canonical / Open Graph / sitemap). */
function absolute_url(string $path = ''): string
{
    if ($path === '' ) return url();
    if (preg_match('#^https?://#i', $path)) return $path;
    return url($path);
}
