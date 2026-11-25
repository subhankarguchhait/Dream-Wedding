<?php defined('APP_NAME') || exit;
/** @var array $s */ ?>
<section class="section why" data-section="why">
  <div class="container">
    <?php sec_head($s, 'sec-head--center'); ?>
    <div class="why__grid">
      <?php foreach (sl($s, 'items') as $i => $it): ?>
        <article class="why-item reveal-card" style="--d:<?= ($i % 3) * .08 ?>s"><i class="<?= e(safe_icon($it['icon'] ?? '')) ?>" aria-hidden="true"></i><h3><?= e($it['title'] ?? '') ?></h3><p><?= e($it['text'] ?? '') ?></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
