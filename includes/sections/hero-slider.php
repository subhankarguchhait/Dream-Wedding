<?php defined('APP_NAME') || exit;
/** @var array $s  Hero video slider — slides come from the hero_slides table (Admin → Hero Slider). */
$preview = false;
$slides = public_collection('slides');
$previewId = (int)($_GET['preview_slide'] ?? 0);
if ($previewId) {
    try {
        if (current_admin() && ($row = db_one('SELECT * FROM hero_slides WHERE id = ?', [$previewId]))) { $slides = [$row]; $preview = true; }
    } catch (Throwable $e) { /* not signed in or DB unavailable: show normal slides */ }
}
if (!$slides) return;
$total = count($slides);
?>
<section class="hs" data-hero-slider aria-roledescription="carousel" aria-label="Wedding film showreel">
  <?php if ($preview): ?><div class="hs__preview">Preview — only you can see this slide<?= ($row['status'] ?? '') !== 'published' ? ' (inactive)' : '' ?></div><?php endif; ?>
  <?php foreach ($slides as $i => $sl):
    $dur = max(3, min(60, (int)($sl['duration'] ?? 8)));
    $ov = max(0, min(90, (int)($sl['overlay_opacity'] ?? 45))) / 100;
    $align = in_array($sl['text_alignment'] ?? 'left', ['left', 'center', 'right'], true) ? $sl['text_alignment'] : 'left';
    $poster = media_url($sl['poster_image'] ?? '');
    $mp4 = video_src($sl['video_path'] ?? '') ?: (preg_match('#\.mp4(\?|$)#i', (string)($sl['video_url'] ?? '')) ? video_src($sl['video_url']) : '');
    $webm = video_src($sl['video_webm_path'] ?? '') ?: (preg_match('#\.webm(\?|$)#i', (string)($sl['video_url'] ?? '')) ? video_src($sl['video_url']) : '');
    $mobile = video_src($sl['mobile_video_path'] ?? '');
    $tag = $i === 0 ? 'h1' : 'h2';
  ?>
    <article class="hs__slide<?= $i === 0 ? ' is-active' : '' ?>" data-dur="<?= $dur ?>" data-align="<?= $align ?>" style="--ov:<?= $ov ?>;--dur:<?= $dur ?>s" aria-roledescription="slide" aria-label="<?= $i + 1 ?> of <?= $total ?>"<?= $i === 0 ? '' : ' aria-hidden="true"' ?>>
      <div class="hs__media">
        <?php if ($poster): ?><img src="<?= e($poster) ?>" alt="" <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?> decoding="async"><?php endif; ?>
        <?php if ($mp4 || $webm || $mobile): ?>
          <video muted playsinline preload="none" disablepictureinpicture aria-hidden="true" tabindex="-1"<?= $poster ? ' poster="' . e($poster) . '"' : '' ?>
            data-mp4="<?= e($mp4) ?>" data-webm="<?= e($webm) ?>" data-mobile="<?= e($mobile) ?>"></video>
        <?php endif; ?>
      </div>
      <div class="hs__overlay"></div>
      <div class="hs__content"><div class="container">
        <?php if (!empty($sl['eyebrow'])): ?><p class="hs__eyebrow"><?= e($sl['eyebrow']) ?></p><?php endif; ?>
        <<?= $tag ?> class="hs__heading" data-split><?= rich($sl['heading'] ?? '') ?></<?= $tag ?>>
        <?php if (!empty($sl['subtitle'])): ?><p class="hs__sub"><?= e($sl['subtitle']) ?></p><?php endif; ?>
        <div class="hs__actions">
          <?php cms_button((string)($sl['primary_button_text'] ?? ''), (string)($sl['primary_button_url'] ?? ''), 'btn btn--spark btn--lg'); ?>
          <?php cms_button((string)($sl['secondary_button_text'] ?? ''), (string)($sl['secondary_button_url'] ?? ''), 'btn btn--ghost-light btn--lg', false); ?>
        </div>
      </div></div>
    </article>
  <?php endforeach; ?>

  <div class="hs__controls"<?= $total < 2 ? ' hidden' : '' ?>>
    <div class="container">
      <div class="hs__count" aria-live="polite"><b data-hs-current>01</b> / <?= str_pad((string)$total, 2, '0', STR_PAD_LEFT) ?></div>
      <div class="hs__dots" role="tablist" aria-label="Choose slide">
        <?php foreach ($slides as $i => $sl): ?><button type="button" role="tab" style="--dur:<?= max(3, min(60, (int)($sl['duration'] ?? 8))) ?>s" aria-label="Slide <?= $i + 1 ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"<?= $i === 0 ? ' class="is-active"' : '' ?> data-hs-go="<?= $i ?>"><i></i></button><?php endforeach; ?>
      </div>
      <div class="hs__nav">
        <button type="button" class="hs__btn" data-hs-toggle aria-label="Pause slideshow"><i class="fa-solid fa-pause"></i></button>
        <button type="button" class="hs__btn" data-hs-prev aria-label="Previous slide"><i class="fa-solid fa-arrow-left"></i></button>
        <button type="button" class="hs__btn" data-hs-next aria-label="Next slide"><i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </div>
  </div>
  <?php if (sv($s, 'scroll_text') !== ''): ?><a class="hs__scroll" href="#after-hero"><?= e(sv($s, 'scroll_text')) ?></a><?php endif; ?>
</section>
<div id="after-hero"></div>
