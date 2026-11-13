<?php
/**
 * Pencatatan Pengeluaran Barang Keluar WMS CWE
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

// Generate Nomor Transaksi Otomatis
$auto_no_trx = generate_kode_transaksi($pdo, 'BK');

// Pre-selected ID Barang jika dari Scanner / Master Barang
$selected_barang_id = (int)($_GET['id_barang'] ?? 0);

// Ambil Barang yang Memiliki Stok > 0
$barang_list = $pdo->query("
    SELECT b.id, b.kode_barang, b.barcode, b.nama_barang, b.stok, s.nama_satuan, r.kode_rak, r.nama_rak
    FROM barang b
    JOIN satuan s ON b.id_satuan = s.id
    JOIN rak r ON b.id_rak = r.id
    WHERE b.stok > 0
    ORDER BY b.nama_barang ASC
")->fetchAll();

// Ambil Unit / Departemen Pemohon
$dept_list = $pdo->query("SELECT id, kode_departemen, nama_departemen, penanggung_jawab FROM departemen ORDER BY nama_departemen ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_transaksi = trim($_POST['no_transaksi'] ?? '');
    $id_barang = (int)($_POST['id_barang'] ?? 0);
    $id_departemen = !empty($_POST['id_departemen']) ? (int)$_POST['id_departemen'] : null;
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $tanggal_keluar = trim($_POST['tanggal_keluar'] ?? date('Y-m-d'));
    $peruntukan = trim($_POST['peruntukan'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $user_id = $_SESSION['user_id'] ?? 1;

    if (empty($no_transaksi) || !$id_barang || $jumlah <= 0 || empty($tanggal_keluar)) {
        set_flash('error', 'Semua kolom bertanda bintang (*) wajib diisi dan jumlah keluar minimal 1.');
    } else {
        try {
            // Periksa stok fisik barang terkini
            $stmt_cek = $pdo->prepare("SELECT stok, nama_barang, id_satuan FROM barang WHERE id = ?");
            $stmt_cek->execute([$id_barang]);
            $item = $stmt_cek->fetch();

            if (!$item) {
                set_flash('error', 'Barang tidak ditemukan.');
            } elseif ($item['stok'] < $jumlah) {
                set_flash('error', "Stok tidak mencukupi! Kuantitas permintaan ({$jumlah}) melebihi stok yang tersedia di rak ({$item['stok']}).");
            } else {
                // Jalankan Transaksi Database (ACID)
                $pdo->beginTransaction();

                // 1. Simpan Transaksi Keluar
                $stmt_out = $pdo->prepare("
                    INSERT INTO barang_keluar (no_transaksi, id_barang, id_departemen, jumlah, tanggal_keluar, peruntukan, keterangan, user_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt_out->execute([
                    $no_transaksi, $id_barang, $id_departemen, $jumlah, $tanggal_keluar, $peruntukan, $keterangan, $user_id
                ]);

                // 2. Kurangi Stok di Master Barang
                $stmt_upd = $pdo->prepare("UPDATE barang SET stok = stok - ? WHERE id = ?");
                $stmt_upd->execute([$jumlah, $id_barang]);

                $pdo->commit();

                set_flash('success', "Pengeluaran barang ({$no_transaksi}) berhasil dicatat. Sisa stok di rak telah diperbarui.");
                header('Location: ' . BASE_URL . 'modules/barang_keluar/index.php');
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash('error', 'Gagal memproses pengeluaran barang: ' . $e->getMessage());
        }
    }
}

require_once __DIR__ . '/../../layout/header.php';
require_once __DIR__ . '/../../layout/sidebar.php';
?>

<div id="content-wrapper">
    <?php require_once __DIR__ . '/../../layout/navbar.php'; ?>

    <main class="container-fluid px-4 py-4">
        <?= render_flash() ?>

        <div class="mb-4">
            <a href="<?= BASE_URL ?>modules/barang_keluar/index.php" class="text-decoration-none text-muted small fw-semibold">
                &larr; Kembali ke Daftar Barang Keluar
            </a>
            <h4 class="fw-bold mt-2 text-dark"><i class="bi bi-box-arrow-up-right text-danger me-2"></i> Formulir Pengeluaran Barang (Gudang)</h4>
            <p class="text-muted small mb-0">Catat penyerahan sarana perkebunan, alat praktikum, bahan lab, atau ATK kepada pemohon.</p>
        </div>

        <div class="card shadow-sm" style="max-width: 900px;">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark">Data Pengeluaran & Serah Terima</h6>
                <a href="<?= BASE_URL ?>modules/scanner/index.php" class="btn btn-sm btn-outline-success rounded-pill">
                    <i class="bi bi-upc-scan me-1"></i> Scan Barcode Cepat
                </a>
            </div>
            <div class="card-body p-4">
                <form action="" method="POST" id="formKeluar">
                    <div class="row g-3">
                        <!-- Nomor Transaksi & Tanggal -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nomor Transaksi <span class="text-danger">*</span></label>
                            <input type="text" name="no_transaksi" class="form-control font-monospace fw-bold bg-light" value="<?= htmlspecialchars($_POST['no_transaksi'] ?? $auto_no_trx) ?>" readonly required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tanggal Pengeluaran <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_keluar" class="form-control" value="<?= htmlspecialchars($_POST['tanggal_keluar'] ?? date('Y-m-d')) ?>" required>
                        </div>

                        <!-- Pilih Barang -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Pilih Barang yang Dikeluarkan <span class="text-danger">*</span></label>
                            <select name="id_barang" id="selectBarangKeluar" class="form-select" required>
                                <option value="">-- Pilih Barang (Hanya yang memiliki stok tersedia) --</option>
                                <?php foreach ($barang_list as $b): ?>
                                    <option value="<?= $b['id'] ?>" 
                                            data-stok="<?= $b['stok'] ?>" 
                                            data-satuan="<?= htmlspecialchars($b['nama_satuan']) ?>"
                                            data-rak="<?= htmlspecialchars($b['kode_rak']) ?> (<?= htmlspecialchars($b['nama_rak']) ?>)"
                                            <?= ((isset($_POST['id_barang']) ? $_POST['id_barang'] : $selected_barang_id) == $b['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['kode_barang']) ?> - <?= htmlspecialchars($b['nama_barang']) ?> (Tersedia: <?= $b['stok'] ?> <?= htmlspecialchars($b['nama_satuan']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Panel Detail Rak & Stok Tersedia (Live Info) -->
                        <div class="col-12" id="infoBarangKeluarPanel" style="display: none;">
                            <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted d-block">Ambil dari Rak:</small>
                                    <span class="fw-bold text-primary font-monospace" id="infoRakKeluarText">-</span>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">Maksimal Stok yang Boleh Diambil:</small>
                                    <span class="badge bg-success fs-6" id="infoStokKeluarText">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- Departemen & Peruntukan -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Departemen / Program Studi Pemohon <span class="text-danger">*</span></label>
                            <select name="id_departemen" class="form-select" required>
                                <option value="">-- Pilih Unit / Prodi Pemohon --</option>
                                <?php foreach ($dept_list as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= (isset($_POST['id_departemen']) && $_POST['id_departemen'] == $d['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($d['nama_departemen']) ?> (<?= htmlspecialchars($d['kode_departemen']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Peruntukan / Kegiatan Praktikum <span class="text-danger">*</span></label>
                            <input type="text" name="peruntukan" class="form-control" placeholder="Contoh: Praktikum Panen BKS Angkatan 2024" value="<?= htmlspecialchars($_POST['peruntukan'] ?? '') ?>" required>
                        </div>

                        <!-- Kuantitas Jumlah Keluar -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Jumlah Barang yang Dikeluarkan <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="jumlah" id="inputJumlahKeluar" class="form-control fw-bold fs-5 text-danger" min="1" placeholder="0" value="<?= htmlspecialchars($_POST['jumlah'] ?? '') ?>" required>
                                <span class="input-group-text bg-light" id="satuanKeluarLabel">Unit</span>
                            </div>
                            <small class="text-danger small mt-1" id="stokWarning" style="display: none;">
                                <i class="bi bi-exclamation-triangle-fill"></i> Jumlah melebihi stok yang tersedia di rak!
                            </small>
                        </div>

                        <!-- Catatan / Keterangan -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Catatan Tambahan (Nama Pengambil / No Memo)</label>
                            <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Diambil oleh staf lab sdr. Hendra" value="<?= htmlspecialchars($_POST['keterangan'] ?? '') ?>">
                        </div>

                        <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                            <a href="<?= BASE_URL ?>modules/barang_keluar/index.php" class="btn btn-light border px-4">Batal</a>
                            <button type="submit" id="btnSubmitKeluar" class="btn btn-danger px-4 fw-bold">
                                <i class="bi bi-check-circle me-1"></i> Simpan Pengeluaran Barang
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectBarang = document.getElementById('selectBarangKeluar');
    const infoPanel = document.getElementById('infoBarangKeluarPanel');
    const infoRak = document.getElementById('infoRakKeluarText');
    const infoStok = document.getElementById('infoStokKeluarText');
    const satuanLabel = document.getElementById('satuanKeluarLabel');
    const inputJumlah = document.getElementById('inputJumlahKeluar');
    const warning = document.getElementById('stokWarning');
    const btnSubmit = document.getElementById('btnSubmitKeluar');

    let currentMaxStock = 0;

    function updateInfo() {
        const selected = selectBarang.options[selectBarang.selectedIndex];
        if (selected && selected.value) {
            currentMaxStock = parseInt(selected.getAttribute('data-stok')) || 0;
            infoPanel.style.display = 'block';
            infoRak.innerHTML = '<i class="bi bi-grid-3x3 me-1"></i> ' + selected.getAttribute('data-rak');
            infoStok.textContent = currentMaxStock + ' ' + selected.getAttribute('data-satuan');
            satuanLabel.textContent = selected.getAttribute('data-satuan') || 'Unit';
            inputJumlah.max = currentMaxStock;
            validateQuantity();
        } else {
            infoPanel.style.display = 'none';
            satuanLabel.textContent = 'Unit';
            currentMaxStock = 0;
        }
    }

    function validateQuantity() {
        const val = parseInt(inputJumlah.value) || 0;
        if (val > currentMaxStock && currentMaxStock > 0) {
            warning.style.display = 'block';
            btnSubmit.disabled = true;
        } else {
            warning.style.display = 'none';
            btnSubmit.disabled = false;
        }
    }

    selectBarang.addEventListener('change', updateInfo);
    inputJumlah.addEventListener('input', validateQuantity);
    updateInfo();
});
</script>

