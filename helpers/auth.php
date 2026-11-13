<?php
/**
 * Helper Autentikasi dan Hak Akses (Role-Based Access Control)
 */

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array {
        if (!is_logged_in()) {
            return null;
        }
        return [
            'id'       => $_SESSION['user_id'],
            'nama'     => $_SESSION['user_nama'] ?? '',
            'username' => $_SESSION['user_username'] ?? '',
            'role'     => $_SESSION['user_role'] ?? 'operator',
            'email'    => $_SESSION['user_email'] ?? '',
        ];
    }
}

if (!function_exists('require_login')) {
    function require_login(): void {
        if (!is_logged_in()) {
            $_SESSION['flash_error'] = 'Silakan login terlebih dahulu untuk mengakses sistem.';
            header('Location: ' . BASE_URL . 'modules/auth/login.php');
            exit;
        }
    }
}

if (!function_exists('require_admin')) {
    function require_admin(): void {
        require_login();
        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            $_SESSION['flash_error'] = 'Akses ditolak! Anda tidak memiliki izin untuk mengakses halaman tersebut.';
            header('Location: ' . BASE_URL . 'modules/dashboard/index.php');
            exit;
        }
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token(?string $token): bool {
        if (!$token || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

