<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin-layout.php';
$me = require_admin('admin');

$values = settings(true);
// List settings (event types, budgets, categories…) are stored as JSON — decode them for the tag editors
foreach (settings_groups() as $g) foreach ($g['fields'] as $fd) {
    if (($fd['t'] ?? '') === 'tags' && is_string($values[$fd['k']] ?? null)) $values[$fd['k']] = json_dec($values[$fd['k']], []);
}
$admins = db_all('SELECT id, name, email, role, status, last_login, created_at FROM admins ORDER BY created_at');

admin_header('Settings', 'settings');
admin_page_head('Website settings', 'Branding, contact details, social links, booking form options, footer and SEO — used on every page of the website.');
?>
<div class="tabs">
  <?php foreach (settings_groups() as $k => $g): ?><a href="#<?= e($k) ?>"><i class="<?= $g['icon'] ?>"></i><?= e($g['label']) ?></a><?php endforeach; ?>
  <a href="#admins"><i class="fa-solid fa-user-shield"></i>Administrators</a>
</div>
<div class="grid" style="gap:18px">
  <?php foreach (settings_groups() as $k => $g): ?>
    <form class="card sec-card" id="<?= e($k) ?>" method="post" action="<?= e(url('admin/actions/settings-save.php')) ?>" data-ajax data-track-dirty>
      <div class="card__head"><h2><i class="<?= $g['icon'] ?>"></i><?= e($g['label']) ?></h2><span class="sec-card__dirty">Unsaved</span></div>
      <div class="card__body">
        <?= csrf_field() ?><input type="hidden" name="group" value="<?= e($k) ?>">
        <?php render_fields($g['fields'], $values); ?>
        <div class="sec-card__foot"><button class="btn btn--gold" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save <?= e(strtolower($g['label'])) ?></button></div>
      </div>
    </form>
  <?php endforeach; ?>

  <section class="card" id="admins">
    <div class="card__head"><h2><i class="fa-solid fa-user-shield"></i>Administrators</h2><button class="btn btn--forest btn--sm" type="button" data-modal-open="adminUserModal" data-admin-new><i class="fa-solid fa-user-plus"></i>Add administrator</button></div>
    <div class="card__body" style="padding-bottom:0"><div class="alert alert--info"><i class="fa-solid fa-circle-info"></i><span><strong>Administrator</strong> can do everything including settings and accounts. <strong>Editor</strong> can manage pages, the hero slider, services, albums, films, testimonials, team, media and enquiries.</span></div></div>
    <div class="table-wrap" style="margin-top:16px"><table class="table">
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($admins as $u): $self = (int)$u['id'] === (int)$me['id']; ?>
          <tr>
            <td><strong><?= e($u['name']) ?></strong><?php if ($self): ?> <span class="badge badge--published">You</span><?php endif; ?></td>
            <td class="muted"><?= e($u['email']) ?></td>
            <td><?= e(ROLES[$u['role']] ?? $u['role']) ?></td>
            <td><span class="badge badge--<?= $u['status'] === 'active' ? 'published' : 'draft' ?>"><?= e(ucfirst($u['status'])) ?></span></td>
            <td class="muted"><?= e($u['last_login'] ? format_date($u['last_login'], true) : 'Never') ?></td>
            <td><div class="actions">
              <button class="btn btn--ghost btn--sm" type="button" data-modal-open="adminUserModal" data-admin-user="<?= e(json_encode(['id' => (int)$u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role'], 'status' => $u['status'], 'self' => $self])) ?>"><i class="fa-solid fa-pen"></i>Edit</button>
              <?php if (!$self): ?>
                <form method="post" action="<?= e(url('admin/actions/admin-user-delete.php')) ?>" data-ajax data-confirm="Remove <?= e($u['name']) ?>'s access to the CMS?" data-danger>
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <button class="btn btn--danger btn--icon btn--sm" type="submit" aria-label="Remove <?= e($u['name']) ?>"><i class="fa-solid fa-trash-can"></i></button>
                </form>
              <?php endif; ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  </section>
</div>

<template id="adminUserModal" data-title="Administrator">
  <form method="post" action="<?= e(url('admin/actions/admin-user-save.php')) ?>" data-ajax>
    <?= csrf_field() ?><input type="hidden" name="id" value="0">
    <div class="form-grid">
      <div class="f"><label>Name <span class="req">*</span></label><input type="text" name="name" maxlength="120" required><small class="f__err" data-err-for="name"></small></div>
      <div class="f"><label>Email (login) <span class="req">*</span></label><input type="email" name="email" maxlength="190" required><small class="f__err" data-err-for="email"></small></div>
      <div class="f"><label>Role</label><select name="role"><option value="editor">Editor — content, media & enquiries</option><option value="admin">Administrator — everything</option></select></div>
      <div class="f"><label>Status</label><select name="status"><option value="active">Active</option><option value="disabled">Disabled (cannot log in)</option></select></div>
      <div class="f f--full"><label>Password <span data-pw-hint>(required)</span></label><input type="password" name="password" autocomplete="new-password"><small class="f__help">At least 10 characters with letters and numbers. Leave empty when editing to keep the current password.</small><small class="f__err" data-err-for="password"></small></div>
    </div>
    <div class="modal__foot" style="margin:22px -22px -22px"><button type="button" class="btn btn--ghost" data-modal-close>Cancel</button><button class="btn btn--gold" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save administrator</button></div>
  </form>
</template>
<?php admin_footer();
