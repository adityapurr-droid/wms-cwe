<?php
/**
 * Riwayat Transaksi Barang Keluar WMS CWE
 * Politeknik Kelapa Sawit Citra Widya Edukasi
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';
require_once __DIR__ . '/../../helpers/format.php';

require_login();

$page_title = 'Pengeluaran Barang Keluar';
$current_page = 'barang_keluar';
$user_role = $_SESSION['user_role'] ?? 'operator';

// Filter Tanggal
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT 
        bk.*, 
        b.kode_barang, b.nama_barang, b.barcode,
        s.nama_satuan, 
        r.kode_rak, r.nama_rak,
        dept.nama_departemen, dept.kode_departemen,
        u.nama as nama_user
    FROM barang_keluar bk
    JOIN barang b ON bk.id_barang = b.id
    JOIN satuan s ON b.id_satuan = s.id
    JOIN rak r ON b.id_rak = r.id
    LEFT JOIN departemen dept ON bk.id_departemen = dept.id
    JOIN users u ON bk.user_id = u.id
    WHERE bk.tanggal_keluar BETWEEN ? AND ?
    ORDER BY bk.id DESC
");
$stmt->execute([$tgl_mulai, $tgl_selesai]);
$transaksi_list = $stmt->fetchAll();

// Hitung total unit dikeluarkan
$total_unit_periode = 0;
foreach ($transaksi_list as $row) {
    $total_unit_periode += $row['jumlah'];
}

require_once __DIR__ . '/../../layout/header.php';
require_once __DIR__ . '/../../layout/sidebar.php';
?>

<div id="content-wrapper">
    <?php require_once __DIR__ . '/../../layout/navbar.php'; ?>

    <main class="container-fluid px-4 py-4">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-box-arrow-up-right text-danger me-2"></i> Pengeluaran Barang Keluar (Gudang)</h4>
                <p class="text-muted small mb-0">Pencatatan distribusi sarana praktikum, alat laboratorium, pupuk, dan ATK ke unit pemohon di kampus CWE.</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <a href="<?= BASE_URL ?>modules/scanner/index.php" class="btn btn-outline-success rounded-pill fw-semibold">
                    <i class="bi bi-upc-scan me-1"></i> Scan Barcode
                </a>
                <a href="<?= BASE_URL ?>modules/barang_keluar/tambah.php" class="btn btn-danger rounded-pill shadow-sm">
                    <i class="bi bi-dash-circle me-1"></i> Catat Pengeluaran Baru
                </a>
            </div>
        </div>

        <!-- Filter Periode & Ringkasan -->
        <div class="card shadow-sm mb-4">
            <div class="card-body py-3">
                <form action="" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Dari Tanggal:</label>
                        <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_mulai) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Sampai Tanggal:</label>
                        <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_selesai) ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2 pt-md-3">
                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3">Filter</button>
                        <a href="<?= BASE_URL ?>modules/barang_keluar/index.php" class="btn btn-sm btn-light border rounded-pill px-3">Bulan Ini</a>
                    </div>
                    <div class="col-md-3 text-md-end pt-md-3">
                        <span class="badge bg-danger-subtle text-danger fs-6 px-3 py-2 rounded-pill">
                            Total Keluar: <strong><?= number_format($total_unit_periode) ?> Unit</strong>
                        </span>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Transaksi Keluar -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle datatable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th style="width: 40px;">No</th>
                                <th>No. Transaksi & Tanggal</th>
                                <th>Barang & Lokasi Rak</th>
                                <th>Unit / Departemen Pemohon</th>
                                <th>Peruntukan</th>
                                <th class="text-center" style="width: 100px;">Jumlah</th>
                                <th>Petugas</th>
                                <th class="text-end" style="width: 110px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($transaksi_list as $t): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <div class="fw-bold text-dark font-monospace"><?= htmlspecialchars($t['no_transaksi']) ?></div>
                                        <small class="text-muted"><i class="bi bi-calendar-event me-1"></i> <?= format_tanggal($t['tanggal_keluar']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($t['nama_barang']) ?></div>
                                        <small class="badge bg-light text-secondary border font-monospace"><?= htmlspecialchars($t['kode_barang']) ?></small>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace ms-1">
                                            Rak: <?= htmlspecialchars($t['kode_rak']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($t['nama_departemen'] ?? 'Unit Kampus') ?></div>
                                        <small class="badge bg-secondary-subtle text-secondary font-monospace"><?= htmlspecialchars($t['kode_departemen'] ?? '-') ?></small>
                                    </td>
                                    <td class="small">
                                        <span class="text-dark fw-semibold"><?= htmlspecialchars($t['peruntukan'] ?? '-') ?></span>
                                        <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($t['keterangan'] ?? '') ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger-subtle text-danger fs-6 fw-bold px-3 py-1 rounded-pill">
                                            -<?= number_format($t['jumlah']) ?> <?= htmlspecialchars($t['nama_satuan']) ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted">
                                        <i class="bi bi-person me-1"></i> <?= htmlspecialchars($t['nama_user']) ?>
                                    </td>
                                    <td class="text-end">
                                        <!-- Cetak Bukti BASTB -->
                                        <a href="<?= BASE_URL ?>modules/barang_keluar/cetak_nota.php?id=<?= $t['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-circle me-1" title="Cetak Berita Acara (BASTB)">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <?php if ($user_role === 'admin'): ?>
                                        <!-- Batalkan Transaksi (Kembalikan Stok) -->
                                        <a href="<?= BASE_URL ?>modules/barang_keluar/hapus.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle btn-delete-confirm" data-name="transaksi <?= htmlspecialchars($t['no_transaksi']) ?> (stok akan dikembalikan ke gudang)" title="Batalkan Pengeluaran">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <?php endif; ?>
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

