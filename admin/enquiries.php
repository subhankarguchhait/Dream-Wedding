<?php
/** Booking enquiries — list with search & filters, status changes, CSV export; ?view=ID shows one enquiry with internal notes. */
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin-layout.php';
require __DIR__ . '/../includes/admin-collection.php';
require __DIR__ . '/../includes/enquiry-filters.php';
$me = require_admin();

$statusForm = function (array $r, bool $compact = true): void { ?>
  <form method="post" action="<?= e(url('admin/actions/enquiry-update.php')) ?>" data-ajax data-autosubmit>
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
    <select name="status" aria-label="Status for <?= e($r['name']) ?>" class="status-select status-select--<?= e($r['status']) ?>"<?= $compact ? '' : ' style="width:100%"' ?>><?php foreach (ENQUIRY_STATUSES as $k => $l): ?><option value="<?= $k ?>"<?= $r['status'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
  </form>
<?php };

/* ------------------------------------------------------------------ single enquiry */
$viewId = (int)($_GET['view'] ?? 0);
if ($viewId) {
    $r = db_one('SELECT * FROM enquiries WHERE id = ?', [$viewId]);
    if (!$r) { flash('error', 'Enquiry not found.'); redirect('admin/enquiries.php'); }
    $notes = db_all('SELECT n.*, a.name AS admin_name FROM enquiry_notes n LEFT JOIN admins a ON a.id = n.admin_id WHERE n.enquiry_id = ? ORDER BY n.created_at DESC, n.id DESC', [$viewId]);
    $days = $r['event_date'] ? (int)floor((strtotime($r['event_date']) - strtotime(date('Y-m-d'))) / 86400) : null;
    admin_header('Enquiries', 'enquiries');
    admin_page_head($r['name'] . ($r['partner_name'] ? ' & ' . $r['partner_name'] : ''), $r['enquiry_id'] . ' · received ' . format_date($r['created_at'], true),
        '<a class="btn btn--ghost" href="' . e(url('admin/enquiries.php')) . '"><i class="fa-solid fa-arrow-left"></i>All enquiries</a>'
        . '<a class="btn btn--forest" href="' . e(contact_href('whatsapp', $r['phone'])) . '" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i>WhatsApp</a>'
        . '<a class="btn btn--gold" href="mailto:' . e($r['email']) . '?subject=' . rawurlencode('Your ' . $r['event_type'] . ' enquiry — ' . $r['enquiry_id']) . '"><i class="fa-solid fa-reply"></i>Reply by email</a>');
    ?>
    <div class="enq-grid">
      <div class="grid" style="gap:20px">
        <div class="card">
          <div class="card__head"><h2><i class="fa-regular fa-calendar-check"></i>Event details</h2><span class="badge badge--<?= e($r['status']) ?>"><?= e(ENQUIRY_STATUSES[$r['status']] ?? $r['status']) ?></span></div>
          <div class="card__body enq-facts">
            <div><small>Event type</small><strong><?= e($r['event_type']) ?></strong></div>
            <div><small>Event date</small><strong><?= e(format_date($r['event_date'])) ?></strong><?php if ($days !== null): ?> <span class="muted">(<?= $days >= 0 ? "in $days days" : abs($days) . ' days ago' ?>)</span><?php endif; ?></div>
            <div><small>Location</small><strong><?= e($r['event_location']) ?></strong></div>
            <div><small>Number of days</small><strong><?= (int)$r['event_days'] ?></strong></div>
            <div><small>Interested service</small><strong><?= e($r['service']) ?></strong></div>
            <div><small>Estimated budget</small><strong><?= e($r['budget']) ?></strong></div>
            <div><small>Email</small><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a></div>
            <div><small>Phone</small><a href="<?= e(contact_href('phone', $r['phone'])) ?>"><?= e($r['phone']) ?></a></div>
          </div>
        </div>
        <div class="card"><div class="card__head"><h2><i class="fa-regular fa-message"></i>Message</h2></div><div class="card__body"><div class="enq-detail__msg"><?= nl2br(e($r['message']), false) ?></div></div></div>
      </div>
      <div class="grid" style="gap:20px">
        <div class="card"><div class="card__head"><h2><i class="fa-solid fa-signal"></i>Status</h2></div><div class="card__body"><?php $statusForm($r, false); ?><p class="f__help" style="margin-top:8px">Last updated <?= e(format_date($r['updated_at'], true)) ?></p></div></div>
        <div class="card">
          <div class="card__head"><h2><i class="fa-solid fa-note-sticky"></i>Internal notes</h2><span class="f__help">Only admins see these</span></div>
          <div class="card__body grid" style="gap:14px">
            <form method="post" action="<?= e(url('admin/actions/enquiry-note.php')) ?>" data-ajax class="grid" style="gap:8px">
              <?= csrf_field() ?><input type="hidden" name="op" value="add"><input type="hidden" name="enquiry_id" value="<?= (int)$r['id'] ?>">
              <div class="f"><label for="note">Add a note</label><textarea id="note" name="note" rows="3" maxlength="2000" required placeholder="e.g. Called on Monday, sending quotation for 2-day coverage"></textarea><small class="f__err" data-err-for="note"></small></div>
              <div><button class="btn btn--forest btn--sm" type="submit"><i class="fa-solid fa-plus"></i>Add note</button></div>
            </form>
            <div class="notes">
              <?php foreach ($notes as $n): ?>
                <div class="note">
                  <small><span><?= e($n['admin_name'] ?? 'Admin') ?> · <?= e(format_date($n['created_at'], true)) ?></span>
                    <form method="post" action="<?= e(url('admin/actions/enquiry-note.php')) ?>" data-ajax data-confirm="Delete this note?" data-danger>
                      <?= csrf_field() ?><input type="hidden" name="op" value="delete"><input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                      <button type="submit" class="btn btn--ghost btn--icon btn--sm" aria-label="Delete note"><i class="fa-solid fa-xmark"></i></button>
                    </form></small>
                  <p><?= e($n['note']) ?></p>
                </div>
              <?php endforeach; ?>
              <?php if (!$notes): ?><p class="muted">No notes yet.</p><?php endif; ?>
            </div>
          </div>
        </div>
        <form class="card" method="post" action="<?= e(url('admin/actions/enquiry-delete.php')) ?>" data-ajax data-confirm="Delete this enquiry and its notes? This cannot be undone — export to CSV first if you need a record." data-danger>
          <div class="card__body" style="display:flex;justify-content:space-between;align-items:center;gap:10px">
            <span class="muted">IP <?= e($r['ip_address'] ?: '—') ?></span>
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="back" value="1">
            <button class="btn btn--danger btn--sm" type="submit"><i class="fa-solid fa-trash-can"></i>Delete enquiry</button>
          </div>
        </form>
      </div>
    </div>
    <?php
    admin_footer();
    exit;
}

/* ------------------------------------------------------------------ list */
[$whereSql, $params, $f] = enquiry_filters();
$total = (int)db_val("SELECT COUNT(*) FROM enquiries WHERE $whereSql", $params);
$pg = paginate($total, 20, (int)($_GET['p'] ?? 1));
$rows = db_all("SELECT e.*, (SELECT COUNT(*) FROM enquiry_notes n WHERE n.enquiry_id = e.id) AS notes FROM enquiries e WHERE $whereSql ORDER BY e.created_at DESC, e.id DESC LIMIT ? OFFSET ?", [...$params, $pg['per'], $pg['offset']]);
$eventTypes = array_values(array_unique(array_merge(option_list('event_types'), array_column(db_all("SELECT DISTINCT event_type FROM enquiries WHERE event_type <> ''"), 'event_type'))));
$services = array_values(array_unique(array_merge(option_list('service_options'), array_column(db_all("SELECT DISTINCT service FROM enquiries WHERE service <> ''"), 'service'))));
$counts = [];
foreach (db_all('SELECT status, COUNT(*) AS n FROM enquiries GROUP BY status') as $c) $counts[$c['status']] = (int)$c['n'];
$exportUrl = url('admin/actions/enquiry-export.php') . qs(['p' => null]);

admin_header('Enquiries', 'enquiries');
admin_page_head('Booking enquiries', 'Every “Check your date” request. Open one to see full details, add internal notes and update its status.',
    '<a class="btn btn--gold" href="' . e($exportUrl) . '"><i class="fa-solid fa-file-csv"></i>Export CSV</a>');
?>
<div class="chips-filter" style="margin-bottom:16px">
  <a class="<?= $f['status'] === '' ? 'is-active' : '' ?>" href="<?= e(url('admin/enquiries.php')) ?>">All (<?= array_sum($counts) ?>)</a>
  <?php foreach (ENQUIRY_STATUSES as $k => $l): ?><a class="<?= $f['status'] === $k ? 'is-active' : '' ?>" href="<?= e(url('admin/enquiries.php?status=' . $k)) ?>"><?= e($l) ?> (<?= $counts[$k] ?? 0 ?>)</a><?php endforeach; ?>
</div>
<div class="card">
  <form class="toolbar" method="get">
    <div class="search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Search name, phone, email, location or reference…" aria-label="Search enquiries"></div>
    <select name="status" aria-label="Status"><option value="">All statuses</option><?php foreach (ENQUIRY_STATUSES as $k => $l): ?><option value="<?= $k ?>"<?= $f['status'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
    <select name="event_type" aria-label="Event type"><option value="">All event types</option><?php foreach ($eventTypes as $o): ?><option<?= $f['event_type'] === $o ? ' selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select>
    <select name="service" aria-label="Service"><option value="">All services</option><?php foreach ($services as $o): ?><option<?= $f['service'] === $o ? ' selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select>
    <label class="muted" style="display:flex;gap:6px;align-items:center;font-size:12px">Event date <input type="date" name="from" value="<?= e($f['from']) ?>" aria-label="Event date from"> – <input type="date" name="to" value="<?= e($f['to']) ?>" aria-label="Event date to"></label>
    <button class="btn btn--forest btn--sm" type="submit"><i class="fa-solid fa-filter"></i>Filter</button>
    <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/enquiries.php')) ?>">Reset</a>
  </form>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Couple / client</th><th>Event</th><th>Service &amp; budget</th><th>Status</th><th>Received</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): $view = url('admin/enquiries.php?view=' . (int)$r['id']); ?>
          <tr class="<?= $r['status'] === 'new' ? 'is-unread' : '' ?>">
            <td><a href="<?= e($view) ?>"><strong><?= e($r['name']) ?><?= $r['partner_name'] ? ' & ' . e($r['partner_name']) : '' ?></strong></a><div class="muted"><?= e($r['phone']) ?> · <?= e($r['email']) ?></div></td>
            <td><strong><?= e($r['event_type']) ?></strong><div class="muted"><?= e(format_date($r['event_date'])) ?> · <?= e($r['event_location']) ?> · <?= (int)$r['event_days'] ?> day<?= (int)$r['event_days'] === 1 ? '' : 's' ?></div></td>
            <td><?= e($r['service']) ?><div class="muted"><?= e($r['budget']) ?></div></td>
            <td><?php $statusForm($r); ?><?php if ($r['notes']): ?><div class="muted" style="font-size:12px;margin-top:4px"><i class="fa-solid fa-note-sticky"></i> <?= (int)$r['notes'] ?> note<?= (int)$r['notes'] > 1 ? 's' : '' ?></div><?php endif; ?></td>
            <td class="muted"><?= e(format_date($r['created_at'], true)) ?><div><?= e($r['enquiry_id']) ?></div></td>
            <td><div class="actions">
              <a class="btn btn--ghost btn--sm" href="<?= e($view) ?>"><i class="fa-regular fa-eye"></i>Open</a>
              <form method="post" action="<?= e(url('admin/actions/enquiry-delete.php')) ?>" data-ajax data-confirm="Delete the enquiry from <?= e($r['name']) ?>? This cannot be undone — export to CSV first if you need a record." data-danger>
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn--danger btn--icon btn--sm" type="submit" aria-label="Delete enquiry from <?= e($r['name']) ?>"><i class="fa-solid fa-trash-can"></i></button>
              </form>
            </div></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6"><div class="empty"><i class="fa-regular fa-envelope-open"></i><h3><?= array_sum($counts) ? 'No enquiries match these filters' : 'No enquiries yet' ?></h3>New booking requests from the website will appear here.</div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="toolbar" style="border-top:1px solid var(--line);border-bottom:0;justify-content:space-between">
    <span class="muted">Showing <?= count($rows) ?> of <?= $total ?> enquiries</span>
    <?php render_pagination($pg); ?>
  </div>
</div>
<?php admin_footer();
