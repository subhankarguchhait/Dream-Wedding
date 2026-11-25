<?php defined('APP_NAME') || exit;
/** @var array $s */
$photos = recent_moments(max(6, min(60, (int)($s['limit'] ?? 24)))); if (!$photos) return;
$cats = array_values(array_unique(array_filter(array_column($photos, 'category')))); ?>
<section class="section" data-section="moments" style="background:var(--white)">
  <div class="container">
    <?php sec_head($s, 'sec-head--center'); ?>
    <?php if (count($cats) > 1): ?>
      <div class="filters reveal" role="tablist" aria-label="Filter photos" data-filter-for="moments">
        <button type="button" role="tab" class="is-active" aria-selected="true" data-filter="all">All</button>
        <?php foreach ($cats as $c): ?><button type="button" role="tab" aria-selected="false" data-filter="<?= e($c) ?>"><?= e($c) ?></button><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="masonry" data-filter-items="moments" data-lightbox-group="moments">
      <?php foreach ($photos as $p): $alt = $p['alt_text'] ?: ($p['album_title'] ?? 'Wedding photo'); ?>
        <button type="button" class="masonry__item" data-category="<?= e($p['category'] ?? '') ?>" data-lightbox="<?= e(media_url($p['file_path'])) ?>" data-caption="<?= e($p['caption'] ?: ($p['album_title'] ?? '')) ?>" aria-label="View photo: <?= e($alt) ?>">
          <?= photo_tag($p['thumb_path'] ?: $p['file_path'], $alt, (int)$p['width'] ?: null, (int)$p['height'] ?: null) ?>
          <?php if (!empty($p['album_title'])): ?><figcaption><?= e($p['album_title']) ?></figcaption><?php endif; ?>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
</section>
