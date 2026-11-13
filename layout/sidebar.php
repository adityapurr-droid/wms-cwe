<?php
/**
 * Layout Sidebar WMS CWE
 */
$current_page = $current_page ?? 'dashboard';
$user_role = $_SESSION['user_role'] ?? 'operator';
?>
<!-- Sidebar Backdrop on Mobile -->
<div id="sidebarBackdrop"></div>

<!-- Sidebar -->
<nav id="sidebar">
    <div class="sidebar-brand d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <div class="rounded-3 bg-white p-1 d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 46px; height: 46px;">
                <img src="<?= BASE_URL ?>assets/img/logo-cwe.png" alt="Logo CWE" style="max-height: 40px; max-width: 40px; object-fit: contain;">
            </div>
            <div>
                <h6 class="mb-0 text-white fw-bold tracking-wide">WMS CWE</h6>
                <small class="text-white-50" style="font-size: 0.72rem;">Politeknik CWE</small>
            </div>
        </div>
        <!-- Tombol Tutup Sidebar di Mobile -->
        <button type="button" class="btn btn-sm btn-link text-white-50 p-1 d-lg-none text-decoration-none" id="sidebarClose" title="Tutup Menu">
            <i class="bi bi-x-lg fs-5"></i>
        </button>
    </div>

    <ul class="sidebar-nav">
        <!-- Main Menu -->
        <li class="sidebar-heading">Utama</li>
        <li>
            <a href="<?= BASE_URL ?>modules/dashboard/index.php" class="sidebar-link <?= ($current_page === 'dashboard') ? 'active' : '' ?>">
                <i class="bi bi-grid-fill"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>modules/scanner/index.php" class="sidebar-link <?= ($current_page === 'scanner') ? 'active' : '' ?>">
                <i class="bi bi-qr-code-scan"></i>
                <span>Scanner Barcode</span>
                <span class="badge bg-warning text-dark ms-auto font-monospace" style="font-size: 0.68rem;">LIVE</span>
            </a>
        </li>

        <!-- Transaksi Gudang (1 Pintu) -->
        <li class="sidebar-heading">Operasional Gudang</li>
        <li>
            <a href="<?= BASE_URL ?>modules/barang_masuk/index.php" class="sidebar-link <?= ($current_page === 'barang_masuk') ? 'active' : '' ?>">
                <i class="bi bi-box-arrow-in-down"></i>
                <span>Barang Masuk</span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>modules/barang_keluar/index.php" class="sidebar-link <?= ($current_page === 'barang_keluar') ? 'active' : '' ?>">
                <i class="bi bi-box-arrow-up-right"></i>
                <span>Barang Keluar</span>
            </a>
        </li>

        <!-- Inventori Barang -->
        <li class="sidebar-heading">Data Inventori</li>
        <li>
            <a href="<?= BASE_URL ?>modules/barang/index.php" class="sidebar-link <?= ($current_page === 'barang') ? 'active' : '' ?>">
                <i class="bi bi-boxes"></i>
                <span>Master Barang</span>
            </a>
        </li>

        <!-- Master Data (Khusus Admin) -->
        <?php if ($user_role === 'admin'): ?>
        <li class="sidebar-heading">Pengaturan Rak & Master</li>
        <li>
            <a href="<?= BASE_URL ?>modules/rak/index.php" class="sidebar-link <?= ($current_page === 'rak') ? 'active' : '' ?>">
                <i class="bi bi-columns-gap"></i>
                <span>Rak Penyimpanan</span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>modules/kategori/index.php" class="sidebar-link <?= ($current_page === 'kategori') ? 'active' : '' ?>">
                <i class="bi bi-tags-fill"></i>
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
                <span>Pemasok / Vendor</span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>modules/departemen/index.php" class="sidebar-link <?= ($current_page === 'departemen') ? 'active' : '' ?>">
                <i class="bi bi-building-check"></i>
                <span>Departemen / Unit</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Laporan & Akun -->
        <li class="sidebar-heading">Laporan & Pengguna</li>
        <li>
            <a href="<?= BASE_URL ?>modules/laporan/index.php" class="sidebar-link <?= ($current_page === 'laporan') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>Rekapitulasi Laporan</span>
            </a>
        </li>
        <?php if ($user_role === 'admin'): ?>
        <li>
            <a href="<?= BASE_URL ?>modules/users/index.php" class="sidebar-link <?= ($current_page === 'users') ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i>
                <span>Kelola Pengguna</span>
            </a>
        </li>
        <?php endif; ?>

        <li class="mt-4 pt-2 border-top border-secondary border-opacity-25">
            <a href="<?= BASE_URL ?>modules/auth/logout.php" class="sidebar-link text-danger btn-logout-confirm">
                <i class="bi bi-box-arrow-left text-danger"></i>
                <span>Keluar (Logout)</span>
            </a>
        </li>
    </ul>
</nav>
<!-- End Sidebar -->

