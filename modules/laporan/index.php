<?php
/**
 * Modul Rekapitulasi Laporan Inventori & Mutasi WMS CWE
 * Politeknik Kelapa Sawit Citra Widya Edukasi
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';
require_once __DIR__ . '/../../helpers/format.php';

require_login();

$page_title = 'Rekapitulasi Laporan';
$current_page = 'laporan';

// Parameter Filter
$jenis_laporan = $_GET['jenis'] ?? 'semua'; // semua, masuk, keluar, stok
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
$filter_kategori = (int)($_GET['kategori'] ?? 0);
$filter_rak = (int)($_GET['rak'] ?? 0);

$kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC")->fetchAll();
$rak_list = $pdo->query("SELECT * FROM rak ORDER BY kode_rak ASC")->fetchAll();

$data_laporan = [];
$total_masuk = 0;
$total_keluar = 0;

if ($jenis_laporan === 'stok') {
    // Laporan Posisi Saldo Stok Saat Ini
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
    // Laporan Mutasi (Masuk, Keluar, atau Semua)
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
        return $t2 <=> $t1; // Terbaru di atas
    });

    foreach ($data_laporan as $d) {
        if ($d['tipe'] === 'MASUK') {
            $total_masuk += $d['jumlah'];
        } else {
            $total_keluar += $d['jumlah'];
        }
    }
}

// Build URL Query String untuk tombol Cetak & Export
$query_params = http_build_query([
    'jenis'      => $jenis_laporan,
    'tgl_mulai'  => $tgl_mulai,
    'tgl_selesai'=> $tgl_selesai,
    'kategori'   => $filter_kategori,
    'rak'        => $filter_rak
]);

require_once __DIR__ . '/../../layout/header.php';
require_once __DIR__ . '/../../layout/sidebar.php';
?>

<div id="content-wrapper">
    <?php require_once __DIR__ . '/../../layout/navbar.php'; ?>

    <main class="container-fluid px-4 py-4">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-file-earmark-bar-graph text-output-data me-2"></i> Rekapitulasi Laporan Inventori & Mutasi</h4>
                <p class="text-muted small mb-0">Laporan transaksi logistik pergudangan 1 pintu Politeknik Kelapa Sawit Citra Widya Edukasi.</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <a href="<?= BASE_URL ?>modules/laporan/cetak.php?<?= $query_params ?>" target="_blank" class="btn btn-outline-output-data px-3">
                    <i class="bi bi-printer me-1"></i> Cetak Dokumen Resmi
                </a>
                <a href="<?= BASE_URL ?>modules/laporan/export_excel.php?<?= $query_params ?>" class="btn btn-output-data px-3 fw-semibold">
                    <i class="bi bi-file-earmark-excel me-1"></i> Ekspor ke Excel
                </a>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-funnel text-persediaan me-1"></i> Parameter Kriteria Laporan</h6>
            </div>
            <div class="card-body">
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted">Jenis Laporan:</label>
                        <select name="jenis" class="form-select form-select-sm">
                            <option value="semua" <?= ($jenis_laporan === 'semua') ? 'selected' : '' ?>>Semua Mutasi (Masuk & Keluar)</option>
                            <option value="masuk" <?= ($jenis_laporan === 'masuk') ? 'selected' : '' ?>>Khusus Penerimaan Barang Masuk</option>
                            <option value="keluar" <?= ($jenis_laporan === 'keluar') ? 'selected' : '' ?>>Khusus Pengeluaran Barang Keluar</option>
                            <option value="stok" <?= ($jenis_laporan === 'stok') ? 'selected' : '' ?>>Posisi Saldo Stok Saat Ini (Per Rak)</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted">Dari Tanggal:</label>
                        <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_mulai) ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted">Sampai Tanggal:</label>
                        <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_selesai) ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted">Kategori:</label>
                        <select name="kategori" class="form-select form-select-sm">
                            <option value="0">-- Semua Kategori --</option>
                            <?php foreach ($kategori_list as $k): ?>
                                <option value="<?= $k['id'] ?>" <?= ($filter_kategori == $k['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted">Lokasi Rak:</label>
                        <select name="rak" class="form-select form-select-sm">
                            <option value="0">-- Semua Rak --</option>
                            <?php foreach ($rak_list as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= ($filter_rak == $r['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['kode_rak']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-sm btn-theme-main w-100">Terapkan</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($jenis_laporan !== 'stok'): ?>
        <!-- Metric Cards Hasil Filter -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card p-3 border-start border-4 border-input-data">
                    <span class="text-muted small text-uppercase">Total Barang Masuk Periode Ini</span>
                    <h3 class="fw-bold text-input-data mb-0 mt-1">+<?= number_format($total_masuk) ?> <span class="fs-6 fw-normal text-muted">Unit</span></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 border-start border-4 border-output-data">
                    <span class="text-muted small text-uppercase">Total Barang Keluar Periode Ini</span>
                    <h3 class="fw-bold text-output-data mb-0 mt-1">-<?= number_format($total_keluar) ?> <span class="fs-6 fw-normal text-muted">Unit</span></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 border-start border-4 border-success">
                    <span class="text-muted small text-uppercase">Perubahan Bersih (Net Mutasi)</span>
                    <h3 class="fw-bold text-success mb-0 mt-1"><?= ($total_masuk - $total_keluar >= 0 ? '+' : '') . number_format($total_masuk - $total_keluar) ?> <span class="fs-6 fw-normal text-muted">Unit</span></h3>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabel Hasil Rekapitulasi -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark">Hasil Rekapitulasi Data</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <?php if ($jenis_laporan === 'stok'): ?>
                        <!-- Tabel Saldo Stok Fisik -->
                        <table class="table table-hover align-middle datatable">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th style="width: 40px;">No</th>
                                    <th>Kode & Barcode</th>
                                    <th>Nama Barang & Spesifikasi</th>
                                    <th>Kategori</th>
                                    <th>Lokasi Rak</th>
                                    <th class="text-center">Sisa Stok Fisik</th>
                                    <th class="text-center">Batas Minimum</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($data_laporan as $row): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td class="font-monospace fw-bold"><?= htmlspecialchars($row['kode_barang']) ?><br><small class="text-muted"><?= htmlspecialchars($row['barcode']) ?></small></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_barang']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($row['spesifikasi'] ?? '-') ?></small>
                                        </td>
                                        <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($row['nama_kategori']) ?></span></td>
                                        <td><span class="badge bg-primary-subtle text-primary font-monospace"><?= htmlspecialchars($row['kode_rak']) ?></span></td>
                                        <td class="text-center fw-bold fs-6"><?= number_format($row['stok']) ?> <?= htmlspecialchars($row['nama_satuan']) ?></td>
                                        <td class="text-center text-muted"><?= number_format($row['stok_minimum']) ?></td>
                                        <td class="text-center">
                                            <?php if ($row['stok'] == 0): ?>
                                                <span class="badge bg-danger">Habis</span>
                                            <?php elseif ($row['stok'] <= $row['stok_minimum']): ?>
                                                <span class="badge bg-warning text-dark">Kritis</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Aman</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <!-- Tabel Mutasi Transaksi -->
                        <table class="table table-hover align-middle datatable">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th style="width: 40px;">No</th>
                                    <th>Tanggal & No. Trx</th>
                                    <th>Jenis</th>
                                    <th>Barang & Rak</th>
                                    <th>Pihak / Rekanan / Departemen</th>
                                    <th>Referensi / Peruntukan</th>
                                    <th class="text-center">Jumlah</th>
                                    <th>Petugas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($data_laporan as $row): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <span class="font-monospace fw-bold text-dark"><?= htmlspecialchars($row['no_transaksi']) ?></span>
                                            <div class="text-muted small"><?= format_tanggal($row['tanggal']) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge <?= ($row['tipe'] === 'MASUK') ? 'bg-info-subtle text-info' : 'bg-danger-subtle text-danger' ?>">
                                                <?= $row['tipe'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_barang']) ?></div>
                                            <small class="badge bg-light text-secondary border font-monospace"><?= htmlspecialchars($row['kode_rak']) ?></small>
                                        </td>
                                        <td class="small fw-semibold"><?= htmlspecialchars($row['pihak_terkait']) ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($row['ref_dokumen'] ?? '-') ?></td>
                                        <td class="text-center">
                                            <span class="fw-bold <?= ($row['tipe'] === 'MASUK') ? 'text-success' : 'text-danger' ?>">
                                                <?= ($row['tipe'] === 'MASUK' ? '+' : '-') . number_format($row['jumlah']) ?> <?= htmlspecialchars($row['nama_satuan']) ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted"><?= htmlspecialchars($row['nama_petugas']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>

