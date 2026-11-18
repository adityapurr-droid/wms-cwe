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
        <button id="sidebarToggle" class="btn btn-light btn-sm border text-secondary me-2 me-md-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;" title="Sembunyikan / Tampilkan Menu" aria-label="Toggle Menu">
            <i class="bi bi-list fs-5"></i>
        </button>
        <!-- Tampilan Brand di Mobile -->
        <div class="d-flex align-items-center d-md-none">
            <img src="<?= BASE_URL ?>assets/img/logo-cwe.png" alt="CWE" style="height: 24px; margin-right: 6px;">
            <span class="fw-bold" style="font-size: 0.85rem; color: var(--theme-main);">WMS CWE</span>
        </div>
        <!-- Tampilan Brand di Desktop -->
        <div class="d-none d-md-block">
            <div class="d-inline-flex align-items-center">
                <img src="<?= BASE_URL ?>assets/img/logo-cwe.png" alt="CWE" style="height: 22px; margin-right: 8px;">
                <span class="fw-bold" style="color: var(--theme-main); font-size: 0.92rem;">Warehouse Management System CWE</span>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2">
        <!-- Notifikasi Stok Menipis (Hanya Ikon Saja) -->
        <div class="dropdown">
            <button class="btn btn-link text-secondary position-relative p-2 text-decoration-none border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Peringatan Stok" aria-label="Notifikasi Stok">
                <i class="bi bi-bell fs-5"></i>
                <?php if ($stok_kritis_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge bg-output-data" style="font-size: 0.65rem; border-radius: 3px !important;">
                        <?= $stok_kritis_count ?>
                    </span>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border mt-2 p-2" style="width: 310px; max-width: 90vw;">
                <li class="p-2 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold small text-dark">Peringatan Stok Menipis</span>
                    <span class="badge bg-output-data-subtle text-output-data"><?= $stok_kritis_count ?> Barang</span>
                </li>
                <?php if (empty($stok_kritis_items)): ?>
                    <li class="p-3 text-center text-muted small">
                        <i class="bi bi-check-circle text-persediaan fs-4 d-block mb-1"></i>
                        Semua persediaan barang dalam batas aman.
                    </li>
                <?php else: ?>
                    <?php foreach ($stok_kritis_items as $item): ?>
                        <li>
                            <a class="dropdown-item p-2 small d-flex justify-content-between align-items-center" href="<?= BASE_URL ?>modules/barang/index.php">
                                <div>
                                    <div class="fw-semibold text-truncate" style="max-width: 190px; color: var(--color-normal-text);"><?= htmlspecialchars($item['nama_barang']) ?></div>
                                    <div class="text-muted" style="font-size: 0.72rem;">Batas Min: <?= $item['stok_minimum'] ?></div>
                                </div>
                                <span class="badge bg-output-data">Sisa: <?= $item['stok'] ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <li class="pt-2 border-top text-center">
                        <a href="<?= BASE_URL ?>modules/barang/index.php" class="text-persediaan small fw-semibold text-decoration-none">Lihat Semua Persediaan Menipis &rarr;</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Profil Akun Pengguna (Tanpa Foto Profil) -->
        <div class="dropdown ms-1">
            <button class="btn btn-light btn-sm d-flex align-items-center border px-3 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Profil Pengguna">
                <div class="text-start me-2">
                    <div class="fw-bold lh-1" style="font-size: 0.82rem; color: var(--color-normal-text);"><?= htmlspecialchars($_SESSION['user_nama'] ?? 'User') ?></div>
                    <small class="text-muted text-uppercase" style="font-size: 0.65rem;"><?= htmlspecialchars($_SESSION['user_role'] ?? 'operator') ?></small>
                </div>
                <i class="bi bi-chevron-down text-muted small"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border mt-2 p-2" style="min-width: 200px;">
                <li class="px-3 py-2 border-bottom">
                    <p class="mb-0 fw-bold small text-dark"><?= htmlspecialchars($_SESSION['user_nama'] ?? 'User') ?></p>
                    <small class="text-muted font-monospace"><?= htmlspecialchars($_SESSION['user_username'] ?? '') ?></small>
                    <div class="mt-1">
                        <span class="badge <?= ($_SESSION['user_role'] ?? '') === 'admin' ? 'bg-persediaan' : 'bg-secondary' ?> text-uppercase" style="font-size: 0.65rem;">
                            <?= htmlspecialchars($_SESSION['user_role'] ?? 'operator') ?>
                        </span>
                    </div>
                </li>
                <li>
                    <a class="dropdown-item py-2 text-output-data small mt-1 btn-logout-confirm" href="<?= BASE_URL ?>modules/auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
<!-- End Topbar -->
