<?php
/**
 * Publish/unpublish (op=toggle) and drag-and-drop ordering (op=reorder) for any collection.
 */
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';
require_admin();
require_post_csrf();

$key = post_str('collection', 20);
$schemas = collection_schemas();
if (!isset($schemas[$key])) respond(false, 'Unknown collection.');
$c = $schemas[$key];

if (post_str('op', 10) === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);
    $row = db_one("SELECT id, {$c['title']} AS title FROM {$c['table']} WHERE id = ?", [$id]);
    if (!$row) respond(false, 'Item not found.', [], null, 404);
    $status = post_str('published', 1) === '1' ? 'published' : 'draft';
    db_update($c['table'], ['status' => $status, 'updated_at' => now()], 'id = ?', [$id]);
    log_activity($status === 'published' ? 'Published ' . $c['singular'] : 'Unpublished ' . $c['singular'], $row['title']);
    respond(true, "“{$row['title']}” is now " . ($status === 'published' ? 'live on the website.' : 'hidden from the website.'), ['status' => $status]);
}

if (post_str('op', 10) === 'reorder') {
    $ids = array_values(array_unique(array_map('intval', (array)($_POST['ids'] ?? []))));
    if (!$ids || count($ids) > 200) respond(false, 'Invalid order.');
    $place = implode(',', array_fill(0, count($ids), '?'));
    // Re-use the existing order slots of these rows so items on other pages keep their positions
    $slots = array_map('intval', array_column(db_all("SELECT display_order FROM {$c['table']} WHERE id IN ($place) ORDER BY display_order, id", $ids), 'display_order'));
    if (count($slots) !== count($ids)) respond(false, 'Some items no longer exist. Refresh the page.');
    if (count(array_unique($slots)) !== count($slots)) $slots = range(min($slots), min($slots) + count($slots) - 1);
    db_tx(function () use ($ids, $slots, $c) {
        foreach ($ids as $i => $id) db_update($c['table'], ['display_order' => $slots[$i], 'updated_at' => now()], 'id = ?', [$id]);
    });
    log_activity('Reordered ' . strtolower($c['label']));
    respond(true, 'Display order saved.');
}
respond(false, 'Unknown action.');
