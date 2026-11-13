<?php
/**
 * Logout Sesi WMS CWE
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../helpers/alerts.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

session_start();
set_flash('info', 'Anda telah berhasil keluar dari sistem WMS CWE.');
header('Location: ' . BASE_URL . 'modules/auth/login.php');
exit;

