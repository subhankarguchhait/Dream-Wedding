<?php
/**
 * Album photo manager used by admin/albums.php (one album) and admin/gallery.php (all photos).
 * Multi-upload with progress, drag & drop ordering, captions & alt text, set cover, replace,
 * delete, bulk select → delete / move to another album.
 */
declare(strict_types=1);
defined('APP_NAME') || exit;
require_once __DIR__ . '/admin-layout.php';
require_once __DIR__ . '/admin-collection.php';

function photo_manager(?array $album, string $navKey): void
{
    $albums = db_all('SELECT id, title, couple_names, cover_image, status FROM portfolio_albums ORDER BY display_order, id');
    $filter = $album ? (int)$album['id'] : (int)($_GET['album'] ?? 0);
    $q = get_str('q', 100);
    $where = ['1=1']; $params = [];
    if ($filter) { $where[] = 'i.album_id = ?'; $params[] = $filter; }
    if ($q !== '') { $where[] = '(i.caption LIKE ? OR i.alt_text LIKE ? OR a.title LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
    $whereSql = implode(' AND ', $where);
    $total = (int)db_val("SELECT COUNT(*) FROM portfolio_images i JOIN portfolio_albums a ON a.id = i.album_id WHERE $whereSql", $params);
    $pg = paginate($total, $album ? 300 : 60, (int)($_GET['p'] ?? 1));
    $photos = db_all("SELECT i.*, a.title AS album_title, a.cover_image AS album_cover FROM portfolio_images i JOIN portfolio_albums a ON a.id = i.album_id
        WHERE $whereSql ORDER BY " . ($album || $filter ? 'i.display_order, i.id' : 'i.created_at DESC, i.id DESC') . ' LIMIT ? OFFSET ?', [...$params, $pg['per'], $pg['offset']]);
    $canSort = ($album || $filter) && $q === '' && $pg['pages'] < 2;
    $uploadAlbum = $album ? (int)$album['id'] : $filter;
    $action = url('admin/actions/album-photo.php');

    if ($album) {
        admin_header('Album photos', $navKey);
        admin_page_head($album['title'], 'Upload, caption, reorder and organise the photos of this album. Alt text helps Google and screen readers.',
            '<a class="btn btn--ghost" href="' . e(url('admin/portfolio.php')) . '"><i class="fa-solid fa-arrow-left"></i>All albums</a>'
            . '<a class="btn btn--ghost" href="' . e(url('admin/portfolio.php?edit=' . (int)$album['id'])) . '"><i class="fa-solid fa-pen"></i>Album details</a>'
            . ($album['status'] === 'published' ? '<a class="btn btn--forest" href="' . e(album_url($album['slug'])) . '" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i>View album</a>' : '<span class="badge badge--draft">Hidden album</span>'));
    } else {
        admin_header('Album photos', $navKey);
        admin_page_head('Album photos', 'Every photo from every portfolio album. Filter by album, edit captions and alt text, move or delete in bulk.',
            '<a class="btn btn--ghost" href="' . e(url('admin/portfolio.php')) . '"><i class="fa-solid fa-book-open"></i>Manage albums</a>');
    }
    ?>
    <?php if (!$albums): ?>
      <div class="card"><div class="empty"><i class="fa-solid fa-book-open"></i><h3>No albums yet</h3>Create an album first, then upload its photos.
        <a class="btn btn--gold btn--sm" style="margin-top:10px" href="<?= e(url('admin/portfolio.php?new=1')) ?>"><i class="fa-solid fa-plus"></i>New album</a></div></div>
    <?php admin_footer(); return; endif; ?>

    <?php if ($uploadAlbum): ?>
      <div class="card photo-drop" data-photo-drop data-album="<?= $uploadAlbum ?>" style="margin-bottom:18px">
        <div class="card__body" style="display:flex;align-items:center;gap:18px;flex-wrap:wrap">
          <span class="stat-card__icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
          <div style="flex:1;min-width:220px"><strong>Drop photos here or choose files</strong><div class="f__help">JPG, PNG or WebP · up to <?= UPLOAD_MAX_BYTES / 1048576 ?> MB each · up to 50 at a time. Large photos are resized and converted to WebP automatically, with a small thumbnail for fast loading.</div></div>
          <button type="button" class="btn btn--gold" data-photo-pick><i class="fa-solid fa-images"></i>Upload photos</button>
          <input type="file" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" hidden data-photo-input>
        </div>
        <div class="progress" hidden data-photo-progress><span></span></div>
        <div class="f__help" data-photo-status style="padding:0 20px 14px" hidden></div>
      </div>
    <?php endif; ?>

    <div class="card">
      <form class="toolbar" method="get">
        <?php if ($album): ?><input type="hidden" name="id" value="<?= (int)$album['id'] ?>"><?php else: ?>
          <select name="album" aria-label="Album" onchange="this.form.submit()"><option value="">All albums</option><?php foreach ($albums as $a): ?><option value="<?= (int)$a['id'] ?>"<?= $filter === (int)$a['id'] ? ' selected' : '' ?>><?= e($a['title']) ?></option><?php endforeach; ?></select>
        <?php endif; ?>
        <div class="search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search captions or alt text…" aria-label="Search photos"></div>
        <button class="btn btn--forest btn--sm" type="submit"><i class="fa-solid fa-filter"></i>Apply</button>
        <span class="spacer"></span>
        <label class="photo-selall"><input type="checkbox" data-photo-all> Select all</label>
        <select data-photo-target aria-label="Move selected to album"><option value="">Move selected to…</option><?php foreach ($albums as $a): ?><option value="<?= (int)$a['id'] ?>"><?= e($a['title']) ?></option><?php endforeach; ?></select>
        <button type="button" class="btn btn--ghost btn--sm" data-photo-bulk="bulk_move" disabled><i class="fa-solid fa-right-left"></i>Move</button>
        <button type="button" class="btn btn--danger btn--sm" data-photo-bulk="bulk_delete" disabled><i class="fa-solid fa-trash-can"></i>Delete selected</button>
      </form>
      <div class="card__body">
        <div class="photo-grid" data-photo-grid data-album="<?= $canSort ? $uploadAlbum : 0 ?>" data-action="<?= e($action) ?>">
          <?php foreach ($photos as $p): $isCover = $p['album_cover'] === $p['file_path']; ?>
            <article class="photo-item<?= $isCover ? ' is-cover' : '' ?>" data-id="<?= (int)$p['id'] ?>"<?= $canSort ? ' draggable="true"' : '' ?>>
              <label class="photo-item__check"><input type="checkbox" data-photo-check value="<?= (int)$p['id'] ?>" aria-label="Select photo"></label>
              <?php if ($isCover): ?><span class="photo-item__cover">Cover</span><?php endif; ?>
              <a class="photo-item__img" href="<?= e(media_url($p['file_path'])) ?>" target="_blank" rel="noopener"><img src="<?= e(media_url($p['thumb_path'] ?: $p['file_path'])) ?>" alt="" loading="lazy"></a>
              <form method="post" action="<?= e($action) ?>" data-ajax class="photo-item__form">
                <?= csrf_field() ?><input type="hidden" name="op" value="update"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <?php if (!$album && !$filter): ?><small class="muted clamp-1"><?= e($p['album_title']) ?></small><?php endif; ?>
                <input type="text" name="caption" value="<?= e($p['caption']) ?>" maxlength="255" placeholder="Caption (optional)" aria-label="Caption">
                <input type="text" name="alt_text" value="<?= e($p['alt_text']) ?>" maxlength="190" placeholder="Alt text — describe the photo" aria-label="Alt text">
                <div class="photo-item__btns">
                  <button class="btn btn--forest btn--sm" type="submit" title="Save caption & alt text"><i class="fa-solid fa-floppy-disk"></i></button>
                  <button class="btn btn--ghost btn--sm" type="button" data-photo-cover title="Use as album cover"<?= $isCover ? ' disabled' : '' ?>><i class="fa-solid fa-star"></i></button>
                  <button class="btn btn--ghost btn--sm" type="button" data-photo-replace title="Replace image"><i class="fa-solid fa-arrows-rotate"></i></button>
                  <button class="btn btn--danger btn--sm" type="button" data-photo-delete title="Delete photo"><i class="fa-solid fa-trash-can"></i></button>
                  <?php if ($canSort): ?><span class="drag" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></span><?php endif; ?>
                </div>
              </form>
            </article>
          <?php endforeach; ?>
        </div>
        <?php if (!$photos): ?><div class="empty"><i class="fa-regular fa-images"></i><h3><?= $q ? 'No photos match your search' : 'No photos yet' ?></h3><?= $uploadAlbum ? 'Upload the first photos above.' : 'Choose an album to upload photos into it.' ?></div><?php endif; ?>
      </div>
      <div class="toolbar" style="border-top:1px solid var(--line);border-bottom:0;justify-content:space-between">
        <span class="muted"><?= $total ?> photo<?= $total === 1 ? '' : 's' ?></span>
        <?php if ($canSort && $photos): ?><span class="f__help"><i class="fa-solid fa-grip-vertical"></i> Drag photos to change their order in the album.</span><?php endif; ?>
        <?php render_pagination($pg); ?>
      </div>
    </div>
    <input type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" hidden data-photo-replace-input>
    <?php
    admin_footer();
}
