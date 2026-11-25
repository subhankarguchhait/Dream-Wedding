<?php
/**
 * Shared admin screen for hero slides, services, albums, films, testimonials and team:
 *   list view  — search, status filter, sort, pagination, publish switch, drag & drop order, delete
 *   edit view  — ?edit=ID or ?new=1, schema-driven form saved with AJAX
 */
declare(strict_types=1);
defined('APP_NAME') || exit; // no direct access
require_once __DIR__ . '/admin-layout.php';

function collection_admin_page(string $key): void
{
    $c = collection_schemas()[$key];
    require_admin();
    $editId = (int)($_GET['edit'] ?? 0);
    if ($editId || isset($_GET['new'])) { collection_edit_view($key, $c, $editId); return; }
    collection_list_view($key, $c);
}

function collection_list_view(string $key, array $c): void
{
    $q = get_str('q', 100);
    $status = in_array(get_str('status'), ['published', 'draft'], true) ? get_str('status') : '';
    $sorts = ['order' => 'Display order', 'newest' => 'Newest first', 'oldest' => 'Oldest first', 'name' => 'Name A–Z'];
    $sort = array_key_exists(get_str('sort'), $sorts) ? get_str('sort') : 'order';
    $orderSql = match ($sort) { 'newest' => 'created_at DESC, id DESC', 'oldest' => 'created_at ASC, id ASC', 'name' => $c['title'] . ' ASC', default => 'display_order ASC, id ASC' };

    $where = ['1=1'];
    $params = [];
    if ($q !== '') {
        $where[] = '(' . implode(' OR ', array_map(fn($col) => "$col LIKE ?", $c['search'])) . ')';
        foreach ($c['search'] as $_) $params[] = '%' . $q . '%';
    }
    if ($status) { $where[] = 'status = ?'; $params[] = $status; }
    $whereSql = implode(' AND ', $where);
    $total = (int)db_val("SELECT COUNT(*) FROM {$c['table']} WHERE $whereSql", $params);
    $pg = paginate($total, 15, (int)($_GET['p'] ?? 1));
    $rows = db_all("SELECT * FROM {$c['table']} WHERE $whereSql ORDER BY $orderSql LIMIT ? OFFSET ?", [...$params, $pg['per'], $pg['offset']]);
    $all = (int)db_val("SELECT COUNT(*) FROM {$c['table']}");
    $canDrag = $sort === 'order' && $q === '' && $status === '';
    $photoCounts = [];
    if (!empty($c['photos'])) foreach (db_all('SELECT album_id, COUNT(*) AS n FROM portfolio_images GROUP BY album_id') as $pc) $photoCounts[(int)$pc['album_id']] = (int)$pc['n'];
    $self = url('admin/' . $c['admin']);

    admin_header($c['label'], $key);
    admin_page_head($c['label'], 'Add, edit, hide and reorder ' . strtolower($c['label']) . '. Only published items appear on the website.',
        '<a class="btn btn--ghost" href="' . e(url($c['public'])) . '" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i>View on website</a>'
        . '<a class="btn btn--gold" href="' . e($self . '?new=1') . '"><i class="fa-solid fa-plus"></i>Add ' . e($c['singular']) . '</a>');
    ?>
    <div class="card">
      <form class="toolbar" method="get" action="<?= e($self) ?>">
        <div class="search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search <?= e(strtolower($c['label'])) ?>…" aria-label="Search"></div>
        <select name="status" aria-label="Status" onchange="this.form.submit()">
          <option value="">All statuses</option>
          <option value="published"<?= $status === 'published' ? ' selected' : '' ?>>Published</option>
          <option value="draft"<?= $status === 'draft' ? ' selected' : '' ?>>Hidden</option>
        </select>
        <select name="sort" aria-label="Sort" onchange="this.form.submit()">
          <?php foreach ($sorts as $k => $l): ?><option value="<?= $k ?>"<?= $k === $sort ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn--forest btn--sm" type="submit"><i class="fa-solid fa-filter"></i>Apply</button>
        <?php if ($q || $status || $sort !== 'order'): ?><a class="btn btn--ghost btn--sm" href="<?= e($self) ?>">Reset</a><?php endif; ?>
      </form>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th style="width:36px"></th><th><?= e(ucfirst($c['singular'])) ?></th><th>Status</th><th>Updated</th><th style="text-align:right">Actions</th></tr></thead>
          <tbody data-sortable="<?= $canDrag ? e($key) : '' ?>">
            <?php foreach ($rows as $r): $thumb = media_url($r[$c['thumb']] ?? ''); $title = plain((string)$r[$c['title']]); ?>
              <tr data-id="<?= (int)$r['id'] ?>"<?= $canDrag ? ' draggable="true"' : '' ?>>
                <td><?php if ($canDrag): ?><span class="drag" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></span><?php endif; ?></td>
                <td><div class="cell-thumb">
                  <?php if ($thumb): ?><img src="<?= e($thumb) ?>" alt="" loading="lazy"><?php else: ?><span class="ph"><i class="<?= e(isset($c['icon_field']) ? safe_icon($r[$c['icon_field']] ?? '', $c['icon']) : $c['icon']) ?>"></i></span><?php endif; ?>
                  <div><a href="<?= e($self . '?edit=' . (int)$r['id']) ?>"><strong><?= e($title) ?></strong></a>
                    <span class="muted clamp-1"><?= e($r[$c['subtitle']] ?? '') ?></span>
                    <?php if (!empty($r['is_featured'])): ?><span class="badge badge--in_progress" style="margin-top:4px">Featured</span><?php endif; ?>
                    <?php if ($key === 'slides'): ?><span class="muted" style="font-size:12px"><?= ($r['video_path'] || $r['video_url']) ? '<i class="fa-solid fa-video"></i> Video' : '<i class="fa-regular fa-image"></i> Poster only' ?> · <?= (int)$r['duration'] ?>s · overlay <?= (int)$r['overlay_opacity'] ?>%</span><?php endif; ?>
                    <?php if ($key === 'albums'): ?><span class="muted" style="font-size:12px"><?= e(implode(' · ', array_filter([$r['category'], $r['location'], nice_date($r['event_date'] ?? null)]))) ?></span><?php endif; ?></div>
                </div></td>
                <td><label class="switch"><input type="checkbox" data-publish="<?= e($key) ?>" data-id="<?= (int)$r['id'] ?>"<?= $r['status'] === 'published' ? ' checked' : '' ?> aria-label="Published"><span class="switch__track"></span><span class="muted" data-publish-label><?= $r['status'] === 'published' ? 'Live' : 'Hidden' ?></span></label></td>
                <td class="muted"><?= e(format_date($r['updated_at'])) ?></td>
                <td><div class="actions">
                  <?php if (!empty($c['photos'])): ?><a class="btn btn--ghost btn--sm" href="<?= e(url('admin/albums.php?id=' . (int)$r['id'])) ?>"><i class="fa-regular fa-images"></i>Photos (<?= (int)($photoCounts[(int)$r['id']] ?? 0) ?>)</a><?php endif; ?>
                  <?php if (!empty($c['preview'])): ?><a class="btn btn--ghost btn--sm" href="<?= e(url('index.php?preview_slide=' . (int)$r['id'])) ?>" target="_blank" rel="noopener"><i class="fa-regular fa-eye"></i>Preview</a><?php endif; ?>
                  <a class="btn btn--ghost btn--sm" href="<?= e($self . '?edit=' . (int)$r['id']) ?>"><i class="fa-solid fa-pen"></i>Edit</a>
                  <form method="post" action="<?= e(url('admin/actions/collection-delete.php')) ?>" data-ajax data-confirm="Delete “<?= e($title) ?>”? This permanently removes it from the website." data-danger>
                    <?= csrf_field() ?><input type="hidden" name="collection" value="<?= e($key) ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn--danger btn--icon btn--sm" type="submit" title="Delete" aria-label="Delete <?= e($title) ?>"><i class="fa-solid fa-trash-can"></i></button>
                  </form>
                </div></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="5"><div class="empty"><i class="<?= $c['icon'] ?>"></i><h3><?= $all ? 'Nothing matches your filters' : 'No ' . e(strtolower($c['label'])) . ' yet' ?></h3>
                <?php if (!$all): ?><a class="btn btn--gold btn--sm" style="margin-top:10px" href="<?= e($self . '?new=1') ?>"><i class="fa-solid fa-plus"></i>Add your first <?= e($c['singular']) ?></a><?php endif; ?></div></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="toolbar" style="border-top:1px solid var(--line);border-bottom:0;justify-content:space-between">
        <span class="muted">Showing <?= count($rows) ?> of <?= $total ?><?= $total !== $all ? " (filtered from $all)" : '' ?></span>
        <?php if ($canDrag && $rows): ?><span class="f__help"><i class="fa-solid fa-grip-vertical"></i> Drag rows to change the order on the website.</span>
        <?php elseif ($rows): ?><span class="f__help">Reset filters and sort by “Display order” to drag rows.</span><?php endif; ?>
        <?php render_pagination($pg); ?>
      </div>
    </div>
    <?php
    admin_footer();
}

function collection_edit_view(string $key, array $c, int $id): void
{
    $item = [];
    if ($id) {
        $row = db_one("SELECT * FROM {$c['table']} WHERE id = ?", [$id]);
        if (!$row) { flash('error', ucfirst($c['singular']) . ' not found.'); redirect('admin/' . $c['admin']); }
        $item = decode_item($key, $row);
    }
    $self = url('admin/' . $c['admin']);
    $title = $id ? 'Edit ' . $c['singular'] : 'New ' . $c['singular'];
    admin_header($c['label'], $key);
    $extra = '';
    if ($id && !empty($c['photos'])) $extra .= '<a class="btn btn--forest" href="' . e(url('admin/albums.php?id=' . $id)) . '"><i class="fa-regular fa-images"></i>Manage photos (' . (int)db_val('SELECT COUNT(*) FROM portfolio_images WHERE album_id = ?', [$id]) . ')</a>';
    if ($id && !empty($c['preview'])) $extra .= '<a class="btn btn--forest" href="' . e(url('index.php?preview_slide=' . $id)) . '" target="_blank" rel="noopener"><i class="fa-regular fa-eye"></i>Preview slide</a>';
    admin_page_head($title, $id ? 'Last updated ' . format_date($item['updated_at'] ?? null, true) : 'Fill in the details and press Save. You can hide it until it\'s ready.',
        $extra . '<a class="btn btn--ghost" href="' . e($self) . '"><i class="fa-solid fa-arrow-left"></i>Back to ' . e(strtolower($c['label'])) . '</a>');
    ?>
    <form class="card" method="post" action="<?= e(url('admin/actions/collection-save.php')) ?>" data-ajax data-track-dirty>
      <div class="card__body">
        <?= csrf_field() ?>
        <input type="hidden" name="collection" value="<?= e($key) ?>">
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <?php render_fields($c['fields'], $item); ?>
      </div>
      <div class="modal__foot" style="border-radius:0 0 var(--radius) var(--radius)">
        <span class="spacer f__help">Changes appear on the website as soon as you save.</span>
        <a class="btn btn--ghost" href="<?= e($self) ?>">Cancel</a>
        <button class="btn btn--gold" type="submit"><i class="fa-solid fa-floppy-disk"></i><?= $id ? 'Save changes' : 'Add ' . e($c['singular']) ?></button>
      </div>
    </form>
    <?php
    admin_footer();
}

function render_pagination(array $pg): void
{
    if ($pg['pages'] < 2) return;
    echo '<nav class="pagination" aria-label="Pagination">';
    for ($i = 1; $i <= $pg['pages']; $i++) {
        echo $i === $pg['page'] ? '<span aria-current="page">' . $i . '</span>' : '<a href="' . e(qs(['p' => $i])) . '">' . $i . '</a>';
    }
    echo '</nav>';
}
