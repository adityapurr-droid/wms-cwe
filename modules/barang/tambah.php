<?php
/**
 * Tambah Master Barang Baru WMS CWE
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';
require_once __DIR__ . '/../../helpers/format.php';

require_admin();

$page_title = 'Tambah Barang Baru';
$current_page = 'barang';

// Data Referensi Dropdown
$kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC")->fetchAll();
$satuan_list = $pdo->query("SELECT * FROM satuan ORDER BY nama_satuan ASC")->fetchAll();
$rak_list = $pdo->query("SELECT * FROM rak ORDER BY kode_rak ASC")->fetchAll();

// Generate Kode Barang & Barcode Otomatis
$auto_kode = generate_kode_barang($pdo);
$auto_barcode = generate_barcode_auto($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_barang = strtoupper(trim($_POST['kode_barang'] ?? ''));
    $barcode = trim($_POST['barcode'] ?? '');
    $nama_barang = trim($_POST['nama_barang'] ?? '');
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $id_satuan = (int)($_POST['id_satuan'] ?? 0);
    $id_rak = (int)($_POST['id_rak'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 0);
    $stok_minimum = (int)($_POST['stok_minimum'] ?? 5);
    $spesifikasi = trim($_POST['spesifikasi'] ?? '');

    if (empty($kode_barang) || empty($barcode) || empty($nama_barang) || !$id_kategori || !$id_satuan || !$id_rak) {
        set_flash('error', 'Semua kolom bertanda bintang (*) wajib diisi.');
    } else {
        try {
            // Cek duplikasi kode atau barcode
            $cek = $pdo->prepare("SELECT id FROM barang WHERE kode_barang = ? OR barcode = ?");
            $cek->execute([$kode_barang, $barcode]);
            if ($cek->fetch()) {
                set_flash('error', "Kode barang atau barcode sudah terdaftar di sistem.");
            } else {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    INSERT INTO barang (kode_barang, barcode, nama_barang, id_kategori, id_satuan, id_rak, stok, stok_minimum, spesifikasi)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $kode_barang, $barcode, $nama_barang, $id_kategori, $id_satuan, $id_rak, $stok, $stok_minimum, $spesifikasi
                ]);
                $barang_id = $pdo->lastInsertId();

                // Jika ada stok awal > 0, catat sebagai transaksi masuk awal
                if ($stok > 0) {
                    $no_trx = generate_kode_transaksi($pdo, 'BM');
                    $tgl = date('Y-m-d');
                    $user_id = $_SESSION['user_id'] ?? 1;

                    $stmt_in = $pdo->prepare("
                        INSERT INTO barang_masuk (no_transaksi, id_barang, id_supplier, jumlah, tanggal_masuk, no_surat_jalan, keterangan, user_id)
                        VALUES (?, ?, NULL, ?, ?, 'STOK-AWAL', 'Pencatatan Saldo Awal Barang', ?)
                    ");
                    $stmt_in->execute([$no_trx, $barang_id, $stok, $tgl, $user_id]);
                }

                $pdo->commit();
                set_flash('success', "Barang '{$nama_barang}' ({$kode_barang}) berhasil disimpan.");
                header('Location: ' . BASE_URL . 'modules/barang/index.php');
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash('error', 'Gagal menyimpan barang: ' . $e->getMessage());
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
            <a href="<?= BASE_URL ?>modules/barang/index.php" class="text-decoration-none text-muted small fw-semibold">
                &larr; Kembali ke Master Barang
            </a>
            <h4 class="fw-bold mt-2 text-dark"><i class="bi bi-plus-circle text-success me-2"></i> Tambah Master Barang Baru</h4>
            <p class="text-muted small mb-0">Isi formulir untuk mendaftarkan barang baru ke rak penyimpanan gudang 1 pintu CWE.</p>
        </div>

        <div class="card shadow-sm" style="max-width: 900px;">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark">Formulir Identitas & Spesifikasi Barang</h6>
            </div>
            <div class="card-body p-4">
                <form action="" method="POST">
                    <div class="row g-3">
                        <!-- Kode Barang & Barcode -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kode Barang <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-tag"></i></span>
                                <input type="text" name="kode_barang" class="form-control font-monospace fw-bold" value="<?= htmlspecialchars($_POST['kode_barang'] ?? $auto_kode) ?>" required>
                            </div>
                            <small class="text-muted" style="font-size: 0.72rem;">Format otomatis: BRG-001, BRG-002, dst.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nomor Barcode / QR Code <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-upc"></i></span>
                                <input type="text" name="barcode" id="barcodeInput" class="form-control font-monospace" value="<?= htmlspecialchars($_POST['barcode'] ?? $auto_barcode) ?>" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="regenerateBarcode()" title="Buat Barcode Acak Baru">
                                    <i class="bi bi-arrow-repeat"></i> Acak
                                </button>
                            </div>
                            <small class="text-muted" style="font-size: 0.72rem;">Dapat di-scan menggunakan scanner laser atau kamera.</small>
                        </div>

                        <!-- Nama Barang -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" name="nama_barang" class="form-control" placeholder="Contoh: Dodos Sawit 10 cm / Pupuk NPK 16-16-16" value="<?= htmlspecialchars($_POST['nama_barang'] ?? '') ?>" required>
                        </div>

                        <!-- Kategori, Satuan, Lokasi Rak -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Kategori Barang <span class="text-danger">*</span></label>
                            <select name="id_kategori" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($kategori_list as $k): ?>
                                    <option value="<?= $k['id'] ?>" <?= (isset($_POST['id_kategori']) && $_POST['id_kategori'] == $k['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($k['nama_kategori']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Satuan Ukuran <span class="text-danger">*</span></label>
                            <select name="id_satuan" class="form-select" required>
                                <option value="">-- Pilih Satuan --</option>
                                <?php foreach ($satuan_list as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= (isset($_POST['id_satuan']) && $_POST['id_satuan'] == $s['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['nama_satuan']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Lokasi Rak Gudang 1 Pintu <span class="text-danger">*</span></label>
                            <select name="id_rak" class="form-select" required>
                                <option value="">-- Pilih Rak Penyimpanan --</option>
                                <?php foreach ($rak_list as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= (isset($_POST['id_rak']) && $_POST['id_rak'] == $r['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['kode_rak']) ?> (<?= htmlspecialchars($r['nama_rak']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Stok Awal & Stok Minimum -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Stok Awal Fisik Saat Ini</label>
                            <input type="number" name="stok" class="form-control" min="0" value="<?= htmlspecialchars($_POST['stok'] ?? '0') ?>">
                            <small class="text-muted" style="font-size: 0.72rem;">Jika diisi > 0, sistem akan otomatis mencatat saldo awal di kartu stok.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Batas Stok Minimum (Safety Stock) <span class="text-danger">*</span></label>
                            <input type="number" name="stok_minimum" class="form-control" min="1" value="<?= htmlspecialchars($_POST['stok_minimum'] ?? '5') ?>" required>
                            <small class="text-muted" style="font-size: 0.72rem;">Sistem akan memberi peringatan di dashboard jika stok mencapai angka ini.</small>
                        </div>

                        <!-- Spesifikasi -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Spesifikasi Teknis / Keterangan Tambahan</label>
                            <textarea name="spesifikasi" class="form-control" rows="3" placeholder="Contoh: Merek, tipe baja, komposisi senyawa kimia, nomor part mesin, dsb."><?= htmlspecialchars($_POST['spesifikasi'] ?? '') ?></textarea>
                        </div>

                        <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                            <a href="<?= BASE_URL ?>modules/barang/index.php" class="btn btn-light border px-4">Batal</a>
                            <button type="submit" class="btn btn-cwe-primary px-4 fw-bold">
                                <i class="bi bi-save me-1"></i> Simpan Data Barang
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>

<script>
function regenerateBarcode() {
    const random = Math.floor(100000 + Math.random() * 900000);
    document.getElementById('barcodeInput').value = '8991' + random;
}
</script>

