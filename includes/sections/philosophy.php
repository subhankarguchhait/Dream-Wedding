<?php defined('APP_NAME') || exit;
/** @var array $s */ ?>
<section class="philosophy" data-section="philosophy">
  <div class="philosophy__bg" data-parallax="0.2"><?= img_tag(sv($s, 'image')) ?></div>
  <div class="container">
    <?php if (sv($s, 'eyebrow') !== ''): ?><p class="eyebrow eyebrow--light reveal" style="justify-content:center"><span class="eyebrow__line"></span><span><?= e(sv($s, 'eyebrow')) ?></span></p><?php endif; ?>
    <h2 class="sec-title reveal" style="color:var(--ivory);margin-bottom:40px"><?= rich(sv($s, 'title')) ?></h2>
    <blockquote class="reveal"><?= e(sv($s, 'content')) ?></blockquote>
    <?php if (sv($s, 'signature') !== ''): ?><cite class="reveal"><?= e(sv($s, 'signature')) ?></cite><?php endif; ?>
  </div>
</section>
