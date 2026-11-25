<?php defined('APP_NAME') || exit;
/** @var array $s */
$services = public_collection('services'); ?>
<section class="section" data-section="list" style="padding-bottom:0">
  <div class="container"><?php sec_head($s, 'sec-head--center'); ?></div>
</section>
<?php foreach ($services as $i => $svc): $gallery = array_slice((array)($svc['gallery'] ?? []), 0, 8); ?>
  <section class="svc-detail" id="<?= e($svc['slug']) ?>">
    <div class="container svc-detail__grid">
      <div class="svc-detail__media">
        <div class="svc-detail__cover reveal-clip"><?= photo_tag($svc['cover_image'] ?? '', $svc['title']) ?></div>
        <?php if ($gallery): ?>
          <div class="svc-thumbs" data-lightbox-group="svc-<?= e($svc['slug']) ?>">
            <?php foreach ($gallery as $g): ?><button type="button" data-lightbox="<?= e(media_url($g)) ?>" data-caption="<?= e($svc['title']) ?>" aria-label="View photo"><?= photo_tag($g, $svc['title']) ?></button><?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div>
        <div class="svc-detail__num reveal"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></div>
        <?php if (!empty($svc['tagline'])): ?><p class="svc-detail__tag reveal"><?= e($svc['tagline']) ?></p><?php endif; ?>
        <h2 class="reveal"><?= e($svc['title']) ?></h2>
        <div class="prose reveal"><?= paras($svc['details'] ?: $svc['description']) ?></div>
        <?php if ($f = (array)($svc['features'] ?? [])): ?><ul class="svc-detail__features reveal"><?php foreach ($f as $x): ?><li><i class="fa-solid fa-diamond"></i><span><?= e($x) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
        <?php if (!empty($svc['price_note'])): ?><p class="svc-detail__price reveal"><?= e($svc['price_note']) ?></p><?php endif; ?>
        <div class="reveal"><a class="btn btn--spark magnetic" href="<?= e(url('contact.php?service=' . rawurlencode($svc['title']))) ?>"><span>Enquire about this</span><i class="fa-solid fa-arrow-right"></i></a></div>
      </div>
    </div>
  </section>
<?php endforeach; ?>
<?php if (!$services): ?><div class="container"><p class="empty-state">Services will appear here once published.</p></div><?php endif; ?>
