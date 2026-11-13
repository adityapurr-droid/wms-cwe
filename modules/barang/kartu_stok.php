<?php
/**
 * Kartu Stok & Buku Mutasi Barang (Stock Ledger) WMS CWE
 * Politeknik Kelapa Sawit Citra Widya Edukasi
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';
require_once __DIR__ . '/../../helpers/format.php';

require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . BASE_URL . 'modules/barang/index.php');
    exit;
}

// Ambil Detail Barang
$stmt = $pdo->prepare("
    SELECT b.*, k.nama_kategori, s.nama_satuan, r.nama_rak, r.kode_rak
    FROM barang b
    JOIN kategori k ON b.id_kategori = k.id
    JOIN satuan s ON b.id_satuan = s.id
    JOIN rak r ON b.id_rak = r.id
    WHERE b.id = ?
");
$stmt->execute([$id]);
$barang = $stmt->fetch();

if (!$barang) {
    set_flash('error', 'Barang tidak ditemukan.');
    header('Location: ' . BASE_URL . 'modules/barang/index.php');
    exit;
}

$page_title = 'Kartu Stok - ' . $barang['nama_barang'];
$current_page = 'barang';

// Ambil Seluruh Mutasi Masuk
$stmt_in = $pdo->prepare("
    SELECT 
        bm.tanggal_masuk as tanggal,
        bm.created_at,
        bm.no_transaksi,
        'MASUK' as tipe,
        bm.jumlah as masuk,
        0 as keluar,
        COALESCE(s.nama_supplier, 'Penerimaan Langsung / Stok Awal') as pihak_terkait,
        bm.keterangan,
        u.nama as petugas
    FROM barang_masuk bm
    LEFT JOIN supplier s ON bm.id_supplier = s.id
    JOIN users u ON bm.user_id = u.id
    WHERE bm.id_barang = ?
");
$stmt_in->execute([$id]);
$mutasi_masuk = $stmt_in->fetchAll();

// Ambil Seluruh Mutasi Keluar
$stmt_out = $pdo->prepare("
    SELECT 
        bk.tanggal_keluar as tanggal,
        bk.created_at,
        bk.no_transaksi,
        'KELUAR' as tipe,
        0 as masuk,
        bk.jumlah as keluar,
        CONCAT(COALESCE(d.nama_departemen, 'Unit Kampus'), ' (', COALESCE(bk.peruntukan, '-'), ')') as pihak_terkait,
        bk.keterangan,
        u.nama as petugas
    FROM barang_keluar bk
    LEFT JOIN departemen d ON bk.id_departemen = d.id
    JOIN users u ON bk.user_id = u.id
    WHERE bk.id_barang = ?
");
$stmt_out->execute([$id]);
$mutasi_keluar = $stmt_out->fetchAll();

// Gabungkan dan Urutkan Kronologis
$mutasi = array_merge($mutasi_masuk, $mutasi_keluar);
usort($mutasi, function($a, $b) {
    $t1 = strtotime($a['tanggal'] . ' ' . date('H:i:s', strtotime($a['created_at'])));
    $t2 = strtotime($b['tanggal'] . ' ' . date('H:i:s', strtotime($b['created_at'])));
    return $t1 <=> $t2;
});

// Hitung Saldo Berjalan
$running_balance = 0;
foreach ($mutasi as &$m) {
    $running_balance = $running_balance + $m['masuk'] - $m['keluar'];
    $m['saldo'] = $running_balance;
}
unset($m);

require_once __DIR__ . '/../../layout/header.php';
require_once __DIR__ . '/../../layout/sidebar.php';
?>

<div id="content-wrapper">
    <?php require_once __DIR__ . '/../../layout/navbar.php'; ?>

    <main class="container-fluid px-4 py-4">
        <?= render_flash() ?>

        <!-- Gaya cetak: hilangkan antarmuka website bila halaman ini dicetak langsung (Ctrl+P) -->
        <style>
            @media print {
                @page { size: A4 landscape; margin: 10mm; }
                html, body { height: auto !important; overflow: visible !important; background: #fff !important; }
                #sidebar, #sidebarBackdrop, .topbar, footer, .no-print { display: none !important; }
                #wrapper { display: block !important; height: auto !important; overflow: visible !important; }
                #content-wrapper { display: block !important; height: auto !important; overflow: visible !important; }
                main.container-fluid { padding: 0 !important; }
                .card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
                .table-responsive { overflow: visible !important; }
                .table thead { display: table-header-group; }
                .table tr { page-break-inside: avoid; }
            }
        </style>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <a href="<?= BASE_URL ?>modules/barang/index.php" class="text-decoration-none text-muted small fw-semibold no-print">
                    &larr; Kembali ke Master Barang
                </a>
                <h4 class="fw-bold mt-2 text-dark"><i class="bi bi-journal-text text-success me-2"></i> Kartu Stok & Buku Mutasi Inventori</h4>
                <p class="text-muted small mb-0">Riwayat kronologis penambahan dan pengeluaran fisik barang di gudang CWE.</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2 no-print">
                <a href="<?= BASE_URL ?>modules/barang/cetak_kartu_stok.php?id=<?= $barang['id'] ?>" target="_blank" class="btn btn-outline-dark rounded-pill px-3">
                    <i class="bi bi-printer me-1"></i> Cetak Kartu Stok
                </a>
                <a href="<?= BASE_URL ?>modules/barang_masuk/tambah.php?id_barang=<?= $barang['id'] ?>" class="btn btn-success rounded-pill px-3">
                    <i class="bi bi-plus-circle me-1"></i> Terima Masuk
                </a>
                <a href="<?= BASE_URL ?>modules/barang_keluar/tambah.php?id_barang=<?= $barang['id'] ?>" class="btn btn-outline-danger rounded-pill px-3">
                    <i class="bi bi-dash-circle me-1"></i> Catat Keluar
                </a>
            </div>
        </div>

        <!-- Ringkasan Info Barang -->
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row g-3 align-items-center">
                    <div class="col-md-7">
                        <span class="badge bg-success-subtle text-success font-monospace px-2 py-1 mb-1"><?= htmlspecialchars($barang['kode_barang']) ?></span>
                        <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($barang['nama_barang']) ?></h4>
                        <div class="text-muted small mb-2">
                            <span><i class="bi bi-upc me-1"></i> Barcode: <strong><?= htmlspecialchars($barang['barcode']) ?></strong></span>
                            <span class="mx-2">&bull;</span>
                            <span>Kategori: <strong><?= htmlspecialchars($barang['nama_kategori']) ?></strong></span>
                        </div>
                        <p class="small text-secondary mb-0">Spesifikasi: <?= htmlspecialchars($barang['spesifikasi'] ?? '-') ?></p>
                    </div>

                    <div class="col-md-5">
                        <div class="p-3 bg-light rounded-3 border d-flex justify-content-around text-center">
                            <div>
                                <small class="text-muted d-block">Lokasi Rak:</small>
                                <span class="badge bg-primary fs-6 font-monospace mt-1">
                                    <i class="bi bi-grid-3x3-gap me-1"></i> <?= htmlspecialchars($barang['kode_rak']) ?>
                                </span>
                                <div class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($barang['nama_rak']) ?></div>
                            </div>
                            <div class="border-start ps-3">
                                <small class="text-muted d-block">Saldo Fisik Saat Ini:</small>
                                <span class="fs-4 fw-bold text-success mt-1 d-block"><?= number_format($barang['stok']) ?></span>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($barang['nama_satuan']) ?></span>
                            </div>
                            <div class="border-start ps-3">
                                <small class="text-muted d-block">Batas Minimum:</small>
                                <span class="fs-5 fw-bold text-secondary mt-1 d-block"><?= number_format($barang['stok_minimum']) ?></span>
                                <small class="text-muted"><?= htmlspecialchars($barang['nama_satuan']) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Riwayat Buku Mutasi -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark">Alur Kronologis Mutasi Fisik Barang</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light small text-uppercase text-center">
                            <tr>
                                <th style="width: 40px;">No</th>
                                <th>Tanggal</th>
                                <th>No. Transaksi</th>
                                <th>Pihak / Rekanan / Unit</th>
                                <th>Keterangan</th>
                                <th style="width: 90px;" class="text-success">Masuk (+)</th>
                                <th style="width: 90px;" class="text-danger">Keluar (-)</th>
                                <th style="width: 100px;" class="text-primary">Saldo Akhir</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mutasi)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">Belum ada catatan mutasi transaksi untuk barang ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($mutasi as $m): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td class="small"><?= format_tanggal($m['tanggal']) ?></td>
                                        <td class="font-monospace small fw-bold">
                                            <span class="badge <?= ($m['tipe'] === 'MASUK') ? 'bg-info-subtle text-info' : 'bg-danger-subtle text-danger' ?>">
                                                <?= htmlspecialchars($m['no_transaksi']) ?>
                                            </span>
                                        </td>
                                        <td class="small"><?= htmlspecialchars($m['pihak_terkait']) ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($m['keterangan'] ?? '-') ?></td>
                                        <td class="text-center text-success fw-bold">
                                            <?= ($m['masuk'] > 0) ? '+' . number_format($m['masuk']) : '-' ?>
                                        </td>
                                        <td class="text-center text-danger fw-bold">
                                            <?= ($m['keluar'] > 0) ? '-' . number_format($m['keluar']) : '-' ?>
                                        </td>
                                        <td class="text-center fw-bold bg-light text-dark">
                                            <?= number_format($m['saldo']) ?> <?= htmlspecialchars($barang['nama_satuan']) ?>
                                        </td>
                                        <td class="small text-muted"><?= htmlspecialchars($m['petugas']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>

