<?php defined('APP_NAME') || exit;
/** @var array $s */
$albums = public_collection('albums');
$cats = array_values(array_unique(array_filter(array_column($albums, 'category')))); ?>
<section class="section" data-section="albums">
  <div class="container">
    <?php sec_head($s, 'sec-head--center'); ?>
    <?php if (count($cats) > 1): ?>
      <div class="filters reveal" role="tablist" aria-label="Filter albums" data-filter-for="albums">
        <button type="button" role="tab" class="is-active" aria-selected="true" data-filter="all">All</button>
        <?php foreach ($cats as $c): ?><button type="button" role="tab" aria-selected="false" data-filter="<?= e($c) ?>"><?= e($c) ?></button><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="albums" data-filter-items="albums">
      <?php foreach ($albums as $a): $n = album_photo_count($a); ?>
        <a class="album-card reveal-card" data-category="<?= e($a['category']) ?>" href="<?= e(album_url($a['slug'])) ?>">
          <?= photo_tag($a['cover_image'], $a['title']) ?>
          <span class="album-card__count"><i class="fa-regular fa-images"></i> <?= $n ?></span>
          <div class="album-card__body"><small><?= e($a['category']) ?></small><h3><?= e($a['couple_names'] ?: $a['title']) ?></h3><p><?= e(implode(' · ', array_filter([$a['title'] !== $a['couple_names'] ? $a['title'] : '', $a['location']]))) ?></p></div>
        </a>
      <?php endforeach; ?>
    </div>
    <?php if (!$albums): ?><p class="empty-state">Albums will appear here once published.</p><?php endif; ?>
  </div>
</section>
