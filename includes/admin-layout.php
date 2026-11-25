<?php
/**
 * Admin page chrome: <head>, sidebar, top bar (notifications + profile), flash toasts, scripts.
 */
declare(strict_types=1);
defined('APP_NAME') || exit; // no direct access
require_once __DIR__ . '/form.php';

function admin_nav(): array
{
    return [
        'dashboard' => ['dashboard.php', 'fa-solid fa-gauge-high', 'Dashboard', 'editor'],
        'pages' => ['pages.php', 'fa-solid fa-file-pen', 'Pages & Sections', 'editor'],
        'slides' => ['hero-slider.php', 'fa-solid fa-clapperboard', 'Hero Slider', 'editor'],
        'services' => ['services.php', 'fa-solid fa-camera-retro', 'Services', 'editor'],
        'albums' => ['portfolio.php', 'fa-solid fa-book-open', 'Portfolio Albums', 'editor'],
        'gallery' => ['gallery.php', 'fa-solid fa-images', 'Album Photos', 'editor'],
        'films' => ['films.php', 'fa-solid fa-film', 'Films', 'editor'],
        'reels' => ['reels.php', 'fa-solid fa-mobile-screen-button', 'Reels', 'editor'],
        'testimonials' => ['testimonials.php', 'fa-solid fa-quote-left', 'Testimonials', 'editor'],
        'team' => ['team.php', 'fa-solid fa-user-group', 'Team', 'editor'],
        'enquiries' => ['enquiries.php', 'fa-solid fa-inbox', 'Enquiries', 'editor'],
        'media' => ['media.php', 'fa-solid fa-photo-film', 'Media Library', 'editor'],
        'settings' => ['settings.php', 'fa-solid fa-sliders', 'Settings', 'admin'],
        'profile' => ['profile.php', 'fa-solid fa-user-gear', 'My Profile', 'editor'],
    ];
}

function admin_header(string $title, string $active): void
{
    $a = current_admin();
    $newCount = (int)db_val("SELECT COUNT(*) FROM enquiries WHERE status = 'new'");
    $latest = db_all("SELECT id, name, service, message, created_at FROM enquiries WHERE status = 'new' ORDER BY created_at DESC LIMIT 5");
    $flashes = take_flashes();
    ?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <meta name="base-url" content="<?= e(url()) ?>">
  <title><?= e($title) ?> · <?= e(setting('site_name')) ?> CMS</title>
  <link rel="icon" href="<?= e(media_url(setting('favicon')) ?: url('assets/images/brand/favicon-192.png')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="<?= e(asset('admin/css/admin.css')) ?>">
</head>
<body class="admin" data-admin-page="<?= e($active) ?>" data-flash="<?= e(json_encode($flashes, JSON_UNESCAPED_UNICODE)) ?>">
  <aside class="sidebar" id="sidebar" aria-label="Sidebar">
    <a class="sidebar__brand" href="<?= e(url('admin/dashboard.php')) ?>">
      <img src="<?= e(url('assets/images/brand/dreamm-weddings-mark.webp')) ?>" alt="" width="36" height="40" style="height:40px;width:auto">
      <div><strong><?= site_name_html() ?></strong><small>Content Studio</small></div>
    </a>
    <p class="sidebar__label">Manage</p>
    <nav aria-label="Admin navigation">
      <?php foreach (admin_nav() as $key => [$file, $icon, $label, $role]):
        if ($role === 'admin' && $a['role'] !== 'admin') continue; ?>
        <a href="<?= e(url('admin/' . $file)) ?>" class="side-link<?= $key === $active ? ' is-active' : '' ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>>
          <i class="<?= $icon ?>" aria-hidden="true"></i><span><?= e($label) ?></span>
          <?php if ($key === 'enquiries'): ?><span class="count" data-new-count<?= $newCount ? '' : ' hidden' ?>><?= $newCount ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar__foot">
      <a class="side-link" href="<?= e(url()) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i><span>View website</span></a>
      <form method="post" action="<?= e(url('admin/logout.php')) ?>" data-confirm="Log out of the CMS?">
        <?= csrf_field() ?>
        <button class="side-link" type="submit"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span>Log out</span></button>
      </form>
    </div>
  </aside>
  <div class="sidebar-backdrop" data-sidebar-close></div>

  <div class="admin-main">
    <header class="topbar">
      <button class="topbar__menu" type="button" aria-label="Open menu" data-sidebar-toggle><i class="fa-solid fa-bars"></i></button>
      <h1 class="topbar__title"><?= e($title) ?></h1>
      <div class="topbar__actions">
        <div class="dropdown">
          <button class="icon-btn" type="button" aria-label="Notifications" aria-haspopup="menu" aria-expanded="false" data-dropdown>
            <i class="fa-regular fa-bell"></i><span class="dot" data-new-count<?= $newCount ? '' : ' hidden' ?>><?= $newCount ?></span>
          </button>
          <div class="dropdown__menu" role="menu">
            <div class="dropdown__head"><strong>New enquiries</strong><small>Checks for new messages every minute</small></div>
            <div class="notif-list" data-notif-list>
              <?php if (!$latest): ?><p class="notif-empty">You're all caught up ✨</p><?php endif; ?>
              <?php foreach ($latest as $n): ?>
                <a class="notif" href="<?= e(url('admin/enquiries.php?view=' . (int)$n['id'])) ?>"><strong><?= e($n['name']) ?> · <?= e($n['service'] ?: 'General') ?></strong><small><?= e(time_ago($n['created_at'])) ?> — <?= e(mb_substr($n['message'], 0, 80)) ?></small></a>
              <?php endforeach; ?>
            </div>
            <a class="dropdown__item" href="<?= e(url('admin/enquiries.php')) ?>"><i class="fa-solid fa-inbox"></i>Open all enquiries</a>
          </div>
        </div>
        <div class="dropdown">
          <button class="profile-btn" type="button" aria-haspopup="menu" aria-expanded="false" data-dropdown>
            <span class="avatar"><?= e(initials($a['name'])) ?></span><span><?= e($a['name']) ?></span><i class="fa-solid fa-chevron-down" style="font-size:10px;color:var(--muted)"></i>
          </button>
          <div class="dropdown__menu" role="menu">
            <div class="dropdown__head"><strong><?= e($a['name']) ?></strong><small><?= e($a['email']) ?> · <?= e(ROLES[$a['role']] ?? $a['role']) ?></small></div>
            <a class="dropdown__item" href="<?= e(url('admin/profile.php')) ?>"><i class="fa-solid fa-user-gear"></i>My profile & password</a>
            <?php if ($a['role'] === 'admin'): ?><a class="dropdown__item" href="<?= e(url('admin/settings.php')) ?>"><i class="fa-solid fa-sliders"></i>Website settings</a><?php endif; ?>
            <a class="dropdown__item" href="<?= e(url()) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-globe"></i>View website</a>
            <form method="post" action="<?= e(url('admin/logout.php')) ?>"><?= csrf_field() ?><button class="dropdown__item" type="submit"><i class="fa-solid fa-right-from-bracket"></i>Log out</button></form>
          </div>
        </div>
      </div>
    </header>
    <main class="workspace" id="workspace">
<?php
}

function admin_footer(): void
{
    ?>
    </main>
  </div>
  <div class="toasts" id="toasts" aria-live="polite"></div>
  <datalist id="link-suggestions"><option value="index.php"><option value="about.php"><option value="services.php"><option value="portfolio.php"><option value="films.php"><option value="contact.php"><option value="#whatsapp"><option value="#email"><option value="#phone"></datalist>

  <script src="<?= e(asset('admin/js/media.js')) ?>" defer></script>
  <script src="<?= e(asset('admin/js/admin.js')) ?>" defer></script>
</body>
</html>
<?php
}

/** Page heading row used at the top of each admin page. */
function admin_page_head(string $title, string $subtitle = '', string $actionsHtml = ''): void
{
    echo '<div class="page-head"><div><h1>' . e($title) . '</h1>' . ($subtitle ? '<p>' . e($subtitle) . '</p>' : '') . '</div>'
        . ($actionsHtml ? '<div class="page-head__actions">' . $actionsHtml . '</div>' : '') . '</div>';
}
