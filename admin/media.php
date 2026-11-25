<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin-layout.php';
require __DIR__ . '/../includes/uploads.php';
require_admin();

$folder = in_array(get_str('folder'), MEDIA_FOLDERS, true) ? get_str('folder') : '';
$q = get_str('q', 100);
$where = ['1=1'];
$params = [];
if ($folder) { $where[] = 'folder = ?'; $params[] = $folder; }
if ($q !== '') { $where[] = '(original_name LIKE ? OR alt_text LIKE ? OR file_name LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
$whereSql = implode(' AND ', $where);
$total = (int)db_val("SELECT COUNT(*) FROM media WHERE $whereSql", $params);
$pg = paginate($total, 48, (int)($_GET['p'] ?? 1));
$items = db_all("SELECT * FROM media WHERE $whereSql ORDER BY uploaded_at DESC, id DESC LIMIT ? OFFSET ?", [...$params, $pg['per'], $pg['offset']]);
$counts = [];
foreach (db_all('SELECT folder, COUNT(*) AS n FROM media GROUP BY folder') as $r) $counts[$r['folder']] = (int)$r['n'];

admin_header('Media Library', 'media');
admin_page_head('Media Library', 'All images used on the website. Upload once, reuse anywhere.');
?>
<div class="card" style="margin-bottom:20px">
  <div class="card__body">
    <div style="display:flex;gap:10px;align-items:center;margin-bottom:12px;flex-wrap:wrap">
      <label class="f__label" for="uploadFolder">Upload into folder:</label>
      <select id="uploadFolder" style="width:auto"><?php foreach (MEDIA_FOLDERS as $f): ?><option value="<?= e($f) ?>"<?= $f === ($folder ?: 'general') ? ' selected' : '' ?>><?= e(ucfirst($f)) ?></option><?php endforeach; ?></select>
    </div>
    <div class="dropzone" tabindex="0" role="button" aria-label="Upload images" data-dropzone>
      <i class="fa-solid fa-cloud-arrow-up"></i><strong>Drop images here or click to upload</strong>
      <small>JPG, PNG, WebP or SVG · up to <?= UPLOAD_MAX_BYTES / 1048576 ?> MB each · photos are resized to <?= UPLOAD_MAX_DIMENSION ?>px and converted to WebP</small>
    </div>
    <input type="file" multiple accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml" hidden data-dropzone-input>
    <div class="uploads" data-upload-list></div>
  </div>
</div>

<div class="card">
  <form class="toolbar" method="get">
    <div class="search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search by file name or alt text…" aria-label="Search media"></div>
    <?php if ($folder): ?><input type="hidden" name="folder" value="<?= e($folder) ?>"><?php endif; ?>
    <button class="btn btn--forest btn--sm" type="submit">Search</button>
    <span class="muted"><?= $total ?> image<?= $total === 1 ? '' : 's' ?></span>
  </form>
  <div class="toolbar"><div class="chips-filter">
    <a class="<?= $folder === '' ? 'is-active' : '' ?>" href="<?= e(url('admin/media.php' . ($q ? '?q=' . rawurlencode($q) : ''))) ?>">All (<?= array_sum($counts) ?>)</a>
    <?php foreach (MEDIA_FOLDERS as $f): ?><a class="<?= $folder === $f ? 'is-active' : '' ?>" href="<?= e(url('admin/media.php?folder=' . $f . ($q ? '&q=' . rawurlencode($q) : ''))) ?>"><?= e(ucfirst($f)) ?> (<?= $counts[$f] ?? 0 ?>)</a><?php endforeach; ?>
  </div></div>
  <div class="media-grid">
    <?php foreach ($items as $m):
      $data = ['id' => (int)$m['id'], 'url' => media_url($m['file_path']), 'path' => $m['file_path'], 'name' => $m['original_name'] ?: $m['file_name'], 'file' => $m['file_name'],
        'alt' => $m['alt_text'], 'folder' => $m['folder'], 'type' => $m['file_type'], 'size' => (int)$m['file_size'], 'width' => $m['width'], 'height' => $m['height'],
        'uploaded' => format_date($m['uploaded_at'], true), 'used' => media_usage_count($m['file_path'])]; ?>
      <button type="button" class="media-item" data-media="<?= e(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>" title="<?= e($data['name']) ?>">
        <img src="<?= e($data['url']) ?>" alt="<?= e($m['alt_text']) ?>" loading="lazy">
        <span class="media-item__meta"><strong><?= e($data['name']) ?></strong><small><?= e(format_date($m['uploaded_at'])) ?> · <?= e(ucfirst($m['folder'])) ?><?= $data['used'] ? ' · in use' : '' ?></small></span>
      </button>
    <?php endforeach; ?>
    <?php if (!$items): ?><div class="empty" style="grid-column:1/-1"><i class="fa-regular fa-images"></i><h3><?= $total || $q || $folder ? 'No images match' : 'Your library is empty' ?></h3>Upload your first images above.</div><?php endif; ?>
  </div>
  <div class="toolbar" style="border-top:1px solid var(--line);border-bottom:0;justify-content:flex-end"><?php require_once __DIR__ . '/../includes/admin-collection.php'; render_pagination($pg); ?></div>
</div>
<?php admin_footer();
