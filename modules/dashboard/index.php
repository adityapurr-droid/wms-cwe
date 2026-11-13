<?php
/**
 * Dashboard Interaktif & Informatif WMS CWE
 * Politeknik Kelapa Sawit Citra Widya Edukasi
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';
require_once __DIR__ . '/../../helpers/format.php';

require_login();

$page_title = 'Dashboard Utama';
$current_page = 'dashboard';

// 1. KPI Metrik Utama
$total_sku = (int)$pdo->query("SELECT COUNT(*) FROM barang")->fetchColumn();
$total_stok_unit = (int)$pdo->query("SELECT COALESCE(SUM(stok), 0) FROM barang")->fetchColumn();
$total_rak = (int)$pdo->query("SELECT COUNT(*) FROM rak")->fetchColumn();

// Transaksi Masuk Bulan Ini
$stmt_bm_month = $pdo->query("SELECT COUNT(*) as jml_transaksi, COALESCE(SUM(jumlah), 0) as total_unit FROM barang_masuk WHERE MONTH(tanggal_masuk) = MONTH(CURRENT_DATE()) AND YEAR(tanggal_masuk) = YEAR(CURRENT_DATE())");
$bm_month = $stmt_bm_month->fetch();

// Transaksi Keluar Bulan Ini
$stmt_bk_month = $pdo->query("SELECT COUNT(*) as jml_transaksi, COALESCE(SUM(jumlah), 0) as total_unit FROM barang_keluar WHERE MONTH(tanggal_keluar) = MONTH(CURRENT_DATE()) AND YEAR(tanggal_keluar) = YEAR(CURRENT_DATE())");
$bk_month = $stmt_bk_month->fetch();

// Transaksi Masuk & Keluar Hari Ini
$today_bm_unit = (int)$pdo->query("SELECT COALESCE(SUM(jumlah), 0) FROM barang_masuk WHERE tanggal_masuk = CURRENT_DATE()")->fetchColumn();
$today_bk_unit = (int)$pdo->query("SELECT COALESCE(SUM(jumlah), 0) FROM barang_keluar WHERE tanggal_keluar = CURRENT_DATE()")->fetchColumn();

// Barang Kritis (Stok <= Stok Minimum)
$stmt_kritis = $pdo->query("
    SELECT b.*, k.nama_kategori, s.nama_satuan, r.nama_rak, r.kode_rak
    FROM barang b
    JOIN kategori k ON b.id_kategori = k.id
    JOIN satuan s ON b.id_satuan = s.id
    JOIN rak r ON b.id_rak = r.id
    WHERE b.stok <= b.stok_minimum
    ORDER BY b.stok ASC
    LIMIT 6
");
$barang_kritis = $stmt_kritis->fetchAll();
$total_kritis = (int)$pdo->query("SELECT COUNT(*) FROM barang WHERE stok <= stok_minimum")->fetchColumn();

// 2. Data Grafik 7 Hari Terakhir
$chart_days = [];
$chart_bm_data = [];
$chart_bk_data = [];

for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-{$i} days"));
    $label = date('d M', strtotime($tgl));
    $chart_days[] = $label;

    $stmt_d_bm = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM barang_masuk WHERE tanggal_masuk = ?");
    $stmt_d_bm->execute([$tgl]);
    $chart_bm_data[] = (int)$stmt_d_bm->fetchColumn();

    $stmt_d_bk = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM barang_keluar WHERE tanggal_keluar = ?");
    $stmt_d_bk->execute([$tgl]);
    $chart_bk_data[] = (int)$stmt_d_bk->fetchColumn();
}

// 3. Data Distribusi Kategori (Donut Chart)
$stmt_kat_chart = $pdo->query("
    SELECT k.nama_kategori, COUNT(b.id) as total_item, COALESCE(SUM(b.stok), 0) as total_stok
    FROM kategori k
    LEFT JOIN barang b ON k.id = b.id_kategori
    GROUP BY k.id, k.nama_kategori
    ORDER BY total_stok DESC
");
$kategori_chart_rows = $stmt_kat_chart->fetchAll();
$kat_labels = [];
$kat_stok_data = [];
foreach ($kategori_chart_rows as $row) {
    $kat_labels[] = $row['nama_kategori'];
    $kat_stok_data[] = (int)$row['total_stok'];
}

// 4. Data Keterisian Rak Gudang (Horizontal Bar Chart)
$stmt_rak_chart = $pdo->query("
    SELECT r.kode_rak, r.nama_rak, COUNT(b.id) as total_sku, COALESCE(SUM(b.stok), 0) as total_stok
    FROM rak r
    LEFT JOIN barang b ON r.id = b.id_rak
    GROUP BY r.id, r.kode_rak, r.nama_rak
    ORDER BY total_stok DESC
    LIMIT 6
");
$rak_chart_rows = $stmt_rak_chart->fetchAll();
$rak_labels = [];
$rak_stok_data = [];
foreach ($rak_chart_rows as $row) {
    $rak_labels[] = $row['kode_rak'];
    $rak_stok_data[] = (int)$row['total_stok'];
}

// 5. Aktivitas Mutasi Terkini (5 Transaksi Terakhir)
$stmt_recent_in = $pdo->query("
    SELECT bm.*, b.nama_barang, b.kode_barang, s.nama_supplier, u.nama as nama_user
    FROM barang_masuk bm
    JOIN barang b ON bm.id_barang = b.id
    LEFT JOIN supplier s ON bm.id_supplier = s.id
    JOIN users u ON bm.user_id = u.id
    ORDER BY bm.created_at DESC
    LIMIT 5
");
$recent_in = $stmt_recent_in->fetchAll();

$stmt_recent_out = $pdo->query("
    SELECT bk.*, b.nama_barang, b.kode_barang, d.nama_departemen, d.kode_departemen, u.nama as nama_user
    FROM barang_keluar bk
    JOIN barang b ON bk.id_barang = b.id
    LEFT JOIN departemen d ON bk.id_departemen = d.id
    JOIN users u ON bk.user_id = u.id
    ORDER BY bk.created_at DESC
    LIMIT 5
");
$recent_out = $stmt_recent_out->fetchAll();

require_once __DIR__ . '/../../layout/header.php';
require_once __DIR__ . '/../../layout/sidebar.php';
?>

<div id="content-wrapper">
    <?php require_once __DIR__ . '/../../layout/navbar.php'; ?>

    <main class="container-fluid px-4 py-4">
        <?= render_flash() ?>

        <!-- Welcome Banner -->
        <div class="card bg-success text-white mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #0f5132 0%, #157347 60%, #198754 100%);">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-white text-success px-3 py-1 rounded-pill fw-bold text-uppercase me-2" style="font-size: 0.75rem;">
                                Logistik Terpusat
                            </span>
                            <span class="text-white-50 small"><i class="bi bi-clock me-1"></i> <?= date('d M Y') ?> - Gudang 1 Pintu Aktif</span>
                        </div>
                        <h3 class="fw-bold mb-1">Selamat Datang, <?= htmlspecialchars($_SESSION['user_nama'] ?? 'Petugas') ?>!</h3>
                        <p class="mb-0 text-white-50 small" style="max-width: 650px;">
                            Sistem WMS Politeknik Kelapa Sawit Citra Widya Edukasi siap membantu pemantauan stok rak sarana praktikum, bahan lab, dan suku cadang secara real-time.
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0 d-flex flex-wrap gap-2 justify-content-md-end">
                        <a href="<?= BASE_URL ?>modules/scanner/index.php" class="btn btn-warning fw-bold px-4 py-2 rounded-pill shadow-sm scan-pulse">
                            <i class="bi bi-upc-scan me-1"></i> Scan Barcode
                        </a>
                        <a href="<?= BASE_URL ?>modules/barang_masuk/tambah.php" class="btn btn-outline-light fw-semibold px-3 py-2 rounded-pill">
                            <i class="bi bi-plus-lg me-1"></i> Penerimaan
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 KPI Cards -->
        <div class="row g-3 mb-4">
            <!-- Total SKU Barang -->
            <div class="col-xl-3 col-sm-6">
                <div class="card h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted fw-semibold small text-uppercase">Total SKU Barang</span>
                            <h2 class="fw-bold mb-0 mt-1 text-dark"><?= number_format($total_sku) ?></h2>
                            <small class="text-muted"><i class="bi bi-layers me-1 text-primary"></i> <?= $total_rak ?> Rak Gudang</small>
                        </div>
                        <div class="rounded-4 bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                            <i class="bi bi-boxes fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Unit Stok Tersimpan -->
            <div class="col-xl-3 col-sm-6">
                <div class="card h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted fw-semibold small text-uppercase">Total Fisik Stok</span>
                            <h2 class="fw-bold mb-0 mt-1 text-success"><?= number_format($total_stok_unit) ?></h2>
                            <small class="text-muted"><i class="bi bi-check2-circle text-success me-1"></i> Unit / Kemasan Siap Pakai</small>
                        </div>
                        <div class="rounded-4 bg-success-subtle text-success p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                            <i class="bi bi-box-seam fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barang Masuk Bulan Ini -->
            <div class="col-xl-3 col-sm-6">
                <div class="card h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted fw-semibold small text-uppercase">Barang Masuk (Bulan Ini)</span>
                            <h2 class="fw-bold mb-0 mt-1 text-info"><?= number_format($bm_month['total_unit']) ?> <span class="fs-6 fw-normal text-muted">unit</span></h2>
                            <small class="text-muted"><i class="bi bi-arrow-down-left text-info me-1"></i> <?= $bm_month['jml_transaksi'] ?> Transaksi (Hari ini: <?= $today_bm_unit ?>)</small>
                        </div>
                        <div class="rounded-4 bg-info-subtle text-info p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                            <i class="bi bi-box-arrow-in-down fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stok Menipis / Kritis Alert -->
            <div class="col-xl-3 col-sm-6">
                <div class="card h-100 p-3 border-<?= ($total_kritis > 0) ? 'danger' : 'light' ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted fw-semibold small text-uppercase">Stok Kritis / Reorder</span>
                            <h2 class="fw-bold mb-0 mt-1 <?= ($total_kritis > 0) ? 'text-danger' : 'text-secondary' ?>"><?= $total_kritis ?></h2>
                            <small class="<?= ($total_kritis > 0) ? 'text-danger fw-semibold' : 'text-muted' ?>">
                                <?= ($total_kritis > 0) ? '<i class="bi bi-exclamation-triangle-fill me-1"></i> Perlu pengadaan ulang!' : '<i class="bi bi-shield-check text-success me-1"></i> Semua stok aman' ?>
                            </small>
                        </div>
                        <div class="rounded-4 <?= ($total_kritis > 0) ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-secondary' ?> p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                            <i class="bi bi-bell-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Grafik & Visualisasi Interaktif -->
        <div class="row g-3 mb-4">
            <!-- Grafik Tren Mutasi 7 Hari -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-graph-up text-success me-2"></i> Tren Mutasi Barang (7 Hari Terakhir)</h6>
                            <small class="text-muted">Perbandingan unit barang masuk vs unit barang keluar</small>
                        </div>
                        <div class="badge bg-light text-secondary border">Real-time</div>
                    </div>
                    <div class="card-body">
                        <canvas id="mutationTrendChart" style="max-height: 290px; width: 100%;"></canvas>
                    </div>
                </div>
            </div>

            <!-- Grafik Donut Komposisi Kategori -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-pie-chart text-info me-2"></i> Sebaran Kategori</h6>
                            <small class="text-muted">Proporsi stok per kelompok barang</small>
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center">
                        <canvas id="categoryDonutChart" style="max-height: 240px; width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Peringatan Stok Kritis & Keterisian Rak -->
        <div class="row g-3 mb-4">
            <!-- Tabel Peringatan Stok Menipis -->
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0 text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> Peringatan Stok Kritis / Reorder Point</h6>
                            <small class="text-muted">Barang yang kuantitasnya di bawah atau sama dengan stok minimum</small>
                        </div>
                        <a href="<?= BASE_URL ?>modules/barang/index.php" class="btn btn-sm btn-outline-secondary rounded-pill">Lihat Semua</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-muted text-uppercase">
                                    <tr>
                                        <th>Kode & Nama Barang</th>
                                        <th>Lokasi Rak</th>
                                        <th class="text-center">Sisa Stok</th>
                                        <th class="text-center">Batas Min</th>
                                        <th class="text-end">Aksi Cepat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($barang_kritis)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted small">
                                                <i class="bi bi-shield-check text-success fs-3 d-block mb-1"></i>
                                                Luar biasa! Tidak ada barang dalam kondisi stok menipis saat ini.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($barang_kritis as $item): ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-bold text-dark d-block"><?= htmlspecialchars($item['nama_barang']) ?></span>
                                                    <small class="badge bg-light text-secondary border font-monospace"><?= htmlspecialchars($item['kode_barang']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">
                                                        <i class="bi bi-grid-3x3-gap me-1 text-primary"></i> <?= htmlspecialchars($item['kode_rak']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-danger rounded-pill px-3 py-1 fw-bold fs-6">
                                                        <?= $item['stok'] ?> <?= htmlspecialchars($item['nama_satuan']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center text-muted small fw-semibold">
                                                    <?= $item['stok_minimum'] ?> <?= htmlspecialchars($item['nama_satuan']) ?>
                                                </td>
                                                <td class="text-end">
                                                    <a href="<?= BASE_URL ?>modules/barang_masuk/tambah.php?id_barang=<?= $item['id'] ?>" class="btn btn-sm btn-success rounded-pill px-3" title="Tambah Stok">
                                                        <i class="bi bi-plus-circle me-1"></i> Restock
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafik Horizontal Keterisian Rak Gudang -->
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-columns-gap text-primary me-2"></i> Keterisian Rak Penyimpanan Gudang</h6>
                            <small class="text-muted">Jumlah unit fisik tersimpan per rak</small>
                        </div>
                        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                        <a href="<?= BASE_URL ?>modules/rak/index.php" class="btn btn-sm btn-outline-primary rounded-pill">Kelola Rak</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <canvas id="rackBarChart" style="max-height: 260px; width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Riwayat Aktivitas Transaksi Terbaru (Masuk & Keluar) -->
        <div class="row g-3">
            <!-- 5 Transaksi Barang Masuk Terkini -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box-arrow-in-down text-info me-2"></i> Penerimaan Masuk Terakhir</h6>
                        <a href="<?= BASE_URL ?>modules/barang_masuk/index.php" class="small text-decoration-none text-info fw-semibold">Lihat Riwayat &rarr;</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php if (empty($recent_in)): ?>
                                <div class="p-3 text-center text-muted small">Belum ada transaksi barang masuk.</div>
                            <?php else: ?>
                                <?php foreach ($recent_in as $in): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($in['nama_barang']) ?></div>
                                            <div class="small text-muted">
                                                <span class="badge bg-info-subtle text-info font-monospace"><?= htmlspecialchars($in['no_transaksi']) ?></span>
                                                <span class="mx-1">&bull;</span>
                                                <span><?= htmlspecialchars($in['nama_supplier'] ?? 'Pemasok Umum') ?></span>
                                            </div>
                                            <div class="small text-muted" style="font-size: 0.75rem;">
                                                <i class="bi bi-calendar3 me-1"></i> <?= format_tanggal($in['tanggal_masuk']) ?> oleh <?= htmlspecialchars($in['nama_user']) ?>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-success-subtle text-success fs-6 fw-bold px-3 py-2 rounded-pill">
                                                +<?= number_format($in['jumlah']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5 Transaksi Barang Keluar Terkini -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box-arrow-up-right text-danger me-2"></i> Pengeluaran Keluar Terakhir</h6>
                        <a href="<?= BASE_URL ?>modules/barang_keluar/index.php" class="small text-decoration-none text-danger fw-semibold">Lihat Riwayat &rarr;</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php if (empty($recent_out)): ?>
                                <div class="p-3 text-center text-muted small">Belum ada transaksi barang keluar.</div>
                            <?php else: ?>
                                <?php foreach ($recent_out as $out): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($out['nama_barang']) ?></div>
                                            <div class="small text-muted">
                                                <span class="badge bg-danger-subtle text-danger font-monospace"><?= htmlspecialchars($out['no_transaksi']) ?></span>
                                                <span class="mx-1">&bull;</span>
                                                <span><?= htmlspecialchars($out['nama_departemen'] ?? 'Unit Kampus') ?></span>
                                            </div>
                                            <div class="small text-muted" style="font-size: 0.75rem;">
                                                <i class="bi bi-calendar3 me-1"></i> <?= format_tanggal($out['tanggal_keluar']) ?> untuk: <?= htmlspecialchars($out['peruntukan'] ?? '-') ?>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-danger-subtle text-danger fs-6 fw-bold px-3 py-2 rounded-pill">
                                                -<?= number_format($out['jumlah']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>

<!-- Script Inisialisasi Chart.js Dashboard -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Line / Bar Chart Tren Mutasi 7 Hari
    const ctxTrend = document.getElementById('mutationTrendChart');
    if (ctxTrend) {
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_days) ?>,
                datasets: [
                    {
                        label: 'Barang Masuk',
                        data: <?= json_encode($chart_bm_data) ?>,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#0d6efd',
                        pointRadius: 4
                    },
                    {
                        label: 'Barang Keluar',
                        data: <?= json_encode($chart_bk_data) ?>,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#dc3545',
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        padding: 10
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 5 }
                    }
                }
            }
        });
    }

    // 2. Donut Chart Sebaran Kategori
    const ctxDonut = document.getElementById('categoryDonutChart');
    if (ctxDonut) {
        new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($kat_labels) ?>,
                datasets: [{
                    data: <?= json_encode($kat_stok_data) ?>,
                    backgroundColor: [
                        '#198754', '#0d6efd', '#ffc107', '#0dcaf0', '#6f42c1', '#fd7e14'
                    ],
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { size: 11 } }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // 3. Horizontal Bar Chart Keterisian Rak Gudang
    const ctxRack = document.getElementById('rackBarChart');
    if (ctxRack) {
        new Chart(ctxRack, {
            type: 'bar',
            data: {
                labels: <?= json_encode($rak_labels) ?>,
                datasets: [{
                    label: 'Total Unit Stok',
                    data: <?= json_encode($rak_stok_data) ?>,
                    backgroundColor: 'rgba(15, 81, 50, 0.75)',
                    borderColor: '#0f5132',
                    borderWidth: 1.5,
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });
    }
});
</script>

