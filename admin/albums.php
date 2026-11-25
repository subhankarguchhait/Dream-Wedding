<?php
/** Photo manager for one portfolio album: admin/albums.php?id=ALBUM_ID. Without an id, shows the album picker. */
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin-photos.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    $albums = db_all('SELECT a.*, (SELECT COUNT(*) FROM portfolio_images i WHERE i.album_id = a.id) AS photos FROM portfolio_albums a ORDER BY a.display_order, a.id');
    admin_header('Album photos', 'albums');
    admin_page_head('Choose an album', 'Pick the album whose photos you want to manage.', '<a class="btn btn--gold" href="' . e(url('admin/portfolio.php?new=1')) . '"><i class="fa-solid fa-plus"></i>New album</a>'); ?>
    <div class="grid grid--4">
      <?php foreach ($albums as $a): ?>
        <a class="card album-pick" href="<?= e(url('admin/albums.php?id=' . (int)$a['id'])) ?>">
          <img src="<?= e(media_url($a['cover_image'])) ?>" alt="" loading="lazy">
          <div class="card__body"><strong><?= e($a['title']) ?></strong><div class="muted"><?= (int)$a['photos'] ?> photos · <?= $a['status'] === 'published' ? 'Live' : 'Hidden' ?></div></div>
        </a>
      <?php endforeach; ?>
    </div>
    <?php if (!$albums): ?><div class="card"><div class="empty"><i class="fa-solid fa-book-open"></i><h3>No albums yet</h3></div></div><?php endif;
    admin_footer();
    exit;
}
$album = db_one('SELECT * FROM portfolio_albums WHERE id = ?', [$id]);
if (!$album) { flash('error', 'Album not found.'); redirect('admin/portfolio.php'); }
photo_manager($album, 'albums');
