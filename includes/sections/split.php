<?php defined('APP_NAME') || exit;
/** @var array $s */ ?>
<section class="section split<?= 'split' === 'split-reverse' ? ' split--reverse' : '' ?>" data-section="<?= e($key) ?>">
  <div class="container split__grid">
    <div class="split__media reveal-clip"><div data-parallax-inner="0.08" style="height:112%"><?= img_tag(sv($s, 'image'), plain(sv($s, 'title'))) ?></div></div>
    <div>
      <?php sec_head($s); ?>
      <div class="prose reveal"><?= paras(sv($s, 'content')) ?></div>
      <?php if ($points = sl($s, 'points')): ?><ul class="ticks reveal"><?php foreach ($points as $p): ?><li><i class="fa-solid fa-check" aria-hidden="true"></i><span><?= e($p) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
    </div>
  </div>
</section>
