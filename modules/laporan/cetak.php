<?php
/**
 * Cetak Laporan Resmi Rekapitulasi Inventori WMS CWE
 * Politeknik Kelapa Sawit Citra Widya Edukasi
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/format.php';

require_login();

$jenis_laporan = $_GET['jenis'] ?? 'semua';
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
$filter_kategori = (int)($_GET['kategori'] ?? 0);
$filter_rak = (int)($_GET['rak'] ?? 0);

$judul_laporan = "REKAPITULASI MUTASI INVENTORI GUDANG";
if ($jenis_laporan === 'masuk') {
    $judul_laporan = "LAPORAN PENERIMAAN BARANG MASUK";
} elseif ($jenis_laporan === 'keluar') {
    $judul_laporan = "LAPORAN PENGELUARAN BARANG KELUAR";
} elseif ($jenis_laporan === 'stok') {
    $judul_laporan = "LAPORAN POSISI SALDO STOK RAK GUDANG";
}

$data_laporan = [];
$total_masuk = 0;
$total_keluar = 0;

if ($jenis_laporan === 'stok') {
    $sql = "
        SELECT b.*, k.nama_kategori, s.nama_satuan, r.kode_rak, r.nama_rak
        FROM barang b
        JOIN kategori k ON b.id_kategori = k.id
        JOIN satuan s ON b.id_satuan = s.id
        JOIN rak r ON b.id_rak = r.id
        WHERE 1=1
    ";
    $p = [];
    if ($filter_kategori > 0) {
        $sql .= " AND b.id_kategori = ?";
        $p[] = $filter_kategori;
    }
    if ($filter_rak > 0) {
        $sql .= " AND b.id_rak = ?";
        $p[] = $filter_rak;
    }
    $sql .= " ORDER BY b.id_rak ASC, b.nama_barang ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($p);
    $data_laporan = $stmt->fetchAll();
} else {
    $list_in = [];
    $list_out = [];

    if ($jenis_laporan === 'semua' || $jenis_laporan === 'masuk') {
        $sql_in = "
            SELECT 
                bm.tanggal_masuk as tanggal,
                bm.created_at,
                bm.no_transaksi,
                'MASUK' as tipe,
                b.kode_barang, b.nama_barang,
                s.nama_satuan,
                r.kode_rak,
                bm.jumlah,
                COALESCE(sup.nama_supplier, 'Penerimaan Langsung') as pihak_terkait,
                bm.no_surat_jalan as ref_dokumen,
                bm.keterangan,
                u.nama as nama_petugas
            FROM barang_masuk bm
            JOIN barang b ON bm.id_barang = b.id
            JOIN satuan s ON b.id_satuan = s.id
            JOIN rak r ON b.id_rak = r.id
            LEFT JOIN supplier sup ON bm.id_supplier = sup.id
            JOIN users u ON bm.user_id = u.id
            WHERE bm.tanggal_masuk BETWEEN ? AND ?
        ";
        $p_in = [$tgl_mulai, $tgl_selesai];
        if ($filter_kategori > 0) {
            $sql_in .= " AND b.id_kategori = ?";
            $p_in[] = $filter_kategori;
        }
        if ($filter_rak > 0) {
            $sql_in .= " AND b.id_rak = ?";
            $p_in[] = $filter_rak;
        }
        $stmt_in = $pdo->prepare($sql_in);
        $stmt_in->execute($p_in);
        $list_in = $stmt_in->fetchAll();
    }

    if ($jenis_laporan === 'semua' || $jenis_laporan === 'keluar') {
        $sql_out = "
            SELECT 
                bk.tanggal_keluar as tanggal,
                bk.created_at,
                bk.no_transaksi,
                'KELUAR' as tipe,
                b.kode_barang, b.nama_barang,
                s.nama_satuan,
                r.kode_rak,
                bk.jumlah,
                COALESCE(dept.nama_departemen, 'Unit Kampus') as pihak_terkait,
                bk.peruntukan as ref_dokumen,
                bk.keterangan,
                u.nama as nama_petugas
            FROM barang_keluar bk
            JOIN barang b ON bk.id_barang = b.id
            JOIN satuan s ON b.id_satuan = s.id
            JOIN rak r ON b.id_rak = r.id
            LEFT JOIN departemen dept ON bk.id_departemen = dept.id
            JOIN users u ON bk.user_id = u.id
            WHERE bk.tanggal_keluar BETWEEN ? AND ?
        ";
        $p_out = [$tgl_mulai, $tgl_selesai];
        if ($filter_kategori > 0) {
            $sql_out .= " AND b.id_kategori = ?";
            $p_out[] = $filter_kategori;
        }
        if ($filter_rak > 0) {
            $sql_out .= " AND b.id_rak = ?";
            $p_out[] = $filter_rak;
        }
        $stmt_out = $pdo->prepare($sql_out);
        $stmt_out->execute($p_out);
        $list_out = $stmt_out->fetchAll();
    }

    $data_laporan = array_merge($list_in, $list_out);
    usort($data_laporan, function($a, $b) {
        $t1 = strtotime($a['tanggal'] . ' ' . date('H:i:s', strtotime($a['created_at'])));
        $t2 = strtotime($b['tanggal'] . ' ' . date('H:i:s', strtotime($b['created_at'])));
        return $t1 <=> $t2;
    });

    foreach ($data_laporan as $d) {
        if ($d['tipe'] === 'MASUK') $total_masuk += $d['jumlah'];
        else $total_keluar += $d['jumlah'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $judul_laporan ?> - <?= APP_NAME ?></title>
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
            max-width: 950px;
            margin: 0 auto;
            background: #ffffff;
            padding: 35px 45px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border-radius: 8px;
        }
        .kop-surat {
            border-bottom: 3px double #0f5132;
            padding-bottom: 12px;
            margin-bottom: 25px;
        }
        @media print {
            body { background: #ffffff; padding: 0; }
            .document-page { box-shadow: none; padding: 15px 25px; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container text-center no-print mb-4">
    <button class="btn btn-success fw-bold px-4 me-2" onclick="window.print()">
        <i class="bi bi-printer"></i> Cetak Laporan (Print)
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

    <!-- Judul Dokumen & Periode -->
    <div class="text-center mb-4">
        <h5 class="fw-bold text-dark text-uppercase mb-1" style="text-decoration: underline;"><?= $judul_laporan ?></h5>
        <?php if ($jenis_laporan !== 'stok'): ?>
            <div class="text-secondary small">Periode: <?= format_tanggal($tgl_mulai) ?> s/d <?= format_tanggal($tgl_selesai) ?></div>
        <?php else: ?>
            <div class="text-secondary small">Posisi Data: Tanggal <?= format_tanggal(date('Y-m-d')) ?></div>
        <?php endif; ?>
        <small class="text-muted font-monospace">Penyimpanan: Gudang Terpusat 1 Pintu CWE</small>
    </div>

    <!-- Tabel Data -->
    <table class="table table-bordered table-sm align-middle mb-4" style="font-size: 0.85rem;">
        <?php if ($jenis_laporan === 'stok'): ?>
            <thead class="table-light text-center small text-uppercase">
                <tr>
                    <th style="width: 35px;">No</th>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Rak Simpan</th>
                    <th style="width: 90px;">Sisa Stok</th>
                    <th style="width: 70px;">Min</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($data_laporan as $r): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="font-monospace fw-bold"><?= htmlspecialchars($r['kode_barang']) ?></td>
                        <td><?= htmlspecialchars($r['nama_barang']) ?></td>
                        <td><?= htmlspecialchars($r['nama_kategori']) ?></td>
                        <td class="text-center font-monospace"><?= htmlspecialchars($r['kode_rak']) ?></td>
                        <td class="text-center fw-bold"><?= number_format($r['stok']) ?> <?= htmlspecialchars($r['nama_satuan']) ?></td>
                        <td class="text-center text-muted"><?= $r['stok_minimum'] ?></td>
                        <td class="text-center">
                            <?= ($r['stok'] == 0) ? 'Habis' : (($r['stok'] <= $r['stok_minimum']) ? 'Menipis' : 'Aman') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        <?php else: ?>
            <thead class="table-light text-center small text-uppercase">
                <tr>
                    <th style="width: 35px;">No</th>
                    <th>Tanggal</th>
                    <th>No. Transaksi</th>
                    <th>Tipe</th>
                    <th>Barang & Rak</th>
                    <th>Pihak / Rekanan / Unit</th>
                    <th style="width: 80px;">Jumlah</th>
                    <th>Petugas</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($data_laporan as $r): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= format_tanggal($r['tanggal']) ?></td>
                        <td class="font-monospace fw-semibold"><?= htmlspecialchars($r['no_transaksi']) ?></td>
                        <td class="text-center fw-bold <?= ($r['tipe'] === 'MASUK') ? 'text-success' : 'text-danger' ?>">
                            <?= $r['tipe'] ?>
                        </td>
                        <td><?= htmlspecialchars($r['nama_barang']) ?> (<?= htmlspecialchars($r['kode_rak']) ?>)</td>
                        <td><?= htmlspecialchars($r['pihak_terkait']) ?></td>
                        <td class="text-center fw-bold <?= ($r['tipe'] === 'MASUK') ? 'text-success' : 'text-danger' ?>">
                            <?= ($r['tipe'] === 'MASUK' ? '+' : '-') . number_format($r['jumlah']) ?> <?= htmlspecialchars($r['nama_satuan']) ?>
                        </td>
                        <td><?= htmlspecialchars($r['nama_petugas']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-light fw-bold">
                    <td colspan="6" class="text-end">Total Mutasi Unit:</td>
                    <td class="text-center">
                        <span class="text-success">+<?= number_format($total_masuk) ?></span> / 
                        <span class="text-danger">-<?= number_format($total_keluar) ?></span>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>

    <!-- Tanda Tangan Pengesahan -->
    <div class="row text-center mt-5 small">
        <div class="col-6">
            <p class="mb-1 text-muted">Mengetahui,</p>
            <p class="mb-0 fw-bold">Kepala Bagian Sarana & Logistik CWE</p>
            <div style="height: 70px;"></div>
            <p class="fw-bold mb-0 text-decoration-underline">( Dr. Ir. Sugeng Priyono, M.Si )</p>
            <small class="text-muted">NIDN. 0412097001</small>
        </div>
        <div class="col-6">
            <p class="mb-1 text-muted">Bekasi, <?= format_tanggal(date('Y-m-d')) ?></p>
            <p class="mb-0 fw-bold">Petugas Administrasi Gudang</p>
            <div style="height: 70px;"></div>
            <p class="fw-bold mb-0 text-decoration-underline">( <?= htmlspecialchars($_SESSION['user_nama'] ?? 'Petugas Logistik') ?> )</p>
            <small class="text-muted">Unit Logistik Kampus CWE</small>
        </div>
    </div>
</div>

</body>
</html>

