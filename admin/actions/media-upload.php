<?php
/** Upload one or more images (field "files[]" or "file"). Returns the stored items as JSON. */
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';
require __DIR__ . '/../../includes/uploads.php';
$admin = require_admin();
require_post_csrf();

$folder = post_str('folder', 40);
$files = [];
if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
    foreach ($_FILES['files']['name'] as $i => $n) {
        $files[] = ['name' => $n, 'type' => $_FILES['files']['type'][$i], 'tmp_name' => $_FILES['files']['tmp_name'][$i], 'error' => $_FILES['files']['error'][$i], 'size' => $_FILES['files']['size'][$i]];
    }
} elseif (isset($_FILES['file'])) {
    $files[] = $_FILES['file'];
}
if (!$files) respond(false, 'No file received. The file may be larger than the server allows (check upload_max_filesize in php.ini).');
if (count($files) > 20) respond(false, 'Please upload at most 20 images at a time.');

$saved = [];
$errors = [];
foreach ($files as $f) {
    try {
        $m = store_uploaded_image($f, $folder);
        $m['id'] = db_insert('media', $m + ['alt_text' => '', 'uploaded_by' => (int)$admin['id'], 'uploaded_at' => now(), 'updated_at' => now()]);
        $saved[] = ['id' => $m['id'], 'path' => $m['file_path'], 'url' => media_url($m['file_path']), 'name' => $m['original_name']];
        log_activity('Uploaded image', $m['original_name']);
    } catch (UploadError $e) {
        $errors[] = basename((string)$f['name']) . ': ' . $e->getMessage();
    } catch (Throwable $e) {
        log_error('Upload failed: ' . $e->getMessage());
        $errors[] = basename((string)$f['name']) . ': unexpected error while saving.';
    }
}
if (!$saved) respond(false, implode(' ', $errors) ?: 'Upload failed.', ['errors_list' => $errors]);
respond(true, count($saved) . ' image' . (count($saved) > 1 ? 's' : '') . ' uploaded.' . ($errors ? ' Some files were skipped.' : ''), ['items' => $saved, 'errors_list' => $errors]);
