<?php defined('APP_NAME') || exit;
/** @var array $s */
$u = sv($s, 'embed_url');
if (!preg_match('#^https://(www\.)?google\.[a-z.]+/maps(/embed|\?)#i', $u)) return; ?>
<section class="map" data-section="map">
  <iframe src="<?= e($u) ?>" title="Map showing our location" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
</section>
