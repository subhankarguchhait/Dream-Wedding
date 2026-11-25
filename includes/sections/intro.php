<?php defined('APP_NAME') || exit;
/** @var array $s */ ?>
<section class="section intro2" data-section="intro">
  <div class="container intro2__grid">
    <div class="intro2__media reveal-clip">
      <span class="intro2__frame" aria-hidden="true"></span>
      <div class="intro2__main"><div data-parallax-inner="0.1"><?= img_tag(sv($s, 'image'), plain(sv($s, 'title'))) ?></div></div>
      <?php if (sv($s, 'image2') !== ''): ?><div class="intro2__small"><?= img_tag(sv($s, 'image2'), '') ?></div><?php endif; ?>
    </div>
    <div>
      <?php sec_head($s); ?>
      <div class="prose reveal"><?= paras(sv($s, 'content')) ?></div>
      <?php if (sv($s, 'signature') !== ''): ?><p class="signature reveal"><?= e(sv($s, 'signature')) ?></p><?php endif; ?>
      <div class="reveal" style="margin-top:30px"><?php cms_button(sv($s, 'button_text'), sv($s, 'button_url'), 'btn btn--outline'); ?></div>
    </div>
  </div>
</section>
