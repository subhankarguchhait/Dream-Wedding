<?php defined('APP_NAME') || exit;
/** @var array $s  Home → "Made for your phone": horizontal strip of vertical reels.
 *  Uploaded MP4s play silently while on screen; a tap opens the reel with sound in the video modal. */
$limit = max(1, min(20, (int)($s['limit'] ?? 10) ?: 10));
$reels = [];
foreach (public_collection('reels') as $r) {
    $emb = film_media($r);
    if (!$emb) continue;                                   // nothing playable → skip
    $r['_emb'] = $emb;
    $r['_file'] = $emb['file'];
    $r['_poster'] = media_url($r['poster_image'] ?? '') ?: ($emb['thumb'] ?? '');
    $reels[] = $r;
    if (count($reels) >= $limit) break;
}
if (!$reels) return;
$sid = 'reels-' . e($key ?: 'home'); ?>
<section class="section reels-sec" data-section="reels">
  <div class="container">
    <div class="split-head">
      <?php sec_head($s); ?>
      <div class="split-head__action reveal">
        <?php cms_button(sv($s, 'button_text'), sv($s, 'button_url'), 'btn btn--ghost'); ?>
        <div class="reels-nav" aria-hidden="<?= count($reels) < 3 ? 'true' : 'false' ?>">
          <button type="button" data-reels-scroll="-1" data-target="<?= $sid ?>" aria-label="Previous reels"><i class="fa-solid fa-arrow-left"></i></button>
          <button type="button" data-reels-scroll="1" data-target="<?= $sid ?>" aria-label="Next reels"><i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </div>
    </div>
  </div>
  <div class="reels" id="<?= $sid ?>">
    <?php foreach ($reels as $i => $r): $emb = $r['_emb']; ?>
      <button type="button" class="reel reveal-card" style="--d:<?= min($i, 5) * .07 ?>s"
        data-video="<?= e(video_data($emb, $r['title'], true)) ?>"
        aria-label="Play reel: <?= e($r['title']) ?>">
        <?php if ($r['_poster']): ?><img src="<?= e($r['_poster']) ?>" alt="" loading="lazy" decoding="async"><?php endif; ?>
        <?php if ($r['_file']): ?><video muted loop playsinline preload="none" data-src="<?= e($r['_file']) ?>" aria-hidden="true"></video><?php endif; ?>
        <span class="reel__badge"><i class="fa-solid fa-play" aria-hidden="true"></i> <?= e($r['category'] ?: 'Reel') ?></span>
        <span class="reel__sound" aria-hidden="true"><i class="fa-solid fa-volume-high"></i></span>
        <span class="reel__cap"><strong><?= e($r['title']) ?></strong><?php if (!empty($r['couple_names'])): ?><small><?= e($r['couple_names']) ?></small><?php endif; ?></span>
      </button>
    <?php endforeach; ?>
  </div>
</section>
