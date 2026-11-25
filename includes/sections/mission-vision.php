<?php defined('APP_NAME') || exit;
/** @var array $s */ ?>
<section class="section mv" data-section="mission_vision">
  <div class="container">
    <?php sec_head($s, 'sec-head--center'); ?>
    <div class="mv__grid">
      <article class="mv__card tilt reveal"><span class="mv__icon"><i class="fa-solid fa-bullseye" aria-hidden="true"></i></span><h3><?= e(sv($s, 'mission_title', 'Our Mission')) ?></h3><p><?= e(sv($s, 'mission')) ?></p></article>
      <div class="mv__media reveal-clip"><?= img_tag(sv($s, 'image')) ?></div>
      <article class="mv__card tilt reveal"><span class="mv__icon"><i class="fa-solid fa-eye" aria-hidden="true"></i></span><h3><?= e(sv($s, 'vision_title', 'Our Vision')) ?></h3><p><?= e(sv($s, 'vision')) ?></p></article>
    </div>
  </div>
</section>
