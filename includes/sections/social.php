<?php defined('APP_NAME') || exit;
/** @var array $s */
$imgs = array_slice(sl($s, 'images'), 0, 12); if (!$imgs) return;
$ig = setting('social_instagram'); ?>
<section class="section" data-section="social" style="padding-bottom:0">
  <div class="container">
    <?php sec_head($s, 'sec-head--center'); ?>
  </div>
  <div class="social__grid">
    <?php foreach ($imgs as $i => $p): ?>
      <a class="social__item reveal-card" style="--d:<?= ($i % 6) * .06 ?>s" href="<?= e($ig ?: '#') ?>"<?= $ig ? ' target="_blank" rel="noopener"' : '' ?> aria-label="Open Instagram"><?= photo_tag($p, 'Wedding moment from our Instagram') ?></a>
    <?php endforeach; ?>
  </div>
  <?php if ($ig && sv($s, 'handle') !== ''): ?><div class="center-cta" style="margin:40px 0 0;padding-bottom:var(--section)"><a class="btn btn--outline magnetic" href="<?= e($ig) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-instagram"></i><span><?= e(sv($s, 'handle')) ?></span></a></div><?php endif; ?>
</section>
