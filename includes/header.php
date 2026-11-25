<?php
/** @var array $page  from get_page() */
defined('APP_NAME') || exit; // no direct access
$siteTitle = setting('seo_title', APP_NAME);
$metaTitle = trim((string)($page['meta']['title'] ?? ''));
$docTitle = $metaTitle !== '' ? $metaTitle . ' | ' . $siteTitle : $siteTitle;
$metaDesc = trim((string)($page['meta']['description'] ?? '')) ?: setting('seo_description');
$favicon = media_url(setting('favicon')) ?: url('assets/images/favicon.svg');
$ogImage = media_url(setting('og_image'));
$canonical = $page['canonical'] ?? url($page['slug'] === 'home' ? '' : (page_schemas()[$page['slug']]['file'] ?? ''));
$ogImage = $page['og_image'] ?? $ogImage;
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($docTitle) ?></title>
  <meta name="description" content="<?= e($metaDesc) ?>">
  <meta name="keywords" content="<?= e(setting('seo_keywords')) ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($docTitle) ?>">
  <meta property="og:description" content="<?= e($metaDesc) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <?php if ($ogImage): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
  <meta name="twitter:card" content="summary_large_image">
  <meta name="theme-color" content="#111111">
  <script>document.documentElement.classList.add('js');</script>
  <link rel="icon" href="<?= e($favicon) ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= e(url('assets/images/brand/favicon-32.png')) ?>">
  <link rel="apple-touch-icon" href="<?= e(url('assets/images/brand/apple-touch-icon.png')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400;1,500&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-grid.min.css">
  <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/css/animations.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/css/responsive.css')) ?>">
  <?php if (!empty($page['preload'])): ?><link rel="preload" as="image" href="<?= e($page['preload']) ?>"><?php endif; ?>
  <script type="application/ld+json"><?= json_encode([
      '@context' => 'https://schema.org', '@type' => 'LocalBusiness', 'name' => setting('site_name'), 'image' => $ogImage ?: url('assets/images/hero/hero-1.jpg'),
      'url' => url(), 'email' => setting('email'), 'telephone' => setting('phone'), 'address' => setting('address'),
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
</head>
