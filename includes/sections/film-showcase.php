<?php defined('APP_NAME') || exit;
/** @var array $s */
$film = featured_collection('films', 1)[0] ?? null; if (!$film) return; ?>
<section class="film-show" data-section="film">
  <div class="film-show__bg" data-parallax="0.2"><?= img_tag(sv($s, 'image')) ?></div>
  <div class="container">
    <?php sec_head($s, 'sec-head--center sec-head--light'); ?>
    <?php include __DIR__ . '/_film-frame.php'; ?>
    <div class="center-cta reveal"><?php cms_button(sv($s, 'button_text'), sv($s, 'button_url'), 'btn btn--ghost-light'); ?></div>
  </div>
</section>
