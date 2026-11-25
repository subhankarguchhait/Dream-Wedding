<?php defined('APP_NAME') || exit;
/** @var array $s  Home → "Recent work": mosaic of the latest films (featured first).
 *  Uploaded MP4 films preview silently on hover (desktop) / while on screen (phones); click → full player with sound. */
$limit = max(1, min(9, (int)($s['limit'] ?? 5) ?: 5));
$films = [];
foreach (public_collection('films') as $f) {
    if (!($m = film_media($f))) continue;
    $f['_m'] = $m;
    $films[] = $f;
}
usort($films, fn($a, $b) => (int)!empty($b['is_featured']) <=> (int)!empty($a['is_featured'])); // stable: keeps admin order inside each group
$films = array_slice($films, 0, $limit);
if (!$films) return; ?>
<section class="section recent-work" data-section="recent">
  <div class="container">
    <div class="split-head">
      <?php sec_head($s, 'sec-head--light'); ?>
      <div class="split-head__action reveal"><?php cms_button(sv($s, 'button_text'), sv($s, 'button_url'), 'btn btn--ghost-light'); ?></div>
    </div>
    <div class="bento bento--<?= count($films) ?>">
      <?php foreach ($films as $i => $f):
        $m = $f['_m'];
        $thumb = media_url($f['thumbnail'] ?? '') ?: $m['thumb'];
        $meta = implode(' · ', array_filter([$f['couple_names'] ?? '', $f['location'] ?? '']));
        $kind = $m['type'] === 'file' ? 'MP4' : ucfirst($m['type']); ?>
        <button type="button" class="bento__tile reveal-card<?= $m['file'] ? ' has-preview' : '' ?><?= $thumb ? '' : ' no-thumb' ?>" style="--d:<?= ($i % 3) * .08 ?>s"
          data-video="<?= e(video_data($m, $f['title'])) ?>" aria-label="Play film: <?= e($f['title']) ?>">
          <?php if ($thumb): ?><img src="<?= e($thumb) ?>" alt="" loading="lazy" decoding="async"><?php endif; ?>
          <?php if ($m['file']): ?>
            <video class="bento__preview" muted loop playsinline preload="<?= $thumb ? 'none' : 'metadata' ?>"
              <?= $thumb ? 'data-src="' . e($m['file']) . '"' : 'src="' . e($m['file']) . '#t=0.5"' ?> aria-hidden="true"></video>
          <?php endif; ?>
          <span class="bento__play" aria-hidden="true"><i class="fa-solid fa-play"></i></span>
          <?php if (!empty($f['is_featured'])): ?><span class="bento__star"><i class="fa-solid fa-star" aria-hidden="true"></i> Featured</span><?php endif; ?>
          <span class="bento__len" <?= ($m['file'] && empty($f['duration_label'])) ? 'data-auto-len' : '' ?>><?= e($f['duration_label'] ?? '') ?></span>
          <span class="bento__cap">
            <small><?= e($f['category'] ?: 'Film') ?></small>
            <strong><?= e($f['title']) ?></strong>
            <?php if ($meta !== ''): ?><em><?= e($meta) ?></em><?php endif; ?>
          </span>
          <span class="bento__bar" aria-hidden="true"><i></i></span>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
</section>
