<?php
/**
 * gallery.php?album=slug — one wedding story: details, story text, masonry gallery with lightbox,
 * and links to the previous / next album. Albums and photos are managed in Admin → Portfolio.
 */
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$slug = get_str('album', 190);
$album = preg_match('/^[a-z0-9-]+$/', $slug) ? find_album($slug) : null;
if (!$album) {
    http_response_code(404);
    $page = ['slug' => 'portfolio', 'nav' => 'portfolio', 'meta' => ['title' => 'Album not found', 'description' => '', 'name' => 'Portfolio']];
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/navbar.php'; ?>
    <section class="section album-head" style="padding-top:200px;min-height:70vh">
      <div class="container">
        <p class="eyebrow" style="justify-content:center"><span class="eyebrow__line"></span><span>404</span></p>
        <h1 class="sec-title">This story could not be <em>found</em></h1>
        <p class="album-story">The album may have been moved or is not published yet.</p>
        <p style="margin-top:30px"><a class="btn btn--spark" href="<?= e(url('portfolio.php')) ?>"><span>Browse all stories</span><i class="fa-solid fa-arrow-right"></i></a></p>
      </div>
    </section>
    <?php require __DIR__ . '/includes/footer.php';
    exit;
}

$images = album_images($album);
$all = public_collection('albums');
$idx = array_search($album['slug'], array_column($all, 'slug'), true);
$prev = $idx !== false && $idx > 0 ? $all[$idx - 1] : null;
$next = $idx !== false && $idx < count($all) - 1 ? $all[$idx + 1] : null;
$name = $album['couple_names'] ?: $album['title'];
$desc = trim(mb_substr(preg_replace('/\s+/', ' ', strip_tags((string)$album['description'])), 0, 160));

$page = [
    'slug' => 'portfolio', 'nav' => 'portfolio',
    'meta' => ['title' => $name . ' — ' . $album['category'], 'description' => $desc ?: "{$album['title']} — a wedding story by " . setting('site_name'), 'name' => $album['title']],
    'canonical' => absolute_url('gallery.php?album=' . rawurlencode($album['slug'])),
    'og_image' => absolute_url(media_url($album['cover_image'])),
];
$hero = ['image' => $album['cover_image'], 'eyebrow' => $album['category'], 'title' => $name, 'content' => $album['title'] !== $name ? $album['title'] : ''];
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
render_section('page-hero', $hero, 'hero', $page + ['meta' => ['name' => 'Portfolio'] + $page['meta']]);
?>
<section class="album-head">
  <div class="container">
    <div class="album-meta reveal">
      <span><i class="fa-regular fa-folder-open"></i><?= e($album['category']) ?></span>
      <?php if (!empty($album['event_date'])): ?><span><i class="fa-regular fa-calendar"></i><?= e(nice_date($album['event_date'])) ?></span><?php endif; ?>
      <?php if (!empty($album['location'])): ?><span><i class="fa-solid fa-location-dot"></i><?= e($album['location']) ?></span><?php endif; ?>
      <span><i class="fa-regular fa-images"></i><?= count($images) ?> photographs</span>
    </div>
    <?php if (!empty($album['description'])): ?><div class="album-story prose reveal"><?= paras($album['description']) ?></div><?php endif; ?>
  </div>
</section>

<section class="section" style="padding-top:20px">
  <div class="container">
    <?php if ($images): ?>
      <div class="masonry" data-lightbox-group="album">
        <?php foreach ($images as $i => $img): $alt = $img['alt_text'] ?: "$name — photo " . ($i + 1); ?>
          <button type="button" class="masonry__item" data-lightbox="<?= e(media_url($img['file_path'])) ?>" data-caption="<?= e($img['caption'] ?: '') ?>" aria-label="View photo <?= $i + 1 ?> of <?= count($images) ?>">
            <?= photo_tag($img['thumb_path'] ?: $img['file_path'], $alt, (int)$img['width'] ?: null, (int)$img['height'] ?: null) ?>
            <?php if (!empty($img['caption'])): ?><figcaption><?= e($img['caption']) ?></figcaption><?php endif; ?>
          </button>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="empty-state"><i class="fa-regular fa-images"></i>Photos for this story are coming soon.</p>
    <?php endif; ?>
    <div class="center-cta reveal">
      <a class="btn btn--spark magnetic" href="<?= e(url('contact.php')) ?>"><span>Check your date</span><i class="fa-solid fa-arrow-right"></i></a>
      <a class="btn btn--outline" href="<?= e(url('portfolio.php')) ?>"><span>All stories</span></a>
    </div>
  </div>
  <?php if ($prev || $next): ?>
    <nav class="album-pager" aria-label="More stories">
      <?php if ($prev): ?><a href="<?= e(album_url($prev['slug'])) ?>" rel="prev"><small><i class="fa-solid fa-arrow-left"></i> Previous story</small><strong><?= e($prev['couple_names'] ?: $prev['title']) ?></strong></a><?php else: ?><span></span><?php endif; ?>
      <?php if ($next): ?><a href="<?= e(album_url($next['slug'])) ?>" rel="next"><small>Next story <i class="fa-solid fa-arrow-right"></i></small><strong><?= e($next['couple_names'] ?: $next['title']) ?></strong></a><?php endif; ?>
    </nav>
  <?php endif; ?>
</section>
<?php
render_section('cta', get_page('portfolio')['sections']['cta'] ?? [], 'cta', $page);
require __DIR__ . '/includes/footer.php';
