<?php
/**
 * terms.php — every visible section and its order come from MySQL (pages + page_sections).
 * Edit the content in Admin → Pages & Sections.
 */
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$page = get_page('terms');
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
render_sections($page);
require __DIR__ . '/includes/footer.php';
