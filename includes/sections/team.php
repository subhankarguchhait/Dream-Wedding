<?php defined('APP_NAME') || exit;
/** @var array $s */ $team = public_collection('team'); ?>
<section class="section team" data-section="team">
  <div class="container">
    <?php sec_head($s, 'sec-head--center'); ?>
    <div class="team__grid">
      <?php foreach ($team as $i => $m): ?>
        <article class="member reveal-card" style="--d:<?= ($i % 4) * 0.1 ?>s">
          <?= img_tag($m['profile_image'] ?? '', $m['name']) ?>
          <div class="member__info">
            <span><?= e($m['designation']) ?></span>
            <h3><?= e($m['name']) ?></h3>
            <?php if (!empty($m['bio'])): ?><p><?= e($m['bio']) ?></p><?php endif; ?>
            <?php social_links(is_array($m['social_links'] ?? null) ? $m['social_links'] : [], 'member__social'); ?>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if (!$team): ?><p class="empty-state"><i class="fa-regular fa-user"></i>Team members will appear here once published.</p><?php endif; ?>
    </div>
  </div>
</section>
