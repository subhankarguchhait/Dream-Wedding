<?php defined('APP_NAME') || exit;
/** @var array $s */ $steps = sl($s, 'steps'); ?>
<section class="section process" data-section="process">
  <div class="container">
    <?php sec_head($s, 'sec-head--center'); ?>
    <ol class="process__list" style="--steps:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $st): ?>
        <li class="step reveal" style="--d:<?= $i * 0.15 ?>s">
          <span class="step__num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <h3><?= e($st['title'] ?? '') ?></h3>
          <p><?= e($st['text'] ?? '') ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
