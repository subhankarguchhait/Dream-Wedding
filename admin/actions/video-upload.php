<?php
/** Upload one hero-slider video (MP4/WebM). Admin session · POST · CSRF · size/extension/MIME/signature checks. */
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';
require __DIR__ . '/../../includes/uploads.php';
$admin = require_admin();
require_post_csrf();

if (empty($_FILES['file'])) respond(false, 'No video received. It may be larger than the server allows — raise upload_max_filesize and post_max_size (see README) or compress the clip.');
try {
    $m = store_uploaded_video($_FILES['file']);
    $m['id'] = db_insert('media', $m + ['alt_text' => '', 'uploaded_by' => (int)$admin['id'], 'uploaded_at' => now(), 'updated_at' => now()]);
    log_activity('Uploaded video', $m['original_name']);
    respond(true, 'Video uploaded — remember to press Save.', ['items' => [['id' => $m['id'], 'path' => $m['file_path'], 'url' => media_url($m['file_path']), 'name' => $m['original_name']]]]);
} catch (UploadError $e) {
    respond(false, $e->getMessage());
} catch (Throwable $e) {
    log_error('Video upload failed: ' . $e->getMessage());
    respond(false, 'Unexpected error while saving the video.', [], null, 500);
}
