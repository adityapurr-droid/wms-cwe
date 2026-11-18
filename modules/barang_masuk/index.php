<?php
/**
 * Riwayat Transaksi Barang Masuk (Penerimaan 1 Pintu) WMS CWE
 * Politeknik Kelapa Sawit Citra Widya Edukasi
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';
require_once __DIR__ . '/../../helpers/format.php';

require_login();

$page_title = 'Penerimaan Barang Masuk';
$current_page = 'barang_masuk';
$user_role = $_SESSION['user_role'] ?? 'operator';

// Filter Tanggal jika ada
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT 
        bm.*, 
        b.kode_barang, b.nama_barang, b.barcode,
        s.nama_satuan, 
        r.kode_rak, r.nama_rak,
        sup.nama_supplier, 
        u.nama as nama_user
    FROM barang_masuk bm
    JOIN barang b ON bm.id_barang = b.id
    JOIN satuan s ON b.id_satuan = s.id
    JOIN rak r ON b.id_rak = r.id
    LEFT JOIN supplier sup ON bm.id_supplier = sup.id
    JOIN users u ON bm.user_id = u.id
    WHERE bm.tanggal_masuk BETWEEN ? AND ?
    ORDER BY bm.id DESC
");
$stmt->execute([$tgl_mulai, $tgl_selesai]);
$transaksi_list = $stmt->fetchAll();

// Hitung total unit diterima pada periode ini
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
                <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-box-arrow-in-down text-input-data me-2"></i> Penerimaan Barang </h4>
                <p class="text-muted small mb-0">Pencatatan pasokan masuk dari vendor/supplier yang diterima langsung di gudang CWE.</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <a href="<?= BASE_URL ?>modules/scanner/index.php" class="btn btn-outline-persediaan fw-semibold">
                    <i class="bi bi-upc-scan me-1"></i> Scan Barcode
                </a>
                <a href="<?= BASE_URL ?>modules/barang_masuk/tambah.php" class="btn btn-theme-main shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> Catat Penerimaan Baru
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
                        <button type="submit" class="btn btn-sm btn-theme-main px-3">Filter</button>
                        <a href="<?= BASE_URL ?>modules/barang_masuk/index.php" class="btn btn-sm btn-light border px-3">Bulan Ini</a>
                    </div>
                    <div class="col-md-3 text-md-end pt-md-3">
                        <span class="badge bg-input-data-subtle text-input-data fs-6 px-3 py-2">
                            Total Periode: <strong><?= number_format($total_unit_periode) ?> Unit</strong>
                        </span>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Transaksi Masuk -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle datatable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th style="width: 40px;">No</th>
                                <th>No. Transaksi & Tanggal</th>
                                <th>Barang & Lokasi Rak</th>
                                <th>Pemasok (Supplier)</th>
                                <th>No. Surat Jalan (DO)</th>
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
                                        <small class="text-muted"><i class="bi bi-calendar-event me-1"></i> <?= format_tanggal($t['tanggal_masuk']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($t['nama_barang']) ?></div>
                                        <small class="badge bg-light text-secondary border font-monospace"><?= htmlspecialchars($t['kode_barang']) ?></small>
                                        <span class="badge bg-persediaan-subtle text-persediaan border font-monospace ms-1">
                                            Rak: <?= htmlspecialchars($t['kode_rak']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($t['nama_supplier'] ?? 'Penerimaan Langsung') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($t['keterangan'] ?? '-') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($t['no_surat_jalan'] ?? '-') ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-input-data-subtle text-input-data fs-6 fw-bold px-2 py-1">
                                            +<?= number_format($t['jumlah']) ?> <?= htmlspecialchars($t['nama_satuan']) ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted">
                                        <i class="bi bi-person me-1"></i> <?= htmlspecialchars($t['nama_user']) ?>
                                    </td>
                                    <td class="text-end">
                                        <!-- Cetak Bukti Masuk / BASTB (Output Data: #C7260A) -->
                                        <a href="<?= BASE_URL ?>modules/barang_masuk/cetak_nota.php?id=<?= $t['id'] ?>" target="_blank" class="btn btn-sm btn-outline-output-data d-inline-flex align-items-center justify-content-center me-1" style="width: 28px; height: 28px; padding: 0;" title="Cetak Bukti Penerimaan">
                                            <i class="bi bi-printer" style="font-size: 0.8rem;"></i>
                                        </a>
                                        <?php if ($user_role === 'admin'): ?>
                                        <!-- Batalkan Transaksi (Hapus & Rollback Stok - Output/Hapus: #C7260A) -->
                                        <a href="<?= BASE_URL ?>modules/barang_masuk/hapus.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-output-data btn-delete-confirm d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; padding: 0;" data-name="transaksi <?= htmlspecialchars($t['no_transaksi']) ?> (stok akan dikurangi kembali)" title="Batalkan Transaksi">
                                            <i class="bi bi-trash" style="font-size: 0.8rem;"></i>
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

