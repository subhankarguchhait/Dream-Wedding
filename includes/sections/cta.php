<?php defined('APP_NAME') || exit;
/** @var array $s */ ?>
<section class="cta" data-section="cta">
  <div class="cta__bg" data-parallax="0.2"><?= img_tag(sv($s, 'image')) ?></div>
  <div class="cta__veil"></div>
  <canvas class="particles" data-particles="30" aria-hidden="true"></canvas>
  <div class="container cta__inner">
    <?php if (sv($s, 'eyebrow') !== ''): ?><p class="eyebrow eyebrow--light reveal"><span class="eyebrow__line"></span><span><?= e(sv($s, 'eyebrow')) ?></span></p><?php endif; ?>
    <h2 class="cta__title reveal"><?= rich(sv($s, 'title')) ?></h2>
    <?php if (sv($s, 'content') !== ''): ?><p class="cta__text reveal"><?= e(sv($s, 'content')) ?></p><?php endif; ?>
    <div class="cta__actions reveal">
      <?php cms_button(sv($s, 'button_text'), sv($s, 'button_url'), 'btn btn--spark'); ?>
      <?php cms_button(sv($s, 'cta2_text'), sv($s, 'cta2_url'), 'btn btn--ghost-light', false); ?>
    </div>
  </div>
</section>
