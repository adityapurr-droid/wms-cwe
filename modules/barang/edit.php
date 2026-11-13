<?php
/**
 * Edit Master Data Barang WMS CWE
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';
require_once __DIR__ . '/../../helpers/format.php';

require_admin();

$page_title = 'Ubah Data Barang';
$current_page = 'barang';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . BASE_URL . 'modules/barang/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM barang WHERE id = ?");
$stmt->execute([$id]);
$barang = $stmt->fetch();

if (!$barang) {
    set_flash('error', 'Data barang tidak ditemukan.');
    header('Location: ' . BASE_URL . 'modules/barang/index.php');
    exit;
}

// Data Referensi Dropdown
$kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC")->fetchAll();
$satuan_list = $pdo->query("SELECT * FROM satuan ORDER BY nama_satuan ASC")->fetchAll();
$rak_list = $pdo->query("SELECT * FROM rak ORDER BY kode_rak ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_barang = strtoupper(trim($_POST['kode_barang'] ?? ''));
    $barcode = trim($_POST['barcode'] ?? '');
    $nama_barang = trim($_POST['nama_barang'] ?? '');
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $id_satuan = (int)($_POST['id_satuan'] ?? 0);
    $id_rak = (int)($_POST['id_rak'] ?? 0);
    $stok_minimum = (int)($_POST['stok_minimum'] ?? 5);
    $spesifikasi = trim($_POST['spesifikasi'] ?? '');

    if (empty($kode_barang) || empty($barcode) || empty($nama_barang) || !$id_kategori || !$id_satuan || !$id_rak) {
        set_flash('error', 'Semua kolom bertanda bintang (*) wajib diisi.');
    } else {
        try {
            // Cek duplikasi kode atau barcode selain ID ini
            $cek = $pdo->prepare("SELECT id FROM barang WHERE (kode_barang = ? OR barcode = ?) AND id != ?");
            $cek->execute([$kode_barang, $barcode, $id]);
            if ($cek->fetch()) {
                set_flash('error', "Kode barang atau barcode sudah digunakan oleh barang lain.");
            } else {
                $upd = $pdo->prepare("
                    UPDATE barang SET 
                        kode_barang = ?, 
                        barcode = ?, 
                        nama_barang = ?, 
                        id_kategori = ?, 
                        id_satuan = ?, 
                        id_rak = ?, 
                        stok_minimum = ?, 
                        spesifikasi = ?
                    WHERE id = ?
                ");
                $upd->execute([
                    $kode_barang, $barcode, $nama_barang, $id_kategori, $id_satuan, $id_rak, $stok_minimum, $spesifikasi, $id
                ]);

                set_flash('success', "Data barang '{$nama_barang}' berhasil diperbarui.");
                header('Location: ' . BASE_URL . 'modules/barang/index.php');
                exit;
            }
        } catch (PDOException $e) {
            set_flash('error', 'Gagal memperbarui barang: ' . $e->getMessage());
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
            <h4 class="fw-bold mt-2 text-dark"><i class="bi bi-pencil-square text-warning me-2"></i> Ubah Data Barang</h4>
            <p class="text-muted small mb-0">Perbarui informasi spesifikasi atau penempatan rak untuk barang <?= htmlspecialchars($barang['nama_barang']) ?>.</p>
        </div>

        <div class="card shadow-sm" style="max-width: 900px;">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark">Formulir Perubahan Data</h6>
            </div>
            <div class="card-body p-4">
                <form action="" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kode Barang <span class="text-danger">*</span></label>
                            <input type="text" name="kode_barang" class="form-control font-monospace fw-bold" value="<?= htmlspecialchars($_POST['kode_barang'] ?? $barang['kode_barang']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nomor Barcode <span class="text-danger">*</span></label>
                            <input type="text" name="barcode" class="form-control font-monospace" value="<?= htmlspecialchars($_POST['barcode'] ?? $barang['barcode']) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" name="nama_barang" class="form-control" value="<?= htmlspecialchars($_POST['nama_barang'] ?? $barang['nama_barang']) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Kategori Barang <span class="text-danger">*</span></label>
                            <select name="id_kategori" class="form-select" required>
                                <?php foreach ($kategori_list as $k): ?>
                                    <option value="<?= $k['id'] ?>" <?= ((isset($_POST['id_kategori']) ? $_POST['id_kategori'] : $barang['id_kategori']) == $k['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($k['nama_kategori']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Satuan Ukuran <span class="text-danger">*</span></label>
                            <select name="id_satuan" class="form-select" required>
                                <?php foreach ($satuan_list as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= ((isset($_POST['id_satuan']) ? $_POST['id_satuan'] : $barang['id_satuan']) == $s['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['nama_satuan']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Lokasi Rak Gudang 1 Pintu <span class="text-danger">*</span></label>
                            <select name="id_rak" class="form-select" required>
                                <?php foreach ($rak_list as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= ((isset($_POST['id_rak']) ? $_POST['id_rak'] : $barang['id_rak']) == $r['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['kode_rak']) ?> (<?= htmlspecialchars($r['nama_rak']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Sisa Stok Fisik Saat Ini</label>
                            <input type="text" class="form-control bg-light font-monospace fw-bold" value="<?= $barang['stok'] ?>" readonly>
                            <small class="text-muted" style="font-size: 0.72rem;">Penambahan atau pengurangan stok dilakukan melalui menu Transaksi Masuk/Keluar.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Batas Stok Minimum <span class="text-danger">*</span></label>
                            <input type="number" name="stok_minimum" class="form-control" min="1" value="<?= htmlspecialchars($_POST['stok_minimum'] ?? $barang['stok_minimum']) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Spesifikasi Teknis / Keterangan</label>
                            <textarea name="spesifikasi" class="form-control" rows="3"><?= htmlspecialchars($_POST['spesifikasi'] ?? $barang['spesifikasi']) ?></textarea>
                        </div>

                        <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                            <a href="<?= BASE_URL ?>modules/barang/index.php" class="btn btn-light border px-4">Batal</a>
                            <button type="submit" class="btn btn-warning px-4 fw-bold">
                                <i class="bi bi-save me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>

