<?php
/**
 * Page editor actions (POST + CSRF):
 *   op=save    save one section's fields
 *   op=toggle  show / hide a section
 *   op=order   save the order of all sections (keys[] in order)
 *   op=seo     save page meta title + description
 */
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';
require __DIR__ . '/../../includes/form.php';
require_admin();
require_post_csrf();

$schemas = page_schemas();
$slug = post_str('page', 20);
if (!isset($schemas[$slug])) respond(false, 'Unknown page.');
$schema = $schemas[$slug];
$op = post_str('op', 10);

/** Get (or create) the page row id. */
function page_id(string $slug): int
{
    $id = db_val('SELECT id FROM pages WHERE page_slug = ?', [$slug]);
    if ($id) return (int)$id;
    $meta = default_pages()[$slug]['meta'];
    return db_insert('pages', ['page_name' => $meta['name'], 'page_slug' => $slug, 'meta_title' => $meta['title'], 'meta_description' => $meta['description'],
        'status' => 'published', 'created_at' => now(), 'updated_at' => now()]);
}

/** Get (or create from defaults) a section row id. */
function section_id(int $pageId, string $slug, string $key): int
{
    $id = db_val('SELECT id FROM page_sections WHERE page_id = ? AND section_key = ?', [$pageId, $key]);
    if ($id) return (int)$id;
    $row = section_to_row(default_pages()[$slug]['sections'][$key] ?? []);
    $order = (int)db_val('SELECT COALESCE(MAX(display_order), 0) FROM page_sections WHERE page_id = ?', [$pageId]) + 1;
    return db_insert('page_sections', $row + ['page_id' => $pageId, 'section_key' => $key, 'display_order' => $order, 'is_visible' => 1, 'created_at' => now(), 'updated_at' => now()]);
}

$pageId = page_id($slug);
$label = $schema['label'];

switch ($op) {
    case 'save': {
        $key = post_str('section', 60);
        if (!isset($schema['sections'][$key])) respond(false, 'Unknown section.');
        $def = $schema['sections'][$key];
        [$clean, $errors] = validate_fields($def['fields'], $_POST['f'] ?? []);
        if ($errors) respond(false, 'Please fix the highlighted fields.', ['errors' => $errors]);
        $id = section_id($pageId, $slug, $key);
        db_update('page_sections', section_to_row($clean) + ['updated_at' => now()], 'id = ?', [$id]);
        db_update('pages', ['updated_at' => now()], 'id = ?', [$pageId]);
        log_activity('Updated section', "$label → {$def['label']}");
        respond(true, "“{$def['label']}” saved — the live page now shows your changes.", ['summary' => plain($clean['title'] ?? '')]);
    }
    case 'toggle': {
        $key = post_str('section', 60);
        if (!isset($schema['sections'][$key])) respond(false, 'Unknown section.');
        $visible = post_str('visible', 1) === '1' ? 1 : 0;
        $id = section_id($pageId, $slug, $key);
        db_update('page_sections', ['is_visible' => $visible, 'updated_at' => now()], 'id = ?', [$id]);
        log_activity($visible ? 'Showed section' : 'Hid section', "$label → {$schema['sections'][$key]['label']}");
        respond(true, $schema['sections'][$key]['label'] . ($visible ? ' is now visible on the website.' : ' is now hidden from the website.'));
    }
    case 'order': {
        $keys = array_values(array_filter((array)($_POST['keys'] ?? []), fn($k) => is_string($k) && isset($schema['sections'][$k])));
        if (count($keys) !== count(array_unique($keys)) || !$keys) respond(false, 'Invalid order.');
        db_tx(function () use ($keys, $pageId, $slug) {
            foreach ($keys as $i => $k) {
                $id = section_id($pageId, $slug, $k);
                db_update('page_sections', ['display_order' => $i + 1, 'updated_at' => now()], 'id = ?', [$id]);
            }
        });
        log_activity('Reordered sections', $label);
        respond(true, 'Section order saved — the live page has been updated.');
    }
    case 'seo': {
        [$clean, $errors] = validate_fields(page_seo_fields(), $_POST['f'] ?? []);
        if ($errors) respond(false, 'Please fix the highlighted fields.', ['errors' => $errors]);
        db_update('pages', $clean + ['updated_at' => now()], 'id = ?', [$pageId]);
        log_activity('Updated SEO', $label);
        respond(true, 'SEO settings saved.');
    }
}
respond(false, 'Unknown action.');
