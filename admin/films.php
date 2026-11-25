<?php
/** Films — uploaded MP4s or YouTube / Vimeo / MP4 links — list, search, sort, publish, reorder, add, edit, delete. */
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin-collection.php';

// Older databases don't have the films.video_path column yet → explain the one-time update instead of failing on save.
try {
    db_val('SELECT video_path FROM films LIMIT 1');
} catch (Throwable $e) {
    require_admin();
    admin_header('Films', 'films'); ?>
    <div class="card" style="padding:32px;max-width:760px">
      <h2 style="margin-top:0">One-time database update needed</h2>
      <p>To upload films as MP4, open <strong>phpMyAdmin → your database → SQL</strong>, paste the contents of
      <code>database/update-films-upload.sql</code> and press <strong>Go</strong>. Then reload this page.</p>
      <p style="color:#6f6a63">It only adds one column to the films table — your existing films are not changed.</p>
    </div>
    <?php admin_footer();
    exit;
}

collection_admin_page('films');
