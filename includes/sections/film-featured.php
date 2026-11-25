<?php defined('APP_NAME') || exit;
/** @var array $s */
$film = featured_collection('films', 1)[0] ?? null; if (!$film) return; ?>
<section class="section" data-section="featured" style="background:var(--dark);color:var(--ivory)">
  <div class="container">
    <?php sec_head($s, 'sec-head--center sec-head--light'); ?>
    <?php include __DIR__ . '/_film-frame.php'; ?>
    <?php if (!empty($film['description'])): ?><p class="reveal" style="max-width:720px;margin:30px auto 0;text-align:center;color:var(--muted-light)"><?= e($film['description']) ?></p><?php endif; ?>
  </div>
</section>
