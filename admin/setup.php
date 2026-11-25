<?php
/**
 * One-time creation of the FIRST administrator.
 * Works only while the admins table is empty AND only with the SETUP_KEY from config/config.php.
 * After the first admin exists this page permanently refuses to run. You may delete it afterwards.
 */
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

$error = '';
$done = false;
try {
    $locked = (int)db_val('SELECT COUNT(*) FROM admins') > 0;
} catch (Throwable $e) {
    admin_fatal('Database not ready', 'Import <code>database/dreamm_framme_event.sql</code> in phpMyAdmin and check <code>config/database.php</code>, then reload this page.');
}
$defaultKey = SETUP_KEY === 'CHANGE-ME-to-a-long-random-phrase' || strlen(SETUP_KEY) < 12;
$v = ['name' => post_str('name', 120), 'email' => mb_strtolower(post_str('email', 190))];

if (!$locked && !$defaultKey && is_post()) {
    // Throttle guesses of the setup key using the login limiter
    if (login_is_locked('setup@local')) {
        $error = 'Too many attempts. Wait ' . LOGIN_WINDOW_MINUTES . ' minutes.';
    } elseif (!csrf_valid()) {
        $error = 'Session expired — please try again.';
    } elseif (!hash_equals(SETUP_KEY, (string)($_POST['setup_key'] ?? ''))) {
        record_login_attempt('setup@local', false);
        $error = 'The setup key is incorrect. It is the SETUP_KEY value in config/config.php.';
    } elseif (mb_strlen($v['name']) < 2) {
        $error = 'Please enter your name.';
    } elseif (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (($p = password_problem((string)($_POST['password'] ?? ''), $v['email'])) !== '') {
        $error = 'Password: ' . $p;
    } elseif (($_POST['password'] ?? '') !== ($_POST['password_confirm'] ?? '')) {
        $error = 'The two passwords do not match.';
    } else {
        db_tx(function () use ($v) {
            if ((int)db_val('SELECT COUNT(*) FROM admins') > 0) throw new RuntimeException('Setup already completed.');
            db_insert('admins', ['name' => $v['name'], 'email' => $v['email'], 'password_hash' => password_hash((string)$_POST['password'], PASSWORD_DEFAULT),
                'role' => 'admin', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        });
        $done = true;
    }
}
?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Setup · Dreamm Weddings CMS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= e(asset('admin/css/admin.css')) ?>"></head>
<body class="admin"><div class="login" style="grid-template-columns:1fr"><section class="login__panel"><div class="login__box" style="width:min(480px,100%)">
  <h2>Create the first administrator</h2>
  <?php if ($done): ?>
    <div class="alert alert--ok"><i class="fa-solid fa-circle-check"></i><span>Your administrator account is ready. This setup page is now locked. For extra safety you can delete <code>admin/setup.php</code>.</span></div>
    <p style="margin-top:16px"><a class="btn btn--forest" href="<?= e(url('admin/login.php')) ?>"><i class="fa-solid fa-right-to-bracket"></i>Go to login</a></p>
  <?php elseif ($locked): ?>
    <div class="alert alert--info"><i class="fa-solid fa-lock"></i><span>Setup is already complete. Log in, or ask an administrator to add you under Settings → Administrators.</span></div>
    <p style="margin-top:16px"><a class="btn btn--forest" href="<?= e(url('admin/login.php')) ?>">Go to login</a></p>
  <?php elseif ($defaultKey): ?>
    <div class="alert alert--warn"><i class="fa-solid fa-key"></i><span>First open <code>config/config.php</code> and change <code>SETUP_KEY</code> to your own long secret phrase (at least 12 characters). Then reload this page and type that phrase below.</span></div>
  <?php else: ?>
    <p>This page works only once. You will need the <code>SETUP_KEY</code> from <code>config/config.php</code>.</p>
    <?php if ($error): ?><div class="alert alert--error" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($error) ?></span></div><?php endif; ?>
    <form method="post" style="margin-top:16px">
      <?= csrf_field() ?>
      <div class="f"><label for="k">Setup key</label><input id="k" name="setup_key" type="password" required autocomplete="off"></div>
      <div class="f"><label for="n">Your name</label><input id="n" name="name" type="text" value="<?= e($v['name']) ?>" required></div>
      <div class="f"><label for="e">Email (your login)</label><input id="e" name="email" type="email" value="<?= e($v['email']) ?>" required autocomplete="username"></div>
      <div class="f"><label for="p">Password</label><input id="p" name="password" type="password" required autocomplete="new-password"><small class="f__help">At least 10 characters with letters and numbers.</small></div>
      <div class="f"><label for="p2">Confirm password</label><input id="p2" name="password_confirm" type="password" required autocomplete="new-password"></div>
      <button class="btn btn--gold" type="submit" style="padding:13px"><i class="fa-solid fa-user-shield"></i>Create administrator</button>
    </form>
  <?php endif; ?>
</div></section></div></body></html>
