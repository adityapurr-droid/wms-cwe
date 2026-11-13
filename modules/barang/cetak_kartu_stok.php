<?php
/**
 * Cetak Kartu Stok & Buku Mutasi Barang (Print-Ready) WMS CWE
 * Politeknik Kelapa Sawit Citra Widya Edukasi
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
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
    header('Location: ' . BASE_URL . 'modules/barang/index.php');
    exit;
}

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
usort($mutasi, function ($a, $b) {
    $t1 = strtotime($a['tanggal'] . ' ' . date('H:i:s', strtotime($a['created_at'])));
    $t2 = strtotime($b['tanggal'] . ' ' . date('H:i:s', strtotime($b['created_at'])));
    return $t1 <=> $t2;
});

// Hitung Saldo Berjalan & Total Mutasi
$running_balance = 0;
$total_masuk = 0;
$total_keluar = 0;
foreach ($mutasi as &$m) {
    $m['masuk'] = (int)$m['masuk'];
    $m['keluar'] = (int)$m['keluar'];
    $running_balance = $running_balance + $m['masuk'] - $m['keluar'];
    $m['saldo'] = $running_balance;
    $total_masuk += $m['masuk'];
    $total_keluar += $m['keluar'];
}
unset($m);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Stok - <?= htmlspecialchars($barang['nama_barang']) ?> - <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>assets/img/logo-cwe.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8f9fa;
            color: #212529;
            padding: 20px;
        }
        .document-page {
            max-width: 1000px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px 38px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            border-radius: 8px;
        }
        .kop-surat {
            border-bottom: 3px double #0f5132;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .judul-dokumen {
            text-decoration: underline;
            letter-spacing: 0.5px;
        }
        .info-table th {
            width: 130px;
            background-color: #f1f7f4;
            color: #0f5132;
            font-weight: 600;
            white-space: nowrap;
        }
        .info-table td, .info-table th {
            padding: 0.4rem 0.6rem;
            font-size: 0.85rem;
        }
        .table-print {
            font-size: 0.78rem;
            margin-bottom: 0;
        }
        .table-print thead th {
            background-color: #e7f3ec;
            color: #0f5132;
            text-align: center;
            vertical-align: middle;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }
        .table-print tbody td {
            vertical-align: middle;
        }
        .table-print tfoot td {
            font-size: 0.8rem;
        }

        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .document-page {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
                border-radius: 0;
            }
            .table-print thead {
                display: table-header-group;
            }
            .table-print tr {
                page-break-inside: avoid;
            }
            a[href]::after {
                content: "";
            }
        }
    </style>
</head>
<body>

<div class="container text-center no-print mb-4">
    <button class="btn btn-success fw-bold px-4 me-2" onclick="window.print()">
        <i class="bi bi-printer"></i> Cetak Kartu Stok
    </button>
    <button class="btn btn-light border px-4" onclick="window.close()">
        Tutup
    </button>
</div>

<div class="document-page">
    <!-- Kop Surat Resmi -->
    <div class="kop-surat d-flex align-items-center">
        <div class="me-3">
            <img src="<?= BASE_URL ?>assets/img/logo-cwe.png" alt="Logo CWE" style="height: 72px; object-fit: contain;">
        </div>
        <div>
            <h5 class="fw-bold mb-0 text-success text-uppercase"><?= INSTITUTION_NAME ?></h5>
            <small class="text-muted d-block fw-semibold"><?= INSTITUTION_TAGLINE ?></small>
            <div class="small text-secondary" style="font-size: 0.75rem;"><?= INSTITUTION_ADDRESS ?></div>
            <div class="small text-secondary" style="font-size: 0.72rem;"><?= INSTITUTION_CONTACT ?></div>
        </div>
    </div>

    <!-- Judul Dokumen -->
    <div class="text-center mb-3">
        <h5 class="fw-bold text-dark text-uppercase judul-dokumen mb-1">Kartu Stok &amp; Buku Mutasi Barang</h5>
        <div class="text-secondary small">Dicetak: <?= format_tanggal(date('Y-m-d'), true) ?></div>
        <small class="text-muted font-monospace">Penyimpanan: Gudang Terpusat 1 Pintu CWE</small>
    </div>

    <!-- Identitas Barang -->
    <table class="table table-sm table-bordered info-table mb-3">
        <tbody>
            <tr>
                <th>Kode Barang</th>
                <td class="font-monospace fw-semibold"><?= htmlspecialchars($barang['kode_barang']) ?></td>
                <th>Barcode</th>
                <td class="font-monospace fw-semibold"><?= htmlspecialchars($barang['barcode']) ?></td>
            </tr>
            <tr>
                <th>Nama Barang</th>
                <td colspan="3" class="fw-bold"><?= htmlspecialchars($barang['nama_barang']) ?></td>
            </tr>
            <tr>
                <th>Kategori</th>
                <td><?= htmlspecialchars($barang['nama_kategori']) ?></td>
                <th>Lokasi Rak</th>
                <td><span class="font-monospace fw-semibold"><?= htmlspecialchars($barang['kode_rak']) ?></span> &mdash; <?= htmlspecialchars($barang['nama_rak']) ?></td>
            </tr>
            <tr>
                <th>Spesifikasi</th>
                <td colspan="3"><?= htmlspecialchars($barang['spesifikasi'] ?: '-') ?></td>
            </tr>
            <tr>
                <th>Saldo Fisik</th>
                <td class="fw-bold text-success"><?= number_format($barang['stok']) ?> <?= htmlspecialchars($barang['nama_satuan']) ?></td>
                <th>Batas Minimum</th>
                <td class="fw-semibold text-secondary"><?= number_format($barang['stok_minimum']) ?> <?= htmlspecialchars($barang['nama_satuan']) ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Tabel Mutasi -->
    <table class="table table-bordered table-print align-middle">
        <thead>
            <tr>
                <th style="width: 32px;">No</th>
                <th style="width: 82px;">Tanggal</th>
                <th>No. Transaksi</th>
                <th>Pihak / Rekanan / Unit</th>
                <th>Keterangan</th>
                <th style="width: 65px;">Masuk (+)</th>
                <th style="width: 65px;">Keluar (-)</th>
                <th style="width: 85px;">Saldo</th>
                <th style="width: 90px;">Petugas</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($mutasi)): ?>
                <tr>
                    <td colspan="9" class="text-center py-3 text-muted">Belum ada catatan mutasi transaksi untuk barang ini.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($mutasi as $m): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= format_tanggal($m['tanggal']) ?></td>
                        <td class="font-monospace fw-semibold"><?= htmlspecialchars($m['no_transaksi']) ?></td>
                        <td><?= htmlspecialchars($m['pihak_terkait']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($m['keterangan'] ?: '-') ?></td>
                        <td class="text-center fw-bold text-success">
                            <?= ($m['masuk'] > 0) ? '+' . number_format($m['masuk']) : '-' ?>
                        </td>
                        <td class="text-center fw-bold text-danger">
                            <?= ($m['keluar'] > 0) ? '-' . number_format($m['keluar']) : '-' ?>
                        </td>
                        <td class="text-center fw-bold"><?= number_format($m['saldo']) ?></td>
                        <td><?= htmlspecialchars($m['petugas']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="table-light fw-bold">
                <td colspan="5" class="text-end">Total Mutasi:</td>
                <td class="text-center text-success">+<?= number_format($total_masuk) ?></td>
                <td class="text-center text-danger">-<?= number_format($total_keluar) ?></td>
                <td class="text-center"><?= number_format($running_balance) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <div class="text-end small text-muted mt-1">
        Saldo akhir dalam <?= htmlspecialchars($barang['nama_satuan']) ?>.
    </div>

    <!-- Tanda Tangan -->
    <div class="row text-center mt-5 small">
        <div class="col-6">
            <p class="mb-1 text-muted">Mengetahui,</p>
            <p class="mb-0 fw-bold">Kepala Bagian Sarana &amp; Logistik</p>
            <div style="height: 65px;"></div>
            <p class="fw-bold mb-0">( ......................................... )</p>
        </div>
        <div class="col-6">
            <p class="mb-1 text-muted">Bekasi, <?= format_tanggal(date('Y-m-d')) ?></p>
            <p class="mb-0 fw-bold">Petugas Administrasi Gudang</p>
            <div style="height: 65px;"></div>
            <p class="fw-bold mb-0">( <?= htmlspecialchars($_SESSION['user_nama'] ?? 'Petugas Logistik') ?> )</p>
        </div>
    </div>
</div>

</body>
</html>
