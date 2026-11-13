<?php
/**
 * Logika Pemrosesan Login WMS CWE
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'modules/auth/login.php');
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    set_flash('error', 'Token keamanan CSRF tidak valid. Silakan coba lagi.');
    header('Location: ' . BASE_URL . 'modules/auth/login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    set_flash('error', 'Username dan password wajib diisi.');
    header('Location: ' . BASE_URL . 'modules/auth/login.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, nama, username, password, role, email FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Regenerasi session ID untuk mencegah session fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nama'] = $user['nama'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];

        set_flash('success', 'Selamat datang kembali, ' . htmlspecialchars($user['nama']) . '!');
        header('Location: ' . BASE_URL . 'modules/dashboard/index.php');
        exit;
    } else {
        set_flash('error', 'Username atau password yang Anda masukkan salah.');
        header('Location: ' . BASE_URL . 'modules/auth/login.php');
        exit;
    }
} catch (PDOException $e) {
    set_flash('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
    header('Location: ' . BASE_URL . 'modules/auth/login.php');
    exit;
}

