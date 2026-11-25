<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

$next = get_str('next', 40);
$next = preg_match('/^[a-z\-]+\.php$/', $next) && $next !== 'login.php' ? $next : 'dashboard.php';
$error = '';
$email = '';
$dbOk = true;
$noAdmins = false;

try {
    if (current_admin()) redirect('admin/' . $next);
    $noAdmins = (int)db_val('SELECT COUNT(*) FROM admins') === 0;
} catch (Throwable $e) {
    $dbOk = false;
}

if ($dbOk && is_post()) {
    $email = post_str('email', 190);
    $password = (string)($_POST['password'] ?? '');
    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '' || strlen($password) > 200) {
        $error = 'Please enter your email and password.';
    } else {
        [$ok, $result] = attempt_login($email, $password);
        if ($ok) {
            log_activity('Logged in');
            redirect('admin/' . $next);
        }
        $error = $result;
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Sign in · <?= e(setting('site_name')) ?> CMS</title>
  <link rel="icon" href="<?= e(url('assets/images/brand/favicon-192.png')) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="<?= e(asset('admin/css/admin.css')) ?>">
</head>
<body class="admin">
  <div class="login">
    <section class="login__art">
      <a href="<?= e(url()) ?>" style="display:flex;align-items:center;gap:10px">
        <img src="<?= e(url('assets/images/brand/dreamm-weddings-mark.webp')) ?>" alt="" width="36" height="40" style="height:40px;width:auto">
        <strong style="font-family:var(--head);font-size:26px"><?= site_name_html() ?></strong>
      </a>
      <div>
        <h1>Your website,<br><em>your way.</em></h1>
        <p>Edit pages, the hero slider, albums, films and settings — changes appear on the website as soon as you press Save.</p>
      </div>
      <small style="opacity:.6">Content Studio · PHP & MySQL</small>
    </section>
    <section class="login__panel">
      <div class="login__box">
        <h2>Welcome back</h2>
        <p>Sign in with your administrator account.</p>
        <?php if (!$dbOk): ?>
          <div class="alert alert--error"><i class="fa-solid fa-database"></i><span><strong>Can't connect to the database.</strong> Start MySQL (XAMPP Control Panel), import <code>database/dreamm_framme_event.sql</code> and check <code>config/database.php</code>.</span></div>
        <?php elseif ($noAdmins): ?>
          <div class="alert alert--warn"><i class="fa-solid fa-user-plus"></i><span>No administrator exists yet. <a href="<?= e(url('admin/setup.php')) ?>"><strong>Create the first admin account →</strong></a></span></div>
        <?php endif; ?>
        <?php if (get_str('bye') === '1'): ?><div class="alert alert--ok"><i class="fa-solid fa-circle-check"></i><span>You have been logged out.</span></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert--error" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($error) ?></span></div><?php endif; ?>
        <form method="post" action="<?= e(url('admin/login.php' . ($next !== 'dashboard.php' ? '?next=' . $next : ''))) ?>" novalidate style="margin-top:16px">
          <?= csrf_field() ?>
          <div class="f"><label for="email">Email</label><input id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="username" required autofocus></div>
          <div class="f">
            <label for="password">Password</label>
            <div class="pw"><input id="password" name="password" type="password" autocomplete="current-password" required><button type="button" aria-label="Show password" onclick="const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';this.firstElementChild.className=p.type==='password'?'fa-regular fa-eye':'fa-regular fa-eye-slash'"><i class="fa-regular fa-eye"></i></button></div>
          </div>
          <button class="btn btn--forest" type="submit" style="padding:13px"<?= $dbOk ? '' : ' disabled' ?>><i class="fa-solid fa-right-to-bracket"></i>Sign in</button>
          <div class="login__links">
            <span class="f__help">Forgot your password? Ask another administrator to reset it (Settings → Administrators).</span>
          </div>
          <div class="login__links"><a href="<?= e(url()) ?>">← Back to website</a></div>
        </form>
      </div>
    </section>
  </div>
</body>
</html>
