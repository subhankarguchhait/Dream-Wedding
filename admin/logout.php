<?php
/** Secure logout: POST + CSRF only, so a link or image on another site can't log you out. */
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

if (!is_post() || !csrf_valid()) {
    redirect(current_admin() ? 'admin/dashboard.php' : 'admin/login.php');
}
if (current_admin()) log_activity('Logged out');
logout_admin();
redirect('admin/login.php?bye=1');
