<?php defined('APP_NAME') || exit;
/** @var array $s */
$films = public_collection('films');
$cats = array_values(array_unique(array_filter(array_column($films, 'category')))); ?>
<section class="section" data-section="grid">
  <div class="container">
    <?php sec_head($s, 'sec-head--center'); ?>
    <?php if (count($cats) > 1): ?>
      <div class="filters film-filters reveal" role="tablist" aria-label="Filter films" data-filter-for="films">
        <button type="button" role="tab" class="is-active" aria-selected="true" data-filter="all">All films</button>
        <?php foreach ($cats as $c): ?><button type="button" role="tab" aria-selected="false" data-filter="<?= e($c) ?>"><?= e($c) ?></button><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="films-grid" data-filter-items="films">
      <?php foreach ($films as $i => $f): $emb = film_media($f); if (!$emb) continue; $thumb = media_url($f['thumbnail'] ?? '') ?: $emb['thumb']; ?>
        <button type="button" class="film-card reveal-card" style="--d:<?= ($i % 3) * .1 ?>s" data-category="<?= e($f['category']) ?>" data-video="<?= e(video_data($emb, $f['title'])) ?>" aria-label="Play film: <?= e($f['title']) ?>">
          <div class="film-card__thumb"><?php if ($thumb): ?><img src="<?= e($thumb) ?>" alt="" loading="lazy" width="640" height="360"><?php elseif ($emb['file']): ?><video src="<?= e($emb['file']) ?>#t=0.5" muted playsinline preload="metadata" aria-hidden="true"></video><?php endif; ?><span class="play" aria-hidden="true"><i class="fa-solid fa-play"></i></span><?php if (!empty($f['duration_label'])): ?><span class="film-card__len"><?= e($f['duration_label']) ?></span><?php endif; ?></div>
          <h3><?= e($f['title']) ?></h3>
          <small><?= e(implode(' · ', array_filter([$f['category'], $f['couple_names'], $f['location']]))) ?></small>
          <?php if (!empty($f['description'])): ?><p><?= e($f['description']) ?></p><?php endif; ?>
        </button>
      <?php endforeach; ?>
    </div>
    <?php if (!$films): ?><p class="empty-state">Films will appear here once published.</p><?php endif; ?>
  </div>
</section>
