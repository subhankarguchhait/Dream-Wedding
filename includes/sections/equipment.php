<?php defined('APP_NAME') || exit;
/** @var array $s */ ?>
<section class="section" data-section="equipment">
  <div class="container">
    <?php sec_head($s, 'sec-head--center'); ?>
    <div class="equip__grid reveal">
      <?php foreach (sl($s, 'items') as $it): ?><article class="equip"><i class="<?= e(safe_icon($it['icon'] ?? '', 'fa-solid fa-camera')) ?>" aria-hidden="true"></i><h3><?= e($it['title'] ?? '') ?></h3><p><?= e($it['text'] ?? '') ?></p></article><?php endforeach; ?>
    </div>
  </div>
</section>
