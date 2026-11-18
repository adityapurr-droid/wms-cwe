<?php
/**
 * Modul Master Rak Gudang WMS CWE
 * Politeknik Kelapa Sawit Citra Widya Edukasi
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';
require_once __DIR__ . '/../../helpers/format.php';

require_admin();

$page_title = 'Kelola Rak Penyimpanan Gudang';
$current_page = 'rak';

// Tangani Tambah & Edit via Modal POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $kode_rak = strtoupper(trim($_POST['kode_rak'] ?? ''));
    $nama_rak = trim($_POST['nama_rak'] ?? '');
    $kapasitas = trim($_POST['kapasitas'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if (empty($kode_rak) || empty($nama_rak)) {
        set_flash('error', 'Kode Rak dan Nama Rak wajib diisi.');
    } else {
        try {
            if ($action === 'create') {
                // Cek duplikasi kode
                $cek = $pdo->prepare("SELECT id FROM rak WHERE kode_rak = ?");
                $cek->execute([$kode_rak]);
                if ($cek->fetch()) {
                    set_flash('error', "Kode rak '{$kode_rak}' sudah digunakan.");
                } else {
                    $ins = $pdo->prepare("INSERT INTO rak (kode_rak, nama_rak, kapasitas, keterangan) VALUES (?, ?, ?, ?)");
                    $ins->execute([$kode_rak, $nama_rak, $kapasitas, $keterangan]);
                    set_flash('success', "Rak '{$kode_rak}' berhasil ditambahkan.");
                }
            } elseif ($action === 'update' && $id > 0) {
                $upd = $pdo->prepare("UPDATE rak SET kode_rak = ?, nama_rak = ?, kapasitas = ?, keterangan = ? WHERE id = ?");
                $upd->execute([$kode_rak, $nama_rak, $kapasitas, $keterangan, $id]);
                set_flash('success', "Data rak '{$kode_rak}' berhasil diperbarui.");
            }
        } catch (PDOException $e) {
            set_flash('error', 'Gagal memproses data rak: ' . $e->getMessage());
        }
    }
    header('Location: ' . BASE_URL . 'modules/rak/index.php');
    exit;
}

// Tangani Hapus
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        // Cek apakah ada barang yang disimpan di rak ini
        $cek_barang = $pdo->prepare("SELECT COUNT(*) FROM barang WHERE id_rak = ?");
        $cek_barang->execute([$id]);
        $count = $cek_barang->fetchColumn();

        if ($count > 0) {
            set_flash('error', "Rak tidak dapat dihapus karena masih digunakan oleh {$count} data barang.");
        } else {
            $del = $pdo->prepare("DELETE FROM rak WHERE id = ?");
            $del->execute([$id]);
            set_flash('success', "Rak berhasil dihapus.");
        }
    }
    header('Location: ' . BASE_URL . 'modules/rak/index.php');
    exit;
}

// Ambil Daftar Rak beserta Jumlah Barang & Total Unit Stok
$stmt = $pdo->query("
    SELECT r.*, COUNT(b.id) as total_sku, COALESCE(SUM(b.stok), 0) as total_stok
    FROM rak r
    LEFT JOIN barang b ON r.id = b.id_rak
    GROUP BY r.id
    ORDER BY r.kode_rak ASC
");
$rak_list = $stmt->fetchAll();

require_once __DIR__ . '/../../layout/header.php';
require_once __DIR__ . '/../../layout/sidebar.php';
?>

<div id="content-wrapper">
    <?php require_once __DIR__ . '/../../layout/navbar.php'; ?>

    <main class="container-fluid px-4 py-4">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-columns-gap text-persediaan me-2"></i> Manajemen Rak Gudang</h4>
                <p class="text-muted small mb-0">Atur zona dan rak penyimpanan barang pada gudang terpusat 1 pintu Politeknik Kelapa Sawit CWE.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <button type="button" class="btn btn-theme-main shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahRak">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Rak Baru
                </button>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark">Daftar Rak & Kapasitas Penyimpanan</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle datatable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Kode Rak</th>
                                <th>Nama Rak / Zona</th>
                                <th>Kapasitas</th>
                                <th>Keterangan</th>
                                <th class="text-center">Jumlah SKU</th>
                                <th class="text-center">Total Stok Fisik</th>
                                <th class="text-end" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($rak_list as $r): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <span class="badge bg-persediaan-subtle text-persediaan border font-monospace px-2 py-1 fs-6">
                                            <?= htmlspecialchars($r['kode_rak']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($r['nama_rak']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['kapasitas'] ?? '-') ?></span></td>
                                    <td class="small text-muted"><?= htmlspecialchars($r['keterangan'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-persediaan-subtle text-persediaan"><?= $r['total_sku'] ?> SKU</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-persediaan-subtle text-persediaan fw-bold"><?= number_format($r['total_stok']) ?> Unit</span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center me-1 btn-edit-rak" style="width: 28px; height: 28px; padding: 0;"
                                                data-id="<?= $r['id'] ?>"
                                                data-kode="<?= htmlspecialchars($r['kode_rak']) ?>"
                                                data-nama="<?= htmlspecialchars($r['nama_rak']) ?>"
                                                data-kapasitas="<?= htmlspecialchars($r['kapasitas'] ?? '') ?>"
                                                data-keterangan="<?= htmlspecialchars($r['keterangan'] ?? '') ?>"
                                                title="Ubah Rak">
                                            <i class="bi bi-pencil" style="font-size: 0.8rem;"></i>
                                        </button>
                                        <a href="<?= BASE_URL ?>modules/rak/index.php?action=delete&id=<?= $r['id'] ?>" 
                                           class="btn btn-sm btn-outline-output-data btn-delete-confirm d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; padding: 0;"
                                           data-name="Rak <?= htmlspecialchars($r['kode_rak']) ?>" 
                                           title="Hapus Rak">
                                            <i class="bi bi-trash" style="font-size: 0.8rem;"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

<!-- Modal Tambah Rak -->
<div class="modal fade" id="modalTambahRak" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-success me-2"></i> Tambah Rak Penyimpanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Kode Rak (Unik) <span class="text-danger">*</span></label>
                    <input type="text" name="kode_rak" class="form-control font-monospace" placeholder="Contoh: RAK-A3" required>
                    <small class="text-muted" style="font-size: 0.75rem;">Gunakan huruf besar dan strip, misal RAK-B3 atau RAK-LAB.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Rak / Zona Deskripsi <span class="text-danger">*</span></label>
                    <input type="text" name="nama_rak" class="form-control" placeholder="Contoh: Rak Pupuk Makro Sawit" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Estimasi Kapasitas</label>
                    <input type="text" name="kapasitas" class="form-control" placeholder="Contoh: 50 Sak / 100 Pcs">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Keterangan Tambahan</label>
                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan peruntukan atau letak rak di dalam gudang"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success fw-bold"><i class="bi bi-save me-1"></i> Simpan Rak</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Rak -->
<div class="modal fade" id="modalEditRak" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i> Edit Data Rak</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Kode Rak (Unik) <span class="text-danger">*</span></label>
                    <input type="text" name="kode_rak" id="edit_kode_rak" class="form-control font-monospace" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Rak / Zona Deskripsi <span class="text-danger">*</span></label>
                    <input type="text" name="nama_rak" id="edit_nama_rak" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Estimasi Kapasitas</label>
                    <input type="text" name="kapasitas" id="edit_kapasitas" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Keterangan Tambahan</label>
                    <textarea name="keterangan" id="edit_keterangan" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-save me-1"></i> Perbarui Rak</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.btn-edit-rak');
    const modalEdit = new bootstrap.Modal(document.getElementById('modalEditRak'));

    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_kode_rak').value = this.getAttribute('data-kode');
            document.getElementById('edit_nama_rak').value = this.getAttribute('data-nama');
            document.getElementById('edit_kapasitas').value = this.getAttribute('data-kapasitas');
            document.getElementById('edit_keterangan').value = this.getAttribute('data-keterangan');
            modalEdit.show();
        });
    });
});
</script>

