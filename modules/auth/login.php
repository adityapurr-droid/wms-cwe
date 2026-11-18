<?php
/**
 * Halaman Login WMS Politeknik Kelapa Sawit CWE
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';

// Jika sudah login, langsung ke dashboard
if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'modules/dashboard/index.php');
    exit;
}

$csrf_token = csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Masuk - <?= APP_NAME ?> <?= INSTITUTION_NAME ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>assets/img/logo-cwe.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #153618;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
            background: #ffffff;
            width: 100%;
            max-width: 440px;
        }
        .form-panel {
            padding: 2.25rem 2rem;
        }
        @media (max-width: 575.98px) {
            .form-panel {
                padding: 1.75rem 1.25rem;
            }
        }
        .form-control {
            border-radius: 4px;
            padding: 0.7rem 0.9rem;
            border: 1px solid #ced4da;
            font-size: 0.92rem;
            color: #1a1d20;
        }
        .form-control:focus {
            border-color: #153618;
            box-shadow: 0 0 0 0.2rem rgba(21, 54, 24, 0.2);
        }
        .btn-cwe-login {
            background-color: #153618;
            color: #ffffff;
            border: 1px solid #153618;
            border-radius: 4px;
            padding: 0.75rem 1.5rem;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .btn-cwe-login:hover {
            background-color: #0e2410;
            color: #ffffff;
        }
        .badge-quick {
            cursor: pointer;
            border-radius: 4px !important;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="form-panel">
        <!-- Logo & Identitas Aplikasi -->
        <div class="text-center mb-4">
            <div class="d-inline-flex rounded-3 bg-white p-2 mb-3 shadow-sm border">
                <img src="<?= BASE_URL ?>assets/img/logo-cwe.png" alt="Logo CWE" style="height: 54px; object-fit: contain;">
            </div>
            <h5 class="fw-bold mb-1 text-black"><?= APP_FULL_NAME ?></h5>
            <p class="text-muted small mb-0"><?= INSTITUTION_NAME ?></p>
        </div>

        <?= render_flash() ?>

        <form action="<?= BASE_URL ?>modules/auth/process_login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div class="mb-3">
                <label for="username" class="form-label fw-semibold text-secondary small">Nama Pengguna (Username)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" id="username" name="username" placeholder="Masukkan username" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <label for="password" class="form-label fw-semibold text-secondary small">Kata Sandi (Password)</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control border-start-0 border-end-0 ps-0" id="password" name="password" placeholder="Masukkan kata sandi" required>
                    <button class="input-group-text bg-light border-start-0 text-muted" type="button" id="togglePassword">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-cwe-login w-100 mb-4">
                <i class="bi bi-box-arrow-in-right me-2"></i> Masuk ke Sistem
            </button>
        </form>

        <!-- Akun Cepat untuk Uji Coba -->
        <div class="p-3 bg-light border" style="border-radius: 4px;">
            <span class="fw-bold text-muted small d-block mb-2">Akun Demo (Klik untuk Isi Cepat):</span>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-success badge-quick" onclick="fillAccount('admin', 'admin123')">
                    <i class="bi bi-person-fill-gear me-1"></i> Admin (admin / admin123)
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary badge-quick" onclick="fillAccount('operator', 'operator123')">
                    <i class="bi bi-person-badge me-1"></i> Operator (operator / operator123)
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle Password Visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        eyeIcon.classList.toggle('bi-eye');
        eyeIcon.classList.toggle('bi-eye-slash');
    });

    // Quick Fill Demo Credentials
    function fillAccount(u, p) {
        document.getElementById('username').value = u;
        document.getElementById('password').value = p;
    }
</script>

</body>
</html>

