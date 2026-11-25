<?php defined('APP_NAME') || exit;
/** @var array $s */
$blocks = preg_split('/\R\s*\R/u', trim(sv($s, 'content'))) ?: []; ?>
<section class="section" data-section="body">
  <div class="container legal">
    <h2><?= rich(sv($s, 'title')) ?></h2>
    <?php if (sv($s, 'description') !== ''): ?><p class="legal__updated"><?= e(sv($s, 'description')) ?></p><?php endif; ?>
    <?php foreach ($blocks as $b): $b = trim($b); if ($b === '') continue; ?>
      <?php if (str_starts_with($b, '## ')): ?><h3><?= e(substr($b, 3)) ?></h3><?php else: ?><p><?= nl2br(e($b), false) ?></p><?php endif; ?>
    <?php endforeach; ?>
  </div>
</section>
