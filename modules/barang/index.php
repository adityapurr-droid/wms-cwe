<?php
/**
 * Master Data Barang WMS CWE
 * Politeknik Kelapa Sawit Citra Widya Edukasi
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';
require_once __DIR__ . '/../../helpers/format.php';

require_login();

$page_title = 'Master Data Barang';
$current_page = 'barang';
$user_role = $_SESSION['user_role'] ?? 'operator';

// Filter Kategori & Rak jika dipilih
$filter_kategori = (int)($_GET['kategori'] ?? 0);
$filter_rak = (int)($_GET['rak'] ?? 0);
$filter_status = $_GET['status'] ?? '';

$sql = "
    SELECT b.*, k.nama_kategori, s.nama_satuan, r.nama_rak, r.kode_rak
    FROM barang b
    JOIN kategori k ON b.id_kategori = k.id
    JOIN satuan s ON b.id_satuan = s.id
    JOIN rak r ON b.id_rak = r.id
    WHERE 1=1
";
$params = [];

if ($filter_kategori > 0) {
    $sql .= " AND b.id_kategori = ?";
    $params[] = $filter_kategori;
}
if ($filter_rak > 0) {
    $sql .= " AND b.id_rak = ?";
    $params[] = $filter_rak;
}
if ($filter_status === 'kritis') {
    $sql .= " AND b.stok <= b.stok_minimum";
} elseif ($filter_status === 'habis') {
    $sql .= " AND b.stok = 0";
}

$sql .= " ORDER BY b.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$barang_list = $stmt->fetchAll();

// Pilihan untuk filter dropdown
$kategori_options = $pdo->query("SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori ASC")->fetchAll();
$rak_options = $pdo->query("SELECT id, kode_rak, nama_rak FROM rak ORDER BY kode_rak ASC")->fetchAll();

require_once __DIR__ . '/../../layout/header.php';
require_once __DIR__ . '/../../layout/sidebar.php';
?>

<div id="content-wrapper">
    <?php require_once __DIR__ . '/../../layout/navbar.php'; ?>

    <main class="container-fluid px-4 py-4">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-boxes text-persediaan me-2"></i> Master Data Barang</h4>
                <p class="text-muted small mb-0">Kelola informasi barang, lokasi rak penyimpanan 1 pintu, saldo stok, dan barcode fisik.</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <a href="<?= BASE_URL ?>modules/scanner/index.php" class="btn btn-outline-persediaan fw-semibold">
                    <i class="bi bi-upc-scan me-1"></i> Scan Barcode
                </a>
                <?php if ($user_role === 'admin'): ?>
                <a href="<?= BASE_URL ?>modules/barang/tambah.php" class="btn btn-theme-main shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Barang Baru
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card shadow-sm mb-4">
            <div class="card-body py-3">
                <form action="" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Filter Kategori:</label>
                        <select name="kategori" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Semua Kategori --</option>
                            <?php foreach ($kategori_options as $ko): ?>
                                <option value="<?= $ko['id'] ?>" <?= ($filter_kategori == $ko['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ko['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Filter Lokasi Rak:</label>
                        <select name="rak" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Semua Rak --</option>
                            <?php foreach ($rak_options as $ro): ?>
                                <option value="<?= $ro['id'] ?>" <?= ($filter_rak == $ro['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ro['kode_rak']) ?> (<?= htmlspecialchars($ro['nama_rak']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Filter Status Stok:</label>
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Semua Status --</option>
                            <option value="kritis" <?= ($filter_status === 'kritis') ? 'selected' : '' ?>>Stok Menipis / Kritis</option>
                            <option value="habis" <?= ($filter_status === 'habis') ? 'selected' : '' ?>>Stok Habis (0)</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2 mt-3 mt-md-0 pt-md-3">
                        <button type="submit" class="btn btn-sm btn-theme-main px-3">Terapkan</button>
                        <a href="<?= BASE_URL ?>modules/barang/index.php" class="btn btn-sm btn-light border px-3">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Barang -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle datatable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th style="width: 40px;">No</th>
                                <th>Kode & Barcode</th>
                                <th>Nama Barang & Spesifikasi</th>
                                <th>Kategori</th>
                                <th>Lokasi Rak</th>
                                <th class="text-center">Sisa Stok</th>
                                <th class="text-center">Status</th>
                                <th class="text-end" style="width: 130px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($barang_list as $b): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <div class="fw-bold text-dark font-monospace"><?= htmlspecialchars($b['kode_barang']) ?></div>
                                        <small class="text-muted font-monospace"><i class="bi bi-upc me-1"></i> <?= htmlspecialchars($b['barcode']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($b['nama_barang']) ?></div>
                                        <small class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($b['spesifikasi'] ?? '-') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($b['nama_kategori']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-persediaan-subtle text-persediaan border font-monospace">
                                            <i class="bi bi-grid-3x3-gap me-1 text-persediaan"></i> <?= htmlspecialchars($b['kode_rak']) ?>
                                        </span>
                                        <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($b['nama_rak']) ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="fs-6 fw-bold text-persediaan"><?= number_format($b['stok']) ?></span>
                                        <span class="text-muted small"><?= htmlspecialchars($b['nama_satuan']) ?></span>
                                        <div class="text-muted" style="font-size: 0.7rem;">Min: <?= $b['stok_minimum'] ?></div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($b['stok'] == 0): ?>
                                            <span class="badge bg-output-data px-2 py-1"><i class="bi bi-x-circle me-1"></i> Habis</span>
                                        <?php elseif ($b['stok'] <= $b['stok_minimum']): ?>
                                            <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-exclamation-triangle me-1"></i> Menipis</span>
                                        <?php else: ?>
                                            <span class="badge bg-input-data-subtle text-input-data px-2 py-1"><i class="bi bi-check2 me-1"></i> Aman</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <div class="d-inline-flex gap-1 justify-content-end">
                                            <!-- Tombol Kartu Stok (Persediaan: #184AC4) -->
                                            <a href="<?= BASE_URL ?>modules/barang/kartu_stok.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-persediaan d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; padding: 0;" title="Riwayat Kartu Stok">
                                                <i class="bi bi-journal-text" style="font-size: 0.78rem;"></i>
                                            </a>
                                            <!-- Tombol Cetak Label Barcode (Output Data: #C7260A) -->
                                            <a href="<?= BASE_URL ?>modules/barang/cetak_barcode.php?id=<?= $b['id'] ?>" target="_blank" class="btn btn-sm btn-outline-output-data d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; padding: 0;" title="Cetak Label Barcode">
                                                <i class="bi bi-printer" style="font-size: 0.78rem;"></i>
                                            </a>
                                            <?php if ($user_role === 'admin'): ?>
                                            <!-- Edit Data -->
                                            <a href="<?= BASE_URL ?>modules/barang/edit.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; padding: 0;" title="Ubah Data">
                                                <i class="bi bi-pencil" style="font-size: 0.78rem;"></i>
                                            </a>
                                            <!-- Hapus Data (Output Data: #C7260A) -->
                                            <a href="<?= BASE_URL ?>modules/barang/hapus.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-output-data btn-delete-confirm d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; padding: 0;" data-name="Barang <?= htmlspecialchars($b['nama_barang']) ?>" title="Hapus Barang">
                                                <i class="bi bi-trash" style="font-size: 0.78rem;"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>

