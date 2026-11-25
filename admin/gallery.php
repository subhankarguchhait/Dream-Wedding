<?php
/** All album photos in one place — filter by album, edit captions/alt text, move or delete in bulk. */
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin-photos.php';
require_admin();
photo_manager(null, 'gallery');
