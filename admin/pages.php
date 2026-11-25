<?php
/**
 * Pages & Sections editor: edit every section, show/hide it, change its order, and edit page SEO.
 */
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin-layout.php';
require_admin();

$schemas = page_schemas();
$slug = get_str('page', 20);
if (!isset($schemas[$slug])) $slug = 'home';
$schema = $schemas[$slug];

$pageRow = db_one('SELECT * FROM pages WHERE page_slug = ?', [$slug]);
$rows = [];
if ($pageRow) {
    foreach (db_all('SELECT * FROM page_sections WHERE page_id = ? ORDER BY display_order, id', [(int)$pageRow['id']]) as $r) $rows[$r['section_key']] = $r;
}
$defaults = default_pages()[$slug]['sections'];

// Sections in their saved order; any schema section missing from the DB appears at the end using default content
$ordered = [];
foreach ($rows as $key => $r) if (isset($schema['sections'][$key])) $ordered[$key] = section_from_row($r);
$pos = count($ordered);
foreach ($schema['sections'] as $key => $def) {
    if (!isset($ordered[$key])) $ordered[$key] = ($defaults[$key] ?? []) + ['_visible' => true, '_order' => ++$pos, '_id' => 0, '_new' => true];
}

$liveUrl = url($schema['file'] === 'index.php' ? '' : $schema['file']);
admin_header('Pages & Sections', 'pages');
admin_page_head($schema['label'] . ' page', 'Open a section to edit it. Use the switch to show or hide it, and the arrows to change its position on the page.',
    '<a class="btn btn--ghost" href="' . e($liveUrl) . '" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i>View live page</a>');
?>
<div class="tabs" role="tablist">
  <?php foreach ($schemas as $k => $s): ?>
    <a role="tab" class="<?= $k === $slug ? 'is-active' : '' ?>" aria-selected="<?= $k === $slug ? 'true' : 'false' ?>" href="<?= e(url('admin/pages.php?page=' . $k)) ?>"><i class="<?= $s['icon'] ?>"></i><?= e($s['label']) ?></a>
  <?php endforeach; ?>
  <span class="sep"></span>
  <a href="<?= e(url('admin/services.php')) ?>"><i class="fa-solid fa-briefcase"></i>Services</a>
  <a href="<?= e(url('admin/testimonials.php')) ?>"><i class="fa-solid fa-quote-left"></i>Testimonials</a>
  <a href="<?= e(url('admin/team.php')) ?>"><i class="fa-solid fa-user-group"></i>Team</a>
</div>

<?php if (!$pageRow): ?>
  <div class="alert alert--warn" style="margin-bottom:16px"><i class="fa-solid fa-triangle-exclamation"></i><span>This page is missing from the database. Saving any section will create it.</span></div>
<?php endif; ?>

<div class="sections" data-sections data-page="<?= e($slug) ?>">
  <?php foreach ($ordered as $key => $s): $def = $schema['sections'][$key]; $visible = !empty($s['_visible']); ?>
    <div class="sec-card<?= $visible ? '' : ' is-off' ?>" data-key="<?= e($key) ?>">
      <div class="sec-card__head" data-sec-toggle>
        <div class="sec-card__order">
          <button type="button" data-sec-move="-1" aria-label="Move <?= e($def['label']) ?> up"><i class="fa-solid fa-chevron-up"></i></button>
          <button type="button" data-sec-move="1" aria-label="Move <?= e($def['label']) ?> down"><i class="fa-solid fa-chevron-down"></i></button>
        </div>
        <span class="sec-card__icon"><i class="<?= $def['icon'] ?>"></i></span>
        <div class="sec-card__title"><strong><?= e($def['label']) ?></strong><small><?= e($def['note'] ?? (plain(sv($s, 'title')) ?: implode(' · ', array_slice(array_filter(sl($s, 'items'), 'is_string'), 0, 4)))) ?></small></div>
        <span class="sec-card__dirty">Unsaved</span>
        <label class="switch" title="Show on website"><input type="checkbox" data-sec-visible<?= $visible ? ' checked' : '' ?> aria-label="Show <?= e($def['label']) ?> on the website"><span class="switch__track"></span></label>
        <i class="fa-solid fa-chevron-down sec-card__chev"></i>
      </div>
      <div class="sec-card__body">
        <?php if (!empty($def['note'])): ?><div class="alert alert--info" style="margin-top:16px"><i class="fa-solid fa-circle-info"></i><?= e($def['note']) ?></div><?php endif; ?>
        <form method="post" action="<?= e(url('admin/actions/page-save.php')) ?>" data-ajax data-track-dirty>
          <?= csrf_field() ?>
          <input type="hidden" name="op" value="save">
          <input type="hidden" name="page" value="<?= e($slug) ?>">
          <input type="hidden" name="section" value="<?= e($key) ?>">
          <?php render_fields($def['fields'], $s); ?>
          <div class="sec-card__foot">
            <a class="btn btn--ghost" href="<?= e($liveUrl) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-eye"></i>Preview</a>
            <button class="btn btn--gold" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save section</button>
          </div>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="sec-card" style="margin-top:14px">
  <div class="sec-card__head" data-sec-toggle>
    <span class="sec-card__icon"><i class="fa-solid fa-magnifying-glass"></i></span>
    <div class="sec-card__title"><strong>SEO for this page</strong><small>Browser tab title and Google description</small></div>
    <span class="sec-card__dirty">Unsaved</span>
    <i class="fa-solid fa-chevron-down sec-card__chev"></i>
  </div>
  <div class="sec-card__body">
    <form method="post" action="<?= e(url('admin/actions/page-save.php')) ?>" data-ajax data-track-dirty>
      <?= csrf_field() ?>
      <input type="hidden" name="op" value="seo">
      <input type="hidden" name="page" value="<?= e($slug) ?>">
      <?php render_fields(page_seo_fields(), ['meta_title' => $pageRow['meta_title'] ?? default_pages()[$slug]['meta']['title'], 'meta_description' => $pageRow['meta_description'] ?? default_pages()[$slug]['meta']['description']]); ?>
      <div class="sec-card__foot"><button class="btn btn--gold" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save SEO</button></div>
    </form>
  </div>
</div>
<?php if ($pageRow): ?><p class="f__help" style="margin-top:14px">Last saved <?= e(format_date($pageRow['updated_at'], true)) ?></p><?php endif; ?>
<?php admin_footer();
