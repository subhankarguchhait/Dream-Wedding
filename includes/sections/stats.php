<?php defined('APP_NAME') || exit;
/** @var array $s */ $items = sl($s, 'items'); if (!$items) return; ?>
<section class="stats" data-section="stats">
  <canvas class="particles" data-particles="25" aria-hidden="true"></canvas>
  <div class="container stats__grid" style="--cols:<?= min(4, count($items)) ?>">
    <?php foreach ($items as $i => $it): $v = str_replace(',', '', (string)($it['value'] ?? '')); $num = preg_match('/^\d+(\.\d+)?$/', $v); ?>
      <div class="stat reveal" style="--d:<?= $i * 0.12 ?>s">
        <strong><span<?= $num ? ' class="counter" data-count="' . e($v) . '"' : '' ?>><?= e($it['value'] ?? '') ?></span><?php if (!empty($it['suffix'])): ?><sup><?= e($it['suffix']) ?></sup><?php endif; ?></strong>
        <span><?= e($it['label'] ?? '') ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</section>
