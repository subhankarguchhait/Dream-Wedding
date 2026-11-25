<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin-layout.php';
$me = require_admin();
$recent = db_all('SELECT action, description, created_at, ip_address FROM activity_logs WHERE admin_id = ? ORDER BY created_at DESC LIMIT 10', [(int)$me['id']]);

admin_header('My Profile', 'profile');
admin_page_head('My profile', 'Your account details and password. Role: ' . (ROLES[$me['role']] ?? $me['role']) . ' · Last login: ' . ($me['last_login'] ? format_date($me['last_login'], true) : '—'));
?>
<div class="grid grid--2" style="align-items:start">
  <form class="card" method="post" action="<?= e(url('admin/actions/profile-save.php')) ?>" data-ajax data-track-dirty>
    <div class="card__head"><h2><i class="fa-solid fa-id-card"></i>Account details</h2></div>
    <div class="card__body grid" style="gap:16px">
      <?= csrf_field() ?><input type="hidden" name="op" value="details">
      <div class="f"><label for="pn">Name</label><input id="pn" type="text" name="name" value="<?= e($me['name']) ?>" maxlength="120" required><small class="f__err" data-err-for="name"></small></div>
      <div class="f"><label for="pe">Email (your login)</label><input id="pe" type="email" name="email" value="<?= e($me['email']) ?>" maxlength="190" required><small class="f__err" data-err-for="email"></small></div>
      <div class="f"><label for="pc">Current password <span class="f__help">(only needed to change your email)</span></label><input id="pc" type="password" name="current_password" autocomplete="current-password"><small class="f__err" data-err-for="current_password"></small></div>
      <div><button class="btn btn--gold" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save details</button></div>
    </div>
  </form>
  <form class="card" method="post" action="<?= e(url('admin/actions/profile-save.php')) ?>" data-ajax>
    <div class="card__head"><h2><i class="fa-solid fa-key"></i>Change password</h2></div>
    <div class="card__body grid" style="gap:16px">
      <?= csrf_field() ?><input type="hidden" name="op" value="password">
      <div class="f"><label for="op">Current password</label><input id="op" type="password" name="current_password" autocomplete="current-password" required><small class="f__err" data-err-for="current_password"></small></div>
      <div class="f"><label for="np">New password</label><input id="np" type="password" name="new_password" autocomplete="new-password" required><small class="f__help">At least 10 characters with letters and numbers.</small><small class="f__err" data-err-for="new_password"></small></div>
      <div class="f"><label for="cp">Confirm new password</label><input id="cp" type="password" name="confirm_password" autocomplete="new-password" required><small class="f__err" data-err-for="confirm_password"></small></div>
      <div><button class="btn btn--forest" type="submit"><i class="fa-solid fa-lock"></i>Change password</button></div>
    </div>
  </form>
</div>
<div class="card" style="margin-top:20px">
  <div class="card__head"><h2><i class="fa-solid fa-clock-rotate-left"></i>Your recent activity</h2></div>
  <div class="card__body" style="padding-top:6px;padding-bottom:6px"><ul class="activity">
    <?php foreach ($recent as $l): ?><li><i class="fa-solid fa-pen-nib"></i><div><span><?= e($l['action']) ?><?php if ($l['description']): ?> · <strong><?= e($l['description']) ?></strong><?php endif; ?></span><small><?= e(format_date($l['created_at'], true)) ?> · IP <?= e($l['ip_address']) ?></small></div></li><?php endforeach; ?>
    <?php if (!$recent): ?><li><i class="fa-regular fa-clock"></i><div>No activity yet.</div></li><?php endif; ?>
  </ul></div>
</div>
<?php admin_footer();
