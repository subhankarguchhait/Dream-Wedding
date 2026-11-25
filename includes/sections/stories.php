<?php defined('APP_NAME') || exit;
/** @var array $s */
$albums = featured_collection('albums', (int)($s['limit'] ?? 4)); if (!$albums) return; ?>
<section class="section stories" data-section="stories">
  <div class="container">
    <?php sec_head($s, 'sec-head--center sec-head--light'); ?>
    <div class="stories__grid">
      <?php foreach ($albums as $i => $a): ?>
        <a class="story-card reveal-card" style="--d:<?= ($i % 2) * .12 ?>s" href="<?= e(album_url($a['slug'])) ?>">
          <?= photo_tag($a['cover_image'], $a['title'] . ($a['couple_names'] ? ' — ' . $a['couple_names'] : '')) ?>
          <div class="story-card__body">
            <span class="story-card__cat"><?= e($a['category']) ?></span>
            <h3><?= e($a['couple_names'] ?: $a['title']) ?></h3>
            <div class="story-card__meta">
              <?php if ($a['location']): ?><span><i class="fa-solid fa-location-dot"></i><?= e($a['location']) ?></span><?php endif; ?>
              <?php if (!empty($a['event_date'])): ?><span><i class="fa-regular fa-calendar"></i><?= e(nice_date($a['event_date'])) ?></span><?php endif; ?>
            </div>
            <span class="story-card__more">View story <i class="fa-solid fa-arrow-right"></i></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="center-cta reveal"><?php cms_button(sv($s, 'button_text'), sv($s, 'button_url'), 'btn btn--ghost-light'); ?></div>
  </div>
</section>
