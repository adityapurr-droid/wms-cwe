<?php
/**
 * Berita Acara Serah Terima Barang (BASTB / Cetak Pengeluaran) WMS CWE
 * Politeknik Kelapa Sawit Citra Widya Edukasi
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/format.php';

require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Transaksi tidak valid.");
}

$stmt = $pdo->prepare("
    SELECT 
        bk.*, 
        b.kode_barang, b.nama_barang, b.barcode, b.spesifikasi,
        s.nama_satuan, 
        r.kode_rak, r.nama_rak,
        dept.nama_departemen, dept.kode_departemen, dept.penanggung_jawab as pic_dept, dept.kontak as kontak_dept,
        u.nama as nama_petugas
    FROM barang_keluar bk
    JOIN barang b ON bk.id_barang = b.id
    JOIN satuan s ON b.id_satuan = s.id
    JOIN rak r ON b.id_rak = r.id
    LEFT JOIN departemen dept ON bk.id_departemen = dept.id
    JOIN users u ON bk.user_id = u.id
    WHERE bk.id = ?
");
$stmt->execute([$id]);
$t = $stmt->fetch();

if (!$t) {
    die("Data transaksi tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara Serah Terima Barang - <?= htmlspecialchars($t['no_transaksi']) ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
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
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px 50px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border-radius: 8px;
        }
        .kop-surat {
            border-bottom: 3px double #0f5132;
            padding-bottom: 12px;
            margin-bottom: 25px;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .document-page {
                box-shadow: none;
                padding: 20px 30px;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="container text-center no-print mb-4">
    <button class="btn btn-success fw-bold px-4 me-2" onclick="window.print()">
        <i class="bi bi-printer"></i> Cetak BASTB (Print)
    </button>
    <button class="btn btn-light border px-4" onclick="window.close()">
        Tutup
    </button>
</div>

<div class="document-page">
    <!-- Kop Surat Resmi Kampus CWE -->
    <div class="kop-surat d-flex align-items-center">
        <div class="me-3">
            <img src="<?= BASE_URL ?>assets/img/logo-cwe.png" alt="Logo CWE" style="height: 75px; object-fit: contain;">
        </div>
        <div>
            <h5 class="fw-bold mb-0 text-success text-uppercase"><?= INSTITUTION_NAME ?></h5>
            <small class="text-muted d-block fw-semibold"><?= INSTITUTION_TAGLINE ?></small>
            <div class="small text-secondary" style="font-size: 0.75rem;"><?= INSTITUTION_ADDRESS ?></div>
            <div class="small text-secondary" style="font-size: 0.72rem;"><?= INSTITUTION_CONTACT ?></div>
        </div>
    </div>

    <!-- Judul Dokumen -->
    <div class="text-center mb-4">
        <h5 class="fw-bold text-dark text-uppercase mb-1" style="text-decoration: underline;">BERITA ACARA SERAH TERIMA BARANG (BASTB)</h5>
        <div class="font-monospace fw-semibold text-secondary small">Nomor Dokumen: <?= htmlspecialchars($t['no_transaksi']) ?></div>
    </div>

    <!-- Informasi Pengeluaran & Pemohon -->
    <div class="row g-3 mb-4 small">
        <div class="col-6">
            <table class="table table-sm table-borderless mb-0">
                <tr>
                    <td style="width: 140px;" class="text-muted">Tanggal Penyerahan</td>
                    <td>: <strong><?= format_tanggal($t['tanggal_keluar']) ?></strong></td>
                </tr>
                <tr>
                    <td class="text-muted">Gudang Pengeluaran</td>
                    <td>: <strong>Pintu Gudang Terpusat CWE</strong></td>
                </tr>
                <tr>
                    <td class="text-muted">Petugas Gudang</td>
                    <td>: <?= htmlspecialchars($t['nama_petugas']) ?></td>
                </tr>
            </table>
        </div>
        <div class="col-6">
            <table class="table table-sm table-borderless mb-0">
                <tr>
                    <td style="width: 140px;" class="text-muted">Unit / Prodi Pemohon</td>
                    <td>: <strong><?= htmlspecialchars($t['nama_departemen'] ?? 'Unit Kampus') ?></strong></td>
                </tr>
                <tr>
                    <td class="text-muted">Peruntukan / Kegiatan</td>
                    <td>: <span class="fw-bold text-dark"><?= htmlspecialchars($t['peruntukan'] ?? '-') ?></span></td>
                </tr>
                <tr>
                    <td class="text-muted">Penanggung Jawab</td>
                    <td>: <?= htmlspecialchars($t['pic_dept'] ?? '-') ?> (<?= htmlspecialchars($t['kontak_dept'] ?? '-') ?>)</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Tabel Rincian Barang Dikeluarkan -->
    <table class="table table-bordered align-middle mb-4">
        <thead class="table-light small text-uppercase text-center">
            <tr>
                <th style="width: 40px;">No</th>
                <th>Kode Barang</th>
                <th>Nama Barang & Spesifikasi</th>
                <th>Sumber Rak Simpan</th>
                <th style="width: 120px;">Jumlah Dikeluarkan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td class="font-monospace fw-bold"><?= htmlspecialchars($t['kode_barang']) ?></td>
                <td>
                    <strong><?= htmlspecialchars($t['nama_barang']) ?></strong>
                    <div class="text-muted small"><?= htmlspecialchars($t['spesifikasi'] ?? '-') ?></div>
                </td>
                <td class="text-center font-monospace">
                    <strong><?= htmlspecialchars($t['kode_rak']) ?></strong>
                    <div class="small text-muted"><?= htmlspecialchars($t['nama_rak']) ?></div>
                </td>
                <td class="text-center fw-bold fs-6 text-danger">
                    <?= number_format($t['jumlah']) ?> <?= htmlspecialchars($t['nama_satuan']) ?>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="p-3 bg-light rounded mb-5 small">
        <strong>Catatan / Keterangan Penyerahan:</strong><br>
        <span class="text-muted"><?= nl2br(htmlspecialchars($t['keterangan'] ?? 'Barang telah diperiksa dan diserahkan dalam kondisi baik untuk kelancaran kegiatan operasional/praktikum.')) ?></span>
    </div>

    <!-- Lembar Tanda Tangan -->
    <div class="row text-center mt-5">
        <div class="col-6">
            <p class="mb-1 text-muted small">Pihak Penerima (Dosen / Mahasiswa / Unit),</p>
            <div style="height: 75px;"></div>
            <p class="fw-bold mb-0 text-decoration-underline">( <?= htmlspecialchars($t['pic_dept'] ?? '..................................') ?> )</p>
            <small class="text-muted"><?= htmlspecialchars($t['nama_departemen'] ?? 'Unit Pemohon') ?></small>
        </div>
        <div class="col-6">
            <p class="mb-1 text-muted small">Pihak Yang Menyerahkan (Petugas Gudang),</p>
            <div style="height: 75px;"></div>
            <p class="fw-bold mb-0 text-decoration-underline">( <?= htmlspecialchars($t['nama_petugas']) ?> )</p>
            <small class="text-muted">Unit Logistik & Pergudangan CWE</small>
        </div>
    </div>
</div>

</body>
</html>

