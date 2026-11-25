<?php
/** Save one settings group (administrators only). */
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';
require __DIR__ . '/../../includes/form.php';
require_admin('admin');
require_post_csrf();

$groups = settings_groups();
$group = post_str('group', 20);
if (!isset($groups[$group])) respond(false, 'Unknown settings group.');
[$clean, $errors] = validate_fields($groups[$group]['fields'], $_POST['f'] ?? []);
if ($errors) respond(false, 'Please fix the highlighted fields.', ['errors' => $errors]);

db_tx(function () use ($clean) {
    foreach ($clean as $k => $v) {
        $v = is_array($v) ? json_enc($v) : (string)$v;
        if (db_val('SELECT id FROM settings WHERE setting_key = ?', [$k])) db_update('settings', ['setting_value' => $v, 'updated_at' => now()], 'setting_key = ?', [$k]);
        else db_insert('settings', ['setting_key' => $k, 'setting_value' => $v, 'updated_at' => now()]);
    }
});
log_activity('Updated settings', $groups[$group]['label']);
respond(true, $groups[$group]['label'] . ' saved — updated across the whole website.');
