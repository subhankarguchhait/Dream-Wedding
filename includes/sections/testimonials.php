<?php defined('APP_NAME') || exit;
/** @var array $s */ $list = public_collection('testimonials'); if (!$list) return; ?>
<section class="section testimonials" data-section="testimonials">
  <div class="container">
    <?php sec_head($s, 'sec-head--center'); ?>
    <div class="t-slider" data-slider>
      <div class="t-track reveal">
        <?php foreach ($list as $i => $t): $rating = max(0, min(5, (int)($t['rating'] ?? 0))); ?>
          <figure class="t-slide<?= $i === 0 ? ' is-active' : '' ?>" aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>">
            <blockquote class="t-slide__quote"><?= e($t['testimonial']) ?></blockquote>
            <?php if ($rating): ?><div class="t-slide__stars" aria-label="<?= $rating ?> out of 5"><?= str_repeat('★', $rating) ?></div><?php endif; ?>
            <figcaption class="t-slide__who">
              <?php if (media_url($t['client_image'] ?? '')): ?><img class="t-slide__avatar" src="<?= e(media_url($t['client_image'])) ?>" alt="" loading="lazy">
              <?php else: ?><span class="t-slide__avatar"><?= e(initials($t['client_name'])) ?></span><?php endif; ?>
              <div><strong><?= e($t['client_name']) ?></strong><small><?= e(implode(', ', array_filter([$t['designation'] ?? '', $t['company'] ?? '']))) ?></small></div>
            </figcaption>
          </figure>
        <?php endforeach; ?>
      </div>
      <?php if (count($list) > 1): ?>
        <div class="t-nav">
          <button type="button" class="t-arrow" data-prev aria-label="Previous testimonial"><i class="fa-solid fa-arrow-left"></i></button>
          <div class="t-dots"><?php foreach ($list as $i => $t): ?><button type="button" class="<?= $i === 0 ? 'is-active' : '' ?>" data-go="<?= $i ?>" aria-label="Show testimonial <?= $i + 1 ?>"></button><?php endforeach; ?></div>
          <button type="button" class="t-arrow" data-next aria-label="Next testimonial"><i class="fa-solid fa-arrow-right"></i></button>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
