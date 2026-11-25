<?php
/**
 * index.php — every visible section and its order come from MySQL (pages + page_sections).
 * Edit the content in Admin → Pages & Sections.
 */
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$page = get_page('home');
$first = public_collection('slides')[0]['poster_image'] ?? '';
if ($first !== '') $page['preload'] = media_url($first);
if (!empty($_GET['preview_slide'])) header('X-Robots-Tag: noindex');
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
render_sections($page);
require __DIR__ . '/includes/footer.php';
