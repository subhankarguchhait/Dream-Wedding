<?php defined('APP_NAME') || exit;
/** @var array $film  Large clickable film poster that opens the video modal. */
$emb = film_media($film);
if (!$emb) return;
$thumb = media_url($film['thumbnail'] ?? '') ?: $emb['thumb'];
?>
<button type="button" class="film-frame reveal" data-video="<?= e(video_data($emb, $film['title'])) ?>" aria-label="Play film: <?= e($film['title']) ?>">
  <?php if ($thumb): ?><img src="<?= e($thumb) ?>" alt="" loading="lazy"><?php elseif ($emb['file']): ?><video src="<?= e($emb['file']) ?>#t=0.5" muted playsinline preload="metadata" aria-hidden="true"></video><?php endif; ?>
  <span class="play" aria-hidden="true"><i class="fa-solid fa-play"></i></span>
  <span class="film-frame__caption"><strong><?= e($film['title']) ?></strong><span><?= e(implode(' · ', array_filter([$film['couple_names'] ?? '', $film['location'] ?? '', $film['duration_label'] ?? '']))) ?></span></span>
</button>
