<?php
/**
 * Layout Sidebar WMS CWE
 * Tema: #153618 & Putih
 */
$current_page = $current_page ?? 'dashboard';
$user_role = $_SESSION['user_role'] ?? 'operator';
?>
<!-- Sidebar Backdrop on Mobile -->
<div id="sidebarBackdrop"></div>

<!-- Sidebar -->
<nav id="sidebar" role="navigation" aria-label="Navigasi Utama">
    <div class="sidebar-brand d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <div class="brand-logo-box">
                <img src="<?= BASE_URL ?>assets/img/logo-cwe.png" alt="Logo CWE" style="max-height: 32px; max-width: 32px; object-fit: contain;">
            </div>
            <div>
                <h6 class="mb-0 text-white fw-bold">WMS CWE</h6>
                <small class="text-white-50" style="font-size: 0.7rem;">Politeknik Kelapa Sawit</small>
            </div>
        </div>
        <!-- Tombol Tutup Sidebar di Mobile -->
        <button type="button" class="btn btn-sm btn-link text-white-50 p-1 d-lg-none text-decoration-none" id="sidebarClose" aria-label="Tutup Menu">
            <i class="bi bi-x-lg fs-5"></i>
        </button>
    </div>

    <ul class="sidebar-nav">
        <!-- Main Menu -->
        <li class="sidebar-heading">Utama</li>
        <li>
            <a href="<?= BASE_URL ?>modules/dashboard/index.php" class="sidebar-link <?= ($current_page === 'dashboard') ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>modules/scanner/index.php" class="sidebar-link link-gold <?= ($current_page === 'scanner') ? 'active' : '' ?>">
                <i class="bi bi-upc-scan"></i>
                <span>Scanner Barcode</span>
            </a>
        </li>
        
        <!-- Operasional Gudang -->
        <li class="sidebar-heading">Operasional Gudang</li>
        <li>
            <a href="<?= BASE_URL ?>modules/barang_masuk/index.php" class="sidebar-link <?= ($current_page === 'barang_masuk') ? 'active' : '' ?>">
                <i class="bi bi-box-arrow-in-down"></i>
                <span>Barang Masuk</span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>modules/barang_keluar/index.php" class="sidebar-link <?= ($current_page === 'barang_keluar') ? 'active' : '' ?>">
                <i class="bi bi-box-arrow-up"></i>
                <span>Barang Keluar</span>
            </a>
        </li>
        
        <!-- Data Inventori / Persediaan -->
        <li class="sidebar-heading">Data Inventori</li>
        <li>
            <a href="<?= BASE_URL ?>modules/barang/index.php" class="sidebar-link <?= ($current_page === 'barang') ? 'active' : '' ?>">
                <i class="bi bi-boxes"></i>
                <span>Master Barang</span>
            </a>
        </li>
        
        <?php if ($user_role === 'admin'): ?>
        <!-- Pengaturan Rak & Master Data -->
        <li class="sidebar-heading">Pengaturan & Master</li>
        <li>
            <a href="<?= BASE_URL ?>modules/rak/index.php" class="sidebar-link <?= ($current_page === 'rak') ? 'active' : '' ?>">
                <i class="bi bi-columns-gap"></i>
                <span>Rak Penyimpanan</span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>modules/kategori/index.php" class="sidebar-link <?= ($current_page === 'kategori') ? 'active' : '' ?>">
                <i class="bi bi-tag"></i>
                <span>Kategori Barang</span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>modules/satuan/index.php" class="sidebar-link <?= ($current_page === 'satuan') ? 'active' : '' ?>">
                <i class="bi bi-rulers"></i>
                <span>Satuan Ukur</span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>modules/supplier/index.php" class="sidebar-link <?= ($current_page === 'supplier') ? 'active' : '' ?>">
                <i class="bi bi-truck"></i>
                <span>Pemasok (Vendor)</span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>modules/departemen/index.php" class="sidebar-link <?= ($current_page === 'departemen') ? 'active' : '' ?>">
                <i class="bi bi-buildings"></i>
                <span>Departemen / Unit</span>
            </a>
        </li>
        <?php endif; ?>
        
        <!-- Laporan & Pengguna -->
        <li class="sidebar-heading">Laporan & Pengguna</li>
        <li>
            <a href="<?= BASE_URL ?>modules/laporan/index.php" class="sidebar-link <?= ($current_page === 'laporan') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-text"></i>
                <span>Laporan & Mutasi</span>
            </a>
        </li>
        <?php if ($user_role === 'admin'): ?>
        <li>
            <a href="<?= BASE_URL ?>modules/users/index.php" class="sidebar-link <?= ($current_page === 'users') ? 'active' : '' ?>">
                <i class="bi bi-people"></i>
                <span>Pengguna</span>
            </a>
        </li>
        <?php endif; ?>
        
        <li class="mt-4 border-top border-secondary border-opacity-25 pt-2">
            <a href="<?= BASE_URL ?>modules/auth/logout.php" class="sidebar-link text-output-data btn-logout-confirm">
                <i class="bi bi-box-arrow-right text-output-data"></i>
                <span>Keluar</span>
            </a>
        </li>
    </ul>
</nav>
<!-- End Sidebar -->
