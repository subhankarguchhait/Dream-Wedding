<?php
/**
 * op=details  rename / alt text / folder
 * op=replace  upload a new file for this item; every page, brand, setting… that used it is updated
 */
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';
require __DIR__ . '/../../includes/uploads.php';
$admin = require_admin();
require_post_csrf();

$id = (int)($_POST['id'] ?? 0);
$m = $id ? db_one('SELECT * FROM media WHERE id = ?', [$id]) : null;
if (!$m) respond(false, 'Image not found.', [], null, 404);

if (post_str('op', 10) === 'details') {
    $folder = post_str('folder', 40);
    $data = [
        'original_name' => post_str('name', 190) ?: $m['original_name'],
        'alt_text' => post_str('alt', 255),
        'folder' => in_array($folder, MEDIA_FOLDERS, true) ? $folder : $m['folder'],
        'updated_at' => now(),
    ];
    db_update('media', $data, 'id = ?', [$id]);
    log_activity('Updated image details', $data['original_name']);
    respond(true, 'Image details saved.', ['reload' => true]);
}

if (post_str('op', 10) === 'replace') {
    if (!isset($_FILES['file'])) respond(false, 'Choose a replacement image.');
    try {
        $new = store_uploaded_image($_FILES['file'], $m['folder']);
    } catch (UploadError $e) {
        respond(false, $e->getMessage());
    }
    $changed = db_tx(function () use ($m, $new, $id) {
        db_update('media', ['file_name' => $new['file_name'], 'file_path' => $new['file_path'], 'file_type' => $new['file_type'], 'file_size' => $new['file_size'],
            'width' => $new['width'], 'height' => $new['height'], 'updated_at' => now()], 'id = ?', [$id]);
        return replace_media_references($m['file_path'], $new['file_path']);
    });
    delete_upload_file($m['file_path']);
    log_activity('Replaced image', $m['original_name']);
    respond(true, 'Image replaced' . ($changed ? " and updated in $changed place" . ($changed > 1 ? 's' : '') : '') . '.', ['url' => media_url($new['file_path']), 'path' => $new['file_path'], 'reload' => true]);
}
respond(false, 'Unknown action.');
