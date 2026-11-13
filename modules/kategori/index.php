<?php
/**
 * Modul Master Kategori Barang WMS CWE
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';
require_once __DIR__ . '/../../helpers/format.php';

require_admin();

$page_title = 'Kelola Kategori Barang';
$current_page = 'kategori';

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if (empty($nama_kategori)) {
        set_flash('error', 'Nama Kategori wajib diisi.');
    } else {
        try {
            if ($action === 'create') {
                $ins = $pdo->prepare("INSERT INTO kategori (nama_kategori, keterangan) VALUES (?, ?)");
                $ins->execute([$nama_kategori, $keterangan]);
                set_flash('success', "Kategori '{$nama_kategori}' berhasil ditambahkan.");
            } elseif ($action === 'update' && $id > 0) {
                $upd = $pdo->prepare("UPDATE kategori SET nama_kategori = ?, keterangan = ? WHERE id = ?");
                $upd->execute([$nama_kategori, $keterangan, $id]);
                set_flash('success', "Kategori '{$nama_kategori}' berhasil diperbarui.");
            }
        } catch (PDOException $e) {
            set_flash('error', 'Gagal memproses kategori: ' . $e->getMessage());
        }
    }
    header('Location: ' . BASE_URL . 'modules/kategori/index.php');
    exit;
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $cek = $pdo->prepare("SELECT COUNT(*) FROM barang WHERE id_kategori = ?");
        $cek->execute([$id]);
        $count = $cek->fetchColumn();

        if ($count > 0) {
            set_flash('error', "Kategori tidak dapat dihapus karena masih digunakan oleh {$count} data barang.");
        } else {
            $del = $pdo->prepare("DELETE FROM kategori WHERE id = ?");
            $del->execute([$id]);
            set_flash('success', "Kategori berhasil dihapus.");
        }
    }
    header('Location: ' . BASE_URL . 'modules/kategori/index.php');
    exit;
}

// Ambil data kategori beserta jumlah barang
$stmt = $pdo->query("
    SELECT k.*, COUNT(b.id) as total_barang
    FROM kategori k
    LEFT JOIN barang b ON k.id = b.id_kategori
    GROUP BY k.id
    ORDER BY k.nama_kategori ASC
");
$kategori_list = $stmt->fetchAll();

require_once __DIR__ . '/../../layout/header.php';
require_once __DIR__ . '/../../layout/sidebar.php';
?>

<div id="content-wrapper">
    <?php require_once __DIR__ . '/../../layout/navbar.php'; ?>

    <main class="container-fluid px-4 py-4">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-tags-fill text-success me-2"></i> Master Kategori Barang</h4>
                <p class="text-muted small mb-0">Kelompokkan barang inventori kebun sawit, laboratorium, bengkel, dan kantor.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <button type="button" class="btn btn-cwe-primary rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Kategori
                </button>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark">Daftar Kategori Inventori</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle datatable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama Kategori</th>
                                <th>Keterangan</th>
                                <th class="text-center">Jumlah Barang</th>
                                <th class="text-end" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($kategori_list as $k): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($k['nama_kategori']) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($k['keterangan'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success"><?= $k['total_barang'] ?> Barang</span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-circle me-1 btn-edit-kat"
                                                data-id="<?= $k['id'] ?>"
                                                data-nama="<?= htmlspecialchars($k['nama_kategori']) ?>"
                                                data-keterangan="<?= htmlspecialchars($k['keterangan'] ?? '') ?>"
                                                title="Ubah Kategori">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="<?= BASE_URL ?>modules/kategori/index.php?action=delete&id=<?= $k['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger rounded-circle btn-delete-confirm" 
                                           data-name="Kategori <?= htmlspecialchars($k['nama_kategori']) ?>" 
                                           title="Hapus Kategori">
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

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-success me-2"></i> Tambah Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="nama_kategori" class="form-control" placeholder="Contoh: Pupuk & Agrokimia" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Deskripsi kelompok barang"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success fw-bold"><i class="bi bi-save me-1"></i> Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="modalEditKategori" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_kat_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i> Edit Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="nama_kategori" id="edit_kat_nama" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Keterangan</label>
                    <textarea name="keterangan" id="edit_kat_keterangan" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-save me-1"></i> Perbarui Kategori</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.btn-edit-kat');
    const modalEdit = new bootstrap.Modal(document.getElementById('modalEditKategori'));

    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_kat_id').value = this.getAttribute('data-id');
            document.getElementById('edit_kat_nama').value = this.getAttribute('data-nama');
            document.getElementById('edit_kat_keterangan').value = this.getAttribute('data-keterangan');
            modalEdit.show();
        });
    });
});
</script>

