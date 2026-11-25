<?php defined('APP_NAME') || exit;
/** @var array $s @var array $page */ ?>
<section class="page-hero" data-section="hero">
  <div class="page-hero__bg" data-parallax="0.25"><?= img_tag(sv($s, 'image'), '', 'fetchpriority="high"') ?></div>
  <div class="page-hero__veil"></div>
  <canvas class="particles" data-particles="40" aria-hidden="true"></canvas>
  <div class="container page-hero__content">
    <p class="crumb reveal"><a href="<?= e(url()) ?>">Home</a><i class="fa-solid fa-chevron-right" aria-hidden="true"></i><span><?= e($page['meta']['name'] ?? page_schemas()[$page['slug']]['label']) ?></span></p>
    <?php if (sv($s, 'eyebrow') !== ''): ?><p class="eyebrow eyebrow--light reveal"><span class="eyebrow__line"></span><span><?= e(sv($s, 'eyebrow')) ?></span></p><?php endif; ?>
    <h1 class="page-hero__title reveal-words"><?= rich(sv($s, 'title')) ?></h1>
    <?php if (sv($s, 'content') !== ''): ?><p class="page-hero__sub reveal"><?= e(sv($s, 'content')) ?></p><?php endif; ?>
  </div>
  <div class="page-hero__scroll" aria-hidden="true"><span></span></div>
</section>
