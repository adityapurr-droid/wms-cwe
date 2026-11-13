<?php
/**
 * Root Entry Point WMS CWE
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/helpers/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'modules/dashboard/index.php');
} else {
    header('Location: ' . BASE_URL . 'modules/auth/login.php');
}
exit;

