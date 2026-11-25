<?php
/** sitemap.php — XML sitemap (served at /sitemap.xml via .htaccess). Lists pages and published albums. */
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=UTF-8');
$urls = [];
foreach (page_schemas() as $slug => $p) {
    $urls[] = [absolute_url($slug === 'home' ? '' : $p['file']), $slug === 'home' ? '1.0' : (in_array($slug, ['privacy', 'terms'], true) ? '0.3' : '0.8'), null];
}
foreach (public_collection('albums') as $a) $urls[] = [absolute_url('gallery.php?album=' . rawurlencode($a['slug'])), '0.7', $a['updated_at'] ?? null];
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n", '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($urls as [$loc, $prio, $mod]) {
    echo '  <url><loc>', htmlspecialchars($loc, ENT_XML1), '</loc>';
    if ($mod && ($t = strtotime($mod))) echo '<lastmod>', date('Y-m-d', $t), '</lastmod>';
    echo '<priority>', $prio, "</priority></url>\n";
}
echo "</urlset>\n";
