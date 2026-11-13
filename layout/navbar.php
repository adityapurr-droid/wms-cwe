<?php
/**
 * Layout Navbar WMS CWE
 */
// Hitung jumlah barang dengan stok menipis (<= stok_minimum)
$stok_kritis_count = 0;
$stok_kritis_items = [];
if (isset($pdo)) {
    try {
        $stmt_notif = $pdo->query("SELECT nama_barang, stok, stok_minimum FROM barang WHERE stok <= stok_minimum ORDER BY stok ASC LIMIT 5");
        $stok_kritis_items = $stmt_notif->fetchAll();
        $stmt_count = $pdo->query("SELECT COUNT(*) FROM barang WHERE stok <= stok_minimum");
        $stok_kritis_count = (int)$stmt_count->fetchColumn();
    } catch (Exception $e) {
        // Abaikan jika database belum siap
    }
}
?>
<!-- Topbar Header -->
<header class="topbar d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center">
        <button id="sidebarToggle" class="btn btn-light btn-sm rounded-circle me-2 me-md-3 border text-secondary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Sembunyikan / Tampilkan Menu">
            <i class="bi bi-list fs-5"></i>
        </button>
        <!-- Tampilan Brand di Mobile -->
        <div class="d-flex align-items-center d-md-none">
            <img src="<?= BASE_URL ?>assets/img/logo-cwe.png" alt="CWE" style="height: 24px; margin-right: 6px;">
            <span class="fw-bold text-success" style="font-size: 0.85rem;">WMS CWE</span>
        </div>
        <!-- Tampilan Brand di Desktop -->
        <div class="d-none d-md-block">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill d-inline-flex align-items-center">
                <img src="<?= BASE_URL ?>assets/img/logo-cwe.png" alt="CWE" style="height: 16px; margin-right: 6px;"> Warehouse Management System CWE
            </span>
        </div>
    </div>

    <div class="d-flex align-items-center gap-1 gap-sm-2">
        <!-- Quick Barcode Scan Button -->
        <a href="<?= BASE_URL ?>modules/scanner/index.php" class="btn btn-outline-success btn-sm rounded-pill px-3 py-1 fw-semibold d-none d-sm-inline-flex align-items-center">
            <i class="bi bi-upc-scan me-2 text-success"></i> Scan Barcode
        </a>

        <!-- Notifikasi Stok Menipis -->
        <div class="dropdown">
            <button class="btn btn-light btn-sm rounded-circle position-relative border p-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Peringatan Stok">
                <i class="bi bi-bell text-secondary fs-6"></i>
                <?php if ($stok_kritis_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                        <?= $stok_kritis_count ?>
                    </span>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 p-2" style="width: 300px; max-width: 90vw; border-radius: 12px;">
                <li class="p-2 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold small text-dark">Peringatan Stok Menipis</span>
                    <span class="badge bg-danger-subtle text-danger"><?= $stok_kritis_count ?> Barang</span>
                </li>
                <?php if (empty($stok_kritis_items)): ?>
                    <li class="p-3 text-center text-muted small">
                        <i class="bi bi-check-circle text-success fs-4 d-block mb-1"></i>
                        Semua persediaan barang dalam batas aman.
                    </li>
                <?php else: ?>
                    <?php foreach ($stok_kritis_items as $item): ?>
                        <li>
                            <a class="dropdown-item p-2 rounded small d-flex justify-content-between align-items-center" href="<?= BASE_URL ?>modules/barang/index.php">
                                <div>
                                    <div class="fw-semibold text-truncate" style="max-width: 190px;"><?= htmlspecialchars($item['nama_barang']) ?></div>
                                    <div class="text-muted" style="font-size: 0.72rem;">Batas Min: <?= $item['stok_minimum'] ?></div>
                                </div>
                                <span class="badge bg-danger">Sisa: <?= $item['stok'] ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <li class="pt-2 border-top text-center">
                        <a href="<?= BASE_URL ?>modules/barang/index.php" class="text-success small fw-semibold text-decoration-none">Lihat Semua Barang Menipis &rarr;</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Profil Akun Pengguna -->
        <div class="dropdown ms-2">
            <button class="btn btn-light btn-sm d-flex align-items-center border rounded-pill px-3 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 28px; height: 28px; font-size: 0.8rem;">
                    <?= strtoupper(substr($_SESSION['user_nama'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="text-start d-none d-lg-block me-2">
                    <div class="fw-bold text-dark lh-1" style="font-size: 0.85rem;"><?= htmlspecialchars($_SESSION['user_nama'] ?? 'User') ?></div>
                    <small class="text-muted text-uppercase" style="font-size: 0.68rem;"><?= htmlspecialchars($_SESSION['user_role'] ?? 'operator') ?></small>
                </div>
                <i class="bi bi-chevron-down text-muted small"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 p-2" style="border-radius: 12px; min-width: 200px;">
                <li class="px-3 py-2 border-bottom">
                    <p class="mb-0 fw-bold small text-dark"><?= htmlspecialchars($_SESSION['user_nama'] ?? 'User') ?></p>
                    <small class="text-muted font-monospace"><?= htmlspecialchars($_SESSION['user_username'] ?? '') ?></small>
                    <div class="mt-1">
                        <span class="badge <?= ($_SESSION['user_role'] ?? '') === 'admin' ? 'bg-primary' : 'bg-secondary' ?> text-uppercase" style="font-size: 0.65rem;">
                            <?= htmlspecialchars($_SESSION['user_role'] ?? 'operator') ?>
                        </span>
                    </div>
                </li>
                <li>
                    <a class="dropdown-item py-2 rounded text-danger small mt-1 btn-logout-confirm" href="<?= BASE_URL ?>modules/auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
<!-- End Topbar -->

