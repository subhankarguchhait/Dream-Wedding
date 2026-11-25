<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin-layout.php';
$admin = require_admin();

$stats = [
    'total' => (int)db_val('SELECT COUNT(*) FROM enquiries'),
    'new' => (int)db_val("SELECT COUNT(*) FROM enquiries WHERE status = 'new'"),
    'confirmed' => (int)db_val("SELECT COUNT(*) FROM enquiries WHERE status IN ('confirmed', 'completed')"),
    'albums' => (int)db_val('SELECT COUNT(*) FROM portfolio_albums'),
    'images' => (int)db_val('SELECT COUNT(*) FROM portfolio_images'),
    'services' => (int)db_val('SELECT COUNT(*) FROM services'),
    'films' => (int)db_val('SELECT COUNT(*) FROM films'),
    'slides' => (int)db_val("SELECT COUNT(*) FROM hero_slides WHERE status = 'published'"),
];
$upcoming = db_all("SELECT * FROM enquiries WHERE event_date >= ? AND status NOT IN ('cancelled') ORDER BY event_date, id LIMIT 6", [date('Y-m-d')]);

// Monthly enquiries — last 12 months (bucketed in PHP so the query is portable)
$start = new DateTimeImmutable('first day of this month 00:00');
$start = $start->modify('-11 months');
$months = [];
for ($i = 0; $i < 12; $i++) { $m = $start->modify("+$i months"); $months[$m->format('Y-m')] = ['label' => $m->format('M'), 'year' => $m->format('Y'), 'n' => 0]; }
foreach (db_all('SELECT created_at FROM enquiries WHERE created_at >= ?', [$start->format('Y-m-d H:i:s')]) as $r) {
    $k = substr($r['created_at'], 0, 7);
    if (isset($months[$k])) $months[$k]['n']++;
}
$max = max(1, ...array_column($months, 'n'));
$recent = db_all('SELECT * FROM enquiries ORDER BY created_at DESC LIMIT 6');
$activity = db_all('SELECT l.*, a.name AS admin_name FROM activity_logs l LEFT JOIN admins a ON a.id = l.admin_id ORDER BY l.created_at DESC LIMIT 8');
$hour = (int)date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

admin_header('Dashboard', 'dashboard');
admin_page_head("$greet, " . explode(' ', $admin['name'])[0], "Here's what's happening on your website.",
    '<a class="btn btn--ghost" href="' . e(url()) . '" target="_blank" rel="noopener"><i class="fa-solid fa-globe"></i>View website</a>'
    . '<a class="btn btn--gold" href="' . e(url('admin/pages.php?page=home')) . '"><i class="fa-solid fa-pen"></i>Edit home page</a>');

$cards = [
    ['fa-solid fa-inbox', 'Total enquiries', $stats['total'], '', 'enquiries.php', 'View all'],
    ['fa-solid fa-bell', 'New enquiries', $stats['new'], 'stat-card--spark', 'enquiries.php?status=new', 'Reply now'],
    ['fa-solid fa-calendar-check', 'Confirmed bookings', $stats['confirmed'], 'stat-card--ok', 'enquiries.php?status=confirmed', 'View'],
    ['fa-solid fa-book-open', 'Portfolio albums', $stats['albums'], 'stat-card--gold', 'portfolio.php', 'Manage'],
    ['fa-solid fa-images', 'Album photos', $stats['images'], '', 'gallery.php', 'Manage'],
    ['fa-solid fa-camera-retro', 'Services', $stats['services'], '', 'services.php', 'Manage'],
    ['fa-solid fa-film', 'Films', $stats['films'], 'stat-card--gold', 'films.php', 'Manage'],
    ['fa-solid fa-clapperboard', 'Active hero slides', $stats['slides'], '', 'hero-slider.php', 'Manage'],
];
?>
<div class="grid grid--4" style="margin-bottom:20px">
  <?php foreach ($cards as [$icon, $label, $n, $tone, $href, $link]): ?>
    <div class="card stat-card <?= $tone ?>">
      <span class="stat-card__icon"><i class="<?= $icon ?>"></i></span>
      <div><small><?= e($label) ?></small><strong><?= number_format($n) ?></strong><a href="<?= e(url('admin/' . $href)) ?>"><?= e($link) ?> →</a></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="grid grid--2-1" style="margin-bottom:20px">
  <div class="card">
    <div class="card__head"><h2><i class="fa-solid fa-chart-column"></i>Monthly enquiries</h2><span class="f__help"><?= array_sum(array_column($months, 'n')) ?> in the last 12 months</span></div>
    <div class="card__body">
      <div class="chart" role="img" aria-label="Bar chart of enquiries per month">
        <?php foreach ($months as $k => $m): ?>
          <div class="chart__col" title="<?= e($m['label'] . ' ' . $m['year'] . ': ' . $m['n']) ?>">
            <div class="chart__bar" style="height:<?= round($m['n'] / $max * 85, 1) ?>%"><?php if ($m['n']): ?><span><?= $m['n'] ?></span><?php endif; ?></div>
            <small><?= e($m['label']) ?></small>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card__head"><h2><i class="fa-solid fa-clock-rotate-left"></i>Recent activity</h2></div>
    <div class="card__body" style="padding-top:6px;padding-bottom:6px">
      <ul class="activity">
        <?php foreach ($activity as $l): ?>
          <li><i class="fa-solid fa-pen-nib"></i><div><span><?= e($l['action']) ?><?php if ($l['description']): ?> · <strong><?= e($l['description']) ?></strong><?php endif; ?></span><small><?= e($l['admin_name'] ?? 'System') ?> · <?= e(format_date($l['created_at'], true)) ?></small></div></li>
        <?php endforeach; ?>
        <?php if (!$activity): ?><li><i class="fa-regular fa-clock"></i><div>No activity yet. Your edits will be logged here.</div></li><?php endif; ?>
      </ul>
    </div>
  </div>
</div>

<div class="grid grid--2-1" style="margin-bottom:20px">
  <div class="card">
    <div class="card__head"><h2><i class="fa-solid fa-envelope-open-text"></i>Recent enquiries</h2><a class="btn btn--ghost btn--sm" href="<?= e(url('admin/enquiries.php')) ?>">Open inbox</a></div>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Name</th><th>Event</th><th>Service</th><th>Status</th><th>Received</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
          <tr class="<?= $r['status'] === 'new' ? 'is-unread' : '' ?>">
            <td><a href="<?= e(url('admin/enquiries.php?view=' . (int)$r['id'])) ?>"><strong><?= e($r['name']) ?></strong></a><div class="muted"><?= e($r['phone']) ?></div></td>
            <td><?= e($r['event_type']) ?><div class="muted"><?= e(format_date($r['event_date'])) ?></div></td>
            <td><?= e($r['service'] ?: '—') ?></td>
            <td><span class="badge badge--<?= e($r['status']) ?>"><?= e(ENQUIRY_STATUSES[$r['status']] ?? $r['status']) ?></span></td>
            <td class="muted" title="<?= e(format_date($r['created_at'], true)) ?>"><?= e(time_ago($r['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="5"><div class="empty"><i class="fa-regular fa-envelope"></i><h3>No enquiries yet</h3>Booking requests will appear here.</div></td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
  <div class="card">
    <div class="card__head"><h2><i class="fa-regular fa-calendar"></i>Upcoming event dates</h2></div>
    <div class="card__body" style="padding-top:4px;padding-bottom:4px">
      <ul class="upcoming">
        <?php foreach ($upcoming as $u): $t = strtotime($u['event_date']); ?>
          <li><span class="date-chip"><b><?= date('j', $t) ?></b><small><?= date('M y', $t) ?></small></span>
            <div style="min-width:0"><a href="<?= e(url('admin/enquiries.php?view=' . (int)$u['id'])) ?>"><strong><?= e($u['name']) ?></strong></a><div class="muted clamp-1"><?= e($u['event_type'] . ' · ' . $u['event_location']) ?></div></div>
            <span class="badge badge--<?= e($u['status']) ?>" style="margin-left:auto"><?= e(ENQUIRY_STATUSES[$u['status']] ?? $u['status']) ?></span></li>
        <?php endforeach; ?>
        <?php if (!$upcoming): ?><li class="muted">No upcoming event dates yet.</li><?php endif; ?>
      </ul>
    </div>
  </div>
</div>

<div class="grid grid--4">
  <?php foreach ([['hero-slider.php', 'fa-solid fa-clapperboard', 'Hero slider', 'Videos, headings, timing'], ['albums.php', 'fa-regular fa-images', 'Upload photos', 'Add photos to an album'], ['pages.php?page=home', 'fa-solid fa-house', 'Home page', 'Sections & order'], ['settings.php#booking', 'fa-solid fa-sliders', 'Booking options', 'Event types, budgets']] as [$h, $i, $t, $d]): ?>
    <a class="card stat-card" href="<?= e(url('admin/' . $h)) ?>"><span class="stat-card__icon"><i class="<?= $i ?>"></i></span><div><strong style="font-size:22px"><?= e($t) ?></strong><small><?= e($d) ?></small></div></a>
  <?php endforeach; ?>
</div>
<?php admin_footer();
