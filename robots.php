<?php
/** robots.php — served at /robots.txt via .htaccess. Keeps the admin area out of search engines. */
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: text/plain; charset=UTF-8');
echo "User-agent: *\nDisallow: /admin/\nDisallow: /actions/\nDisallow: /*?preview_slide=\n\nSitemap: ", absolute_url('sitemap.xml'), "\n";
