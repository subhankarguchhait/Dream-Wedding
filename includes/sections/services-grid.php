<?php defined('APP_NAME') || exit;
/** @var array $s */
$services = featured_collection('services', (int)($s['limit'] ?? 8)); ?>
<section class="section" data-section="services">
  <div class="container">
    <?php sec_head($s, 'sec-head--center'); ?>
    <div class="svc-grid">
      <?php foreach ($services as $i => $svc): ?>
        <a class="svc-card reveal-card" style="--d:<?= ($i % 4) * .08 ?>s" href="<?= e(url('services.php#' . $svc['slug'])) ?>">
          <div class="svc-card__img"><?= photo_tag($svc['cover_image'] ?? '', $svc['title']) ?></div>
          <div class="svc-card__body">
            <span class="svc-card__icon"><i class="<?= e(safe_icon($svc['icon'] ?? '', 'fa-solid fa-camera')) ?>" aria-hidden="true"></i></span>
            <h3><?= e($svc['title']) ?></h3>
            <p><?= e($svc['tagline'] ?: $svc['description']) ?></p>
            <span class="svc-card__link">Discover <i class="fa-solid fa-arrow-right"></i></span>
          </div>
        </a>
      <?php endforeach; ?>
      <?php if (!$services): ?><p class="empty-state"><i class="fa-solid fa-camera"></i>Services will appear here once published.</p><?php endif; ?>
    </div>
    <div class="center-cta reveal"><?php cms_button(sv($s, 'button_text'), sv($s, 'button_url'), 'btn btn--outline'); ?></div>
  </div>
</section>
