<?php
/** @var array $page */
declare(strict_types=1);
defined('APP_NAME') || exit; // no direct access
$navItems = [
    'home' => ['', 'Home'], 'about' => ['about.php', 'About'], 'services' => ['services.php', 'Services'],
    'portfolio' => ['portfolio.php', 'Portfolio'], 'films' => ['films.php', 'Films'], 'contact' => ['contact.php', 'Contact'],
];
$activeNav = $page['nav'] ?? $page['slug'];
$logo = media_url(setting('logo'));
$mono = initials(setting('site_name', APP_NAME));
$brandHtml = $logo !== ''
    ? '<img class="brand__img" src="' . e($logo) . '" alt="' . e(setting('site_name')) . '" width="170" height="50" fetchpriority="high" decoding="async">'
    : '<span class="brand__mono" aria-hidden="true"><span>' . e($mono) . '</span></span><span class="brand__text">' . e(setting('site_name', APP_NAME)) . '<small>' . e(setting('tagline')) . '</small></span>';
?>
<body data-page="<?= e($page['slug']) ?>">
  <div class="preloader" id="preloader" aria-hidden="true">
    <div class="preloader__inner">
      <?php if ($stacked = media_url(setting('logo_stacked') ?: setting('logo'))): ?>
        <img class="preloader__logo" src="<?= e($stacked) ?>" alt="" width="200" height="192" fetchpriority="high">
      <?php else: ?>
        <div class="preloader__mono"><span><?= e($mono) ?></span></div>
        <div class="preloader__word"><?= e(setting('site_name', APP_NAME)) ?><small><?= e(setting('tagline')) ?></small></div>
      <?php endif; ?>
      <div class="preloader__bar"><span></span></div>
    </div>
  </div>
  <div class="cursor" aria-hidden="true"><span class="cursor__dot"></span><span class="cursor__ring"></span></div>
  <div class="scroll-progress" aria-hidden="true"></div>
  <a class="skip-link" href="#main">Skip to content</a>

  <header class="nav" id="nav">
    <div class="container nav__inner">
      <a href="<?= e(url()) ?>" class="brand" aria-label="<?= e(setting('site_name')) ?> — home"><?= $brandHtml ?></a>
      <nav class="nav__links" aria-label="Main navigation">
        <?php foreach ($navItems as $key => [$file, $label]): ?>
          <a href="<?= e(url($file)) ?>"<?= $key === $activeNav ? ' class="is-active" aria-current="page"' : '' ?>><span><?= e($label) ?></span></a>
        <?php endforeach; ?>
      </nav>
      <?php cms_button(setting('nav_cta_text'), setting('nav_cta_link', 'contact.php'), 'btn btn--gold btn--sm nav__cta', false); ?>
      <button class="nav__toggle" id="navToggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="mobileMenu"><span></span><span></span></button>
    </div>
  </header>

  <div class="mobile-menu" id="mobileMenu" aria-hidden="true">
    <nav class="mobile-menu__links" aria-label="Mobile navigation">
      <?php $i = 0; foreach ($navItems as $key => [$file, $label]): ?>
        <a href="<?= e(url($file)) ?>" style="--i:<?= $i ?>"<?= $key === $activeNav ? ' class="is-active"' : '' ?>><small>0<?= ++$i ?></small><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="mobile-menu__foot">
      <?php if (setting('phone')): ?><a href="<?= e(contact_href('phone', setting('phone'))) ?>"><?= e(setting('phone')) ?></a><?php endif; ?>
      <?php if (setting('email')): ?><a href="<?= e(contact_href('email', setting('email'))) ?>"><?= e(setting('email')) ?></a><?php endif; ?>
      <?php social_links(site_socials()); ?>
    </div>
  </div>

  <main id="main">
