<?php
/** Delete an image from the library and the disk (POST + CSRF). */
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';
require __DIR__ . '/../../includes/uploads.php';
require_admin();
require_post_csrf();

$id = (int)($_POST['id'] ?? 0);
$m = $id ? db_one('SELECT * FROM media WHERE id = ?', [$id]) : null;
if (!$m) respond(false, 'Image not found.', [], null, 404);
db_run('DELETE FROM media WHERE id = ?', [$id]);
delete_upload_file($m['file_path']);
log_activity('Deleted image', $m['original_name'] ?: $m['file_name']);
respond(true, 'Image deleted.', ['reload' => true]);
