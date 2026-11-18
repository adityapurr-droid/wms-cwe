<?php
/**
 * Pencatatan Penerimaan Barang Masuk (1 Pintu Gudang) WMS CWE
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

// Generate Nomor Transaksi Otomatis
$auto_no_trx = generate_kode_transaksi($pdo, 'BM');

// Pre-selected ID Barang jika dari Scanner / Dashboard
$selected_barang_id = (int)($_GET['id_barang'] ?? 0);

// Ambil Referensi Barang & Supplier
$barang_list = $pdo->query("
    SELECT b.id, b.kode_barang, b.barcode, b.nama_barang, b.stok, s.nama_satuan, r.kode_rak, r.nama_rak
    FROM barang b
    JOIN satuan s ON b.id_satuan = s.id
    JOIN rak r ON b.id_rak = r.id
    ORDER BY b.nama_barang ASC
")->fetchAll();

$supplier_list = $pdo->query("SELECT id, nama_supplier, kontak_person FROM supplier ORDER BY nama_supplier ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_transaksi = trim($_POST['no_transaksi'] ?? '');
    $id_barang = (int)($_POST['id_barang'] ?? 0);
    $id_supplier = !empty($_POST['id_supplier']) ? (int)$_POST['id_supplier'] : null;
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $tanggal_masuk = trim($_POST['tanggal_masuk'] ?? date('Y-m-d'));
    $no_surat_jalan = trim($_POST['no_surat_jalan'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $user_id = $_SESSION['user_id'] ?? 1;

    if (empty($no_transaksi) || !$id_barang || $jumlah <= 0 || empty($tanggal_masuk)) {
        set_flash('error', 'Semua kolom bertanda bintang (*) wajib diisi dan jumlah masuk harus lebih besar dari 0.');
    } else {
        try {
            // Gunakan PDO Database Transaction untuk konsistensi stok ACID
            $pdo->beginTransaction();

            // 1. Simpan Transaksi Masuk
            $stmt_in = $pdo->prepare("
                INSERT INTO barang_masuk (no_transaksi, id_barang, id_supplier, jumlah, tanggal_masuk, no_surat_jalan, keterangan, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_in->execute([
                $no_transaksi, $id_barang, $id_supplier, $jumlah, $tanggal_masuk, $no_surat_jalan, $keterangan, $user_id
            ]);

            // 2. Tambah Stok Barang di Tabel Barang
            $stmt_upd = $pdo->prepare("UPDATE barang SET stok = stok + ? WHERE id = ?");
            $stmt_upd->execute([$jumlah, $id_barang]);

            $pdo->commit();

            set_flash('success', "Penerimaan barang masuk ({$no_transaksi}) berhasil disimpan. Stok telah ditambahkan ke rak gudang.");
            header('Location: ' . BASE_URL . 'modules/barang_masuk/index.php');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash('error', 'Gagal memproses transaksi masuk: ' . $e->getMessage());
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
            <a href="<?= BASE_URL ?>modules/barang_masuk/index.php" class="text-decoration-none text-muted small fw-semibold">
                &larr; Kembali ke Daftar Barang Masuk
            </a>
            <h4 class="fw-bold mt-2 text-dark"><i class="bi bi-box-arrow-in-down text-input-data me-2"></i> Formulir Penerimaan Barang Masuk (Pintu Gudang)</h4>
            <p class="text-muted small mb-0">Catat barang yang baru tiba dari vendor/supplier untuk langsung dialokasikan ke rak penyimpanan.</p>
        </div>

        <div class="card shadow-sm" style="max-width: 900px;">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark">Data Tanda Terima Barang</h6>
                <a href="<?= BASE_URL ?>modules/scanner/index.php" class="btn btn-sm btn-outline-persediaan">
                    <i class="bi bi-upc-scan me-1"></i> Scan Barcode Cepat
                </a>
            </div>
            <div class="card-body p-4">
                <form action="" method="POST">
                    <div class="row g-3">
                        <!-- Nomor Transaksi & Tanggal -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nomor Transaksi <span class="text-danger">*</span></label>
                            <input type="text" name="no_transaksi" class="form-control font-monospace fw-bold bg-light" value="<?= htmlspecialchars($_POST['no_transaksi'] ?? $auto_no_trx) ?>" readonly required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tanggal Masuk (Penerimaan) <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_masuk" class="form-control" value="<?= htmlspecialchars($_POST['tanggal_masuk'] ?? date('Y-m-d')) ?>" required>
                        </div>

                        <!-- Pilih Barang -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Pilih Barang yang Diterima <span class="text-danger">*</span></label>
                            <select name="id_barang" id="selectBarang" class="form-select" required>
                                <option value="">-- Pilih Barang dari Master --</option>
                                <?php foreach ($barang_list as $b): ?>
                                    <option value="<?= $b['id'] ?>" 
                                            data-stok="<?= $b['stok'] ?>" 
                                            data-satuan="<?= htmlspecialchars($b['nama_satuan']) ?>"
                                            data-rak="<?= htmlspecialchars($b['kode_rak']) ?> (<?= htmlspecialchars($b['nama_rak']) ?>)"
                                            <?= ((isset($_POST['id_barang']) ? $_POST['id_barang'] : $selected_barang_id) == $b['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['kode_barang']) ?> - <?= htmlspecialchars($b['nama_barang']) ?> (Stok saat ini: <?= $b['stok'] ?> <?= htmlspecialchars($b['nama_satuan']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Panel Detail Rak & Stok Saat Ini (Live Info) -->
                        <div class="col-12" id="infoBarangPanel" style="display: none;">
                            <div class="p-3 bg-light border d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted d-block">Lokasi Alokasi Rak Gudang:</small>
                                    <span class="fw-bold text-persediaan font-monospace" id="infoRakText">-</span>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">Stok Fisik Saat Ini:</small>
                                    <span class="badge bg-persediaan fs-6" id="infoStokText">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- Pemasok & No Surat Jalan -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Pemasok / Rekanan (Vendor)</label>
                            <select name="id_supplier" class="form-select">
                                <option value="">-- Tanpa Pemasok / Penerimaan Langsung --</option>
                                <?php foreach ($supplier_list as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= (isset($_POST['id_supplier']) && $_POST['id_supplier'] == $s['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['nama_supplier']) ?> (<?= htmlspecialchars($s['kontak_person'] ?? 'PIC') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nomor Surat Jalan (DO / PO / Nota)</label>
                            <input type="text" name="no_surat_jalan" class="form-control" placeholder="Contoh: SJ-2026/091/SAP" value="<?= htmlspecialchars($_POST['no_surat_jalan'] ?? '') ?>">
                        </div>

                        <!-- Kuantitas Jumlah Masuk (Input Data: #17AB03) -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Jumlah Barang yang Diterima <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="jumlah" class="form-control fw-bold fs-5 text-input-data" min="1" placeholder="0" value="<?= htmlspecialchars($_POST['jumlah'] ?? '') ?>" required>
                                <span class="input-group-text bg-light" id="satuanLabel">Unit</span>
                            </div>
                        </div>

                        <!-- Catatan / Keterangan -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Keterangan / Keperluan Pasokan</label>
                            <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Pengadaan bahan praktikum semester ganjil" value="<?= htmlspecialchars($_POST['keterangan'] ?? '') ?>">
                        </div>

                        <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                            <a href="<?= BASE_URL ?>modules/barang_masuk/index.php" class="btn btn-light border px-4">Batal</a>
                            <button type="submit" class="btn btn-input-data px-4 fw-bold">
                                <i class="bi bi-check-circle me-1"></i> Simpan Penerimaan Barang
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
    const selectBarang = document.getElementById('selectBarang');
    const infoPanel = document.getElementById('infoBarangPanel');
    const infoRak = document.getElementById('infoRakText');
    const infoStok = document.getElementById('infoStokText');
    const satuanLabel = document.getElementById('satuanLabel');

    function updateInfo() {
        const selected = selectBarang.options[selectBarang.selectedIndex];
        if (selected && selected.value) {
            infoPanel.style.display = 'block';
            infoRak.innerHTML = '<i class="bi bi-grid-3x3 me-1"></i> ' + selected.getAttribute('data-rak');
            infoStok.textContent = selected.getAttribute('data-stok') + ' ' + selected.getAttribute('data-satuan');
            satuanLabel.textContent = selected.getAttribute('data-satuan') || 'Unit';
        } else {
            infoPanel.style.display = 'none';
            satuanLabel.textContent = 'Unit';
        }
    }

    selectBarang.addEventListener('change', updateInfo);
    updateInfo(); // Inisialisasi saat load jika sudah ada pre-selected ID
});
</script>

