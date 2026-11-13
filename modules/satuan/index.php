<?php
/**
 * Modul Master Satuan Barang WMS CWE
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';
require_once __DIR__ . '/../../helpers/format.php';

require_admin();

$page_title = 'Kelola Satuan Barang';
$current_page = 'satuan';

// Tambah & Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $nama_satuan = trim($_POST['nama_satuan'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if (empty($nama_satuan)) {
        set_flash('error', 'Nama Satuan wajib diisi.');
    } else {
        try {
            if ($action === 'create') {
                $ins = $pdo->prepare("INSERT INTO satuan (nama_satuan, keterangan) VALUES (?, ?)");
                $ins->execute([$nama_satuan, $keterangan]);
                set_flash('success', "Satuan '{$nama_satuan}' berhasil ditambahkan.");
            } elseif ($action === 'update' && $id > 0) {
                $upd = $pdo->prepare("UPDATE satuan SET nama_satuan = ?, keterangan = ? WHERE id = ?");
                $upd->execute([$nama_satuan, $keterangan, $id]);
                set_flash('success', "Satuan '{$nama_satuan}' berhasil diperbarui.");
            }
        } catch (PDOException $e) {
            set_flash('error', 'Gagal memproses satuan: ' . $e->getMessage());
        }
    }
    header('Location: ' . BASE_URL . 'modules/satuan/index.php');
    exit;
}

// Hapus
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $cek = $pdo->prepare("SELECT COUNT(*) FROM barang WHERE id_satuan = ?");
        $cek->execute([$id]);
        $count = $cek->fetchColumn();

        if ($count > 0) {
            set_flash('error', "Satuan tidak dapat dihapus karena masih digunakan oleh {$count} data barang.");
        } else {
            $del = $pdo->prepare("DELETE FROM satuan WHERE id = ?");
            $del->execute([$id]);
            set_flash('success', "Satuan berhasil dihapus.");
        }
    }
    header('Location: ' . BASE_URL . 'modules/satuan/index.php');
    exit;
}

$stmt = $pdo->query("
    SELECT s.*, COUNT(b.id) as total_barang
    FROM satuan s
    LEFT JOIN barang b ON s.id = b.id_satuan
    GROUP BY s.id
    ORDER BY s.nama_satuan ASC
");
$satuan_list = $stmt->fetchAll();

require_once __DIR__ . '/../../layout/header.php';
require_once __DIR__ . '/../../layout/sidebar.php';
?>

<div id="content-wrapper">
    <?php require_once __DIR__ . '/../../layout/navbar.php'; ?>

    <main class="container-fluid px-4 py-4">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-rulers text-success me-2"></i> Master Satuan Ukuran</h4>
                <p class="text-muted small mb-0">Kelola unit kemasan atau satuan pengukuran stok (Sak, Kg, Liter, Botol, Pcs, dll).</p>
            </div>
            <div class="mt-3 mt-md-0">
                <button type="button" class="btn btn-cwe-primary rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahSatuan">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Satuan
                </button>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark">Daftar Satuan Ukuran Barang</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle datatable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama Satuan</th>
                                <th>Keterangan</th>
                                <th class="text-center">Digunakan di Barang</th>
                                <th class="text-end" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($satuan_list as $s): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><span class="badge bg-light text-dark border px-3 py-2 fw-bold font-monospace"><?= htmlspecialchars($s['nama_satuan']) ?></span></td>
                                    <td class="small text-muted"><?= htmlspecialchars($s['keterangan'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary"><?= $s['total_barang'] ?> Barang</span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-circle me-1 btn-edit-satuan"
                                                data-id="<?= $s['id'] ?>"
                                                data-nama="<?= htmlspecialchars($s['nama_satuan']) ?>"
                                                data-keterangan="<?= htmlspecialchars($s['keterangan'] ?? '') ?>"
                                                title="Ubah Satuan">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="<?= BASE_URL ?>modules/satuan/index.php?action=delete&id=<?= $s['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger rounded-circle btn-delete-confirm" 
                                           data-name="Satuan <?= htmlspecialchars($s['nama_satuan']) ?>" 
                                           title="Hapus Satuan">
                                            <i class="bi bi-trash"></i>
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

<!-- Modal Tambah Satuan -->
<div class="modal fade" id="modalTambahSatuan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-success me-2"></i> Tambah Satuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Satuan <span class="text-danger">*</span></label>
                    <input type="text" name="nama_satuan" class="form-control" placeholder="Contoh: Sak, Liter, Rim" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan satuan / kemasan"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success fw-bold"><i class="bi bi-save me-1"></i> Simpan Satuan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Satuan -->
<div class="modal fade" id="modalEditSatuan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_satuan_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i> Edit Satuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Satuan <span class="text-danger">*</span></label>
                    <input type="text" name="nama_satuan" id="edit_satuan_nama" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Keterangan</label>
                    <textarea name="keterangan" id="edit_satuan_keterangan" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-save me-1"></i> Perbarui Satuan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.btn-edit-satuan');
    const modalEdit = new bootstrap.Modal(document.getElementById('modalEditSatuan'));

    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_satuan_id').value = this.getAttribute('data-id');
            document.getElementById('edit_satuan_nama').value = this.getAttribute('data-nama');
            document.getElementById('edit_satuan_keterangan').value = this.getAttribute('data-keterangan');
            modalEdit.show();
        });
    });
});
</script>

