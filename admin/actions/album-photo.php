<?php
/**
 * Album photo manager actions (admin session · POST · CSRF):
 *   upload (multi, one file per request) · update caption/alt · delete · replace · set cover ·
 *   reorder · bulk_delete · bulk_move
 */
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';
require __DIR__ . '/../../includes/uploads.php';
$admin = require_admin();
require_post_csrf();

$op = post_str('op', 20);
const MAX_PHOTOS_PER_ALBUM = 300;

function photo_row(int $id): array
{
    $row = $id ? db_one('SELECT i.*, a.title AS album_title FROM portfolio_images i JOIN portfolio_albums a ON a.id = i.album_id WHERE i.id = ?', [$id]) : null;
    if (!$row) respond(false, 'Photo not found — it may have been deleted.', [], null, 404);
    return $row;
}
function album_row(int $id): array
{
    $row = $id ? db_one('SELECT id, title, cover_image FROM portfolio_albums WHERE id = ?', [$id]) : null;
    if (!$row) respond(false, 'Album not found.', [], null, 404);
    return $row;
}
function int_ids(): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['ids'] ?? [])))));
    if (!$ids) respond(false, 'Select at least one photo.');
    if (count($ids) > 500) respond(false, 'Too many photos selected at once.');
    return $ids;
}

switch ($op) {
    case 'upload': {
        $album = album_row((int)($_POST['album_id'] ?? 0));
        if ((int)db_val('SELECT COUNT(*) FROM portfolio_images WHERE album_id = ?', [$album['id']]) >= MAX_PHOTOS_PER_ALBUM) respond(false, 'This album already has ' . MAX_PHOTOS_PER_ALBUM . ' photos. Create a second album for the rest.');
        if (empty($_FILES['file'])) respond(false, 'No file received. The file may be larger than the server allows.');
        try {
            $m = store_uploaded_image($_FILES['file'], 'albums');
        } catch (UploadError $e) {
            respond(false, basename((string)($_FILES['file']['name'] ?? 'File')) . ': ' . $e->getMessage());
        }
        $order = (int)db_val('SELECT COALESCE(MAX(display_order), 0) FROM portfolio_images WHERE album_id = ?', [$album['id']]) + 1;
        $id = db_insert('portfolio_images', [
            'album_id' => $album['id'], 'file_path' => $m['file_path'], 'thumb_path' => $m['thumb_path'], 'width' => $m['width'], 'height' => $m['height'],
            'caption' => '', 'alt_text' => mb_substr(trim(preg_replace('/[-_]+/', ' ', pathinfo($m['original_name'], PATHINFO_FILENAME))), 0, 190),
            'display_order' => $order, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if (!$album['cover_image']) db_update('portfolio_albums', ['cover_image' => $m['file_path'], 'updated_at' => now()], 'id = ?', [$album['id']]);
        db_update('portfolio_albums', ['updated_at' => now()], 'id = ?', [$album['id']]);
        respond(true, 'Photo uploaded.', ['id' => $id, 'items' => [['id' => $id, 'path' => $m['file_path']]]]);
    }
    case 'update': {
        $p = photo_row((int)($_POST['id'] ?? 0));
        $caption = clean_text((string)($_POST['caption'] ?? ''), 255);
        $alt = clean_text((string)($_POST['alt_text'] ?? ''), 190);
        db_update('portfolio_images', ['caption' => $caption, 'alt_text' => $alt, 'updated_at' => now()], 'id = ?', [$p['id']]);
        respond(true, 'Photo details saved.');
    }
    case 'delete': {
        $p = photo_row((int)($_POST['id'] ?? 0));
        db_run('DELETE FROM portfolio_images WHERE id = ?', [$p['id']]);
        $album = album_row((int)$p['album_id']);
        if ($album['cover_image'] === $p['file_path']) {   // pick another cover so the album never shows a broken image
            $next = db_val('SELECT file_path FROM portfolio_images WHERE album_id = ? ORDER BY display_order, id LIMIT 1', [$album['id']]);
            if ($next) db_update('portfolio_albums', ['cover_image' => $next, 'updated_at' => now()], 'id = ?', [$album['id']]);
        }
        delete_album_photo_files($p);
        log_activity('Deleted album photo', $p['album_title']);
        respond(true, 'Photo deleted.', ['remove' => true]);
    }
    case 'replace': {
        $p = photo_row((int)($_POST['id'] ?? 0));
        if (empty($_FILES['file'])) respond(false, 'No file received.');
        try { $m = store_uploaded_image($_FILES['file'], 'albums'); } catch (UploadError $e) { respond(false, $e->getMessage()); }
        db_update('portfolio_images', ['file_path' => $m['file_path'], 'thumb_path' => $m['thumb_path'], 'width' => $m['width'], 'height' => $m['height'], 'updated_at' => now()], 'id = ?', [$p['id']]);
        db_run('UPDATE portfolio_albums SET cover_image = ?, updated_at = ? WHERE cover_image = ?', [$m['file_path'], now(), $p['file_path']]);
        delete_album_photo_files($p);
        log_activity('Replaced album photo', $p['album_title']);
        respond(true, 'Photo replaced.', ['reload' => true]);
    }
    case 'cover': {
        $p = photo_row((int)($_POST['id'] ?? 0));
        db_update('portfolio_albums', ['cover_image' => $p['file_path'], 'updated_at' => now()], 'id = ?', [$p['album_id']]);
        log_activity('Changed album cover', $p['album_title']);
        respond(true, 'Cover photo updated.', ['reload' => true]);
    }
    case 'reorder': {
        $album = album_row((int)($_POST['album_id'] ?? 0));
        $ids = int_ids();
        $place = implode(',', array_fill(0, count($ids), '?'));
        if ((int)db_val("SELECT COUNT(*) FROM portfolio_images WHERE album_id = ? AND id IN ($place)", [$album['id'], ...$ids]) !== count($ids)) respond(false, 'Some photos no longer exist. Refresh the page.');
        db_tx(function () use ($ids) { foreach ($ids as $i => $id) db_update('portfolio_images', ['display_order' => $i + 1], 'id = ?', [$id]); });
        respond(true, 'Photo order saved.');
    }
    case 'bulk_delete': {
        $ids = int_ids();
        $place = implode(',', array_fill(0, count($ids), '?'));
        $rows = db_all("SELECT * FROM portfolio_images WHERE id IN ($place)", $ids);
        db_run("DELETE FROM portfolio_images WHERE id IN ($place)", $ids);
        foreach (array_unique(array_column($rows, 'album_id')) as $aid) {
            $a = db_one('SELECT id, cover_image FROM portfolio_albums WHERE id = ?', [(int)$aid]);
            if ($a && in_array($a['cover_image'], array_column($rows, 'file_path'), true)) {
                $next = db_val('SELECT file_path FROM portfolio_images WHERE album_id = ? ORDER BY display_order, id LIMIT 1', [(int)$aid]);
                db_update('portfolio_albums', ['cover_image' => $next ?: $a['cover_image'], 'updated_at' => now()], 'id = ?', [(int)$aid]);
            }
        }
        foreach ($rows as $r) delete_album_photo_files($r);
        log_activity('Deleted album photos', count($rows) . ' photo(s)');
        respond(true, count($rows) . ' photo(s) deleted.', ['reload' => true]);
    }
    case 'bulk_move': {
        $ids = int_ids();
        $target = album_row((int)($_POST['target_album'] ?? 0));
        $order = (int)db_val('SELECT COALESCE(MAX(display_order), 0) FROM portfolio_images WHERE album_id = ?', [$target['id']]);
        db_tx(function () use ($ids, $target, &$order) {
            foreach ($ids as $id) db_update('portfolio_images', ['album_id' => $target['id'], 'display_order' => ++$order, 'updated_at' => now()], 'id = ?', [$id]);
        });
        log_activity('Moved album photos', count($ids) . ' → ' . $target['title']);
        respond(true, count($ids) . ' photo(s) moved to “' . $target['title'] . '”.', ['reload' => true]);
    }
}
respond(false, 'Unknown action.', [], null, 400);
